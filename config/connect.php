<?php

date_default_timezone_set("Asia/Kuala_Lumpur");
error_reporting(E_ALL ^ E_DEPRECATED);

function errorHandler($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode(['error' => "ERROR : [$errno] $errstr - ( $errfile | line $errline)"]);
}
set_error_handler("errorHandler");

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    throw new Exception("Autoloader not found. Please run 'composer install'.");
}

require_once $autoloadPath;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$hostname = $_ENV['DB_HOSTNAME'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'ekc-genius';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

$teacher_passkey = $_ENV['TEACHER_PASSKEY'] ?? '2026';

$dsn = 'mysql:host=' . $hostname . ';dbname=' . $dbname . ';charset=utf8mb4';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $error->getMessage()]);
    session_destroy();
    exit;
}