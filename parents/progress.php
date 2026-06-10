<?php
session_start();
require_once '../config/connect.php';
require_once '../backend/auth.php';
require_once '../backend/formulas.php';

$authUser  = requireAuth('parent');
$pageTitle = 'Progress';
$cssDepth  = '../public/css';

$studentId = $authUser['id'];

$stmt = $pdo->prepare("
    SELECT s.*, c.category_name
    FROM students s
    LEFT JOIN categories c ON c.category_id = s.category_id
    WHERE s.student_id = ? AND s.student_status = 1
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    session_unset(); session_destroy();
    header('Location: ../index.php'); exit;
}

$currentMonth = (int)date('n');
$currentYear  = (int)date('Y');

$monthlyAverages = getHistoricalMonthlyAverages($pdo, $studentId, 6);
$averageGrowth = calculateAverageGrowth($monthlyAverages);

$stmt = $pdo->prepare("
    SELECT a.assessment_title, a.assessment_icon, a.assessment_description, sa.student_assessment_value, sa.student_assessment_month, sa.student_assessment_year
    FROM student_assessments sa
    JOIN assessments a ON a.assessment_id = sa.assessment_id
    WHERE sa.student_id = ? AND sa.student_assessment_status = 1
    ORDER BY sa.student_assessment_year DESC, sa.student_assessment_month DESC, a.assessment_title
");
$stmt->execute([$studentId]);
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT * FROM ai_assessments
    WHERE student_id = ? AND ai_assessment_status = 1
    ORDER BY ai_assessment_year DESC, ai_assessment_month DESC
    LIMIT 1
");
$stmt->execute([$studentId]);
$latestAiAssessment = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT attendance_type, attendance_notes, attendance_created_at
    FROM attendances
    WHERE student_id = ? AND attendance_status = 1
    ORDER BY attendance_created_at DESC
");
$stmt->execute([$studentId]);
$attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
$attendanceTypes = [1 => 'Present', 2 => 'Absent', 3 => 'Late'];
$attendanceColors = [1 => 'emerald', 2 => 'red', 3 => 'amber'];
?>
<?php include '../components/teacher/header.php'; ?>

<?php include __DIR__ . '/../components/parent/sidebar.php'; ?>

<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleParentSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Progress Tracking</h1>
                <p class="text-xs text-slate-400"><?= htmlspecialchars($student['student_name']) ?> · <?= date('l, F d, Y') ?></p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-2xl shrink-0">📊</div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Avg Growth</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800">
                            <?= $averageGrowth !== null ? number_format($averageGrowth, 1) . '<span class="text-sm text-slate-400">%</span>' : '—' ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-2xl shrink-0">📈</div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Data Points</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800"><?= count($monthlyAverages) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-2xl shrink-0">🎯</div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Current Month</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800"><?= date('M Y') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="font-poppins text-base font-semibold text-slate-800">Monthly Progress Overview</h3>
            </div>
            <?php if (empty($monthlyAverages)): ?>
            <p class="px-6 py-8 text-sm text-slate-400 text-center">No progress data available yet.</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Month</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Avg Score</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($monthlyAverages as $data): 
                            $val = $data['avg'];
                            if ($val === null) continue;
                            $pct = ($val / 5) * 100;
                            $color = $val >= 4 ? 'bg-emerald-500' : ($val >= 3 ? 'bg-amber-400' : 'bg-red-500');
                            $statusClass = $val >= 4 ? 'text-emerald-600' : ($val >= 3 ? 'text-amber-600' : 'text-red-500');
                            $statusText = $val >= 4 ? 'Excellent' : ($val >= 3 ? 'Good' : 'Needs Improvement');
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-slate-700">
                                <?= date('F Y', mktime(0,0,0,$data['month'],1,$data['year'])) ?>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-slate-800"><?= number_format($val, 2) ?>/5</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold <?= $statusClass ?> bg-current/10">
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 min-w-[200px]">
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full <?= $color ?> rounded-full transition-all" style="width:<?= $pct ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-poppins text-base font-semibold text-slate-800">Assessment History</h3>
                </div>
                <?php if (empty($assessments)): ?>
                <p class="px-6 py-8 text-sm text-slate-400 text-center">No assessment records found.</p>
                <?php else: ?>
                <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    <?php foreach ($assessments as $a):
                        $val = (int)$a['student_assessment_value'];
                        $pct = ($val / 5) * 100;
                        $color = $val >= 4 ? 'bg-emerald-500' : ($val >= 3 ? 'bg-amber-400' : 'bg-red-500');
                    ?>
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-700"><?= htmlspecialchars($a['assessment_title']) ?></p>
                                <p class="text-xs text-slate-400"><?= date('F Y', mktime(0,0,0,$a['student_assessment_month'],1,$a['student_assessment_year'])) ?></p>
                            </div>
                            <span class="text-lg font-bold text-slate-800"><?= $val ?><span class="text-sm text-slate-400">/5</span></span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full <?= $color ?> rounded-full" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-poppins text-base font-semibold text-slate-800">Attendance History</h3>
                </div>
                <?php if (empty($attendances)): ?>
                <p class="px-6 py-8 text-sm text-slate-400 text-center">No attendance records found.</p>
                <?php else: ?>
                <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    <?php 
                    $present = 0; $absent = 0; $late = 0;
                    foreach ($attendances as $att) {
                        $t = (int)$att['attendance_type'];
                        if ($t === 1) $present++;
                        elseif ($t === 2) $absent++;
                        elseif ($t === 3) $late++;
                    }
                    $total = count($attendances);
                    ?>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="p-3 bg-emerald-50 rounded-xl">
                                <p class="text-2xl font-bold text-emerald-600"><?= $present ?></p>
                                <p class="text-xs text-slate-500">Present</p>
                            </div>
                            <div class="p-3 bg-red-50 rounded-xl">
                                <p class="text-2xl font-bold text-red-600"><?= $absent ?></p>
                                <p class="text-xs text-slate-500">Absent</p>
                            </div>
                            <div class="p-3 bg-amber-50 rounded-xl">
                                <p class="text-2xl font-bold text-amber-600"><?= $late ?></p>
                                <p class="text-xs text-slate-500">Late</p>
                            </div>
                        </div>
                    </div>
                    <?php foreach ($attendances as $att):
                        $t = (int)$att['attendance_type'];
                        $label = $attendanceTypes[$t] ?? 'Unknown';
                        $emoji = $t === 1 ? '✅' : ($t === 2 ? '❌' : '🕐');
                    ?>
                    <div class="px-6 py-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-lg"><?= $emoji ?></span>
                            <div>
                                <p class="text-sm font-medium text-slate-700"><?= $label ?></p>
                                <?php if ($att['attendance_notes']): ?>
                                <p class="text-xs text-slate-400"><?= htmlspecialchars($att['attendance_notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="text-xs text-slate-400 shrink-0"><?= date('d M Y', strtotime($att['attendance_created_at'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

</body>
</html>