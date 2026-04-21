<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
$authUser = requireAuth('teacher');
$pageTitle = 'Analysis';
$cssDepth = '../../public/css';
$location_index = '../..';

$currentMonth = date('n');
$currentYear = date('Y');

// Average Development Score
$stmt = $pdo->prepare("
    SELECT ROUND(AVG(student_assessment_value), 2)
    FROM student_assessments
    WHERE student_assessment_status = 1
      AND student_assessment_month = ?
      AND student_assessment_year = ?
");
$stmt->execute([$currentMonth, $currentYear]);
$averageScore = (float)($stmt->fetchColumn() ?? 0);

// Total Assessments
$stmt = $pdo->query("SELECT COUNT(*) FROM student_assessments WHERE student_assessment_status = 1");
$totalAssessments = (int)$stmt->fetchColumn();

// High Performers (>=4.0)
$stmt = $pdo->prepare("
    SELECT student_id, ROUND(AVG(student_assessment_value), 2) as avg_score
    FROM student_assessments
    WHERE student_assessment_status = 1
      AND student_assessment_month = ?
      AND student_assessment_year = ?
    GROUP BY student_id
    HAVING avg_score >= 4.0
");
$stmt->execute([$currentMonth, $currentYear]);
$highPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$highPerformerCount = count($highPerformers);

// Need Attention (<2.5)
$stmt = $pdo->prepare("
    SELECT student_id, ROUND(AVG(student_assessment_value), 2) as avg_score
    FROM student_assessments
    WHERE student_assessment_status = 1
      AND student_assessment_month = ?
      AND student_assessment_year = ?
    GROUP BY student_id
    HAVING avg_score < 2.5
");
$stmt->execute([$currentMonth, $currentYear]);
$needAttention = $stmt->fetchAll(PDO::FETCH_ASSOC);
$needAttentionCount = count($needAttention);

// Total Students
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_status = 1");
$stmt->execute();
$totalStudents = (int)$stmt->fetchColumn();

// Score Distribution
$stmt = $pdo->prepare("
    SELECT student_id, ROUND(AVG(student_assessment_value), 2) as avg_score
    FROM student_assessments
    WHERE student_assessment_status = 1
      AND student_assessment_month = ?
      AND student_assessment_year = ?
    GROUP BY student_id
    HAVING avg_score IS NOT NULL
");
$stmt->execute([$currentMonth, $currentYear]);
$scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$excellent = 0;
$good = 0;
$developing = 0;
$emerging = 0;
$veryLow = 0;

foreach ($scores as $s) {
    $score = (float)$s['avg_score'];
    if ($score >= 4.5) $excellent++;
    elseif ($score >= 3.5) $good++;
    elseif ($score >= 2.5) $developing++;
    elseif ($score >= 1.5) $emerging++;
    else $veryLow++;
}

$totalScored = count($scores);
$excellentPct = $totalScored > 0 ? round($excellent / $totalScored * 100) : 0;
$goodPct = $totalScored > 0 ? round($good / $totalScored * 100) : 0;
$developingPct = $totalScored > 0 ? round($developing / $totalScored * 100) : 0;
$emergingPct = $totalScored > 0 ? round($emerging / $totalScored * 100) : 0;
$veryLowPct = $totalScored > 0 ? round($veryLow / $totalScored * 100) : 0;

// Top Performers
$stmt = $pdo->prepare("
    SELECT s.student_id, s.student_name, ROUND(AVG(sa.student_assessment_value), 2) as avg_score
    FROM students s
    INNER JOIN student_assessments sa ON sa.student_id = s.student_id AND sa.student_assessment_status = 1
    WHERE sa.student_assessment_month = ? AND sa.student_assessment_year = ?
      AND s.student_status = 1
    GROUP BY s.student_id, s.student_name
    ORDER BY avg_score DESC
    LIMIT 10
");
$stmt->execute([$currentMonth, $currentYear]);
$topPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Activities
$stmt = $pdo->query("SELECT COUNT(*) FROM activites WHERE activity_status = 1");
$totalActivities = (int)$stmt->fetchColumn();

// Attendance Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE attendance_status = 1");
$stmt->execute();
$totalAttendance = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE attendance_type = 1 AND attendance_status = 1");
$stmt->execute();
$presentCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE attendance_type = 2 AND attendance_status = 1");
$stmt->execute();
$absentCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE attendance_type = 3 AND attendance_status = 1");
$stmt->execute();
$lateCount = (int)$stmt->fetchColumn();

// Assessment history for chart
$stmt = $pdo->query("SELECT DISTINCT student_assessment_month, student_assessment_year FROM student_assessments WHERE student_assessment_status = 1 ORDER BY student_assessment_year DESC, student_assessment_month DESC LIMIT 12");
$months = $stmt->fetchAll(PDO::FETCH_ASSOC);

$monthlyAvg = [];
foreach (array_reverse($months) as $m) {
    $stmt = $pdo->prepare("
        SELECT ROUND(AVG(student_assessment_value), 2)
        FROM student_assessments
        WHERE student_assessment_status = 1
          AND student_assessment_month = ?
          AND student_assessment_year = ?
    ");
    $stmt->execute([$m['student_assessment_month'], $m['student_assessment_year']]);
    $avg = (float)($stmt->fetchColumn() ?? 0);
    $monthLabel = date('M Y', mktime(0, 0, 0, $m['student_assessment_month'], 1, $m['student_assessment_year']));
    $monthlyAvg[] = ['label' => $monthLabel, 'avg' => $avg];
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
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Analysis</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        <!-- Stats Overview -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-xl">📊</div>
                    <p class="text-xs text-slate-500 font-medium uppercase">Avg Dev Score</p>
                </div>
                <p class="font-poppins text-3xl font-bold text-slate-800"><?= number_format($averageScore, 1) ?></p>
                <p class="text-xs text-slate-400 mt-1">Current Month</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-xl">📝</div>
                    <p class="text-xs text-slate-500 font-medium uppercase">Total Assessments</p>
                </div>
                <p class="font-poppins text-3xl font-bold text-slate-800"><?= $totalAssessments ?></p>
                <p class="text-xs text-slate-400 mt-1">All time</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-xl">🏆</div>
                    <p class="text-xs text-slate-500 font-medium uppercase">High Performers (≥4.0)</p>
                </div>
                <p class="font-poppins text-3xl font-bold text-slate-800"><?= $highPerformerCount ?></p>
                <p class="text-xs text-slate-400 mt-1">Students</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-xl">⚠️</div>
                    <p class="text-xs text-slate-500 font-medium uppercase">Need Attention (<2.5)</p>
                </div>
                <p class="font-poppins text-3xl font-bold text-slate-800"><?= $needAttentionCount ?></p>
                <p class="text-xs text-slate-400 mt-1">Students</p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Performers -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">🏆 Top Performers</h3>
                <div class="space-y-3">
                    <?php foreach ($topPerformers as $i => $tp): ?>
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold"><?= $i + 1 ?></span>
                            <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($tp['student_name']) ?></span>
                        </div>
                        <span class="text-sm font-bold text-emerald-600"><?= number_format($tp['avg_score'], 1) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($topPerformers)): ?>
                    <p class="text-slate-400 text-center py-4">No assessment data available</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Score Distribution -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">📊 Score Distribution</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="text-sm text-slate-600">Excellent (4.5-5.0)</span>
                        </div>
                        <span class="text-sm font-medium text-slate-800"><?= $excellent ?> students (<?= $excellentPct ?>%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: <?= $excellentPct ?>%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                            <span class="text-sm text-slate-600">Good (3.5-4.4)</span>
                        </div>
                        <span class="text-sm font-medium text-slate-800"><?= $good ?> students (<?= $goodPct ?>%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: <?= $goodPct ?>%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="text-sm text-slate-600">Developing (2.5-3.4)</span>
                        </div>
                        <span class="text-sm font-medium text-slate-800"><?= $developing ?> students (<?= $developingPct ?>%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-amber-500 h-2 rounded-full" style="width: <?= $developingPct ?>%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                            <span class="text-sm text-slate-600">Emerging (1.5-2.4)</span>
                        </div>
                        <span class="text-sm font-medium text-slate-800"><?= $emerging ?> students (<?= $emergingPct ?>%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-orange-500 h-2 rounded-full" style="width: <?= $emergingPct ?>%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="text-sm text-slate-600">Very Low (1.0-1.4)</span>
                        </div>
                        <span class="text-sm font-medium text-slate-800"><?= $veryLow ?> students (<?= $veryLowPct ?>%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: <?= $veryLowPct ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Monthly Trend Chart -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">📈 Development Trend</h3>
                <div class="h-64">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Activity & Attendance Stats -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">📅 Activities & Attendance</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-purple-50 rounded-xl">
                        <p class="text-xs text-purple-600 font-medium uppercase">Total Activities</p>
                        <p class="text-2xl font-bold text-purple-700"><?= $totalActivities ?></p>
                    </div>
                    <div class="p-4 bg-indigo-50 rounded-xl">
                        <p class="text-xs text-indigo-600 font-medium uppercase">Total Students</p>
                        <p class="text-2xl font-bold text-indigo-700"><?= $totalStudents ?></p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm font-medium text-slate-700 mb-3">Attendance Overview</p>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-2 bg-emerald-50 rounded-lg">
                            <span class="text-sm text-slate-600">Present</span>
                            <span class="text-sm font-bold text-emerald-700"><?= $presentCount ?></span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-red-50 rounded-lg">
                            <span class="text-sm text-slate-600">Absent</span>
                            <span class="text-sm font-bold text-red-700"><?= $absentCount ?></span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-amber-50 rounded-lg">
                            <span class="text-sm text-slate-600">Late</span>
                            <span class="text-sm font-bold text-amber-700"><?= $lateCount ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const trendLabels = <?= json_encode(array_column($monthlyAvg, 'label')) ?>;
const trendData = <?= json_encode(array_column($monthlyAvg, 'avg')) ?>;

new Chart(document.getElementById('trendChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [{
            label: 'Average Score',
            data: trendData,
            borderColor: 'rgba(99, 102, 241, 1)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                min: 0,
                max: 5,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>
</body>
</html>