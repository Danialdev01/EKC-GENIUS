<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/connect.php';

$teachers = [];
$stmt = $pdo->prepare("SELECT teacher_id, teacher_name FROM teachers WHERE teacher_status = 1 ORDER BY teacher_name");
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($teachers);
