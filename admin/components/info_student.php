<?php
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

$stmt = $pdo->prepare("SELECT * FROM assessments WHERE assessment_status = 1 ORDER BY assessment_id");
$stmt->execute();
$assessmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

$scoreValues = array_values($scores);
$avgScore = count($scoreValues) > 0 ? round(array_sum($scoreValues) / count($scoreValues), 1) : null;

$currentMonthNum = (int)date('n');
$currentYearNum = (int)date('Y');
$lastMonthNum = $currentMonthNum === 1 ? 12 : $currentMonthNum - 1;
$lastMonthYear = $currentMonthNum === 1 ? $currentYearNum - 1 : $currentYearNum;

$invoiceAmount = is_numeric($student['category_price_invoice'] ?? null) ? (float)$student['category_price_invoice'] : 0;

$stmt = $pdo->prepare("
    SELECT invoice_id, invoice_status FROM invoices 
    WHERE student_id = ? AND invoice_due_month = ? AND invoice_due_year = ?
");
$stmt->execute([$studentId, $currentMonthNum, $currentYearNum]);
$currentInvoice = $stmt->fetch(PDO::FETCH_ASSOC);

$currentPayments = 0;
if ($currentInvoice) {
    $stmt = $pdo->prepare("SELECT SUM(payment_value) as total FROM payments WHERE invoice_id = ? AND payment_status = 1");
    $stmt->execute([$currentInvoice['invoice_id']]);
    $currentPayments = (float)($stmt->fetchColumn() ?? 0);
}

$stmt = $pdo->prepare("
    SELECT invoice_id, invoice_status FROM invoices 
    WHERE student_id = ? AND invoice_due_month = ? AND invoice_due_year = ?
");
$stmt->execute([$studentId, $lastMonthNum, $lastMonthYear]);
$lastInvoice = $stmt->fetch(PDO::FETCH_ASSOC);

$lastPayments = 0;
if ($lastInvoice) {
    $stmt = $pdo->prepare("SELECT SUM(payment_value) as total FROM payments WHERE invoice_id = ? AND payment_status = 1");
    $stmt->execute([$lastInvoice['invoice_id']]);
    $lastPayments = (float)($stmt->fetchColumn() ?? 0);
}

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

$strengths = [];
foreach ($assessmentsList as $a) {
    if (isset($scores[$a['assessment_id']]) && $scores[$a['assessment_id']] >= 4) {
        $strengths[] = $a['assessment_title'];
    }
}

$age = $student['student_year_of_birth'] ? date('Y') - (int)$student['student_year_of_birth'] . ' years old' : '—';

$hasNotes = !empty(trim($student['student_notes'] ?? ''));
$notesButtonText = $hasNotes ? 'Edit Notes' : 'Add Notes';
?>

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
                <a href="<?php echo $location_index?>/admin/students/edit.php?id=<?= (int)$studentId ?>" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors">
                    <span>✏️</span> Edit Student
                </a>
                <a href="<?php echo $location_index?>/admin/pdf/student_report.php?id=<?= (int)$studentId ?>" target="_blank" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                    <span>📄</span> Download Report
                </a>
                <a href="<?php echo $location_index?>/teachers/assessments/add.php?student_id=<?= (int)$studentId ?>" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors">
                    <span>📝</span> Add Assessment
                </a>
                <button onclick="openNotesModal()" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium <?= $hasNotes ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-slate-50 text-slate-700 hover:bg-slate-100' ?> transition-colors">
                    <span>📋</span> <?= $notesButtonText ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-xl">📊</div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Development Score</p>
        </div>
        <?php if ($avgScore): ?>
        <p class="font-poppins text-3xl font-bold text-slate-800">
            <?= $avgScore ?><span class="text-lg text-slate-400">/5</span>
        </p>
        <p class="text-xs text-slate-400 mt-1">Current Month (<?= date('F Y') ?>)</p>
        <?php else: ?>
        <p class="font-poppins text-2xl text-slate-400">—</p>
        <p class="text-xs text-slate-400 mt-1">No assessments yet</p>
        <?php endif; ?>
    </div>

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

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-growth-50 flex items-center justify-center text-xl">⭐</div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Strengths</p>
        </div>
        <?php if (!empty($strengths)): ?>
        <div class="flex flex-wrap gap-1.5">
            <?php foreach ($strengths as $s): ?>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                <?= htmlspecialchars($s) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-slate-400">No strengths identified yet</p>
        <?php endif; ?>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
    <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">Development Profile</h3>
    <div class="flex flex-col lg:flex-row items-center gap-8">
        <div class="w-full max-w-md">
            <canvas id="radarChart"></canvas>
        </div>
        
        <div class="flex-1 space-y-4">
            <div class="p-4 bg-slate-50 rounded-xl">
                <h4 class="text-sm font-semibold text-slate-700 mb-2">Identified Strengths (AI)</h4>
                <p class="text-sm text-slate-600">
                    Strong performance in Communication Skill, Social Interaction, and Eye Contact. 
                    These are key developmental strengths for <?= htmlspecialchars($student['student_name']) ?>.
                </p>
            </div>
            <div class="p-4 bg-slate-50 rounded-xl">
                <h4 class="text-sm font-semibold text-slate-700 mb-2">Development Focus Areas (AI)</h4>
                <p class="text-sm text-slate-600">
                    Attention and Focus, Emotional Regulation, and Motor Coordination. 
                    These areas require targeted intervention to support overall development.
                </p>
            </div>
            <div class="p-4 bg-slate-50 rounded-xl">
                <h4 class="text-sm font-semibold text-slate-700 mb-2">Trend Analysis (AI)</h4>
                <p class="text-sm text-slate-600">
                    <?= htmlspecialchars($student['student_name']) ?> has shown a positive trend in Communication Skill, 
                    with a 15% improvement over the last quarter. However, there is a slight decline in Motor Coordination.
                </p>
            </div>
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

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
    <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">Predictive Development Analytics</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-4 bg-slate-50 rounded-xl">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-1">Predicted Score (3 months)</p>
            <p class="font-poppins text-2xl font-bold text-slate-800">3.4</p>
            <p class="text-xs text-emerald-600">Expected +0.00 improvement</p>
        </div>
        <div class="p-4 bg-slate-50 rounded-xl">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-1">Predicted Score (6 months)</p>
            <p class="font-poppins text-2xl font-bold text-slate-800">3.4</p>
            <p class="text-xs text-emerald-600">Projected +0.00 growth</p>
        </div>
        <div class="p-4 bg-slate-50 rounded-xl">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-1">Development Trajectory</p>
            <p class="font-poppins text-2xl font-bold text-slate-800">➡️ Stable</p>
            <p class="text-xs text-slate-500">Maintaining current level</p>
        </div>
    </div>
</div>

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
                        <button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">Record Assessment (<?= date('F Y') ?>)</h3>
    <form method="post" class="space-y-4">
        <input type="hidden" name="student_id" value="<?= (int)$studentId ?>">
        <input type="hidden" name="assessment_month" value="<?= $currentMonth ?>">
        <input type="hidden" name="assessment_year" value="<?= $currentYear ?>">

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($assessmentsList as $a): ?>
            <div class="p-4 bg-slate-50 rounded-xl">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-lg"><?= htmlspecialchars($a['assessment_icon']) ?></span>
                    <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($a['assessment_title']) ?></p>
                </div>
                <div class="flex gap-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" 
                        onclick="selectScore(<?= (int)$a['assessment_id'] ?>, <?= $i ?>)"
                        data-assessment="<?= (int)$a['assessment_id'] ?>"
                        data-value="<?= $i ?>"
                        class="score-btn flex-1 min-w-[40px] text-center py-2.5 sm:py-1.5 px-1 sm:px-0.5 rounded-lg text-sm sm:text-xs font-medium bg-white border border-slate-200 text-slate-600 transition-colors"
                        <?= isset($scores[$a['assessment_id']]) && $scores[$a['assessment_id']] == $i ? 'style="background-color: #3b82f6 !important; color: white !important; border-color: #3b82f6 !important;"' : '' ?>>
                        <?= $i ?>
                    </button>
                    <?php endfor; ?>
                    <input type="hidden" name="scores[<?= (int)$a['assessment_id'] ?>]" id="score_<?= (int)$a['assessment_id'] ?>" value="<?= $scores[$a['assessment_id']] ?? '' ?>">
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" name="save_assessment" class="bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-6 py-3 rounded-xl transition-colors">
                Save Assessment
            </button>
        </div>
    </form>
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assessment'])) {
    $sid = $_POST['student_id'] ?? null;
    $month = $_POST['assessment_month'] ?? null;
    $year = $_POST['assessment_year'] ?? null;
    $scoresToSave = $_POST['scores'] ?? [];

    if ($sid && $month && $year && !empty($scoresToSave)) {
        $stmt = $pdo->prepare("DELETE FROM student_assessments WHERE student_id = ? AND student_assessment_month = ? AND student_assessment_year = ?");
        $stmt->execute([$sid, $month, $year]);

        $stmt = $pdo->prepare("INSERT INTO student_assessments (student_id, assessment_id, student_assessment_value, student_assessment_month, student_assessment_year, student_assessment_status, student_assessment_created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        foreach ($scoresToSave as $aid => $value) {
            if ($value) {
                $stmt->execute([$sid, $aid, $value, $month, $year]);
            }
        }

        echo "<script>alert('Assessment saved!'); window.location.href = '?id=$sid';</script>";
    }
}
?>

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

<?php
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