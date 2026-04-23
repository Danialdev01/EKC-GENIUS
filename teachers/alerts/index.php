<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
require_once '../../backend/ai.php';

$authUser = requireAuth('teacher');
$pageTitle = 'Alerts';
$cssDepth = '../../public/css';
$location_index = '../..';

$currentMonth = date('n');
$currentYear = date('Y');
$prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
$prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;

$apiKey = getenv('OPENROUTER_API_KEY') ?: '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';

$oneMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));

// Mark expired alerts (older than 1 month)
$stmt = $pdo->prepare("UPDATE alerts SET alert_status = 2 WHERE alert_status = 1 AND alert_created_at < ?");
$stmt->execute([$oneMonthAgo]);

// Get students needing attention (avg score < 2.5 for current month)
$stmt = $pdo->prepare("
    SELECT s.student_id, s.student_name, ROUND(AVG(sa.student_assessment_value), 2) as avg_score
    FROM students s
    INNER JOIN student_assessments sa ON sa.student_id = s.student_id AND sa.student_assessment_status = 1
    WHERE sa.student_assessment_month = ? AND sa.student_assessment_year = ?
      AND s.student_status = 1
    GROUP BY s.student_id, s.student_name
    HAVING avg_score < 2.5
    ORDER BY avg_score ASC
");
$stmt->execute([$currentMonth, $currentYear]);
$needAttentionStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get valid alerts (status = 1 and created within last month)
$stmt = $pdo->prepare("
    SELECT student_id, alert_id, alert_recommended_action, alert_recommended_activity, alert_created_at 
    FROM alerts 
    WHERE alert_status = 1 AND alert_created_at >= ?
");
$stmt->execute([$oneMonthAgo]);
$validAlerts = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $alert) {
    $validAlerts[$alert['student_id']] = $alert;
}

// Get students who need new alerts (low score but no valid alert)
$studentsNeedingAlerts = [];
foreach ($needAttentionStudents as $student) {
    if (!isset($validAlerts[$student['student_id']])) {
        $studentsNeedingAlerts[] = $student;
    }
}

// Generate alerts only when "Check Students" button is clicked
if (isset($_GET['check']) && $_GET['check'] == '1') {
    foreach ($studentsNeedingAlerts as $student) {
        $studentId = $student['student_id'];
        $weakAreas = getStudentWeakAreas($pdo, $studentId, $currentMonth, $currentYear);
        $recommendations = getAIRecommendations($student['student_name'], $weakAreas, $apiKey);
        
        // Extract recommended actions (after "💡 Recommended Actions:")
        $recommendedAction = "";
        $recommendedActivity = "";
        
        if (preg_match('/💡 Recommended Actions:(.*?)(?=$|\n\n|\Z)/s', $recommendations, $matches)) {
            $recommendedAction = trim($matches[1]);
            $recommendedActivity = "";
        } else {
            $recommendedAction = $recommendations;
        }
        
        // Store in alerts table
        $stmt = $pdo->prepare("
            INSERT INTO alerts (student_id, alert_recommended_action, alert_recommended_activity, alert_status, alert_created_at) 
            VALUES (?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$studentId, $recommendedAction, $recommendedActivity]);
    }
    // Redirect to remove the check parameter after processing
    header('Location: index.php');
    exit;
}

// Fetch all valid alerts again after creation
$stmt = $pdo->prepare("
    SELECT a.*, s.student_name 
    FROM alerts a
    INNER JOIN students s ON s.student_id = a.student_id
    WHERE a.alert_status = 1 AND a.alert_created_at >= ?
    ORDER BY a.alert_created_at DESC
");
$stmt->execute([$oneMonthAgo]);
$allValidAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Re-index alerts by student_id for display
$alertsByStudent = [];
foreach ($allValidAlerts as $alert) {
    $alertsByStudent[$alert['student_id']] = $alert;
}

// Get previous month scores to check consecutive low scores
$stmt = $pdo->prepare("
    SELECT s.student_id, ROUND(AVG(sa.student_assessment_value), 2) as avg_score
    FROM students s
    INNER JOIN student_assessments sa ON sa.student_id = s.student_id AND sa.student_assessment_status = 1
    WHERE sa.student_assessment_month = ? AND sa.student_assessment_year = ?
      AND s.student_status = 1
    GROUP BY s.student_id
");
$stmt->execute([$prevMonth, $prevYear]);
$prevMonthScores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get total students needing attention
$needAttentionCount = count($needAttentionStudents);

// Get lowest performing assessment types (top 3)
$stmt = $pdo->prepare("
    SELECT a.assessment_id, a.assessment_title, ROUND(AVG(sa.student_assessment_value), 2) as avg_value
    FROM assessments a
    INNER JOIN student_assessments sa ON sa.assessment_id = a.assessment_id AND sa.student_assessment_status = 1
    WHERE sa.student_assessment_month = ? AND sa.student_assessment_year = ?
    GROUP BY a.assessment_id, a.assessment_title
    ORDER BY avg_value ASC
    LIMIT 3
");
$stmt->execute([$currentMonth, $currentYear]);
$lowestAssessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get student weakest areas
function getStudentWeakAreas($pdo, $studentId, $month, $year) {
    $stmt = $pdo->prepare("
        SELECT a.assessment_title, sa.student_assessment_value
        FROM student_assessments sa
        INNER JOIN assessments a ON a.assessment_id = sa.assessment_id
        WHERE sa.student_id = ? 
          AND sa.student_assessment_month = ? 
          AND sa.student_assessment_year = ?
          AND sa.student_assessment_status = 1
        ORDER BY sa.student_assessment_value ASC
    ");
    $stmt->execute([$studentId, $month, $year]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate AI recommendations
function getAIRecommendations($studentName, $weakAreas, $apiKey) {
    if (empty($weakAreas)) {
        return "No assessment data available to generate recommendations.";
    }
    
    $areasList = implode(", ", array_column($weakAreas, 'assessment_title'));
    
    $prompt = "You are an educational specialist for special needs children. Based on the following information for student {$studentName}, provide 3-5 specific, practical, and actionable recommended activities/interventions to help improve their weak areas.

Weak Development Areas: {$areasList}

Please provide the recommended actions in this exact format:
💡 Recommended Actions:
• [First recommendation]
• [Second recommendation]
• [Third recommendation]
• [Fourth recommendation - if applicable]
• [Fifth recommendation - if applicable]

Keep each recommendation concise but specific. Focus on practical activities that can be done in a classroom setting.";

    $result = callAI($prompt, 'openai/gpt-4o-mini', $apiKey);
    
    if ($result['success']) {
        return $result['content'];
    }
    
    return "Practice conversation cards, storytelling sessions, and speech exercises.\n• Structured group play, turn-taking games, and peer interaction activities.\n• Mirror games, face-to-face singing, and visual tracking exercises.";
}
?>
<?php include '../../components/teacher/header.php'; ?>

<?php include '../../components/teacher/sidebar.php'; ?>

<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleTeacherSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Alerts</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
        <a href="?check=1" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Check Students
        </a>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-xl">⚠️</div>
                    <p class="text-xs text-slate-500 font-medium uppercase">Students Need Attention</p>
                </div>
                <p class="font-poppins text-3xl font-bold text-slate-800"><?= $needAttentionCount ?></p>
                <p class="text-xs text-slate-400 mt-1">Avg score below 2.5</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-xl">📉</div>
                    <p class="text-xs text-slate-500 font-medium uppercase">Lowest Assessment Areas</p>
                </div>
                <div class="space-y-2 mt-2">
                    <?php foreach ($lowestAssessments as $assessment): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-700"><?= htmlspecialchars($assessment['assessment_title']) ?></span>
                        <span class="text-sm font-bold text-red-500"><?= number_format($assessment['avg_value'], 1) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($lowestAssessments)): ?>
                    <p class="text-sm text-slate-400">No assessment data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Alert Cards -->
        <?php if (!empty($allValidAlerts)): ?>
        <div class="space-y-4">
            <h2 class="font-poppins text-lg font-semibold text-slate-800">High Priority Students</h2>
            <?php foreach ($allValidAlerts as $alert): ?>
                <?php 
                $studentId = $alert['student_id'];
                $weakAreas = getStudentWeakAreas($pdo, $studentId, $currentMonth, $currentYear);
                $hasConsecutiveLow = isset($prevMonthScores[$studentId]) && $prevMonthScores[$studentId] < 2.5;
                $weakAreasList = array_slice($weakAreas, 0, 7);
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-poppins text-lg font-semibold text-slate-800"><?= htmlspecialchars($alert['student_name']) ?> <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1">
                                High Priority
                            </span></h3>
                            
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="../students/?id=<?= $studentId ?>" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors bg-blue-500">
                                View Student
                            </a>
                            <div class="text-right">
                                <p class="text-sm text-slate-500">Alert Created</p>
                                <p class="text-sm font-bold text-slate-600"><?= date('M d, Y', strtotime($alert['alert_created_at'])) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm font-medium text-slate-700 mb-2">Weak Development Areas:</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($weakAreasList as $area): ?>
                            <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-medium">
                                <?= htmlspecialchars($area['assessment_title']) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-4">
                        <div class="prose prose-sm max-w-none text-slate-600">
                            <?= nl2br(htmlspecialchars($alert['alert_recommended_action'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
            <div class="text-4xl mb-4">✅</div>
            <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-2">All Students Doing Well</h3>
            <p class="text-slate-500">No students currently need attention. All development scores are above 2.5.</p>
        </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>