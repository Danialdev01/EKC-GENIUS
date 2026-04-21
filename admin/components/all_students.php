<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_student'])) {
        $student_name = trim($_POST['student_name'] ?? '');
        $student_ic = trim($_POST['student_ic'] ?? '');
        $student_year_of_birth = $_POST['student_year_of_birth'] ?? null;
        $category_id = $_POST['category_id'] ?? null;
        $student_parent_name = trim($_POST['student_parent_name'] ?? '');
        $student_parent_email = trim($_POST['student_parent_email'] ?? '');
        $student_parent_number = trim($_POST['student_parent_number'] ?? '');
        $student_notes = trim($_POST['student_notes'] ?? '');
        
        $student_ic = str_replace('-', '', $student_ic);
        
        if (!empty($student_name)) {
            if (isset($_POST['student_id']) && !empty($_POST['student_id'])) {
                $stmt = $pdo->prepare("UPDATE students SET student_name = ?, student_ic = ?, student_year_of_birth = ?, category_id = ?, student_parent_name = ?, student_parent_email = ?, student_parent_number = ?, student_notes = ?, student_updated_at = NOW() WHERE student_id = ?");
                $stmt->execute([$student_name, $student_ic, $student_year_of_birth, $category_id, $student_parent_name, $student_parent_email, $student_parent_number, $student_notes, $_POST['student_id']]);
                $message = 'Student updated successfully!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO students (student_name, student_ic, student_year_of_birth, category_id, student_parent_name, student_parent_email, student_parent_number, student_notes, student_status, student_created_at, student_updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
                $stmt->execute([$student_name, $student_ic, $student_year_of_birth, $category_id, $student_parent_name, $student_parent_email, $student_parent_number, $student_notes]);
                $message = 'Student added successfully!';
            }
        }
    }
    
    if (isset($_POST['delete_student'])) {
        $student_id = $_POST['student_id'] ?? 0;
        if ($student_id) {
            $stmt = $pdo->prepare("UPDATE students SET student_status = 0 WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $message = 'Student removed successfully!';
        }
    }
}

$stmt = $pdo->query("SELECT category_id, category_name FROM categories WHERE category_status = 1 ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE student_status = 1");
$totalStudents = $stmt->fetchColumn();
$totalPages = ceil($totalStudents / $perPage);

$stmt = $pdo->prepare("
    SELECT student_id, student_name, student_ic, student_year_of_birth, category_id
    FROM students 
    WHERE student_status = 1 
    ORDER BY student_name ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getCategoryName($pdo, $categoryId) {
    if (!$categoryId) return '—';
    $stmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ? AND category_status = 1");
    $stmt->execute([$categoryId]);
    return $stmt->fetchColumn() ?: '—';
}

function getAvgScore($pdo, $studentId, $month, $year) {
    $stmt = $pdo->prepare("
        SELECT ROUND(AVG(student_assessment_value), 2) 
        FROM student_assessments 
        WHERE student_id = ? AND student_assessment_status = 1 AND student_assessment_month = ? AND student_assessment_year = ?
    ");
    $stmt->execute([$studentId, $month, $year]);
    return $stmt->fetchColumn();
}

function getInvoiceStatus($pdo, $studentId) {
    $currentMonthNum = (int)date('n');
    $currentYearNum = (int)date('Y');
    $lastMonthNum = $currentMonthNum === 1 ? 12 : $currentMonthNum - 1;
    $lastMonthYear = $currentMonthNum === 1 ? $currentYearNum - 1 : $currentYearNum;

    $stmt = $pdo->prepare("SELECT category_id FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $categoryId = (int)($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT category_price_invoice FROM categories WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $invoiceAmount = (float)($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT category_price_invoice FROM categories WHERE category_id = (SELECT category_id FROM students WHERE student_id = ?)");
    $stmt->execute([$studentId]);
    $categoryPrice = (float)($stmt->fetchColumn() ?? 0);
    if ($categoryPrice > 0) {
        $invoiceAmount = $categoryPrice;
    }

    if ($invoiceAmount <= 0) {
        return 'No Invoice';
    }

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

    if ($currentInvoice && $currentPayments >= $invoiceAmount) {
        return 'paid';
    } elseif ($currentInvoice && $currentPayments > 0 && $currentPayments < $invoiceAmount) {
        return 'partial';
    } elseif ($lastInvoice && $lastPayments < $invoiceAmount) {
        return 'unpaid';
    } elseif ($currentInvoice) {
        return 'unpaid';
    } else {
        return 'No Invoice';
    }
}

$currentMonth = date('n');
$currentYear = date('Y');

foreach ($students as &$student) {
    $student['category_name'] = getCategoryName($pdo, $student['category_id'] ?? null);
    $student['avg_score'] = getAvgScore($pdo, $student['student_id'], $currentMonth, $currentYear);
    $student['invoice_status'] = getInvoiceStatus($pdo, $student['student_id']);
}
unset($student);
?>

<?php if (isset($message)): ?>
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

    <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="font-poppins text-lg font-semibold text-slate-800">Student Overview</h2>
            <p class="text-xs text-slate-400 mt-0.5">Showing <?= count($students) ?> active students · <?= date('F Y') ?></p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative w-full sm:w-72">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input
                    id="studentSearch"
                    type="text"
                    placeholder="Search student…"
                    class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 transition-all"
                >
            </div>
            <a href="<?= $location_index ?>/admin/students/add.php" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-inter text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Student
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full" id="studentTable">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Student</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">IC Number</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Age</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Category</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Avg Score</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Invoice</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="studentTableBody">
                <?php if (empty($students)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-sm">No active students enrolled yet.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($students as $student):
                    $avgScore   = $student['avg_score'] !== null ? (float)$student['avg_score'] : null;
                    $age        = $student['student_year_of_birth']
                                    ? (date('Y') - (int)$student['student_year_of_birth']) . ' yrs'
                                    : '—';
                    $category   = $student['category_name'] ?? '—';
                    $invoiceStatus = $student['invoice_status'] ?? null;

                    if ($avgScore === null) {
                        $scoreBadge  = 'bg-slate-100 text-slate-500';
                        $scoreLabel = '—';
                    } elseif ($avgScore < 2.5) {
                        $scoreBadge  = 'bg-red-100 text-red-600';
                        $scoreLabel = number_format($avgScore, 1);
                    } elseif ($avgScore < 3.5) {
                        $scoreBadge  = 'bg-amber-100 text-amber-700';
                        $scoreLabel = number_format($avgScore, 1);
                    } else {
                        $scoreBadge  = 'bg-emerald-100 text-emerald-700';
                        $scoreLabel = number_format($avgScore, 1);
                    }

                    if ($invoiceStatus === 'paid') {
                        $invoiceBadge = 'bg-emerald-100 text-emerald-700';
                        $invoiceLabel = 'Paid';
                    } elseif ($invoiceStatus === 'partial') {
                        $invoiceBadge = 'bg-amber-100 text-amber-700';
                        $invoiceLabel = 'Partial';
                    } elseif ($invoiceStatus === 'unpaid') {
                        $invoiceBadge = 'bg-red-100 text-red-600';
                        $invoiceLabel = 'Unpaid';
                    } elseif ($invoiceStatus === 'overdue') {
                        $invoiceBadge = 'bg-amber-100 text-amber-700';
                        $invoiceLabel = 'Overdue';
                    } else {
                        $invoiceBadge = 'bg-slate-100 text-slate-500';
                        $invoiceLabel = 'No Invoice';
                    }
                ?>
                <tr class="student-row hover:bg-slate-50 transition-colors" data-name="<?= strtolower(htmlspecialchars($student['student_name'])) ?>">
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
                    <td class="px-4 py-4 text-sm text-slate-600"><?= htmlspecialchars($student['student_ic'] ?? '—') ?></td>
                    <td class="px-4 py-4 text-sm text-slate-600"><?= $age ?></td>
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                            <?= htmlspecialchars($category) ?>
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        <?php if ($avgScore === null): ?>
                        <a href="<?php echo $location_index?>/admin/students/?id=<?= (int)$student['student_id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">
                            Not Assessed Yet
                        </a>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $scoreBadge ?>">
                            <?= $scoreLabel ?>
                            <span class="ml-0.5 opacity-60">/5</span>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $invoiceBadge ?>">
                            <?= $invoiceLabel ?>
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-1">
                            <a href="<?php echo $location_index?>/admin/students/?id=<?= (int)$student['student_id'] ?>" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="<?= $location_index ?>/admin/students/edit.php?id=<?= (int)$student['student_id'] ?>" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-400">
            Showing <?= ($offset + 1) ?>-<?= min($offset + count($students), $totalStudents) ?> of <?= $totalStudents ?> students
        </p>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between">
        <div class="text-sm text-slate-500">
            Page <?= $page ?> of <?= $totalPages ?>
        </div>
        <div class="flex items-center gap-1">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Previous</a>
            <?php endif; ?>
            
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <a href="?page=<?= $p ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $p === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 hover:bg-slate-50' ?>"><?= $p ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var searchInput = document.getElementById('studentSearch');
    var rows = document.querySelectorAll('.student-row');
    var visibleCount = document.getElementById('visibleCount');

    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        var query = this.value.trim().toLowerCase();
        var count = 0;

        rows.forEach(function (row) {
            var name = row.getAttribute('data-name') || '';
            var match = name.includes(query);
            row.style.display = match ? '' : 'none';
            if (match) count++;
        });

        if (visibleCount) {
            visibleCount.textContent = count;
        }
    });
})();
</script>