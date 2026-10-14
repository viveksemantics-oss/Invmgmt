<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Get date filters
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Revenue data
$revenue_stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COUNT(*) as total_invoices,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as collected,
        COALESCE(SUM(CASE WHEN payment_status != 'paid' THEN total_amount ELSE 0 END), 0) as pending
    FROM invoices 
    WHERE invoice_date BETWEEN ? AND ?
");
$revenue_stmt->execute([$from_date, $to_date]);
$revenue = $revenue_stmt->fetch();

// Payments data
$payment_stmt = $pdo->prepare("
    SELECT 
        payment_mode,
        COUNT(*) as count,
        COALESCE(SUM(amount), 0) as total
    FROM payments p
    LEFT JOIN invoices i ON p.invoice_id = i.id
    WHERE p.payment_date BETWEEN ? AND ?
    GROUP BY payment_mode
");
$payment_stmt->execute([$from_date, $to_date]);
$payments = $payment_stmt->fetchAll();

// Jobs data
$job_stmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM printing_jobs 
    WHERE booking_date BETWEEN ? AND ?
    GROUP BY status
");
$job_stmt->execute([$from_date, $to_date]);
$job_status = $job_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
        @media print {
            .no-print { display: none !important; }
            .col-md-10 { width: 100% !important; }
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="col-md-10 p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Financial Report</h2>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </div>
        </div>
        <hr>
        
        <!-- Filter Form -->
        <div class="card no-print">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-4">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                    </div>
                    <div class="col-md-4" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Update Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h2>₹<?php echo number_format($revenue['total_revenue'], 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Collected</h5>
                        <h2>₹<?php echo number_format($revenue['collected'], 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <h2>₹<?php echo number_format($revenue['pending'], 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total Invoices</h5>
                        <h2><?php echo $revenue['total_invoices']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Payment Modes</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="paymentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Job Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="jobStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Details Table -->
        <div class="card mt-3">
            <div class="card-header">
                <h5>Payment Mode Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Payment Mode</th>
                                <th>Number of Payments</th>
                                <th>Total Amount</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($payments) > 0): ?>
                                <?php foreach($payments as $row): ?>
                                    <tr>
                                        <td><strong><?php echo ucfirst(str_replace('_', ' ', $row['payment_mode'])); ?></strong></td>
                                        <td><?php echo $row['count']; ?></td>
                                        <td>₹<?php echo number_format($row['total'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $percentage = $revenue['collected'] > 0 ? ($row['total'] / $revenue['collected']) * 100 : 0;
                                            echo number_format($percentage, 1) . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No payment data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="font-weight: bold; background: #f8f9fa;">
                                <td>TOTAL</td>
                                <td><?php echo array_sum(array_column($payments, 'count')); ?></td>
                                <td>₹<?php echo number_format($revenue['collected'], 2); ?></td>
                                <td>100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Job Status Table -->
        <div class="card mt-3">
            <div class="card-header">
                <h5>Job Status Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Number of Jobs</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($job_status) > 0): ?>
                                <?php 
                                $total_jobs = array_sum(array_column($job_status, 'count'));
                                foreach($job_status as $row): 
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['status'] == 'completed' ? 'success' : 
                                                    ($row['status'] == 'pending' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $row['count']; ?></td>
                                        <td>
                                            <?php 
                                            $percentage = $total_jobs > 0 ? ($row['count'] / $total_jobs) * 100 : 0;
                                            echo number_format($percentage, 1) . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No job data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="font-weight: bold; background: #f8f9fa;">
                                <td>TOTAL</td>
                                <td><?php echo $total_jobs ?? 0; ?></td>
                                <td>100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Payment Chart
        <?php if(count($payments) > 0): ?>
        var ctx1 = document.getElementById('paymentChart').getContext('2d');
        var paymentChart = new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: [<?php echo "'" . implode("','", array_map(function($row) { return ucfirst(str_replace('_', ' ', $row['payment_mode'])); }, $payments)) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($payments, 'total')); ?>],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>
        
        // Job Status Chart
        <?php if(count($job_status) > 0): ?>
        var ctx2 = document.getElementById('jobStatusChart').getContext('2d');
        var jobStatusChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: [<?php echo "'" . implode("','", array_map(function($row) { return ucfirst(str_replace('_', ' ', $row['status'])); }, $job_status)) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($job_status, 'count')); ?>],
                    backgroundColor: ['#36b9cc', '#f6c23e', '#4e73df', '#1cc88a', '#e74a3b', '#858796'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>