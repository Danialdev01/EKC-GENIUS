<?php

require_once __DIR__ . '/config/connect.php';

date_default_timezone_set("Asia/Kuala_Lumpur");

function generateMonthlyInvoices($pdo) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    $stmt = $pdo->prepare("
        SELECT s.student_id, s.student_name, s.category_id, c.category_price_invoice
        FROM students s
        LEFT JOIN categories c ON c.category_id = s.category_id AND c.category_status = 1
        WHERE s.student_status = 1
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $invoicesCreated = 0;
    $invoicesSkipped = 0;
    
    foreach ($students as $student) {
        $monthCheck = $pdo->prepare("
            SELECT invoice_id FROM invoices 
            WHERE student_id = ? 
            AND invoice_due_month = ? 
            AND invoice_due_year = ?
            LIMIT 1
        ");
        $monthCheck->execute([$student['student_id'], $currentMonth, $currentYear]);
        $existingInvoice = $monthCheck->fetch();
        
        if ($existingInvoice) {
            $invoicesSkipped++;
            continue;
        }
        
        $price = $student['category_price_invoice'] ?? '0';
        $price = is_numeric($price) ? $price : 0;
        
        $insertStmt = $pdo->prepare("
            INSERT INTO invoices (
                student_id, 
                invoice_due_month, 
                invoice_due_year, 
                invoice_type, 
                invoice_status, 
                invoice_created_at,
                invoice_updated_at
            ) VALUES (?, ?, ?, 1, 0, NOW(), NOW())
        ");
        $insertStmt->execute([
            $student['student_id'],
            $currentMonth,
            $currentYear
        ]);
        
        $invoicesCreated++;
    }
    
    return [
        'created' => $invoicesCreated,
        'skipped' => $invoicesSkipped,
        'month' => $currentMonth,
        'year' => $currentYear
    ];
}

try {
    $result = generateMonthlyInvoices($pdo);
    echo "Cron completed: {$result['created']} invoices created, {$result['skipped']} skipped for Month {$result['month']}, {$result['year']}\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}