<?php
session_start();
require_once '../config/connect.php';
require_once '../backend/auth.php';
$authUser  = requireAuth('teacher');
$pageTitle = 'Dashboard';
$cssDepth  = '../public/css';
$location_index = '..';

// ── Stats: Total active students ──────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_status = 1");
$stmt->execute();
$totalStudents = (int) $stmt->fetchColumn();

// ── Stats: Average score across all assessments this month ───────────────────
$currentMonth = date('n');
$currentYear  = date('Y');
$stmt = $pdo->prepare("
    SELECT ROUND(AVG(student_assessment_value), 2)
    FROM student_assessments
    WHERE student_assessment_status = 1
      AND student_assessment_month = ?
      AND student_assessment_year  = ?
");
$stmt->execute([$currentMonth, $currentYear]);
$averageScore = $stmt->fetchColumn();
$averageScore = $averageScore !== null ? (float) $averageScore : 0;

// ── Early warning count ────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM (
        SELECT s.student_id, ROUND(AVG(sa.student_assessment_value), 2) AS avg_score
        FROM students s
        LEFT JOIN student_assessments sa
            ON sa.student_id = s.student_id
            AND sa.student_assessment_status = 1
            AND sa.student_assessment_month = ?
            AND sa.student_assessment_year  = ?
        WHERE s.student_status = 1
        GROUP BY s.student_id
        HAVING avg_score IS NOT NULL AND avg_score < 2.5
    ) AS warnings
");
$stmt->execute([$currentMonth, $currentYear]);
$earlyWarningCount = (int) $stmt->fetchColumn();
?>
<?php include '../components/teacher/header.php'; ?>

<?php include '../components/teacher/sidebar.php'; ?>

<!-- Main Wrapper -->
<main class="lg:ml-64 min-h-screen flex flex-col">

    <!-- ── Top Header ─────────────────────────────────────────────────────── -->
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <!-- Hamburger (mobile) -->
            <button onclick="toggleTeacherSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Dashboard</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
    </header>

    <!-- ── Dashboard Content ──────────────────────────────────────────────── -->
    <div class="flex-1 p-4 lg:p-8 space-y-6">

        <!-- ── Stats Cards ──────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Total Students -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-2xl shrink-0">👶</div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Total Students</p>
                    <p class="font-poppins text-3xl font-bold text-slate-800 leading-tight"><?= $totalStudents ?></p>
                </div>
            </div>

            <!-- Early Warning Alerts -->
            <div class="bg-white rounded-2xl border <?= $earlyWarningCount > 0 ? 'border-red-200' : 'border-slate-200' ?> shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl <?= $earlyWarningCount > 0 ? 'bg-red-50' : 'bg-slate-50' ?> flex items-center justify-center text-2xl shrink-0">
                    <?= $earlyWarningCount > 0 ? '🚨' : '✅' ?>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Early Warnings</p>
                    <p class="font-poppins text-3xl font-bold <?= $earlyWarningCount > 0 ? 'text-red-500' : 'text-emerald-500' ?> leading-tight"><?= $earlyWarningCount ?></p>
                </div>
            </div>

            <!-- Average Score -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-2xl shrink-0">📊</div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Avg Score</p>
                    <p class="font-poppins text-3xl font-bold text-slate-800 leading-tight">
                        <?= $averageScore > 0 ? number_format($averageScore, 1) : '—' ?>
                        <?php if ($averageScore > 0): ?>
                        <span class="text-base font-medium text-slate-400">/5</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Activities Planned -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-2xl shrink-0">📋</div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Activities Planned</p>
                    <!-- <p class="font-poppins text-3xl font-bold text-slate-800 leading-tight"><?= $activitiesPlanned ?></p> -->
                </div>
            </div>

        </div>

        <?php include './components/all_students.php'; ?>

    </div><!-- /content -->
</main>

</body>
</html>