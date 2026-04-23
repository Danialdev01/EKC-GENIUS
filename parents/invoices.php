<?php
session_start();
require_once '../config/connect.php';
require_once '../backend/auth.php';

$authUser  = requireAuth('parent');
$pageTitle = 'Invoices';
$cssDepth  = '../public/css';

$studentId = $authUser['id'];

$stmt = $pdo->prepare("
    SELECT s.*, c.category_name, c.category_price_invoice
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

$stmt = $pdo->prepare("
    SELECT i.*, p.payment_id, p.payment_value, p.payment_method, p.payment_status as payment_status, p.payment_created_at
    FROM invoices i
    LEFT JOIN payments p ON p.invoice_id = i.invoice_id
    WHERE i.student_id = ? AND i.invoice_status = 1
    ORDER BY i.invoice_due_year DESC, i.invoice_due_month DESC
");
$stmt->execute([$studentId]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$monthlyFee = $student['category_price_invoice'] ?? 0;
$statusFilter = $_GET['status'] ?? 'all';
?>
<?php include '../components/teacher/header.php'; ?>

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
            ['href' => 'profile.php', 'label' => 'Profile',     'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
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

<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleParentSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Invoices</h1>
                <p class="text-xs text-slate-400"><?= htmlspecialchars($student['student_name']) ?> · <?= date('l, F d, Y') ?></p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-2xl shrink-0">💰</div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Monthly Fee</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800">RM <?= number_format($monthlyFee, 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-2xl shrink-0">✅</div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Paid</p>
                        <p class="font-poppins text-2xl font-bold text-emerald-600">
                            <?= count(array_filter($invoices, fn($i) => $i['invoice_status'] == 2)) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-red-200 shadow-sm p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-2xl shrink-0">⏳</div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Pending</p>
                        <p class="font-poppins text-2xl font-bold text-red-500">
                            <?= count(array_filter($invoices, fn($i) => $i['invoice_status'] == 1)) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <h3 class="font-poppins text-base font-semibold text-slate-800">Invoice History</h3>
                <div class="flex gap-2">
                    <a href="?status=all" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $statusFilter === 'all' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">All</a>
                    <a href="?status=paid" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $statusFilter === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">Paid</a>
                    <a href="?status=pending" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $statusFilter === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">Pending</a>
                </div>
            </div>
            <?php if (empty($invoices)): ?>
            <p class="px-6 py-8 text-sm text-slate-400 text-center">No invoices found.</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Invoice #</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Student</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        $filteredInvoices = $invoices;
                        if ($statusFilter === 'paid') {
                            $filteredInvoices = array_filter($invoices, fn($i) => $i['invoice_status'] == 2);
                        } elseif ($statusFilter === 'pending') {
                            $filteredInvoices = array_filter($invoices, fn($i) => $i['invoice_status'] == 1);
                        }
                        foreach ($filteredInvoices as $inv): 
                            $invoiceStatus = (int)$inv['invoice_status'];
                            $statusClass = $invoiceStatus === 2 ? 'bg-emerald-100 text-emerald-700' : ($invoiceStatus === 3 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                            $statusLabel = $invoiceStatus === 2 ? 'Paid' : ($invoiceStatus === 3 ? 'Overdue' : 'Pending');
                            $invoiceNumber = 'INV-' . str_pad($inv['invoice_id'], 5, '0', STR_PAD_LEFT);
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-slate-700"><?= $invoiceNumber ?></td>
                            <td class="px-6 py-4 text-center text-sm text-slate-600">
                                <?= date('F Y', mktime(0,0,0,$inv['invoice_due_month'],1,$inv['invoice_due_year'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($student['student_name']) ?></td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-slate-800">RM <?= number_format($monthlyFee, 2) ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($invoiceStatus === 1): ?>
                                <span class="text-xs text-slate-400">Awaiting payment</span>
                                <?php elseif ($invoiceStatus === 2 && $inv['payment_id']): ?>
                                <button onclick="viewPayment(<?= $inv['payment_id'] ?>)" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                    View Receipt
                                </button>
                                <?php else: ?>
                                <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($filteredInvoices)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-sm text-slate-400 text-center">No invoices match the selected filter.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="font-poppins text-base font-semibold text-slate-800">Payment Summary</h3>
            </div>
            <div class="p-6">
                <?php
                $totalPaid = 0;
                $totalPending = 0;
                foreach ($invoices as $inv) {
                    if ($inv['invoice_status'] == 2) {
                        $totalPaid += $monthlyFee;
                    } elseif ($inv['invoice_status'] == 1) {
                        $totalPending += $monthlyFee;
                    }
                }
                ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-200">
                        <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide mb-1">Total Paid</p>
                        <p class="font-poppins text-3xl font-bold text-emerald-700">RM <?= number_format($totalPaid, 2) ?></p>
                    </div>
                    <div class="p-4 bg-amber-50 rounded-xl border border-amber-200">
                        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide mb-1">Outstanding Balance</p>
                        <p class="font-poppins text-3xl font-bold text-amber-700">RM <?= number_format($totalPending, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<div id="paymentModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-poppins text-lg font-semibold text-slate-800">Payment Receipt</h3>
            <button onclick="closePaymentModal()" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="paymentDetails" class="space-y-3 text-sm">
        </div>
    </div>
</div>

<script>
function toggleParentSidebar() {
    const sidebar = document.getElementById('parentSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

function viewPayment(paymentId) {
    const modal = document.getElementById('paymentModal');
    const details = document.getElementById('paymentDetails');
    details.innerHTML = '<p class="text-center text-slate-400">Loading...</p>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    fetch(`../backend/get_payment.php?id=${paymentId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                details.innerHTML = `
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500">Payment ID</span>
                        <span class="font-medium text-slate-700">#${data.payment.payment_id}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500">Amount</span>
                        <span class="font-medium text-emerald-600">RM ${parseFloat(data.payment.payment_value).toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500">Method</span>
                        <span class="font-medium text-slate-700">${data.payment.payment_method || 'N/A'}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500">Date</span>
                        <span class="font-medium text-slate-700">${data.payment.payment_created_at ? new Date(data.payment.payment_created_at).toLocaleString() : 'N/A'}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-500">Status</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Paid</span>
                    </div>
                `;
            } else {
                details.innerHTML = '<p class="text-center text-red-500">Failed to load payment details.</p>';
            }
        })
        .catch(() => {
            details.innerHTML = '<p class="text-center text-red-500">Error loading payment.</p>';
        });
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
</body>
</html>