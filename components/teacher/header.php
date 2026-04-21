<?php
/**
 * Dashboard page shell – replaces the old per-module <html><head> boilerplate.
 *
 * Expected variables (set before including):
 *   $pageTitle   – string, e.g. 'Dashboard'
 *   $authUser    – array {id, name, role}  from requireAuth()
 *   $cssDepth    – relative path to public/css  (default '../public/css')
 */
$cssDepth  = $cssDepth  ?? '../public/css';
$authUser  = $authUser  ?? ['name' => 'User', 'role' => 'guest', 'id' => 0];
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= htmlspecialchars($pageTitle) ?> | EKC Genius IR4.0</title>
    <meta name="description" content="EKC Genius IR4.0 – Integrated platform for early childhood education management.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;1,14..32,400&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="<?= $cssDepth ?>/style.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧠</text></svg>">
</head>
<body class="bg-slate-100 text-slate-800 font-inter antialiased">
