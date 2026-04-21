<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
requireAuth('admin');
$pageTitle = 'Assessments';
$cssDepth = '../../public/css';
$location_index = '../..';

$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM students WHERE student_status = 1";
$stmt = $pdo->query($countSql);
$totalStudents = $stmt->fetchColumn();
$totalPages = ceil($totalStudents / $perPage);

$sql = "
    SELECT s.student_id, s.student_name, s.category_id
    FROM students s
    WHERE s.student_status = 1
    ORDER BY s.student_name ASC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->query($sql);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getCategoryName($pdo, $categoryId) {
    if (!$categoryId) return '—';
    $stmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ? AND category_status = 1");
    $stmt->execute([$categoryId]);
    return $stmt->fetchColumn() ?: '—';
}

function getAssessmentData($pdo, $studentId, $month, $year) {
    $stmt = $pdo->prepare("
        SELECT 
            AVG(student_assessment_value) as avg_value,
            MAX(student_assessment_created_at) as last_assessed
        FROM student_assessments 
        WHERE student_id = ? 
          AND student_assessment_month = ? 
          AND student_assessment_year = ?
          AND student_assessment_status = 1
        GROUP BY student_id
    ");
    $stmt->execute([$studentId, $month, $year]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function hasAssessment($pdo, $studentId, $month, $year) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM student_assessments 
        WHERE student_id = ? 
          AND student_assessment_month = ? 
          AND student_assessment_year = ?
          AND student_assessment_status = 1
    ");
    $stmt->execute([$studentId, $month, $year]);
    return $stmt->fetchColumn() > 0;
}

foreach ($students as &$student) {
    $student['category_name'] = getCategoryName($pdo, $student['category_id'] ?? null);
    $assessmentData = getAssessmentData($pdo, $student['student_id'], $currentMonth, $currentYear);
    $student['avg_value'] = $assessmentData['avg_value'] ?? null;
    $student['last_assessed'] = $assessmentData['last_assessed'] ?? null;
    $student['has_assessment'] = hasAssessment($pdo, $student['student_id'], $currentMonth, $currentYear);
}
unset($student);

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
$years = range(date('Y') - 2, date('Y'));
?>
<?php include '../../components/teacher/header.php'; ?>

<?php include '../../components/sidebar.php'; ?>

<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Assessments</h1>
                <p class="text-xs text-slate-400 hidden sm:block">Manage student assessments</p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-poppins text-lg font-semibold text-slate-800">Student Assessments</h2>
                    <p class="text-xs text-slate-400 mt-0.5"><?= $months[$currentMonth] ?> <?= $currentYear ?></p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <form method="get" class="flex flex-wrap items-center gap-2">
                        <select name="month" onchange="this.form.submit()" class="bg-slate-50 text-slate-800 font-inter text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <?php foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num === $currentMonth ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="year" onchange="this.form.submit()" class="bg-slate-50 text-slate-800 font-inter text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <?php foreach ($years as $year): ?>
                            <option value="<?= $year ?>" <?= $year === $currentYear ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <div class="relative w-full sm:w-64">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input
                            id="searchInput"
                            type="text"
                            placeholder="Search student…"
                            class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 transition-all"
                        >
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full" id="assessmentTable">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Student</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Category</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Last Assessed</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Average</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="tableBody">
                        <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">No students found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($students as $student):
                            $avgValue = $student['avg_value'] !== null ? (float)$student['avg_value'] : null;
                            $lastAssessed = $student['last_assessed'];
                            $hasAssessment = $student['has_assessment'];

                            if ($avgValue === null) {
                                $avgBadge = 'bg-slate-100 text-slate-500';
                                $avgLabel = '—';
                            } elseif ($avgValue < 2.5) {
                                $avgBadge = 'bg-red-100 text-red-600';
                                $avgLabel = number_format($avgValue, 1);
                            } elseif ($avgValue < 3.5) {
                                $avgBadge = 'bg-amber-100 text-amber-700';
                                $avgLabel = number_format($avgValue, 1);
                            } else {
                                $avgBadge = 'bg-emerald-100 text-emerald-700';
                                $avgLabel = number_format($avgValue, 1);
                            }

                            $lastAssessedDisplay = $lastAssessed ? date('d M Y', strtotime($lastAssessed)) : '—';
                        ?>
                        <tr class="student-row" data-name="<?= strtolower(htmlspecialchars($student['student_name'])) ?>">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold shrink-0">
                                        <?= strtoupper(substr($student['student_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($student['student_name']) ?></p>
                                        <p class="text-xs text-slate-400">#<?= $student['student_id'] ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                    <?= htmlspecialchars($student['category_name']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600"><?= $lastAssessedDisplay ?></td>
                            <td class="px-4 py-4">
                                <?php if ($avgValue === null): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">Not Assessed</span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $avgBadge ?>">
                                    <?= $avgLabel ?>
                                    <span class="ml-0.5 opacity-60">/5</span>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($hasAssessment): ?>
                                <a href="<?= $location_index ?>/admin/students/?id=<?= (int)$student['student_id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">
                                    View / Edit
                                </a>
                                <?php else: ?>
                                <a href="<?= $location_index ?>/admin/students/?id=<?= (int)$student['student_id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition-colors">
                                    Add Assessment
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between">
                <p class="text-xs text-slate-400">
                    Showing <?= $offset + 1 ?>-<?= min($offset + count($students), $totalStudents) ?> of <?= $totalStudents ?> students
                </p>
                <?php if ($totalPages > 1): ?>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&month=<?= $currentMonth ?>&year=<?= $currentYear ?>&search=<?= urlencode($searchQuery) ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Previous</a>
                    <?php endif; ?>
                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <a href="?page=<?= $p ?>&month=<?= $currentMonth ?>&year=<?= $currentYear ?>&search=<?= urlencode($searchQuery) ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $p === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 hover:bg-slate-50' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&month=<?= $currentMonth ?>&year=<?= $currentYear ?>&search=<?= urlencode($searchQuery) ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
(function() {
    var searchInput = document.getElementById('searchInput');
    var rows = document.querySelectorAll('.student-row');
    
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        var query = searchInput.value.trim().toLowerCase();
        
        rows.forEach(function(row) {
            var name = row.getAttribute('data-name') || '';
            var match = name.includes(query);
            row.style.display = match ? '' : 'none';
        });
    });
})();
</script>