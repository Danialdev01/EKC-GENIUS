<?php
session_start();
require_once __DIR__ . '/../config/connect.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    
    if ($role === 'admin') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_email = ? AND admin_status = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['admin_hash_password'])) {
            $_SESSION['user_id'] = $admin['admin_id'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_name'] = $admin['admin_name'];
            $response['success'] = true;
            $response['redirect'] = 'admin/';
        } else {
            $response['message'] = 'Invalid credentials';
        }
    } elseif ($role === 'teacher') {
        $teacher_id = $_POST['teacher_id'] ?? '';
        $passkey = $_POST['passkey'] ?? '';
        
        if ($passkey === $teacher_passkey && !empty($teacher_id)) {
            $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ? AND teacher_status = 1");
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($teacher) {
                $_SESSION['user_id'] = $teacher['teacher_id'];
                $_SESSION['user_role'] = 'teacher';
                $_SESSION['user_name'] = $teacher['teacher_name'];
                $response['success'] = true;
                $response['redirect'] = 'teachers/';
            } else {
                $response['message'] = 'Teacher not found';
            }
        } else {
            $response['message'] = 'Invalid passkey';
        }
    } elseif ($role === 'parent') {
        $student_ic = $_POST['student_ic'] ?? '';
        $student_ic = str_replace('-', '', $student_ic);
        
        if (!empty($student_ic) && strlen($student_ic) >= 12) {
            $stmt = $pdo->prepare("SELECT * FROM students WHERE student_ic = ? AND student_status = 1");
            $stmt->execute([$student_ic]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                $_SESSION['user_id'] = $student['student_id'];
                $_SESSION['user_role'] = 'parent';
                $_SESSION['user_name'] = $student['student_parent_name'];
                $_SESSION['student_name'] = $student['student_name'];
                $_SESSION['student_ic'] = $student['student_ic'];
                $response['success'] = true;
                $response['redirect'] = 'parents/';
            } else {
                $response['message'] = 'Student IC not found';
            }
        } else {
            $response['message'] = 'Please enter a valid IC number (12 digits)';
        }
    } else {
        $response['message'] = 'Invalid role';
    }
}

echo json_encode($response);
