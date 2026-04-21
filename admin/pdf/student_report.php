<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';
require_once __DIR__ . '/../../backend/auth.php';
$authUser = requireAuth('admin');

require_once __DIR__ . '/../../vendor/autoload.php';

$studentId = $_GET['id'] ?? null;

if (!$studentId) {
    die('Student ID required');
}

$stmt = $pdo->prepare("
    SELECT s.*, c.category_name, c.category_price_invoice
    FROM students s 
    LEFT JOIN categories c ON c.category_id = s.category_id AND c.category_status = 1 
    WHERE s.student_id = ? AND s.student_status = 1
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die('Student not found');
}

$stmt = $pdo->query("SELECT * FROM assessments WHERE assessment_status = 1 ORDER BY assessment_id");
$assessmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentMonth = date('n');
$currentYear = date('Y');

$stmt = $pdo->prepare("
    SELECT sa.assessment_id, sa.student_assessment_value
    FROM student_assessments sa
    WHERE sa.student_id = ? AND sa.student_assessment_month = ? AND sa.student_assessment_year = ? AND sa.student_assessment_status = 1
");
$stmt->execute([$studentId, $currentMonth, $currentYear]);
$scores = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $scores[$row['assessment_id']] = $row['student_assessment_value'];
}

$scoreValues = array_values($scores);
$avgScore = count($scoreValues) > 0 ? round(array_sum($scoreValues) / count($scoreValues), 1) : null;

$age = $student['student_year_of_birth'] ? date('Y') - (int)$student['student_year_of_birth'] . ' years old' : 'N/A';

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Report - ' . htmlspecialchars($student['student_name']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #6366f1; padding-bottom: 20px; }
        .header h1 { color: #6366f1; margin: 0; }
        .header p { color: #666; margin: 5px 0 0; }
        .student-info { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .student-info h2 { margin-top: 0; color: #333; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .info-item { margin: 5px 0; }
        .info-label { font-weight: bold; color: #666; }
        .scores-section { margin-top: 30px; }
        .score-card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .score-value { font-size: 24px; font-weight: bold; color: #6366f1; }
        .assessment-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .assessment-table th, .assessment-table td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .assessment-table th { background: #6366f1; color: white; }
        .assessment-table tr:nth-child(even) { background: #f8f9fa; }
        .avg-score { font-size: 18px; font-weight: bold; color: #10b981; }
        .footer { margin-top: 40px; text-align: center; color: #999; font-size: 12px; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 15px; font-size: 12px; }
        .badge-good { background: #d1fae5; color: #065f46; }
        .badge-avg { background: #fef3c7; color: #92400e; }
        .badge-poor { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>EKC Genius Student Report</h1>
        <p>Generated on ' . date('F d, Y') . '</p>
    </div>
    
    <div class="student-info">
        <h2>' . htmlspecialchars($student['student_name']) . '</h2>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Student ID:</span> #' . $student['student_id'] . '</div>
            <div class="info-item"><span class="info-label">IC Number:</span> ' . htmlspecialchars($student['student_ic'] ?? 'N/A') . '</div>
            <div class="info-item"><span class="info-label">Age:</span> ' . $age . '</div>
            <div class="info-item"><span class="info-label">Category:</span> ' . htmlspecialchars($student['category_name'] ?? 'Uncategorized') . '</div>
            <div class="info-item"><span class="info-label">Parent Name:</span> ' . htmlspecialchars($student['student_parent_name'] ?? 'N/A') . '</div>
            <div class="info-item"><span class="info-label">Parent Phone:</span> ' . htmlspecialchars($student['student_parent_number'] ?? 'N/A') . '</div>
            <div class="info-item"><span class="info-label">Parent Email:</span> ' . htmlspecialchars($student['student_parent_email'] ?? 'N/A') . '</div>
            <div class="info-item"><span class="info-label">Enrolled:</span> ' . ($student['student_enrollment_date'] ? date('M Y', strtotime($student['student_enrollment_date'])) : 'N/A') . '</div>
        </div>
    </div>
    
    <div class="scores-section">
        <h3>Current Month Assessment (' . date('F Y') . ')</h3>
        <div class="score-card">
            <div class="info-label">Average Development Score</div>
            <div class="score-value">' . ($avgScore !== null ? $avgScore . '/5' : 'N/A') . '</div>
            ' . ($avgScore !== null ? '<span class="badge ' . ($avgScore >= 3.5 ? 'badge-good' : ($avgScore >= 2.5 ? 'badge-avg' : 'badge-poor')) . '">' . ($avgScore >= 3.5 ? 'Good' : ($avgScore >= 2.5 ? 'Average' : 'Needs Improvement')) . '</span>' : '') . '
        </div>
        
        <table class="assessment-table">
            <thead>
                <tr>
                    <th>Assessment Area</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>';

foreach ($assessmentsList as $a) {
    $score = $scores[$a['assessment_id']] ?? null;
    $html .= '
                <tr>
                    <td style="text-align: left;">' . htmlspecialchars($a['assessment_icon'] . ' ' . $a['assessment_title']) . '</td>
                    <td>' . ($score !== null ? $score : '—') . '</td>
                </tr>';
}

$html .= '
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p>This report was automatically generated by EKC Genius System</p>
        <p>© ' . date('Y') . ' EKC Genius. All rights reserved.</p>
    </div>
</body>
</html>';

$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="student_report_' . $studentId . '_' . date('Ymd') . '.pdf"');
echo $dompdf->output();