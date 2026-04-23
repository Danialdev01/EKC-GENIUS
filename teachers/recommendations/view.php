<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
require_once '../../backend/ai.php';

$authUser = requireAuth('teacher');
$pageTitle = 'Assessment Details';
$cssDepth = '../../public/css';
$location_index = '../..';

$assessment_id = $_GET['assessment_id'] ?? null;
$currentMonth = date('n');
$currentYear = date('Y');

if (!$assessment_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM assessments WHERE assessment_id = ? AND assessment_status = 1");
$stmt->execute([$assessment_id]);
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['generate_recommendation'])) {
    $apiKey = getenv('OPENROUTER_API_KEY') ?: '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';
    
    $stmt = $pdo->prepare("
        SELECT s.student_name, sa.student_assessment_value
        FROM student_assessments sa
        INNER JOIN students s ON s.student_id = sa.student_id
        WHERE sa.assessment_id = ? AND sa.student_assessment_month = ? AND sa.student_assessment_year = ? AND sa.student_assessment_status = 1 AND sa.student_assessment_value <= 2.5
        ORDER BY sa.student_assessment_value ASC
    ");
    $stmt->execute([$assessment_id, $currentMonth, $currentYear]);
    $studentsWithLowScores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT activity_name FROM activites 
        WHERE activity_type = (SELECT assessment_title FROM assessments WHERE assessment_id = ?)
        AND activity_status = 4
    ");
    $stmt->execute([$assessment_id]);
    $existingActivities = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $existingActivitiesList = !empty($existingActivities) ? implode(", ", $existingActivities) : "None";
    
    if (!empty($studentsWithLowScores)) {
        $studentNames = implode(", ", array_column($studentsWithLowScores, 'student_name'));
        
        $prompt = "You are an educational specialist for special needs children. Based on the following assessment area that students are struggling with, suggest ONE specific, practical activity that will help them improve their skills.

Assessment Area: {$assessment['assessment_title']}

Students needing intervention: {$studentNames}

IMPORTANT: The following activities have already been generated for this assessment area. DO NOT suggest any of these activities again:
{$existingActivitiesList}

Provide your response in this exact format:
Activity Name: [A specific activity name that does not include 'Recommendation for:' - e.g., 'Public speaking with other students', 'Eye contact games', 'Active listening exercises', etc.]
Activity Description: [A detailed description of the activity and its benefits for the student's improvement. Explain why this activity helps students improve in {$assessment['assessment_title']}.]

Do NOT include 'Recommendation for:' in the activity name. The activity name should be a specific, actionable short name (3-7 words). Make sure this is DIFFERENT from the existing activities listed above.";
        
        $result = callAI($prompt, 'openai/gpt-4o-mini', $apiKey);
        
        $activityName = "Activity";
        $activityDescription = "No activity generated.";
        
        if ($result['success']) {
            $content = $result['content'];
            
            if (preg_match('/Activity Name:\s*(.+?)(?:\n|$)/i', $content, $nameMatch)) {
                $activityName = trim($nameMatch[1]);
            }
            
            if (preg_match('/Activity Description:\s*(.+)/i', $content, $descMatch)) {
                $activityDescription = trim($descMatch[1]);
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO activites (activity_name, activity_description, activity_type, activity_status, activity_created_at, activity_updated_at)
            VALUES (?, ?, ?, 4, NOW(), NOW())
        ");
        $stmt->execute([
            $activityName,
            $activityDescription,
            $assessment['assessment_title']
        ]);
        
        $activityId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("
            SELECT student_id FROM student_assessments 
            WHERE assessment_id = ? AND student_assessment_month = ? AND student_assessment_year = ? AND student_assessment_status = 1 AND student_assessment_value <= 2.5
        ");
        $stmt->execute([$assessment_id, $currentMonth, $currentYear]);
        $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($studentIds as $studentId) {
            $stmt = $pdo->prepare("
                INSERT INTO assignments (activity_id, student_id, assignment_notes, assignment_status, assignment_created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$activityId, $studentId, "Auto-assigned from low score recommendation"]);
        }
        
        $message = 'Recommendation generated successfully!';
    } else {
        $message = 'No students found with scores below 2.5 for this assessment.';
    }
    
    header("Location: view.php?assessment_id=$assessment_id");
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.assessment_id, a.assessment_title, a.assessment_icon,
        ROUND(AVG(sa.student_assessment_value), 2) as avg_score,
        COUNT(CASE WHEN sa.student_assessment_value < 2.5 THEN 1 END) as low_score_count
    FROM assessments a
    LEFT JOIN student_assessments sa ON sa.assessment_id = a.assessment_id 
        AND sa.student_assessment_month = ? 
        AND sa.student_assessment_year = ?
        AND sa.student_assessment_status = 1
    WHERE a.assessment_id = ?
    GROUP BY a.assessment_id, a.assessment_title, a.assessment_icon
");
$stmt->execute([$currentMonth, $currentYear, $assessment_id]);
$assessmentStats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT s.student_id, s.student_name, s.student_year_of_birth, c.category_name,
        sa.student_assessment_value, sa.student_assessment_id
    FROM student_assessments sa
    INNER JOIN students s ON s.student_id = sa.student_id
    LEFT JOIN categories c ON c.category_id = s.category_id AND c.category_status = 1
    WHERE sa.assessment_id = ? 
        AND sa.student_assessment_month = ? 
        AND sa.student_assessment_year = ? 
        AND sa.student_assessment_status = 1
    ORDER BY sa.student_assessment_value ASC
");
$stmt->execute([$assessment_id, $currentMonth, $currentYear]);
$allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$lowScoreStudents = array_filter($allStudents, function($s) {
    return $s['student_assessment_value'] < 2.5;
});

$stmt = $pdo->prepare("
    SELECT * FROM activites 
    WHERE activity_type = (SELECT assessment_title FROM assessments WHERE assessment_id = ?)
    AND activity_status = 4
    ORDER BY activity_created_at DESC
");
$stmt->execute([$assessment_id]);
$recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$search = $_GET['search'] ?? '';
$studentPage = isset($_GET['student_page']) ? (int)$_GET['student_page'] : 1;
$activityPage = isset($_GET['activity_page']) ? (int)$_GET['activity_page'] : 1;
$perPage = 10;

$filteredStudents = $lowScoreStudents;
if ($search) {
    $filteredStudents = array_filter($filteredStudents, function($s) use ($search) {
        return stripos($s['student_name'], $search) !== false;
    });
}

$totalStudentPages = ceil(count($filteredStudents) / $perPage);
$studentOffset = ($studentPage - 1) * $perPage;
$paginatedStudents = array_slice($filteredStudents, $studentOffset, $perPage);

$totalActivityPages = ceil(count($recommendations) / $perPage);
$activityOffset = ($activityPage - 1) * $perPage;
$paginatedActivities = array_slice($recommendations, $activityOffset, $perPage);
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
                <h1 class="font-poppins text-xl font-semibold text-slate-800"><?= htmlspecialchars($assessment['assessment_title']) ?></h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
        <a href="index.php" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back
        </a>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="text-sm text-slate-500 mb-1">Average Score</div>
                <div class="text-3xl font-semibold <?= ($assessmentStats['avg_score'] ?? 0) < 2.5 ? 'text-red-600' : 'text-emerald-600' ?>">
                    <?= $assessmentStats['avg_score'] ? number_format($assessmentStats['avg_score'], 1) : '—' ?>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="text-sm text-slate-500 mb-1">Students Below 2.5</div>
                <div class="text-3xl font-semibold text-red-600">
                    <?= (int)($assessmentStats['low_score_count'] ?? 0) ?>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="text-sm text-slate-500 mb-1">Activities Generated</div>
                <div class="text-3xl font-semibold text-amber-600">
                    <?= count($recommendations) ?>
                </div>
            </div>
        </div>

        <br>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-poppins text-lg font-semibold text-slate-800">Students with Low Scores</h2>
                    <p class="text-sm text-slate-500">Students who scored below 2.5 for this assessment</p>
                </div>
                <form method="POST" onsubmit="return confirm('Generate AI recommendation for this assessment?');">
                    <button type="submit" name="generate_recommendation" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-inter font-medium px-5 py-2.5 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Generate AI Recommendation
                    </button>
                </form>
            </div>
            
            <div class="p-4 border-b border-slate-100">
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="assessment_id" value="<?= (int)$assessment_id ?>">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Search students...">
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors">Search</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Student Name</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($paginatedStudents)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-slate-400">
                                No students found with scores below 2.5
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($paginatedStudents as $s): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-medium text-slate-800"><?= htmlspecialchars($s['student_name']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-slate-600"><?= htmlspecialchars($s['category_name'] ?? '—') ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    <?= number_format($s['student_assessment_value'], 1) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalStudentPages > 1): ?>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Showing <?= $studentOffset + 1 ?> to <?= min($studentOffset + $perPage, count($filteredStudents)) ?> of <?= count($filteredStudents) ?> students
                </p>
                <div class="flex items-center gap-2">
                    <?php if ($studentPage > 1): ?>
                    <a href="?assessment_id=<?= (int)$assessment_id ?>&student_page=<?= $studentPage - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalStudentPages; $i++): ?>
                    <a href="?assessment_id=<?= (int)$assessment_id ?>&student_page=<?= $i ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $i === $studentPage ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($studentPage < $totalStudentPages): ?>
                    <a href="?assessment_id=<?= (int)$assessment_id ?>&student_page=<?= $studentPage + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <br>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-poppins text-lg font-semibold text-slate-800">Recommended Activities</h2>
                <p class="text-sm text-slate-500">AI-generated recommendations for this assessment</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Activity Name</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($paginatedActivities)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                No recommendations found. Click "Load New Recommendation" on the main page to generate.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($paginatedActivities as $a): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-medium text-slate-800"><?= htmlspecialchars($a['activity_name']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-600 line-clamp-2"><?= htmlspecialchars($a['activity_description']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                <?= date('d M Y', strtotime($a['activity_created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="edit_activity.php?activity_id=<?= (int)$a['activity_id'] ?>&assessment_id=<?= (int)$assessment_id ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Turn into Activity
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalActivityPages > 1): ?>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Showing <?= $activityOffset + 1 ?> to <?= min($activityOffset + $perPage, count($recommendations)) ?> of <?= count($recommendations) ?> activities
                </p>
                <div class="flex items-center gap-2">
                    <?php if ($activityPage > 1): ?>
                    <a href="?assessment_id=<?= (int)$assessment_id ?>&activity_page=<?= $activityPage - 1 ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalActivityPages; $i++): ?>
                    <a href="?assessment_id=<?= (int)$assessment_id ?>&activity_page=<?= $i ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $i === $activityPage ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($activityPage < $totalActivityPages): ?>
                    <a href="?assessment_id=<?= (int)$assessment_id ?>&activity_page=<?= $activityPage + 1 ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

</body>
</html>