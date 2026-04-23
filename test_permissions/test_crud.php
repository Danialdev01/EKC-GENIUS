<?php
require_once __DIR__ . '/../config/connect.php';

$tests = [];

try {
    $stmt = $pdo->prepare("INSERT INTO test_permissions (test_name, test_value, created_at) VALUES (?, ?, NOW())");
    $stmt->execute(['insert_test', 'value_' . time()]);
    $lastId = $pdo->lastInsertId();
    $tests['insert'] = ['status' => 'pass', 'message' => 'INSERT successful, last ID: ' . $lastId];
} catch (Exception $e) {
    $tests['insert'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->prepare("UPDATE test_permissions SET test_value = ? WHERE id = ?");
    $stmt->execute(['updated_' . time(), $lastId ?? 1]);
    $tests['update'] = ['status' => 'pass', 'message' => 'UPDATE successful'];
} catch (Exception $e) {
    $tests['update'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->prepare("DELETE FROM test_permissions WHERE id = ?");
    $stmt->execute([$lastId ?? 1]);
    $tests['delete'] = ['status' => 'pass', 'message' => 'DELETE successful'];
} catch (Exception $e) {
    $tests['delete'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("SELECT category_id FROM categories LIMIT 1");
    $cat = $stmt->fetch();
    $categoryId = $cat['category_id'] ?? 1;
    
    $stmt = $pdo->prepare("INSERT INTO students (student_name, student_ic, student_year_of_birth, category_id, student_parent_name, student_parent_email, student_parent_number, student_notes, student_status, student_created_at, student_updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
    $testName = 'Test_Student_' . time();
    $stmt->execute([$testName, 'TEST' . time(), '2020', $categoryId, 'Test Parent', 'test@test.com', '0123456789', 'Test note']);
    $studentId = $pdo->lastInsertId();
    $tests['insert_students'] = ['status' => 'pass', 'message' => 'INSERT student successful, ID: ' . $studentId];
    
    $stmt = $pdo->prepare("UPDATE students SET student_status = 0 WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $tests['delete_students'] = ['status' => 'pass', 'message' => 'Soft DELETE student (set status=0) successful'];
} catch (Exception $e) {
    $tests['insert_students'] = ['status' => 'fail', 'message' => $e->getMessage()];
    $tests['delete_students'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

try {
    $stmt = $pdo->query("SELECT student_id, student_name FROM students WHERE student_status = 1 LIMIT 1");
    $student = $stmt->fetch();
    if ($student) {
        $stmt = $pdo->prepare("UPDATE students SET student_notes = ? WHERE student_id = ?");
        $stmt->execute(["Test update at " . date('Y-m-d H:i:s'), $student['student_id']]);
        $tests['update_students'] = ['status' => 'pass', 'message' => 'UPDATE student successful'];
    }
} catch (Exception $e) {
    $tests['update_students'] = ['status' => 'fail', 'message' => $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode([
    'test' => 'CRUD Permissions',
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $tests
], JSON_PRETTY_PRINT);