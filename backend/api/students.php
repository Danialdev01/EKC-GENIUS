<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/connect.php';

$teacherId = $_GET['teacher_id'] ?? null;

$students = [];
if ($teacherId) {
    $stmt = $pdo->prepare("
        SELECT s.student_id, s.student_name, s.student_parent_name 
        FROM students s
        INNER JOIN teachers t ON t.teacher_id = ?
        WHERE s.student_status = 1 
        ORDER BY s.student_name
    ");
    $stmt->execute([$teacherId]);
} else {
    $stmt = $pdo->prepare("
        SELECT student_id, student_name, student_parent_name 
        FROM students 
        WHERE student_status = 1 
        ORDER BY student_name
    ");
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $students[] = [
        'id' => (int)$row['student_id'],
        'name' => $row['student_name'],
        'parent' => $row['student_parent_name'] ?? ''
    ];
}

echo json_encode($students);