<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitles = [
    'index' => 'Welcome',
    'dashboard' => 'Dashboard',
    'students' => 'Students',
    'teachers' => 'Teachers',
    'attendance' => 'Attendance',
    'assessments' => 'Assessments',
    'payments' => 'Payments',
    'reports' => 'Reports',
    'profile' => 'Profile',
    'settings' => 'Settings',
];
$pageTitle = $pageTitles[$currentPage] ?? 'EKC Genius IR4.0';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title><?= htmlspecialchars($pageTitle) ?> | EKC Genius IR4.0 Platform</title>
    <meta name="description" content="EKC Genius IR4.0 - Integrated IR4.0 platform leveraging Mathematical Analytics and Artificial Intelligence for early childhood education management.">
    <meta name="keywords" content="EKC Genius, IR4.0, early childhood, autism education, mathematical analytics, AI education, student management">
    <meta name="author" content="Danial Irfan Bin Zakaria, Dr. Adib Bin Mashuri">
    <meta name="robots" content="index, follow">
    
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?> | EKC Genius IR4.0">
    <meta property="og:description" content="Integrated IR4.0 platform for early childhood education management with AI-driven analytics.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:site_name" content="EKC Genius IR4.0">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?> | EKC Genius IR4.0">
    <meta name="twitter:description" content="Integrated IR4.0 platform for early childhood education management with AI-driven analytics.">
    
    <link rel="canonical" href="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧠</text></svg>">
</head>
<body class="bg-paper-white text-edu-slate font-inter antialiased">
