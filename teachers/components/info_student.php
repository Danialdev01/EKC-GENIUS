<?php
require_once '../../backend/formulas.php';

// ── Get student details ─────────────────────────────────────────────
$studentId = $GLOBALS['studentId'] ?? null;

if (!$studentId) {
    echo "<p class='text-center text-slate-400 py-12'>No student selected.</p>";
    return;
}

$stmt = $pdo->prepare("
    SELECT s.*, c.category_name, c.category_price_invoice
    FROM students s 
    LEFT JOIN categories c ON c.category_id = s.category_id AND c.category_status = 1 
    WHERE s.student_id = ? AND s.student_status = 1
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "<p class='text-center text-slate-400 py-12'>Student not found.</p>";
    return;
}

// ── Get all assessments (from structure.md) ───────────────────────
$stmt = $pdo->prepare("SELECT * FROM assessments WHERE assessment_status = 1 ORDER BY assessment_id");
$stmt->execute();
$assessmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Get current month scores ──────────────────────────────────────
$currentMonth = date('n');
$currentYear = date('Y');

$stmt = $pdo->prepare("
    SELECT sa.assessment_id, sa.student_assessment_value
    FROM student_assessments sa
    WHERE sa.student_id = ? AND sa.student_assessment_month = ? AND sa.student_assessment_year = ? AND sa.student_assessment_status = 1
");
$stmt->execute([$studentId, $currentMonth, $currentYear]);
$scores = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $scores[$row['assessment_id']] = $row['student_assessment_value'];
}

// Calculate avg score
$scoreValues = array_values($scores);
$avgScore = count($scoreValues) > 0 ? round(array_sum($scoreValues) / count($scoreValues), 1) : null;

// ── Payment Status Logic ────────────────────────────────────────────────
$currentMonthNum = (int)date('n');
$currentYearNum = (int)date('Y');
$lastMonthNum = $currentMonthNum === 1 ? 12 : $currentMonthNum - 1;
$lastMonthYear = $currentMonthNum === 1 ? $currentYearNum - 1 : $currentYearNum;

$invoiceAmount = is_numeric($student['category_price_invoice'] ?? null) ? (float)$student['category_price_invoice'] : 0;

// Get current month invoice
$stmt = $pdo->prepare("
    SELECT invoice_id, invoice_status FROM invoices 
    WHERE student_id = ? AND invoice_due_month = ? AND invoice_due_year = ?
");
$stmt->execute([$studentId, $currentMonthNum, $currentYearNum]);
$currentInvoice = $stmt->fetch(PDO::FETCH_ASSOC);

// Get total payments for current month
$currentPayments = 0;
if ($currentInvoice) {
    $stmt = $pdo->prepare("SELECT SUM(payment_value) as total FROM payments WHERE invoice_id = ? AND payment_status = 1");
    $stmt->execute([$currentInvoice['invoice_id']]);
    $currentPayments = (float)($stmt->fetchColumn() ?? 0);
}

// Get last month invoice
$stmt = $pdo->prepare("
    SELECT invoice_id, invoice_status FROM invoices 
    WHERE student_id = ? AND invoice_due_month = ? AND invoice_due_year = ?
");
$stmt->execute([$studentId, $lastMonthNum, $lastMonthYear]);
$lastInvoice = $stmt->fetch(PDO::FETCH_ASSOC);

// Get last month payment
$lastPayments = 0;
if ($lastInvoice) {
    $stmt = $pdo->prepare("SELECT SUM(payment_value) as total FROM payments WHERE invoice_id = ? AND payment_status = 1");
    $stmt->execute([$lastInvoice['invoice_id']]);
    $lastPayments = (float)($stmt->fetchColumn() ?? 0);
}

// Determine payment status
$paymentStatus = 'No Invoice';
$paymentBadgeClass = 'bg-slate-100 text-slate-500';
$paymentLabel = 'No Invoice';

if ($currentInvoice) {
    $lastMonthName = date('F', mktime(0, 0, 0, $lastMonthNum, 1));
    
    if ($invoiceAmount > 0 && $currentPayments >= $invoiceAmount) {
        $paymentStatus = 'paid';
        $paymentBadgeClass = 'bg-emerald-100 text-emerald-700';
        $paymentLabel = 'Paid';
    } elseif ($invoiceAmount > 0 && $currentPayments > 0 && $currentPayments < $invoiceAmount) {
        $paymentStatus = 'partial';
        $paymentBadgeClass = 'bg-amber-100 text-amber-700';
        $paymentLabel = 'Payment Not Completed';
    } elseif ($lastInvoice && $lastPayments < $invoiceAmount) {
        $paymentStatus = 'last_month_unpaid';
        $paymentBadgeClass = 'bg-red-100 text-red-600';
        $paymentLabel = $lastMonthName . ' Not Paid';
    } else {
        $paymentStatus = 'unpaid';
        $paymentBadgeClass = 'bg-red-100 text-red-600';
        $paymentLabel = 'Not Paid';
    }
}

// ── Determine strengths (score >= 4) ─────────────────────────────────
$strengths = [];
foreach ($assessmentsList as $a) {
    if (isset($scores[$a['assessment_id']]) && $scores[$a['assessment_id']] >= 4) {
        $strengths[] = $a['assessment_title'];
    }
}

// Age calculation
$age = $student['student_year_of_birth'] ? date('Y') - (int)$student['student_year_of_birth'] . ' years old' : '—';

// ── AI Assessment Generation ─────────────────────────────────────
$apiKey = getenv('OPENROUTER_API_KEY');

// Check if there's an existing AI assessment for this student for current month
$stmt = $pdo->prepare("
    SELECT * FROM ai_assessments 
    WHERE student_id = ? AND ai_assessment_month = ? AND ai_assessment_year = ? AND ai_assessment_status = 1
");
$stmt->execute([$studentId, $currentMonth, $currentYear]);
$existingAI = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$existingAI && !empty($scores)) {
    require_once '../../backend/ai.php';
    
    // Get all assessment scores for context
    $scoreDetails = [];
    foreach ($assessmentsList as $a) {
        if (isset($scores[$a['assessment_id']])) {
            $scoreDetails[] = $a['assessment_title'] . ": " . $scores[$a['assessment_id']];
        }
    }
    
    // Get previous month scores for trend
    $stmt = $pdo->prepare("
        SELECT sa.assessment_id, sa.student_assessment_value
        FROM student_assessments sa
        WHERE sa.student_id = ? AND sa.student_assessment_month = ? AND sa.student_assessment_year = ? AND sa.student_assessment_status = 1
    ");
    $stmt->execute([$studentId, $lastMonthNum, $lastMonthYear]);
    $prevScores = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $prevScores[$row['assessment_id']] = $row['student_assessment_value'];
    }
    
    $prevScoreDetails = [];
    foreach ($assessmentsList as $a) {
        if (isset($prevScores[$a['assessment_id']])) {
            $prevScoreDetails[] = $a['assessment_title'] . ": " . $prevScores[$a['assessment_id']];
        }
    }
    
    $prompt = "You are an educational specialist for special needs children. Analyze the following assessment data for student {$student['student_name']}.

Current Month (" . date('F Y') . ") Scores:
" . implode(", ", $scoreDetails) . "

Previous Month (" . date('F Y', strtotime("-1 month")) . ") Scores:
" . (empty($prevScoreDetails) ? "No data" : implode(", ", $prevScoreDetails)) . "

Please provide your analysis in exactly this format:
Strengths: [List the top 3 areas where the student performs well (score >= 4)]
Focus Area: [List the top 3 areas that need improvement (score <= 2.5)]
Trend Analysis: [Brief analysis of progress trends between months - positive or negative direction]

Keep each section concise and specific.";

    $result = callAI($prompt, 'openai/gpt-4o-mini', $apiKey);
    
    $aiStrengths = "No strengths identified yet.";
    $aiFocusArea = "No focus areas identified yet.";
    $aiTrendAnalysis = "No trend data available.";
    
    if ($result['success']) {
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
    
    // Store in ai_assessments table
    $stmt = $pdo->prepare("
        INSERT INTO ai_assessments (student_id, ai_assessment_strengths, ai_assessment_focus_area, ai_assessment_trend_analysis, ai_assessment_month, ai_assessment_year, ai_assessment_status, ai_assessment_created_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->execute([$studentId, $aiStrengths, $aiFocusArea, $aiTrendAnalysis, $currentMonth, $currentYear]);
    
    // Fetch the newly created record
    $stmt = $pdo->prepare("
        SELECT * FROM ai_assessments 
        WHERE student_id = ? AND ai_assessment_month = ? AND ai_assessment_year = ? AND ai_assessment_status = 1
    ");
    $stmt->execute([$studentId, $currentMonth, $currentYear]);
    $existingAI = $stmt->fetch(PDO::FETCH_ASSOC);
}

$aiAnalysis = $existingAI ?? null;

// ── Formula Calculations ─────────────────────────────────────────────────────────
// Get historical monthly averages
$monthlyAverages = getHistoricalMonthlyAverages($pdo, $studentId, 6);

// Calculate average growth (Formula 7)
$averageGrowth = calculateAverageGrowth($monthlyAverages);

// Predictive scores (Formula 7)
$predictedScore3 = predictFutureScore($avgScore, $averageGrowth, 3);
$predictedScore6 = predictFutureScore($avgScore, $averageGrowth, 6);

// Calculate trend (Formula 5)
$currentMonthIdx = 0;
$previousMonthIdx = 1;
$trend = null;
if (isset($monthlyAverages[$currentMonthIdx]['avg']) && isset($monthlyAverages[$previousMonthIdx]['avg'])) {
    $trend = calculateTrend($monthlyAverages[$currentMonthIdx]['avg'], $monthlyAverages[$previousMonthIdx]['avg']);
}

// Calculate growth rate if we have enough data (Formula 4)
$growthRate = null;
if (count($monthlyAverages) >= 2 && $monthlyAverages[count($monthlyAverages)-1]['avg'] && $monthlyAverages[0]['avg']) {
    $growthRate = calculateGrowthRate($monthlyAverages[count($monthlyAverages)-1]['avg'], $monthlyAverages[0]['avg']);
}

// Annual development index (Formula 2)
$annualIndex = calculateAnnualIndex(array_column($monthlyAverages, 'avg'));

// Weak areas (Formula 3)
$weakAreas = detectWeakAreas($scores, 2.5);
$weakAreaNames = [];
foreach ($assessmentsList as $a) {
    if (in_array($a['assessment_id'], $weakAreas)) {
        $weakAreaNames[] = $a['assessment_title'];
    }
}

$scoreInterpretation = getScoreInterpretation($avgScore);
?>

<!-- Student Profile Header -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
    <div class="flex flex-col sm:flex-row items-start gap-4">
        <div class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold shrink-0">
            <?= strtoupper(substr($student['student_name'], 0, 1)) ?>
        </div>
        <div class="flex-1">
            <h2 class="font-poppins text-2xl font-bold text-slate-800"><?= htmlspecialchars($student['student_name']) ?></h2>
            <p class="text-sm text-slate-500"><?= $age ?> · <?= htmlspecialchars($student['category_name'] ?? 'Uncategorized') ?></p>
            <p class="text-xs text-slate-400 mt-1"> Enrolled <?= $student['student_enrollment_date'] ? date('M Y', strtotime($student['student_enrollment_date'])) : '—' ?></p>
        </div>
        <a href="./" class="px-4 py-2 rounded-lg text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 self-center">← Back to Students</a>
    </div>
</div>

<?php 
$hasNotes = !empty(trim($student['student_notes'] ?? ''));
$notesButtonText = $hasNotes ? 'Edit Notes' : 'Add Notes';
?>

<!-- Parent Contact & Actions -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
    <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">Parent Contact & Actions</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-lg">👤</div>
                    <div>
                        <p class="text-xs text-slate-500">Parent Name</p>
                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($student['student_parent_name'] ?? '—') ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-lg">📧</div>
                    <div>
                        <p class="text-xs text-slate-500">Email</p>
                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($student['student_parent_email'] ?? '—') ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-lg">📱</div>
                    <div>
                        <p class="text-xs text-slate-500">Phone</p>
                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($student['student_parent_number'] ?? '—') ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="grid grid-cols-2 gap-3">
                <a href="edit.php?id=<?= (int)$studentId ?>" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors">
                    <span>✏️</span> Edit Student
                </a>
                <?php if (!empty($scores)): ?>
                <a href="../../teachers/assessments/edit.php?student_id=<?= (int)$studentId ?>&month=<?= $currentMonth ?>&year=<?= $currentYear ?>" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                    <span>✏️</span> Edit Assessment
                </a>
                <?php else: ?>
                <a href="../../teachers/assessments/add.php?student_id=<?= (int)$studentId ?>" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors">
                    <span>📝</span> Add Assessment
                </a>
                <?php endif; ?>
                <button onclick="openNotesModal()" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium <?= $hasNotes ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-slate-50 text-slate-700 hover:bg-slate-100' ?> transition-colors">
                    <span>📋</span> <?= $notesButtonText ?>
                </button>
                <button class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-slate-50 text-slate-700 hover:bg-slate-100 transition-colors">
                    <span>📅</span> Add To Activity
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <!-- Development Score -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-xl">📊</div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Development Score</p>
        </div>
        <?php if ($avgScore): ?>
        <p class="font-poppins text-3xl font-bold text-slate-800">
            <?= $avgScore ?><span class="text-lg text-slate-400">/5</span>
        </p>
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold <?= $scoreInterpretation['class'] ?> mt-1">
            <?= $scoreInterpretation['emoji'] ?> <?= $scoreInterpretation['label'] ?>
        </span>
        <p class="text-xs text-slate-400 mt-1">Current Month (<?= date('F Y') ?>)</p>
        <?php else: ?>
        <p class="font-poppins text-2xl text-slate-400">—</p>
        <p class="text-xs text-slate-400 mt-1">No assessments yet</p>
        <?php endif; ?>
    </div>

    <!-- Annual Development Index -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-xl">📈</div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Annual Index</p>
        </div>
        <?php if ($annualIndex): ?>
        <p class="font-poppins text-3xl font-bold text-slate-800">
            <?= $annualIndex ?><span class="text-lg text-slate-400">/5</span>
        </p>
        <p class="text-xs text-slate-400 mt-1">Avg over 6 months</p>
        <?php else: ?>
        <p class="font-poppins text-2xl text-slate-400">—</p>
        <p class="text-xs text-slate-400 mt-1">Insufficient data</p>
        <?php endif; ?>
    </div>

    <!-- Invoice Status -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-xl">💳</div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Payment Status</p>
        </div>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold <?= $paymentBadgeClass ?>">
            <?= $paymentLabel ?>
        </span>
        <?php if ($invoiceAmount > 0): ?>
        <p class="text-xs text-slate-400 mt-2">Amount: RM<?= number_format($invoiceAmount, 2) ?></p>
        <?php endif; ?>
    </div>

    <!-- Weak Areas -->
    <div class="bg-white rounded-2xl border <?= empty($weakAreaNames) ? 'border-slate-200' : 'border-red-200' ?> shadow-sm p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl <?= empty($weakAreaNames) ? 'bg-slate-100' : 'bg-red-50' ?> flex items-center justify-center text-xl">
                <?= empty($weakAreaNames) ? '✅' : '⚠️' ?>
            </div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Needs Attention</p>
        </div>
        <?php if (!empty($weakAreaNames)): ?>
        <div class="flex flex-wrap gap-1.5">
            <?php foreach (array_slice($weakAreaNames, 3) as $w): ?>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                <?= htmlspecialchars($w) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <p class="text-xs text-red-500 mt-1">Score below 2.5</p>
        <?php else: ?>
        <p class="text-sm text-emerald-600">All areas healthy</p>
        <p class="text-xs text-slate-400 mt-1">No weak areas detected</p>
        <?php endif; ?>
    </div>
</div>

<!-- Development Profile - Radar Chart -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
    <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">Development Profile</h3>
    <div class="flex flex-col lg:flex-row items-center gap-8">
        <div class="w-full max-w-md">
            <canvas id="radarChart"></canvas>
        </div>
        
        <!-- AI Development Analysis -->
        <div class="flex-1 space-y-4">
            <?php if ($aiAnalysis): ?>
            <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                <h4 class="text-sm font-semibold text-emerald-800 mb-2">Identified Strengths (AI)</h4>
                <p class="text-sm text-emerald-700">
                    <?= htmlspecialchars($aiAnalysis['ai_assessment_strengths']) ?>
                </p>
            </div>
            <div class="p-4 bg-amber-50 rounded-xl border border-amber-100">
                <h4 class="text-sm font-semibold text-amber-800 mb-2">Development Focus Areas (AI)</h4>
                <p class="text-sm text-amber-700">
                    <?= htmlspecialchars($aiAnalysis['ai_assessment_focus_area']) ?>
                </p>
            </div>
            <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                <h4 class="text-sm font-semibold text-blue-800 mb-2">Trend Analysis (AI)</h4>
                <p class="text-sm text-blue-700">
                    <?= htmlspecialchars($aiAnalysis['ai_assessment_trend_analysis']) ?>
                </p>
            </div>
            <?php else: ?>
            <div class="p-4 bg-slate-50 rounded-xl">
                <h4 class="text-sm font-semibold text-slate-700 mb-2">AI Analysis</h4>
                <p class="text-sm text-slate-500">
                    No AI assessment available. Please add student assessments to generate AI analysis.
                </p>
            </div>
            <?php endif; ?>
            <div class="p-4 bg-slate-50 rounded-xl">
                <h4 class="text-sm font-semibold text-slate-700 mb-2">Additional Notes (Teacher)</h4>
                <?php if ($hasNotes): ?>
                <p class="text-sm text-slate-600 whitespace-pre-line"><?= htmlspecialchars($student['student_notes']) ?></p>
                <?php else: ?>
                <p class="text-sm text-slate-400 italic">No notes yet. Click "Add Notes" to add notes about this student.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Predictive Development Analytics -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
    <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">Predictive Development Analytics</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-4 bg-slate-50 rounded-xl">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-1">Predicted Score (3 months)</p>
            <p class="font-poppins text-2xl font-bold text-slate-800"><?= $predictedScore3 ?? '—' ?><span class="text-sm text-slate-400">/5</span></p>
            <?php if ($averageGrowth !== null): ?>
            <p class="text-xs <?= $averageGrowth >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                <?= $averageGrowth >= 0 ? '+' : '' ?><?= $averageGrowth ?> avg monthly growth
            </p>
            <?php else: ?>
            <p class="text-xs text-slate-400">Insufficient data</p>
            <?php endif; ?>
        </div>
        <div class="p-4 bg-slate-50 rounded-xl">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-1">Predicted Score (6 months)</p>
            <p class="font-poppins text-2xl font-bold text-slate-800"><?= $predictedScore6 ?? '—' ?><span class="text-sm text-slate-400">/5</span></p>
            <?php if ($growthRate !== null): ?>
            <p class="text-xs <?= $growthRate >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                <?= $growthRate >= 0 ? '+' : '' ?><?= $growthRate ?>% annual growth
            </p>
            <?php else: ?>
            <p class="text-xs text-slate-400">Need more data</p>
            <?php endif; ?>
        </div>
        <div class="p-4 bg-slate-50 rounded-xl">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-1">Development Trend</p>
            <?php if ($trend): ?>
            <p class="font-poppins text-2xl font-bold <?= $trend['class'] ?>"><?= $trend['icon'] ?> <?= $trend['label'] ?></p>
            <?php else: ?>
            <p class="font-poppins text-2xl font-bold text-slate-400">—</p>
            <?php endif; ?>
            <p class="text-xs text-slate-400">Based on monthly data</p>
        </div>
    </div>
</div>

<!-- Assessment History Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <h3 class="font-poppins text-lg font-semibold text-slate-800">Assessment History</h3>
        <select id="historyMonthSelect" onchange="filterHistory()" class="px-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-400">
            <option value="">All Months</option>
            <?php
            for ($i = 0; $i < 12; $i++) {
                $m = date('n', strtotime("-$i months"));
                $y = date('Y', strtotime("-$i months"));
                $label = date('F Y', strtotime("-$i months"));
                echo '<option value="'.strtolower($label).'">'.$label.'</option>';
            }
            ?>
        </select>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full" id="historyTable">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Month Year</th>
                    <?php foreach ($assessmentsList as $a): ?>
                    <th class="px-2 py-2 text-xs font-semibold text-slate-500 uppercase text-center"><?= htmlspecialchars($a['assessment_icon']) ?></th>
                    <?php endforeach; ?>
                    <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase text-center">Average</th>
                    <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase text-center">Edit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="historyTableBody">
                <?php
                for ($i = 0; $i < 12; $i++) {
                    $m = date('n', strtotime("-$i months"));
                    $y = date('Y', strtotime("-$i months"));
                    $label = date('F Y', strtotime("-$i months"));
                    
                    $stmt = $pdo->prepare("
                        SELECT assessment_id, student_assessment_value 
                        FROM student_assessments 
                        WHERE student_id = ? AND student_assessment_month = ? AND student_assessment_year = ? AND student_assessment_status = 1
                    ");
                    $stmt->execute([$studentId, $m, $y]);
                    $monthScores = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $monthScores[$row['assessment_id']] = $row['student_assessment_value'];
                    }
                    $monthAvg = count($monthScores) > 0 ? round(array_sum($monthScores) / count($monthScores), 1) : null;
                ?>
                <tr class="history-row" data-month="<?= strtolower($label) ?>">
                    <td class="px-3 py-3 text-sm font-medium text-slate-700"><?= $label ?></td>
                    <?php foreach ($assessmentsList as $a): ?>
                    <?php $s = $monthScores[$a['assessment_id']] ?? null; ?>
                    <td class="px-2 py-3 text-center text-sm <?= !$s ? 'text-slate-400' : '' ?>"><?= $s ?? '—' ?></td>
                    <?php endforeach; ?>
                    <td class="px-3 py-3 text-center text-sm font-semibold <?= $monthAvg ? 'text-indigo-600' : 'text-slate-400' ?>"><?= $monthAvg ?? '—' ?></td>
                    <td class="px-3 py-3 text-center">
                        <a href="../../teachers/assessments/edit.php?student_id=<?= (int)$studentId ?>&month=<?= $m ?>&year=<?= $y ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterHistory() {
    const query = document.getElementById('historyMonthSelect').value.toLowerCase();
    document.querySelectorAll('.history-row').forEach(function(row) {
        const month = row.getAttribute('data-month') || '';
        row.style.display = (!query || month.includes(query)) ? '' : 'none';
    });
}

function selectScore(assessmentId, value) {
    const container = document.querySelector('[data-assessment="' + assessmentId + '"]').closest('.flex');
    container.querySelectorAll('.score-btn').forEach(function(btn) {
        btn.style.backgroundColor = '';
        btn.style.color = '';
        btn.style.borderColor = '';
    });
    const selectedBtn = container.querySelector('[data-value="' + value + '"]');
    selectedBtn.style.backgroundColor = '#3b82f6';
    selectedBtn.style.color = 'white';
    selectedBtn.style.borderColor = '#3b82f6';
    document.getElementById('score_' + assessmentId).value = value;
}
</script>

<?php
// Handle save assessment (now handled in add.php)
?>

<!-- Radar Chart Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('radarChart').getContext('2d');
const labels = <?= json_encode(array_column($assessmentsList, 'assessment_title')) ?>;
const data = <?php 
$scoreData = [];
foreach ($assessmentsList as $a) { $scoreData[] = $scores[$a['assessment_id']] ?? 0; }
echo json_encode($scoreData);
?>;

new Chart(ctx, {
    type: 'radar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Current Score',
            data: data,
            backgroundColor: 'rgba(99, 102, 241, 0.2)',
            borderColor: 'rgba(99, 102, 241, 1)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(99, 102, 241, 1)'
        }]
    },
    options: {
        scales: {
            r: {
                min: 0,
                max: 5,
                ticks: { stepSize: 1 },
                pointLabels: { font: { size: 11 } }
            }
        },
        plugins: { legend: { display: false } }
    }
});
</script>

<div id="editAssessmentModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-1">Edit Assessment</h3>
        <p class="text-sm text-slate-500 mb-4" id="editModalTitle"></p>
        <form method="post" id="editAssessmentForm">
            <input type="hidden" name="student_id" value="<?= (int)$studentId ?>">
            <input type="hidden" name="edit_assessment_month" id="editMonth">
            <input type="hidden" name="edit_assessment_year" id="editYear">
            <div id="editScoresContainer" class="space-y-3 mb-4"></div>
            <div class="flex gap-3">
                <button type="submit" name="update_assessment" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-4 py-2.5 rounded-xl transition-colors">Save Changes</button>
                <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(month, year, label) {
    document.getElementById('editMonth').value = month;
    document.getElementById('editYear').value = year;
    document.getElementById('editModalTitle').textContent = label;
    
    const container = document.getElementById('editScoresContainer');
    container.innerHTML = '';
    
    const assessments = <?= json_encode(array_map(function($a) { return ['id' => $a['assessment_id'], 'title' => $a['assessment_title'], 'icon' => $a['assessment_icon']]; }, $assessmentsList)) ?>;
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'get_past_scores=1&student_id=<?= (int)$studentId ?>&month=' + month + '&year=' + year
    })
    .then(response => response.json())
    .then(scores => {
        assessments.forEach(function(a) {
            const value = scores[a.id] || '';
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-3 bg-slate-50 rounded-lg';
            div.innerHTML = '<div class="flex items-center gap-2"><span class="text-lg">' + a.icon + '</span><span class="text-sm font-medium text-slate-700">' + a.title + '</span></div><div class="flex gap-1">';
            for (var i = 1; i <= 5; i++) {
                div.innerHTML += '<button type="button" onclick="selectEditScore(' + a.id + ', ' + i + ')" class="edit-score-btn w-8 h-8 rounded-lg text-sm font-medium border ' + (value == i ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-400') + '" data-assessment="' + a.id + '" data-value="' + i + '">' + i + '</button>';
            }
            div.innerHTML += '<input type="hidden" name="edit_scores[' + a.id + ']" id="edit_score_' + a.id + '" value="' + value + '"></div></div>';
            container.appendChild(div);
        });
    });
    
    document.getElementById('editAssessmentModal').classList.remove('hidden');
    document.getElementById('editAssessmentModal').classList.add('flex');
}

function selectEditScore(assessmentId, value) {
    document.querySelectorAll('.edit-score-btn[data-assessment="' + assessmentId + '"]').forEach(function(btn) {
        btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
        btn.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
    });
    var selectedBtn = document.querySelector('.edit-score-btn[data-assessment="' + assessmentId + '"][data-value="' + value + '"]');
    selectedBtn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
    selectedBtn.classList.remove('bg-white', 'text-slate-600', 'border-slate-200');
    document.getElementById('edit_score_' + assessmentId).value = value;
}

function closeEditModal() {
    document.getElementById('editAssessmentModal').classList.add('hidden');
    document.getElementById('editAssessmentModal').classList.remove('flex');
}

document.getElementById('editAssessmentModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_past_scores'])) {
    $sid = $_POST['student_id'] ?? null;
    $month = $_POST['month'] ?? null;
    $year = $_POST['year'] ?? null;
    
    if ($sid && $month && $year) {
        $stmt = $pdo->prepare("SELECT assessment_id, student_assessment_value FROM student_assessments WHERE student_id = ? AND student_assessment_month = ? AND student_assessment_year = ? AND student_assessment_status = 1");
        $stmt->execute([$sid, $month, $year]);
        $scores = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $scores[$row['assessment_id']] = $row['student_assessment_value'];
        }
        header('Content-Type: application/json');
        echo json_encode($scores);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assessment'])) {
    $sid = $_POST['student_id'] ?? null;
    $month = $_POST['edit_assessment_month'] ?? null;
    $year = $_POST['edit_assessment_year'] ?? null;
    $scoresToSave = $_POST['edit_scores'] ?? [];
    
    if ($sid && $month && $year) {
        $stmt = $pdo->prepare("DELETE FROM student_assessments WHERE student_id = ? AND student_assessment_month = ? AND student_assessment_year = ?");
        $stmt->execute([$sid, $month, $year]);
        
        $stmt = $pdo->prepare("INSERT INTO student_assessments (student_id, assessment_id, student_assessment_value, student_assessment_month, student_assessment_year, student_assessment_status, student_assessment_created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        foreach ($scoresToSave as $aid => $value) {
            if ($value) {
                $stmt->execute([$sid, $aid, $value, $month, $year]);
            }
        }
        
        echo "<script>alert('Assessment updated!'); window.location.href = '?id=$sid';</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    $sid = $_POST['student_id'] ?? null;
    $student_notes = trim($_POST['student_notes'] ?? '');
    
    if ($sid) {
        $stmt = $pdo->prepare("UPDATE students SET student_notes = ?, student_updated_at = NOW() WHERE student_id = ?");
        $stmt->execute([$student_notes, $sid]);
        
        echo "<script>alert('Notes saved!'); window.location.href = '?id=$sid';</script>";
    }
}
?>

<!-- Notes Modal -->
<div id="notesModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6">
        <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-1"><?= $hasNotes ? 'Edit Notes' : 'Add Notes' ?></h3>
        <p class="text-sm text-slate-500 mb-4">Add notes about this student</p>
        <form method="post">
            <input type="hidden" name="student_id" value="<?= (int)$studentId ?>">
            <input type="hidden" name="save_notes" value="1">
            <textarea name="student_notes" rows="6" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter notes about this student..."><?= htmlspecialchars($student['student_notes'] ?? '') ?></textarea>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-4 py-2.5 rounded-xl transition-colors">Save Notes</button>
                <button type="button" onclick="closeNotesModal()" class="px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNotesModal() {
    document.getElementById('notesModal').classList.remove('hidden');
    document.getElementById('notesModal').classList.add('flex');
}

function closeNotesModal() {
    document.getElementById('notesModal').classList.add('hidden');
    document.getElementById('notesModal').classList.remove('flex');
}

document.getElementById('notesModal').addEventListener('click', function(e) {
    if (e.target === this) closeNotesModal();
});
</script>