<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Printing Management System - System Test</h1>";
echo "<hr>";

// Check PHP version
echo "<h3>PHP Version</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if PDO is installed
echo "<h3>PDO Extension</h3>";
if (extension_loaded('pdo_mysql')) {
    echo "<p style='color:green'>✓ PDO MySQL Extension is loaded</p>";
} else {
    echo "<p style='color:red'>✗ PDO MySQL Extension is NOT loaded</p>";
}

// Test database connection
echo "<h3>Database Connection</h3>";
try {
    require_once 'config/database.php';
    echo "<p style='color:green'>✓ Database connected successfully</p>";
    
    // Test if tables exist
    $tables = ['users', 'parties', 'paper_sizes', 'gsm_master', 'paper_stock_in', 'printing_jobs', 'printing_details'];
    $missing = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missing[] = $table;
        }
    }
    
    if (empty($missing)) {
        echo "<p style='color:green'>✓ All required tables exist</p>";
    } else {
        echo "<p style='color:orange'>⚠ Missing tables: " . implode(', ', $missing) . "</p>";
        echo "<p>Please run the SQL script to create all tables.</p>";
    }
    
    // Check default user
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        echo "<p style='color:green'>✓ Default admin user exists</p>";
    } else {
        echo "<p style='color:orange'>⚠ Default admin user not found. Please insert default user.</p>";
        echo "<p>Run this SQL: INSERT INTO users (username, password, full_name, role) VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');</p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color:red'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Check directory structure
echo "<h3>Directory Structure</h3>";
$dirs = [
    'config',
    'includes',
    'modules',
    'modules/dashboard',
    'modules/paper',
    'modules/stock',
    'modules/jobs',
    'modules/production',
    'modules/billing',
    'modules/reports',
    'assets',
    'assets/css',
    'assets/js'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "<p style='color:green'>✓ $dir/</p>";
    } else {
        echo "<p style='color:red'>✗ $dir/ - Missing</p>";
    }
}

// Check configuration
echo "<h3>Configuration</h3>";
echo "<p>BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'Not defined') . "</p>";
echo "<p>Current URL: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// Server info
echo "<h3>Server Information</h3>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Operating System: " . PHP_OS . "</p>";

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li><a href='login.php'>Try Login</a></li>";
echo "<li><a href='modules/dashboard/index.php'>Go to Dashboard</a></li>";
echo "</ol>";
?>