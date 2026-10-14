<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';

// Database configuration
$host = 'localhost';
$dbname = 'printing_management';
$username = 'root';
$password = '';

$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $env_vars = @parse_ini_file($env_file);
    if ($env_vars !== false) {
        $host = $env_vars['DB_HOST'] ?? $host;
        $dbname = $env_vars['DB_NAME'] ?? $dbname;
        $username = $env_vars['DB_USER'] ?? $username;
        $password = $env_vars['DB_PASSWORD'] ?? $password;
    }
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Base URL - Set this to your actual URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host_header = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = '/Invoice_management/'; // Change this if your folder name is different

define('BASE_URL', $protocol . '://' . $host_header . $base_path);
define('APP_NAME', 'Printing Management System');

// Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function generateJobNo() {
    return 'JOB-' . date('Ymd') . '-' . rand(1000, 9999);
}

function generateInvoiceNo() {
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}

function getCurrentStock($size_id, $gsm_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as total_in FROM paper_stock_in WHERE size_id = ? AND gsm_id = ?");
        $stmt->execute([$size_id, $gsm_id]);
        $result = $stmt->fetch();
        $total_in = $result ? $result['total_in'] : 0;
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_used), 0) as total_out FROM paper_stock_out WHERE size_id = ? AND gsm_id = ?");
        $stmt->execute([$size_id, $gsm_id]);
        $result = $stmt->fetch();
        $total_out = $result ? $result['total_out'] : 0;
        
        return $total_in - $total_out;
    } catch(Exception $e) {
        return 0;
    }
}

// Test connection
// echo "<!-- Database connected successfully -->";
?>