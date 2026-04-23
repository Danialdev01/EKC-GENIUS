<?php
require_once __DIR__ . '/../config/connect.php';

$tables = [
    'activites' => [
        'required' => ['activity_name', 'activity_description', 'activity_status'],
        'optional' => ['activity_type'],
        'unique' => 'activity_id',
        'status_field' => 'activity_status'
    ],
    'admins' => [
        'required' => ['admin_name', 'admin_email', 'admin_hash_password', 'admin_status'],
        'optional' => ['admin_created_at', 'admin_updated_at'],
        'unique' => 'admin_id',
        'status_field' => 'admin_status'
    ],
    'ai_assessments' => [
        'required' => ['student_id', 'ai_assessment_strengths', 'ai_assessment_focus_area', 'ai_assessment_trend_analysis', 'ai_assessment_status'],
        'optional' => ['ai_assessment_month', 'ai_assessment_year', 'ai_assessment_created_at', 'ai_assessment_updated_at'],
        'unique' => 'ai_assessment_id',
        'status_field' => 'ai_assessment_status'
    ],
    'alerts' => [
        'required' => ['student_id', 'alert_status'],
        'optional' => ['alert_recommended_action', 'alert_recommended_activity', 'alert_created_at', 'alert_updated_at'],
        'unique' => 'alert_id',
        'status_field' => 'alert_status'
    ],
    'assessments' => [
        'required' => ['assessment_title', 'assessment_status'],
        'optional' => ['category_id', 'assessment_icon', 'assessment_description', 'assessment_created_at', 'assessment_updated_at'],
        'unique' => 'assessment_id',
        'status_field' => 'assessment_status'
    ],
    'assignments' => [
        'required' => ['activity_id', 'student_id', 'assignment_status'],
        'optional' => ['assignment_notes', 'assignment_outcome', 'assignment_created_at', 'assignment_updated_at'],
        'unique' => 'assignment_id',
        'status_field' => 'assignment_status'
    ],
    'attendances' => [
        'required' => ['student_id', 'attendance_type', 'attendance_status'],
        'optional' => ['attendance_notes', 'attendance_datetime', 'attendance_created_at', 'attendance_updated_at'],
        'unique' => 'attendance_id',
        'status_field' => 'attendance_status'
    ],
    'categories' => [
        'required' => ['category_name', 'category_price_invoice', 'category_status'],
        'optional' => ['category_description', 'category_created_at', 'category_updated_at'],
        'unique' => 'category_id',
        'status_field' => 'category_status'
    ],
    'invoices' => [
        'required' => ['student_id', 'invoice_type', 'invoice_status'],
        'optional' => ['invoice_due_month', 'invoice_due_year', 'invoice_created_at', 'invoice_updated_at'],
        'unique' => 'invoice_id',
        'status_field' => 'invoice_status'
    ],
    'payments' => [
        'required' => ['student_id', 'invoice_id', 'payment_value', 'payment_method', 'payment_status'],
        'optional' => ['payment_created_at', 'payment_updated_at'],
        'unique' => 'payment_id',
        'status_field' => 'payment_status'
    ],
    'student_assessments' => [
        'required' => ['assessment_id', 'student_id', 'student_assessment_value', 'student_assessment_status'],
        'optional' => ['student_assessment_month', 'student_assessment_year', 'student_assessment_created_at', 'student_assessment_updated_at'],
        'unique' => 'student_assessment_id',
        'status_field' => 'student_assessment_status'
    ],
    'students' => [
        'required' => ['student_name', 'category_id', 'student_status'],
        'optional' => ['student_ic', 'student_year_of_birth', 'student_parent_name', 'student_parent_email', 'student_parent_number', 'student_notes'],
        'unique' => 'student_id',
        'status_field' => 'student_status'
    ],
    'teachers' => [
        'required' => ['teacher_name', 'teacher_status'],
        'optional' => ['teacher_email', 'teacher_phone_number', 'teacher_specialization', 'teacher_notes', 'teacher_created_at', 'teacher_updated_at'],
        'unique' => 'teacher_id',
        'status_field' => 'teacher_status'
    ]
];

$tests = [];

foreach ($tables as $table => $config) {
    $tableTests = [];
    
    try {
        $pdo->query("DESCRIBE $table");
        $tableTests['describe'] = 'pass';
    } catch (Exception $e) {
        $tableTests['describe'] = 'fail: ' . $e->getMessage();
        $tests[$table] = $tableTests;
        continue;
    }
    
    try {
        $cols = array_merge($config['required'], $config['optional']);
        $placeholders = array_map(fn($c) => '?', $cols);
        
        $values = [];
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        
        foreach ($cols as $col) {
            if (strpos($col, '_created_at') !== false || strpos($col, '_updated_at') !== false) {
                $values[] = $now;
            } elseif (strpos($col, 'date') !== false && strpos($col, '_date') !== false) {
                $values[] = $today;
            } elseif (strpos($col, 'status') !== false) {
                $values[] = 1;
            } elseif (strpos($col, '_id') !== false && $col !== $config['unique']) {
                $values[] = 1;
            } elseif (strpos($col, 'type') !== false || strpos($col, 'method') !== false) {
                $values[] = 1;
            } elseif (strpos($col, '_value') !== false && strpos($col, 'assessment') !== false) {
                $values[] = 3;
            } elseif (strpos($col, 'value') !== false) {
                $values[] = '100';
            } elseif (strpos($col, 'month') !== false) {
                $values[] = date('n');
            } elseif (strpos($col, 'year') !== false) {
                $values[] = date('Y');
            } elseif (strpos($col, '_name') !== false) {
                $values[] = 'Test ' . ucfirst($col) . ' ' . time();
            } elseif (strpos($col, '_email') !== false) {
                $values[] = 'test' . time() . '@test.com';
            } elseif (strpos($col, '_number') !== false) {
                $values[] = '0123456789';
            } elseif (strpos($col, '_notes') !== false || strpos($col, 'description') !== false || strpos($col, 'action') !== false || strpos($col, 'activity') !== false) {
                $values[] = 'Test note';
            } elseif (strpos($col, 'icon') !== false) {
                $values[] = '📚';
            } elseif (strpos($col, 'specialization') !== false) {
                $values[] = 'Math';
            } else {
                $values[] = 'Test';
            }
        }
        
        $sql = "INSERT INTO $table (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $lastId = $pdo->lastInsertId();
        $tableTests['insert'] = 'pass (id: ' . $lastId . ')';
        $tableTests['last_id'] = $lastId;
    } catch (Exception $e) {
        $tableTests['insert'] = 'fail: ' . $e->getMessage();
        $tableTests['last_id'] = null;
    }
    
    if ($tableTests['last_id']) {
        try {
            $statusField = $config['status_field'];
            $stmt = $pdo->prepare("UPDATE $table SET $statusField = 0 WHERE {$config['unique']} = ?");
            $stmt->execute([$tableTests['last_id']]);
            $tableTests['update_status'] = 'pass (soft delete)';
        } catch (Exception $e) {
            $tableTests['update_status'] = 'fail: ' . $e->getMessage();
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE {$config['unique']} = ?");
            $stmt->execute([$tableTests['last_id']]);
            $tableTests['delete'] = 'pass';
        } catch (Exception $e) {
            $tableTests['delete'] = 'fail: ' . $e->getMessage();
        }
    } else {
        $tableTests['update_status'] = 'skip (no insert)';
        $tableTests['delete'] = 'skip (no insert)';
    }
    
    $tests[$table] = $tableTests;
}

$summary = ['total' => count($tables), 'pass_insert' => 0, 'fail_insert' => 0];
foreach ($tests as $t) {
    if (isset($t['insert']) && strpos($t['insert'], 'pass') !== false) {
        $summary['pass_insert']++;
    } elseif (isset($t['insert']) && strpos($t['insert'], 'fail') !== false) {
        $summary['fail_insert']++;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'test' => 'All Tables CRUD Permissions',
    'timestamp' => date('Y-m-d H:i:s'),
    'summary' => $summary,
    'results' => $tests
], JSON_PRETTY_PRINT);