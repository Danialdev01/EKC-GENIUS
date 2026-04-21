<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';
require_once __DIR__ . '/../../backend/auth.php';
$location_index = "../..";
$authUser = requireAuth('admin');
$pageTitle = 'Payments';
$cssDepth = '../../public/css';
?>
<?php include '../../components/teacher/header.php'; ?>
<?php include '../../components/sidebar.php'; ?>

<?php
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get categories
$stmt = $pdo->query("SELECT category_id, category_name FROM categories WHERE category_status = 1 ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build WHERE clause
$whereConditions = ["s.student_status = 1"];
$params = [];

if ($selectedMonth && $selectedYear) {
    $enrollmentCutoff = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
    $whereConditions[] = "s.student_enrollment_date <= ?";
    $params[] = $enrollmentCutoff;
}

if (!empty($searchQuery)) {
    $whereConditions[] = "s.student_name LIKE ?";
    $params[] = "%$searchQuery%";
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$countQuery = "SELECT COUNT(*) FROM students s WHERE $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalStudents = $stmt->fetchColumn();
$totalPages = ceil($totalStudents / $perPage);

// Get students with pagination
$query = "
    SELECT s.student_id, s.student_name, s.student_parent_name, s.student_parent_email, s.student_parent_number, s.student_enrollment_date, c.category_id, c.category_name, c.category_price_invoice
    FROM students s
    LEFT JOIN categories c ON c.category_id = s.category_id AND c.category_status = 1
    WHERE $whereClause
    ORDER BY s.student_name ASC
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get invoice and payment data for each student
$invoiceAmounts = [];
$paymentData = [];

foreach ($students as &$student) {
    $sid = $student['student_id'];
    
    $stmt = $pdo->prepare("SELECT invoice_id, invoice_status FROM invoices WHERE student_id = ? AND invoice_due_month = ? AND invoice_due_year = ?");
    $stmt->execute([$sid, $selectedMonth, $selectedYear]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $student['invoice_id'] = $invoice['invoice_id'] ?? null;
    $student['invoice_status'] = $invoice['invoice_status'] ?? null;
    
    $invoiceAmounts[$sid] = is_numeric($student['category_price_invoice']) ? (float)$student['category_price_invoice'] : 0;
    
    $paidAmount = 0;
    if ($invoice) {
        $stmt = $pdo->prepare("SELECT SUM(payment_value) as total FROM payments WHERE invoice_id = ? AND payment_status = 1");
        $stmt->execute([$invoice['invoice_id']]);
        $paidAmount = (float)($stmt->fetchColumn() ?? 0);
    }
    $paymentData[$sid] = [
        'paid' => $paidAmount,
        'remaining' => $invoiceAmounts[$sid] - $paidAmount
    ];
}
unset($student);

// Handle add/edit payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    $studentId = $_POST['student_id'] ?? null;
    $paymentValue = $_POST['payment_value'] ?? 0;
    $paymentMethodStr = $_POST['payment_method'] ?? '';
    
    $paymentMethodMap = [
        'cash' => 1,
        'bank_transfer' => 2,
        'online_transfer' => 3,
        'credit_card' => 4
    ];
    $paymentMethod = $paymentMethodMap[$paymentMethodStr] ?? 1;
    
    if ($studentId && $paymentValue > 0 && $paymentMethod) {
        // Create invoice if not exists
        $stmt = $pdo->prepare("SELECT invoice_id FROM invoices WHERE student_id = ? AND invoice_due_month = ? AND invoice_due_year = ?");
        $stmt->execute([$studentId, $selectedMonth, $selectedYear]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            $stmt = $pdo->prepare("INSERT INTO invoices (student_id, invoice_due_month, invoice_due_year, invoice_type, invoice_status, invoice_created_at, invoice_updated_at) VALUES (?, ?, ?, 1, 0, NOW(), NOW())");
            $stmt->execute([$studentId, $selectedMonth, $selectedYear]);
            $invoiceId = $pdo->lastInsertId();
        } else {
            $invoiceId = $invoice['invoice_id'];
        }
        
        // Add payment
        $stmt = $pdo->prepare("INSERT INTO payments (student_id, invoice_id, payment_value, payment_method, payment_status, payment_created_at) VALUES (?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$studentId, $invoiceId, $paymentValue, $paymentMethod]);
        
        // Check if fully paid
        $stmt = $pdo->prepare("SELECT SUM(payment_value) as total FROM payments WHERE invoice_id = ? AND payment_status = 1");
        $stmt->execute([$invoiceId]);
        $totalPaid = (float)($stmt->fetchColumn() ?? 0);
        
        // Get invoice amount
        $stmt = $pdo->prepare("SELECT c.category_price_invoice FROM invoices i LEFT JOIN students s ON s.student_id = i.student_id LEFT JOIN categories c ON c.category_id = s.category_id WHERE i.invoice_id = ?");
        $stmt->execute([$invoiceId]);
        $invoiceAmount = (float)($stmt->fetchColumn() ?? 0);
        
        if ($totalPaid >= $invoiceAmount && $invoiceAmount > 0) {
            $stmt = $pdo->prepare("UPDATE invoices SET invoice_status = 1, invoice_updated_at = NOW() WHERE invoice_id = ?");
            $stmt->execute([$invoiceId]);
        }
        
        echo "<script>alert('Payment saved successfully!'); window.location.href = '?month=$selectedMonth&year=$selectedYear';</script>";
    }
}
?>

<!-- Main Content -->
<main class="lg:ml-64 min-h-screen">
    <!-- Top Header -->
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-500 hover:text-slate-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="font-poppins text-xl font-semibold">Payments</h1>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-slate-500"><?= date('F d, Y'); ?></span>
        </div>
    </header>

    <!-- Content -->
    <div class="p-4 lg:p-8 space-y-6">
        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Month</label>
                        <select name="month" id="monthSelect" class="px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $selectedMonth == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Year</label>
                        <select name="year" id="yearSelect" class="px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="flex-1 max-w-md">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Search Student</label>
                    <input type="text" id="searchInput" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search by student name..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500">
                </div>
            </div>
        </div>

        <!-- Payment Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-poppins text-lg font-semibold text-slate-800">
                    Payment Status - <?= date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) ?>
                </h2>
                <p class="text-xs text-slate-400 mt-1">Showing <?= ($offset + 1) ?>-<?= min($offset + count($students), $totalStudents) ?> of <?= $totalStudents ?> students</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Student</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Category</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Invoice Amount</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Paid</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Remaining</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Parent Contact</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400 text-sm">No students enrolled in this period.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($students as $student):
                            $sid = $student['student_id'];
                            $amount = $invoiceAmounts[$sid] ?? 0;
                            $paid = $paymentData[$sid]['paid'] ?? 0;
                            $remaining = $paymentData[$sid]['remaining'] ?? 0;
                            $invoiceStatus = $student['invoice_status'] ?? null;
                            
                            if ($amount > 0 && $paid >= $amount) {
                                $statusBadge = 'bg-emerald-100 text-emerald-700';
                                $statusLabel = 'Paid';
                            } elseif ($invoiceStatus === null && $amount > 0) {
                                $statusBadge = 'bg-red-100 text-red-600';
                                $statusLabel = 'No Invoice';
                            } elseif ($paid > 0 && $paid < $amount) {
                                $statusBadge = 'bg-amber-100 text-amber-700';
                                $statusLabel = 'Partial';
                            } elseif ($amount > 0) {
                                $statusBadge = 'bg-red-100 text-red-600';
                                $statusLabel = 'Not Paid';
                            } else {
                                $statusBadge = 'bg-slate-100 text-slate-500';
                                $statusLabel = 'No Invoice';
                            }
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                                        <?= strtoupper(substr($student['student_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($student['student_name']) ?></p>
                                        <p class="text-xs text-slate-400">#<?= $student['student_id'] ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600"><?= htmlspecialchars($student['category_name'] ?? '—') ?></td>
                            <td class="px-4 py-4 text-sm text-slate-600">RM <?= number_format($amount, 2) ?></td>
                            <td class="px-4 py-4 text-sm text-slate-600">RM <?= number_format($paid, 2) ?></td>
                            <td class="px-4 py-4 text-sm text-slate-600">RM <?= number_format($remaining, 2) ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusBadge ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <div>
                                    <p class="text-slate-600"><?= htmlspecialchars($student['student_parent_name'] ?? '—') ?></p>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($student['student_parent_email'] ?? '—') ?></p>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($student['student_parent_number'] ?? '—') ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-1">
                                    <button onclick="openPaymentModal(<?= $student['student_id'] ?>, <?= $amount ?>, <?= $paid ?>, '<?= htmlspecialchars($student['student_name']) ?>')" class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors">
                                        <span>💳</span> Add Payment
                                    </button>
                                    <?php if ($student['invoice_id']): ?>
                                    <a href="<?= $location_index ?>/admin/pdf/invoice.php?invoice_id=<?= $student['invoice_id'] ?>" target="_blank" class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                                        <span>📄</span> Download
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <div class="text-sm text-slate-500">
                    Page <?= $page ?> of <?= $totalPages ?>
                </div>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $p === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 hover:bg-slate-50' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-poppins text-lg font-semibold text-slate-800">Add Payment</h3>
            <button onclick="document.getElementById('paymentModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="post" class="space-y-4">
            <input type="hidden" name="student_id" id="modal_student_id">
            <input type="hidden" name="save_payment" value="1">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Student</label>
                <input type="text" id="modal_student_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" readonly>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Invoice Amount (RM)</label>
                <input type="text" id="modal_invoice_amount" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" readonly>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Amount Paid (RM)</label>
                <input type="text" id="modal_paid_amount" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" readonly>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Remaining (RM)</label>
                <input type="text" id="modal_remaining" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" readonly>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Payment Amount <span class="text-red-500">*</span></label>
                <input type="number" name="payment_value" step="0.01" min="0.01" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                <select name="payment_method" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500" required>
                    <option value="">Select Method</option>
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="online_transfer">Online Transfer</option>
                    <option value="credit_card">Credit Card</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-xl transition-colors">
                    Save Payment
                </button>
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateUrl() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    const search = document.getElementById('searchInput').value;
    const params = new URLSearchParams();
    params.set('month', month);
    params.set('year', year);
    if (search) params.set('search', search);
    window.location.href = '?' + params.toString();
}

document.getElementById('monthSelect').addEventListener('change', updateUrl);
document.getElementById('yearSelect').addEventListener('change', updateUrl);

let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(updateUrl, 500);
});

function openPaymentModal(studentId, amount, paid, studentName) {
    document.getElementById('modal_student_id').value = studentId;
    document.getElementById('modal_student_name').value = studentName;
    document.getElementById('modal_invoice_amount').value = 'RM ' + amount.toFixed(2);
    document.getElementById('modal_paid_amount').value = 'RM ' + paid.toFixed(2);
    document.getElementById('modal_remaining').value = 'RM ' + (amount - paid).toFixed(2);
    document.getElementById('paymentModal').classList.remove('hidden');
}
</script>

<?php include '../../components/footer.php'; ?>
</body>
</html>