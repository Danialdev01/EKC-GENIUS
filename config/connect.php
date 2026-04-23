<?php

date_default_timezone_set("Asia/Kuala_Lumpur");
error_reporting(E_ALL ^ E_DEPRECATED);

// Custom error handler to return JSON
function errorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return;
    header('Content-Type: application/json');
    echo json_encode(['error' => "PHP Error: [$errno] $errstr - ($errfile | line $errline)"]);
    exit;
}
set_error_handler("errorHandler");

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => "Autoloader not found. Please run 'composer install'."]);
    exit;
}

require_once $autoloadPath;

/**
 * We try to load the .env file, but we wrap it in a try-catch 
 * because Easypanel's system variables (like PHP_LDFLAGS) are 
 * currently causing the parser to fail.
 */
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Exception $e) {
    // If it fails, we rely on variables already in the system environment (Docker)
}

/**
 * VARIABLE RETRIEVAL
 * We check $_ENV first, then fallback to getenv() which is more reliable in Docker.
 * If both are empty, we use the defaults found in your Easypanel logs.
 */
$hostname        = $_ENV['DB_HOSTNAME']    ?? getenv('DB_HOSTNAME') ?: 'web_main';
$dbname          = $_ENV['DB_NAME']        ?? getenv('DB_NAME')     ?: 'ekc-genius';
$username        = $_ENV['DB_USERNAME']    ?? getenv('DB_USERNAME') ?: 'root';
$password        = $_ENV['DB_PASSWORD']    ?? getenv('DB_PASSWORD') ?: '6ec9db743cff850829bb';
$teacher_passkey = $_ENV['TEACHER_PASSKEY'] ?? getenv('TEACHER_PASSKEY') ?: '2026';

// Build the DSN - Notice we explicitly avoid 'localhost' to prevent socket errors
$dsn = "mysql:host=$hostname;dbname=$dbname;charset=utf8mb4;port=3306";

try {
    $pdo = new PDO($dsn, $username, $password);
    
    // Set professional error modes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // Don't hang the site if DB is slow
    
} catch(PDOException $error) {
    header('Content-Type: application/json');
    
    // We include the hostname in the error to help you debug if it's still hitting 'localhost'
    echo json_encode([
        'error' => "Database Connection Failed",
        'message' => $error->getMessage(),
        'debug_info' => [
            'attempted_host' => $hostname,
            'attempted_user' => $username,
            'attempted_db'   => $dbname
        ]
    ]);
    
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    exit;
}
