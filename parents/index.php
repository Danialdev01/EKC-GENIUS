<?php
session_start();
require_once '../config/connect.php';
require_once '../backend/auth.php';
require_once '../backend/formulas.php';

$authUser  = requireAuth('parent');   // student_id stored as user_id for parents
$pageTitle = 'My Child\'s Progress';
$cssDepth  = '../public/css';

$studentId = $authUser['id'];

// ── Student profile ───────────────────────────────────────────────────────────
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

// ── Latest assessment scores this month ───────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT a.assessment_title, a.assessment_icon, sa.student_assessment_value
    FROM student_assessments sa
    JOIN assessments a ON a.assessment_id = sa.assessment_id
    WHERE sa.student_id = ?
      AND sa.student_assessment_month = ?
      AND sa.student_assessment_year  = ?
      AND sa.student_assessment_status = 1
    ORDER BY a.assessment_title
");
$stmt->execute([$studentId, $currentMonth, $currentYear]);
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Average score ─────────────────────────────────────────────────────────────
$avgScore = count($assessments)
    ? round(array_sum(array_column($assessments, 'student_assessment_value')) / count($assessments), 2)
    : null;

$isAtRisk = $avgScore !== null && $avgScore < 2.5;

// ── Latest AI assessment ───────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT * FROM ai_assessments
    WHERE student_id = ?
      AND ai_assessment_status = 1
    ORDER BY ai_assessment_year DESC, ai_assessment_month DESC
    LIMIT 1
");
$stmt->execute([$studentId]);
$aiAssessment = $stmt->fetch(PDO::FETCH_ASSOC);

// ── Recent attendance (last 7) ─────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT attendance_type, attendance_notes, attendance_created_at
    FROM attendances
    WHERE student_id = ? AND attendance_status = 1
    ORDER BY attendance_created_at DESC
    LIMIT 7
");
$stmt->execute([$studentId]);
$attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
$attendanceTypes = [1 => 'Present', 2 => 'Absent', 3 => 'Late'];
$attendanceColors = [1 => 'emerald', 2 => 'red', 3 => 'amber'];

// ── Formula Calculations ───────────────────────────────────────────────
$monthlyAverages = getHistoricalMonthlyAverages($pdo, $studentId, 6);
$averageGrowth = calculateAverageGrowth($monthlyAverages);
$scoreInterpretation = getScoreInterpretation($avgScore);

// Predictive scores
$predictedScore3 = predictFutureScore($avgScore, $averageGrowth, 3);
$predictedScore6 = predictFutureScore($avgScore, $averageGrowth, 6);

// Annual index
$annualIndex = calculateAnnualIndex(array_column($monthlyAverages, 'avg'));

// Weak areas detection
$currentScores = [];
foreach ($assessments as $a) {
    $currentScores[$a['assessment_title']] = $a['student_assessment_value'];
}
$weakAreasList = detectWeakAreas(array_column($currentScores, null, null) ?? [], 2.5);
?>
<?php include '../components/teacher/header.php'; ?>

<!-- Parent Sidebar -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" onclick="toggleParentSidebar()"></div>
<aside id="parentSidebar" class="fixed top-0 left-0 h-full w-64 bg-slate-900 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 flex flex-col">
    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-700/60">
        <a href="../index.php" class="flex items-center gap-2 font-poppins text-lg font-bold">
            <span class="text-2xl">🧠</span>
            <span class="text-slate-100">EKC</span><span class="text-indigo-400">Genius</span>
        </a>
        <button onclick="toggleParentSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="px-5 py-4 border-b border-slate-700/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-base font-semibold text-white shrink-0">
                <?= strtoupper(substr($authUser['name'], 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-100 truncate"><?= htmlspecialchars($authUser['name']) ?></p>
                <p class="text-xs text-indigo-400 font-medium">Parent / Guardian</p>
            </div>
        </div>
    </div>
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        <?php
        $navItems = [
            ['href' => 'index.php',    'label' => 'Overview',     'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['href' => 'progress.php', 'label' => 'Progress',     'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['href' => 'reports.php',  'label' => 'Reports',      'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['href' => 'invoices.php', 'label' => 'Invoices',     'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['href' => 'profile.php', 'label' => 'Profile',     'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
        ];
        $cur = basename($_SERVER['PHP_SELF'], '.php');
        foreach ($navItems as $n): ?>
        <a href="<?= $n['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= ($cur === basename($n['href'], '.php')) ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $n['icon'] ?>"/>
            </svg>
            <?= $n['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="p-4 border-t border-slate-700/60">
        <a href="../backend/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:bg-red-500/15 hover:text-red-400 transition-all">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="lg:ml-64 min-h-screen flex flex-col">
    <!-- Top Header -->
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleParentSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">
                    <?= htmlspecialchars($student['student_name']) ?>
                </h1>
                <p class="text-xs text-slate-400"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($isAtRisk): ?>
            <span class="flex items-center gap-1.5 bg-red-50 text-red-600 border border-red-200 text-xs font-semibold px-3 py-1.5 rounded-full">
                🚨 Needs Attention
            </span>
            <?php else: ?>
            <span class="flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold px-3 py-1.5 rounded-full">
                ✅ On Track
            </span>
            <?php endif; ?>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">

        <!-- Student Profile Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-3xl font-bold shrink-0">
                <?= strtoupper(substr($student['student_name'], 0, 1)) ?>
            </div>
            <div class="flex-1">
                <h2 class="font-poppins text-xl font-bold text-slate-800"><?= htmlspecialchars($student['student_name']) ?></h2>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($student['category_name'] ?? 'N/A') ?> · Enrolled <?= $student['student_enrollment_date'] ? date('M Y', strtotime($student['student_enrollment_date'])) : '—' ?></p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-xs text-slate-400">Parent</p>
                <p class="text-sm font-semibold text-slate-700"><?= htmlspecialchars($student['student_parent_name'] ?? '—') ?></p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-2xl shrink-0">📊</div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Avg Score</p>
                    <p class="font-poppins text-2xl font-bold text-slate-800">
                        <?= $avgScore !== null ? number_format($avgScore, 1) . '<span class="text-sm text-slate-400">/5</span>' : '—' ?>
                    </p>
                    <?php if ($scoreInterpretation): ?>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $scoreInterpretation['class'] ?>">
                        <?= $scoreInterpretation['emoji'] ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-2xl shrink-0">📈</div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Annual Index</p>
                    <p class="font-poppins text-2xl font-bold text-slate-800"><?= $annualIndex !== null ? number_format($annualIndex, 1) : '—' ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-2xl shrink-0">🎯</div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Predicted (3mo)</p>
                    <p class="font-poppins text-2xl font-bold text-slate-800"><?= $predictedScore3 ?? '—' ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border <?= $isAtRisk ? 'border-red-200' : 'border-emerald-200' ?> shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl <?= $isAtRisk ? 'bg-red-50' : 'bg-emerald-50' ?> flex items-center justify-center text-2xl shrink-0">
                    <?= $isAtRisk ? '🚨' : '✅' ?>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Status</p>
                    <p class="font-poppins text-lg font-bold <?= $isAtRisk ? 'text-red-500' : 'text-emerald-600' ?>">
                        <?= $isAtRisk ? 'At Risk' : 'On Track' ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Assessments this month -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-poppins text-base font-semibold text-slate-800">Assessments — <?= date('F Y') ?></h3>
                </div>
                <?php if (empty($assessments)): ?>
                <p class="px-6 py-8 text-sm text-slate-400 text-center">No assessments recorded this month.</p>
                <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($assessments as $a):
                        $val = (int)$a['student_assessment_value'];
                        $pct = ($val / 5) * 100;
                        $color = $val >= 4 ? 'bg-emerald-500' : ($val >= 3 ? 'bg-amber-400' : 'bg-red-500');
                    ?>
                    <div class="px-6 py-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($a['assessment_title']) ?></span>
                            <span class="text-sm font-bold text-slate-800"><?= $val ?><span class="text-slate-400">/5</span></span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full <?= $color ?> rounded-full transition-all" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Attendance -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-poppins text-base font-semibold text-slate-800">Recent Attendance</h3>
                </div>
                <?php if (empty($attendances)): ?>
                <p class="px-6 py-8 text-sm text-slate-400 text-center">No attendance records found.</p>
                <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($attendances as $att):
                        $t = (int)$att['attendance_type'];
                        $label = $attendanceTypes[$t] ?? 'Unknown';
                        $col   = $attendanceColors[$t] ?? 'slate';
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
                        <span class="text-xs text-slate-400 shrink-0"><?= date('d M', strtotime($att['attendance_created_at'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- AI Assessment -->
        <?php if ($aiAssessment): ?>
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl border border-indigo-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-500 flex items-center justify-center text-xl text-white">🤖</div>
                <div>
                    <h3 class="font-poppins text-base font-semibold text-slate-800">AI Assessment</h3>
                    <p class="text-xs text-slate-500"><?= date('F Y', mktime(0,0,0,$aiAssessment['ai_assessment_month'],1,$aiAssessment['ai_assessment_year'])) ?></p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <?php if ($aiAssessment['ai_assessment_strengths']): ?>
                <div class="bg-white/70 rounded-xl p-4">
                    <p class="font-semibold text-emerald-700 mb-1">💪 Strengths</p>
                    <p class="text-slate-600 text-xs leading-relaxed"><?= htmlspecialchars($aiAssessment['ai_assessment_strengths']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($aiAssessment['ai_assessment_focus_area']): ?>
                <div class="bg-white/70 rounded-xl p-4">
                    <p class="font-semibold text-amber-700 mb-1">🎯 Focus Areas</p>
                    <p class="text-slate-600 text-xs leading-relaxed"><?= htmlspecialchars($aiAssessment['ai_assessment_focus_area']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($aiAssessment['ai_assessment_trend_analysis']): ?>
                <div class="bg-white/70 rounded-xl p-4">
                    <p class="font-semibold text-indigo-700 mb-1">📈 Trend</p>
                    <p class="text-slate-600 text-xs leading-relaxed"><?= htmlspecialchars($aiAssessment['ai_assessment_trend_analysis']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

<script>
function toggleParentSidebar() {
    const sidebar = document.getElementById('parentSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>
</body>
</html>
