<?php
/**
 * POST /backend/api/regenerate_ai.php
 *
 * Form-encoded input:  student_id, month, year
 * JSON output:         { success: bool, data?: {...}, error?: string, message?: string }
 *
 * Accepts teacher or admin session. Calls regenerateAiAssessment() and
 * translates its return into a JSON response. Always returns 200 unless
 * auth/method/params are invalid.
 */
header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/../../config/connect.php';
require_once __DIR__ . '/../../backend/auth.php';
require_once __DIR__ . '/../../backend/ai_assessment.php';

$role = $_SESSION['user_role'] ?? null;
if ($role !== 'teacher' && $role !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'unauthorized', 'message' => 'Please sign in as a teacher or admin.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'method_not_allowed', 'message' => 'Use POST.']);
    exit;
}

$studentId = $_POST['student_id'] ?? null;
$month     = $_POST['month']      ?? null;
$year      = $_POST['year']       ?? null;

if (!$studentId || !$month || !$year) {
    echo json_encode(['success' => false, 'error' => 'missing_parameters', 'message' => 'student_id, month, and year are required.']);
    exit;
}

$result = regenerateAiAssessment($pdo, (int)$studentId, (int)$month, (int)$year);

if ($result === null) {
    echo json_encode(['success' => false, 'error' => 'no_scores', 'message' => 'This student has no assessment scores for that month yet.']);
    exit;
}

if (isset($result['error'])) {
    echo json_encode(['success' => false, 'error' => 'api_failed', 'message' => $result['message']]);
    exit;
}

$resMonth = (int)$result['ai_assessment_month'];
$resYear  = (int)$result['ai_assessment_year'];

echo json_encode([
    'success' => true,
    'data' => [
        'strengths'      => $result['ai_assessment_strengths'],
        'focus_area'     => $result['ai_assessment_focus_area'],
        'trend_analysis' => $result['ai_assessment_trend_analysis'],
        'month'          => $resMonth,
        'year'           => $resYear,
        'period'         => date('F Y', mktime(0, 0, 0, $resMonth, 1, $resYear)),
    ],
]);
