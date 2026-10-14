<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('modules/dashboard/index.php');
}

$error = '';
$debug_info = '';

function logLoginAttempt($username, $status, $ip, $details = '') {
    $log_file = __DIR__ . '/login_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] IP: $ip | User: $username | Status: $status | Details: $details" . PHP_EOL;
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    
    // Debug info (remove after testing)
    $debug_info = "Attempting login with username: $username";
    
    try {
        // First check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            $debug_info .= " - User found";
            
            // Check if user is active
            if ($user['is_active'] == 0) {
                $error = 'Account is deactivated. Please contact administrator.';
                logLoginAttempt($username, 'DEACTIVATED', $ip, 'Account is deactivated');
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    $debug_info .= " - Password verified";
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    logLoginAttempt($username, 'SUCCESS', $ip, 'Logged in successfully');
                    redirect('modules/dashboard/index.php');
                } else {
                    $debug_info .= " - Password verification failed";
                    $error = 'Invalid username or password!';
                    logLoginAttempt($username, 'FAILED_PASSWORD', $ip, 'Password verification failed');
                }
            }
        } else {
            $debug_info .= " - User not found";
            $error = 'Invalid username or password!';
            logLoginAttempt($username, 'FAILED_USER_NOT_FOUND', $ip, 'User does not exist');
        }
    } catch(Exception $e) {
        $error = 'Login error: ' . $e->getMessage();
        $debug_info .= " - Exception: " . $e->getMessage();
        logLoginAttempt($username, 'EXCEPTION', $ip, $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .login-card h3 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }
        .login-card .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .login-card .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .login-card .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .login-card .btn-primary {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            font-weight: bold;
            transition: transform 0.3s;
        }
        .login-card .btn-primary:hover {
            transform: scale(1.02);
        }
        .alert {
            margin-top: 15px;
            border-radius: 8px;
        }
        .brand-icon {
            text-align: center;
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
            margin-top: 15px;
            display: <?php echo ($debug_info && $error) ? 'block' : 'none'; ?>;
        }
        .quick-links {
            margin-top: 15px;
            text-align: center;
        }
        .quick-links a {
            margin: 0 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-icon">
            <i class="bi bi-printer"></i>
        </div>
        <h3><?php echo APP_NAME; ?></h3>
        <p class="subtitle">Sign in to your account</p>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required 
                       placeholder="Enter username" value="admin">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required 
                       placeholder="Enter password" value="admin123">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>
        
        <div class="text-center mt-3">
            <small class="text-muted">Default: admin / admin123</small>
        </div>
        
        <div class="quick-links">
            <a href="setup_admin.php" class="btn btn-link btn-sm">Setup Admin</a>
            <a href="test.php" class="btn btn-link btn-sm">System Test</a>
        </div>
        
        <?php if($debug_info): ?>
        <div class="debug-info">
            <strong>Debug:</strong> <?php echo $debug_info; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</body>
</html>