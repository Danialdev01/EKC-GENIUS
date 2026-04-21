<?php

date_default_timezone_set("Asia/Kuala_Lumpur"); 
$hostname = 'localhost';
$dbname = 'ekc-genius';
$username = 'root';
$password = 'danialdev';

$dsn = 'mysql:host=' . $hostname . ';dbname=' . $dbname;

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $error->getMessage()]);
    session_destroy();
    exit;
}


error_reporting(E_ALL ^ E_DEPRECATED);

function errorHandler($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode(['error' => "ERROR : [$errno] $errstr - ( $errfile | line $errline)"]);
}
set_error_handler("errorHandler");