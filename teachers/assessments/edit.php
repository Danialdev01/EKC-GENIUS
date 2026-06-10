<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
requireAuth('teacher');
$pageTitle = 'Edit Assessment';
$cssDepth = '../../public/css';
$location_index = '../..';

$studentId = $_GET['student_id'] ?? null;
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if (!$studentId) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND student_status = 1");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM assessments WHERE assessment_status = 1 ORDER BY assessment_title");
$assessmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT sa.assessment_id, sa.student_assessment_value
    FROM student_assessments sa
    WHERE sa.student_id = ?
      AND sa.student_assessment_month = ?
      AND sa.student_assessment_year = ?
      AND sa.student_assessment_status = 1
");
$stmt->execute([$studentId, $currentMonth, $currentYear]);
$existingScores = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $existingScores[$row['assessment_id']] = $row['student_assessment_value'];
}

$stmt = $pdo->prepare("
    SELECT * FROM ai_assessments 
    WHERE student_id = ? AND ai_assessment_month = ? AND ai_assessment_year = ? AND ai_assessment_status = 1
");
$stmt->execute([$studentId, $currentMonth, $currentYear]);
$aiAssessment = $stmt->fetch(PDO::FETCH_ASSOC);

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$savedSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assessment'])) {
    $studentId = $_POST['student_id'] ?? null;
    $month = $_POST['assessment_month'] ?? null;
    $year = $_POST['assessment_year'] ?? null;
    $scores = $_POST['scores'] ?? [];

    if ($studentId && $month && $year && !empty($scores)) {
        $stmt = $pdo->prepare("DELETE FROM student_assessments WHERE student_id = ? AND student_assessment_month = ? AND student_assessment_year = ?");
        $stmt->execute([$studentId, $month, $year]);

        $stmt = $pdo->prepare("INSERT INTO student_assessments (student_id, assessment_id, student_assessment_value, student_assessment_month, student_assessment_year, student_assessment_status, student_assessment_created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        foreach ($scores as $assessmentId => $value) {
            if ($value) {
                $stmt->execute([$studentId, $assessmentId, $value, $month, $year]);
            }
        }

        require_once '../../backend/ai_assessment.php';
        $regen = regenerateAiAssessment($pdo, (int)$studentId, (int)$month, (int)$year);
        if (is_array($regen) && !isset($regen['error'])) {
            $aiAssessment = $regen;
        }

        $savedSuccess = true;
    }
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
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Edit Assessment</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= $months[$currentMonth] ?> <?= $currentYear ?></p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                        <?= strtoupper(substr($student['student_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h2 class="font-poppins text-lg font-semibold text-slate-800"><?= htmlspecialchars($student['student_name']) ?></h2>
                        <p class="text-xs text-slate-400"><?= $months[$currentMonth] ?> <?= $currentYear ?></p>
                    </div>
                </div>
                <a href="../../teachers/students/?id=<?= (int)$studentId ?>" class="text-sm text-slate-500 hover:text-slate-700">← Back</a>
            </div>

            <?php if ($savedSuccess): ?>
            <div class="mx-6 mt-6 bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-emerald-800">Assessment updated successfully!</p>
                        <p class="text-xs text-emerald-600">Development Profile (AI) has been regenerated with the latest scores.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form method="post" class="p-6">
                <input type="hidden" name="student_id" value="<?= (int)$student['student_id'] ?>">
                <input type="hidden" name="assessment_month" value="<?= $currentMonth ?>">
                <input type="hidden" name="assessment_year" value="<?= $currentYear ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($assessmentsList as $a): ?>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-xl"><?= htmlspecialchars($a['assessment_icon']) ?></span>
                            <h3 class="font-poppins text-sm font-semibold text-slate-800"><?= htmlspecialchars($a['assessment_title']) ?></h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="scores[<?= (int)$a['assessment_id'] ?>]" value="<?= $i ?>" 
                                    <?= isset($existingScores[$a['assessment_id']]) && $existingScores[$a['assessment_id']] == $i ? 'checked' : '' ?>
                                    class="peer sr-only">
                                <div class="text-center py-2 rounded-lg text-sm font-medium bg-white border border-slate-200 text-slate-600 peer-checked:bg-indigo-500 peer-checked:text-white peer-checked:border-indigo-500 transition-colors">
                                    <?= $i ?>
                                </div>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($aiAssessment): ?>
                <div class="mt-6 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl p-6 border border-indigo-100">
                    <h3 class="font-poppins text-base font-semibold text-slate-800 mb-4">Development Profile (AI)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h4 class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-2">Identified Strengths</h4>
                            <p class="text-sm text-slate-600 leading-relaxed"><?= nl2br(htmlspecialchars($aiAssessment['ai_assessment_strengths'] ?? 'No strengths identified yet.')) ?></p>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-amber-600 uppercase tracking-wide mb-2">Development Focus Areas</h4>
                            <p class="text-sm text-slate-600 leading-relaxed"><?= nl2br(htmlspecialchars($aiAssessment['ai_assessment_focus_area'] ?? 'No focus areas identified yet.')) ?></p>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-emerald-600 uppercase tracking-wide mb-2">Trend Analysis</h4>
                            <p class="text-sm text-slate-600 leading-relaxed"><?= nl2br(htmlspecialchars($aiAssessment['ai_assessment_trend_analysis'] ?? 'No trend data available.')) ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-6 flex gap-3">
                    <button type="submit" name="save_assessment" class="bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-6 py-3 rounded-xl transition-colors">
                        Update Assessment
                    </button>
                    <a href="../../teachers/students/?id=<?= (int)$studentId ?>" class="px-6 py-3 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>