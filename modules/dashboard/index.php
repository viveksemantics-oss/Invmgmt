<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fix the path - go up 2 levels to reach root
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Get some stats
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM printing_jobs");
    $totalJobs = $stmt->fetch()['count'] ?? 0;
} catch(Exception $e) {
    $totalJobs = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM printing_jobs WHERE status != 'completed'");
    $pendingJobs = $stmt->fetch()['count'] ?? 0;
} catch(Exception $e) {
    $pendingJobs = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM parties WHERE party_type IN ('customer', 'both')");
    $totalCustomers = $stmt->fetch()['count'] ?? 0;
} catch(Exception $e) {
    $totalCustomers = 0;
}

try {
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE payment_status = 'paid'");
    $totalRevenue = $stmt->fetch()['total'] ?? 0;
} catch(Exception $e) {
    $totalRevenue = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <h2>Welcome to <?php echo APP_NAME; ?></h2>
    <hr>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Jobs</h5>
                    <h1><?php echo $totalJobs; ?></h1>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pending Jobs</h5>
                    <h1><?php echo $pendingJobs; ?></h1>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Customers</h5>
                    <h1><?php echo $totalCustomers; ?></h1>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Revenue</h5>
                    <h1>₹<?php echo number_format($totalRevenue, 2); ?></h1>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <strong>System Status:</strong> Running successfully! 
        <a href="<?php echo BASE_URL; ?>test.php" class="alert-link">Run System Test</a>
    </div>

    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>