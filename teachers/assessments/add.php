<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
requireAuth('teacher');
$pageTitle = 'Add Assessment';
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

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

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

$savedSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assessment']) && !isset($_POST['save_and_next'])) {
    $savedSuccess = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_assessment']) || isset($_POST['save_and_next']))) {
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

        $apiKey = getenv('OPENROUTER_API_KEY') ?: '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';
        
        $stmt = $pdo->query("SELECT * FROM assessments WHERE assessment_status = 1 ORDER BY assessment_title");
        $assessmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT sa.assessment_id, a.assessment_title, sa.student_assessment_value
            FROM student_assessments sa
            INNER JOIN assessments a ON a.assessment_id = sa.assessment_id
            WHERE sa.student_id = ? AND sa.student_assessment_month = ? AND sa.student_assessment_year = ? AND sa.student_assessment_status = 1
        ");
        $stmt->execute([$studentId, $month, $year]);
        $currentScores = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $currentScores[] = $row['assessment_title'] . ": " . $row['student_assessment_value'];
        }
        
        $lastMonth = $month == 1 ? 12 : $month - 1;
        $lastMonthYear = $month == 1 ? $year - 1 : $year;
        
        $allPreviousScores = [];
        for ($i = 1; $i <= 6; $i++) {
            $m = $month - $i;
            $y = $year;
            while ($m < 1) {
                $m += 12;
                $y -= 1;
            }
            if ($m <= 0) continue;
            
            $stmt = $pdo->prepare("
                SELECT sa.assessment_id, a.assessment_title, sa.student_assessment_value
                FROM student_assessments sa
                INNER JOIN assessments a ON a.assessment_id = sa.assessment_id
                WHERE sa.student_id = ? AND sa.student_assessment_month = ? AND sa.student_assessment_year = ? AND sa.student_assessment_status = 1
            ");
            $stmt->execute([$studentId, $m, $y]);
            $scoresData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($scoresData)) {
                $monthLabel = date('F Y', mktime(0, 0, 0, $m, 1, $y));
                $scoresStr = [];
                foreach ($scoresData as $row) {
                    $scoresStr[] = $row['assessment_title'] . ": " . $row['student_assessment_value'];
                }
                $allPreviousScores[$monthLabel] = implode(", ", $scoresStr);
            }
        }
        
        $stmt = $pdo->prepare("SELECT student_name FROM students WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $studentName = $stmt->fetchColumn();
        
        $previousDataStr = "";
        foreach ($allPreviousScores as $monthLabel => $scores) {
            $previousDataStr .= $monthLabel . " Scores: " . $scores . "\n";
        }
        
        $prompt = "You are an educational specialist for special needs children. Analyze the following assessment data for student {$studentName}.

Current Month (" . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ") Scores:
" . implode(", ", $currentScores) . "

Previous Months Data:
" . (empty($previousDataStr) ? "No previous data available" : $previousDataStr) . "

Please provide your analysis in exactly this format:
Strengths: [List the top 3 areas where the student performs well (score >= 4)]
Focus Area: [List the top 3 areas that need improvement (score <= 2.5)]
Trend Analysis: [Analyze the student's current assessment pattern. Look at the previous months data to identify trends. Identify if scores are consistently low, moderately distributed, improving, declining, or showing any particular pattern across development areas over time.]

Keep each section concise and specific.";

        require_once '../../backend/ai.php';
        $result = null;
        if (function_exists('callAI')) {
            try {
                $result = callAI($prompt, 'openai/gpt-4o-mini', $apiKey);
            } catch (Exception $e) {
                $result = ['success' => false];
            }
        } else {
            $result = ['success' => false];
        }
        
        $aiStrengths = "No strengths identified yet.";
        $aiFocusArea = "No focus areas identified yet.";
        $aiTrendAnalysis = "No trend data available.";
        
        if ($result && isset($result['success']) && $result['success']) {
            $content = $result['content'];
            
            if (preg_match('/Strengths:(.*?)(?=Focus Area:|$)/s', $content, $matches)) {
                $aiStrengths = trim($matches[1]);
            }
            if (preg_match('/Focus Area:(.*?)(?=Trend Analysis:|$)/s', $content, $matches)) {
                $aiFocusArea = trim($matches[1]);
            }
            if (preg_match('/Trend Analysis:(.*)/s', $content, $matches)) {
                $aiTrendAnalysis = trim($matches[1]);
            }
        }
        
        $stmt = $pdo->prepare("UPDATE ai_assessments SET ai_assessment_status = 0 WHERE student_id = ? AND ai_assessment_month = ? AND ai_assessment_year = ?");
        $stmt->execute([$studentId, $month, $year]);
        
        $stmt = $pdo->prepare("
            INSERT INTO ai_assessments (student_id, ai_assessment_strengths, ai_assessment_focus_area, ai_assessment_trend_analysis, ai_assessment_month, ai_assessment_year, ai_assessment_status, ai_assessment_created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$studentId, $aiStrengths, $aiFocusArea, $aiTrendAnalysis, $month, $year]);

        if (isset($_POST['save_and_next'])) {
            $stmt = $pdo->prepare("
                SELECT s.student_id 
                FROM students s 
                WHERE s.student_status = 1 
                AND s.student_id > ?
                AND s.student_id NOT IN (
                    SELECT DISTINCT sa.student_id 
                    FROM student_assessments sa 
                    WHERE sa.student_assessment_month = ? 
                    AND sa.student_assessment_year = ?
                )
                ORDER BY s.student_id ASC 
                LIMIT 1
            ");
            $stmt->execute([$studentId, $month, $year]);
            $nextStudent = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($nextStudent) {
                header('Location: add.php?student_id=' . $nextStudent['student_id'] . '&month=' . $month . '&year=' . $year);
                exit;
            } else {
                header('Location: ../../teachers/students/?id=' . $studentId);
                exit;
            }
        } else {
            header('Location: ../../teachers/students/?id=' . $studentId);
            exit;
        }
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
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Add Assessment</h1>
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
                        <p class="text-sm font-medium text-emerald-800">Assessment saved successfully!</p>
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
                        Save Assessment
                    </button>
                    <button type="submit" name="save_and_next" class="bg-emerald-600 hover:bg-emerald-700 text-white font-inter font-medium px-6 py-3 rounded-xl transition-colors">
                        Save and Next Student
                    </button>
                    <a href="../../teachers/students/?id=<?= (int)$studentId ?>" class="px-6 py-3 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>