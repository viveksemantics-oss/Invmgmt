<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

echo "<h1>Create Admin User</h1>";

// First, check if users table exists, if not create it
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        role ENUM('admin', 'production', 'store', 'accountant') DEFAULT 'production',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color:green'>✓ Users table created/verified</p>";
} catch(Exception $e) {
    echo "<p style='color:red'>Error creating table: " . $e->getMessage() . "</p>";
}

// Delete existing admin if exists
try {
    $pdo->exec("DELETE FROM users WHERE username = 'admin'");
    echo "<p>Removed existing admin user (if any)</p>";
} catch(Exception $e) {
    // Ignore if user doesn't exist
}

// Create new admin user
try {
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute(['admin', $password_hash, 'Administrator', 'admin', 1]);
    
    if ($result) {
        echo "<p style='color:green; font-size: 18px;'>✓ Admin user created successfully!</p>";
        echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3>Login Credentials</h3>";
        echo "<p><strong>Username:</strong> <code style='background: #e9ecef; padding: 5px 10px;'>admin</code></p>";
        echo "<p><strong>Password:</strong> <code style='background: #e9ecef; padding: 5px 10px;'>admin123</code></p>";
        echo "</div>";
        
        // Verify the password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user && password_verify('admin123', $user['password'])) {
            echo "<p style='color:green'>✓ Password verification passed!</p>";
        } else {
            echo "<p style='color:red'>✗ Password verification failed!</p>";
        }
    } else {
        echo "<p style='color:red'>Failed to create admin user</p>";
    }
} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// List all users
echo "<h3>All Users in Database</h3>";
try {
    $stmt = $pdo->query("SELECT id, username, full_name, role, is_active FROM users");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Active</th></tr>";
        while($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['full_name'] . "</td>";
            echo "<td>" . $row['role'] . "</td>";
            echo "<td>" . ($row['is_active'] ? '✓' : '✗') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found</p>";
    }
} catch(Exception $e) {
    echo "<p>Error listing users: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<a href='login.php' class='btn btn-primary'>Go to Login</a>";
echo "&nbsp;&nbsp;";
echo "<a href='test.php' class='btn btn-secondary'>Run System Test</a>";
?>