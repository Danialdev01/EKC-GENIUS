<?php
require_once __DIR__ . '/../config/connect.php';

try {
    $pdo->query("CREATE TABLE IF NOT EXISTS test_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_name VARCHAR(255) NOT NULL,
        test_value TEXT,
        created_at DATETIME NOT NULL
    )");
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM test_permissions");
    $result = $stmt->fetch();
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Test table ready',
        'rows' => $result['cnt']
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}