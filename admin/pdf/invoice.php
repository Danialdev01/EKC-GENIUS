<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';
require_once __DIR__ . '/../../backend/auth.php';
$authUser = requireAuth('admin');

require_once __DIR__ . '/../../vendor/autoload.php';

$invoiceId = $_GET['invoice_id'] ?? null;

if (!$invoiceId) {
    die('Invoice ID required');
}

$stmt = $pdo->prepare("
    SELECT i.*, s.student_name, s.student_ic, c.category_name, c.category_price_invoice
    FROM invoices i
    LEFT JOIN students s ON i.student_id = s.student_id
    LEFT JOIN categories c ON s.category_id = c.category_id AND c.category_status = 1
    WHERE i.invoice_id = ?
");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die('Invoice not found');
}

$stmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? AND payment_status = 1");
$stmt->execute([$invoiceId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPaid = 0;
foreach ($payments as $payment) {
    $totalPaid += (float)$payment['payment_value'];
}

$invoiceAmount = (float)($invoice['category_price_invoice'] ?? 0);
$balanceDue = $invoiceAmount - $totalPaid;
$status = $invoice['invoice_status'] == 1 ? 'Paid' : ($balanceDue <= 0 ? 'Paid' : 'Unpaid');
$statusColor = $status === 'Paid' ? '#10b981' : '#ef4444';

$monthName = date('F', mktime(0, 0, 0, $invoice['invoice_due_month'], 1));

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #' . $invoice['invoice_id'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #6366f1; padding-bottom: 20px; }
        .header-left h1 { color: #6366f1; margin: 0; font-size: 28px; }
        .header-left p { color: #666; margin: 5px 0 0; }
        .header-right { text-align: right; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #333; }
        .invoice-number { font-size: 14px; color: #666; margin-top: 5px; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .info-box { background: #f8f9fa; padding: 20px; border-radius: 10px; width: 48%; }
        .info-box h3 { margin-top: 0; color: #333; font-size: 14px; margin-bottom: 15px; }
        .info-row { display: flex; justify-content: space-between; margin: 8px 0; }
        .info-label { color: #666; }
        .info-value { font-weight: bold; color: #333; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; color: white; }
        .table-section { margin-bottom: 30px; }
        .table-section h3 { color: #333; margin-bottom: 15px; }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { background: #6366f1; color: white; padding: 12px; text-align: left; }
        .items-table td { border: 1px solid #ddd; padding: 12px; }
        .items-table tr:nth-child(even) { background: #f8f9fa; }
        .items-table .amount { text-align: right; font-weight: bold; }
        .summary-box { background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: right; }
        .summary-row { display: flex; justify-content: flex-end; margin: 8px 0; }
        .summary-label { color: #666; width: 150px; }
        .summary-value { font-weight: bold; width: 100px; }
        .summary-total { font-size: 18px; color: #6366f1; }
        .footer { margin-top: 40px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
        .payment-history { margin-top: 20px; }
        .payment-history h3 { color: #333; margin-bottom: 10px; }
        .paid-badge { background: #10b981; color: white; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>INVOICE</h1>
            <p>EKC Genius Learning Center</p>
            <p>Generated on ' . date('F d, Y') . '</p>
        </div>
        <div class="header-right">
            <div class="invoice-title">Invoice #' . str_pad($invoice['invoice_id'], 4, '0', STR_PAD_LEFT) . '</div>
            <div class="invoice-number">Due: ' . $monthName . ' ' . $invoice['invoice_due_year'] . '</div>
            <div class="status-badge" style="background: ' . $statusColor . ';">' . $status . '</div>
        </div>
    </div>
    
    <div class="info-section">
        <div class="info-box">
            <h3>Student Information</h3>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value">' . htmlspecialchars($invoice['student_name'] ?? 'N/A') . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">IC Number:</span>
                <span class="info-value">' . htmlspecialchars($invoice['student_ic'] ?? 'N/A') . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Category:</span>
                <span class="info-value">' . htmlspecialchars($invoice['category_name'] ?? 'N/A') . '</span>
            </div>
        </div>
        <div class="info-box">
            <h3>Invoice Details</h3>
            <div class="info-row">
                <span class="info-label">Invoice ID:</span>
                <span class="info-value">#' . str_pad($invoice['invoice_id'], 4, '0', STR_PAD_LEFT) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Due Date:</span>
                <span class="info-value">' . $monthName . ' ' . $invoice['invoice_due_year'] . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">' . $status . '</span>
            </div>
        </div>
    </div>
    
    <div class="table-section">
        <h3>Invoice Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount (RM)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monthly Tuition Fee - ' . $monthName . ' ' . $invoice['invoice_due_year'] . '</td>
                    <td class="amount">' . number_format($invoiceAmount, 2) . '</td>
                </tr>
            </tbody>
        </table>
        
        <div class="summary-box">
            <div class="summary-row">
                <span class="summary-label">Subtotal:</span>
                <span class="summary-value">' . number_format($invoiceAmount, 2) . '</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Paid:</span>
                <span class="summary-value">-' . number_format($totalPaid, 2) . '</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Balance Due:</span>
                <span class="summary-value summary-total">' . number_format(max(0, $balanceDue), 2) . '</span>
            </div>
        </div>
    </div>
    
    <div class="payment-history">
        <h3>Payment History</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount (RM)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

if (count($payments) > 0) {
    foreach ($payments as $payment) {
        $html .= '
                <tr>
                    <td>' . date('d M Y', strtotime($payment['payment_created_at'])) . '</td>
                    <td class="amount">' . number_format((float)$payment['payment_value'], 2) . '</td>
                    <td><span class="paid-badge">PAID</span></td>
                </tr>';
    }
} else {
    $html .= '
                <tr>
                    <td colspan="3" style="text-align: center;">No payments recorded yet.</td>
                </tr>';
}

$html .= '
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>© ' . date('Y') . ' EKC Genius. All rights reserved.</p>
        <p>This is a computer-generated invoice. No signature required.</p>
    </div>
</body>
</html>';

$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="invoice_' . $invoiceId . '_' . date('Ymd') . '.pdf"');
echo $dompdf->output();