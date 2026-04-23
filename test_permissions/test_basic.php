<?php
require_once __DIR__ . '/../config/connect.php';

$tests = [];

$pdo->query("DELETE FROM test_permissions WHERE id > 0");

try {
    $stmt = $pdo->query("SELECT 1");
    $tests['connection'] = ['status' => 'pass', 'message' => 'Database connected successfully'];
} catch (Exception $e) {
    $tests['connection'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("SELECT * FROM students LIMIT 1");
    $result = $stmt->fetch();
    $tests['select_students'] = ['status' => 'pass', 'message' => 'Can SELECT from students table'];
} catch (Exception $e) {
    $tests['select_students'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("DESCRIBE students");
    $tests['describe_students'] = ['status' => 'pass', 'message' => 'Can DESCRIBE students table'];
} catch (Exception $e) {
    $tests['describe_students'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM students");
    $result = $stmt->fetch();
    $tests['count_students'] = ['status' => 'pass', 'message' => 'Total students: ' . $result['cnt']];
} catch (Exception $e) {
    $tests['count_students'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("DESCRIBE categories");
    $tests['describe_categories'] = ['status' => 'pass', 'message' => 'Can DESCRIBE categories table'];
} catch (Exception $e) {
    $tests['describe_categories'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("DESCRIBE assessments");
    $tests['describe_assessments'] = ['status' => 'pass', 'message' => 'Can DESCRIBE assessments table'];
} catch (Exception $e) {
    $tests['describe_assessments'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("DESCRIBE student_assessments");
    $tests['describe_student_assessments'] = ['status' => 'pass', 'message' => 'Can DESCRIBE student_assessments table'];
} catch (Exception $e) {
    $tests['describe_student_assessments'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("DESCRIBE ai_assessments");
    $tests['describe_ai_assessments'] = ['status' => 'pass', 'message' => 'Can DESCRIBE ai_assessments table'];
} catch (Exception $e) {
    $tests['describe_ai_assessments'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("DESCRIBE user_logins");
    $tests['describe_user_logins'] = ['status' => 'pass', 'message' => 'Can DESCRIBE user_logins table'];
} catch (Exception $e) {
    $tests['describe_user_logins'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tests['show_tables'] = ['status' => 'pass', 'message' => 'Found ' . count($tables) . ' tables: ' . implode(', ', $tables)];
} catch (Exception $e) {
    $tests['show_tables'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode([
    'test' => 'Basic Permissions',
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $tests
], JSON_PRETTY_PRINT);