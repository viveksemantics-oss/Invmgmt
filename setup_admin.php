<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

echo "<h1>Admin Setup Script</h1>";
echo "<hr>";

// Check if users table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red'>✗ Users table does not exist!</p>";
        echo "<p>Please create the users table first.</p>";
        exit;
    }
    echo "<p style='color:green'>✓ Users table exists</p>";
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}

// Check if admin exists
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p style='color:green'>✓ Admin user exists</p>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        // Check if password is correct
        $test_password = 'admin123';
        if (password_verify($test_password, $user['password'])) {
            echo "<p style='color:green'>✓ Password is correct!</p>";
        } else {
            echo "<p style='color:orange'>⚠ Password hash doesn't match. Resetting password...</p>";
            // Update password
            $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->execute([$new_hash, 'admin']);
            echo "<p style='color:green'>✓ Password reset to 'admin123'</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠ Admin user not found. Creating...</p>";
        
        // Create admin user
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $hashed_password, 'Administrator', 'admin', 1]);
        
        echo "<p style='color:green'>✓ Admin user created successfully!</p>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin123</strong></p>";
    }
    
    // List all users
    echo "<h3>All Users in Database</h3>";
    $stmt = $pdo->query("SELECT id, username, full_name, role, is_active FROM users");
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Active</th></tr>";
    while($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<a href='login.php'>Go to Login</a>";
?>