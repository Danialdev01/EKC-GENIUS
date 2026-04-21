<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
require_once '../../backend/ai.php';

$authUser = requireAuth('teacher');
$pageTitle = 'Recommendations';
$cssDepth = '../../public/css';
$location_index = '../..';

$currentMonth = date('n');
$currentYear = date('Y');

$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

if (isset($_POST['load_recommendations'])) {
    $apiKey = getenv('OPENROUTER_API_KEY') ?: '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';
    
    $stmt = $pdo->prepare("
        SELECT a.assessment_id, a.assessment_title, ROUND(AVG(sa.student_assessment_value), 2) as avg_score
        FROM assessments a
        INNER JOIN student_assessments sa ON sa.assessment_id = a.assessment_id AND sa.student_assessment_status = 1
        WHERE sa.student_assessment_month = ? AND sa.student_assessment_year = ?
          AND a.assessment_status = 1
        GROUP BY a.assessment_id, a.assessment_title
        HAVING avg_score < 2.5
        ORDER BY avg_score ASC
    ");
    $stmt->execute([$currentMonth, $currentYear]);
    $lowAssessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($lowAssessments as $assess) {
        $assessmentId = $assess['assessment_id'];
        $assessmentTitle = $assess['assessment_title'];
        
        $stmt = $pdo->prepare("
            SELECT s.student_name, sa.student_assessment_value
            FROM student_assessments sa
            INNER JOIN students s ON s.student_id = sa.student_id
            WHERE sa.assessment_id = ? AND sa.student_assessment_month = ? AND sa.student_assessment_year = ? AND sa.student_assessment_status = 1 AND sa.student_assessment_value <= 2.5
            ORDER BY sa.student_assessment_value ASC
        ");
        $stmt->execute([$assessmentId, $currentMonth, $currentYear]);
        $studentsWithLowScores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($studentsWithLowScores)) continue;
        
        $studentNames = implode(", ", array_column($studentsWithLowScores, 'student_name'));
        
        $prompt = "You are an educational specialist for special needs children. Based on the following assessment area that students are struggling with, provide 3-5 specific, practical, and actionable recommended activities/interventions to help improve their skills.

Assessment Area: {$assessmentTitle}

Students needing intervention: {$studentNames}

Please provide the recommended activities in this exact format:
💡 Recommended Activities:
• [First activity]
• [Second activity]
• [Third activity]
• [Fourth activity - if applicable]
• [Fifth activity - if applicable]

Keep each activity concise but specific. Focus on practical activities that can be done in a classroom setting.";
        
        $result = callAI($prompt, 'openai/gpt-4o-mini', $apiKey);
        
        $recommendations = "No recommendations available.";
        if ($result['success']) {
            if (preg_match('/💡 Recommended Activities:(.*?)(?=$|\Z)/s', $result['content'], $matches)) {
                $recommendations = trim($matches[1]);
            } else {
                $recommendations = $result['content'];
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO activites (activity_name, activity_description, activity_type, activity_active_at, activity_status, activity_created_at, activity_updated_at)
            VALUES (?, ?, ?, ?, 4, NOW(), NOW())
        ");
        $stmt->execute([
            "Recommendation for: " . $assessmentTitle,
            $recommendations,
            "learning",
            null
        ]);
        
        $activityId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("
            SELECT student_id FROM student_assessments 
            WHERE assessment_id = ? AND student_assessment_month = ? AND student_assessment_year = ? AND student_assessment_status = 1 AND student_assessment_value <= 2.5
        ");
        $stmt->execute([$assessmentId, $currentMonth, $currentYear]);
        $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($studentIds as $studentId) {
            $stmt = $pdo->prepare("
                INSERT INTO assignments (activity_id, student_id, assignment_notes, assignment_status, assignment_created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$activityId, $studentId, "Auto-assigned from low score recommendation"]);
        }
    }
    
    $message = 'Recommendations loaded successfully!';
}

$offset = ($page - 1) * $perPage;

$whereClauses = ["a.assessment_status = 1"];
$params = [];

if ($search) {
    $whereClauses[] = "a.assessment_title LIKE ?";
    $params[] = "%$search%";
}

$where = implode(" AND ", $whereClauses);

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM assessments a WHERE $where
");
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);

$stmt = $pdo->prepare("
    SELECT a.assessment_id, a.assessment_title, a.assessment_icon,
        ROUND(AVG(sa.student_assessment_value), 2) as avg_score,
        COUNT(CASE WHEN sa.student_assessment_value < 2.5 THEN 1 END) as low_score_count,
        (SELECT COUNT(DISTINCT act.activity_id) FROM activites act 
            WHERE act.activity_name LIKE CONCAT('%', a.assessment_title, '%') 
            AND act.activity_status = 4) as recommendation_count
    FROM assessments a
    LEFT JOIN student_assessments sa ON sa.assessment_id = a.assessment_id 
        AND sa.student_assessment_month = ? 
        AND sa.student_assessment_year = ?
        AND sa.student_assessment_status = 1
    WHERE $where
    GROUP BY a.assessment_id, a.assessment_title, a.assessment_icon
    ORDER BY AVG(sa.student_assessment_value) ASC, low_score_count DESC
");
$stmt->execute(array_merge([$currentMonth, $currentYear], $params));
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$assessments = array_slice($assessments, $offset, $perPage);
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
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Recommendations</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        <?php if (isset($message)): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-4">
            <div class="w-full max-w-xs">
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Search assessments...">
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors">Search</button>
                </form>
            </div>
            <form method="POST" class="flex gap-2">
                <button type="submit" name="load_recommendations" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-inter font-medium px-5 py-2.5 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Load New Recommendation
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Assessment</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg Score</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Students Below 2.5</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Activities Generated</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($assessments)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                No assessments found. Click "Load New Recommendation" to generate recommendations.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($assessments as $a): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl"><?= htmlspecialchars($a['assessment_icon'] ?? '📋') ?></span>
                                    <span class="font-medium text-slate-800"><?= htmlspecialchars($a['assessment_title']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($a['avg_score']): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $a['avg_score'] < 2.5 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' ?>">
                                    <?= number_format($a['avg_score'], 1) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($a['low_score_count'] > 0): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    <?= (int)$a['low_score_count'] ?> students
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($a['recommendation_count'] > 0): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                    <?= (int)$a['recommendation_count'] ?> activities
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="view.php?assessment_id=<?= (int)$a['assessment_id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Showing <?= (($page - 1) * $perPage) + 1 ?> to <?= min($page * $perPage, $totalItems) ?> of <?= $totalItems ?> results
                </p>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $i === $page ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

</body>
</html>