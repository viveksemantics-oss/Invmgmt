<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Build query
$query = "
    SELECT j.*, p.party_name, pd.net_amount, pd.gst_amount, pd.total_amount
    FROM printing_jobs j
    LEFT JOIN parties p ON j.customer_id = p.id
    LEFT JOIN printing_details pd ON j.id = pd.job_id
    WHERE j.booking_date BETWEEN ? AND ?
";

$params = [$from_date, $to_date];

if ($status) {
    $query .= " AND j.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get summary
$total_jobs = count($jobs);
$total_amount = 0;
$completed = 0;
$pending = 0;

foreach ($jobs as $job) {
    $total_amount += $job['total_amount'] ?? 0;
    if ($job['status'] == 'completed') {
        $completed++;
    } else {
        $pending++;
    }
}

// Get status options for filter
$statusOptions = [
    'pending' => 'Pending',
    'plate_making' => 'Plate Making',
    'printing' => 'Printing',
    'uv_printing' => 'UV Printing',
    'lamination' => 'Lamination',
    'completed' => 'Completed'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Report - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
        @media print {
            .no-print { display: none !important; }
            .col-md-10 { width: 100% !important; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="col-md-10 p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Job Report</h2>
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
                    <div class="col-md-3">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <?php foreach($statusOptions as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $status == $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Filter
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
                        <h5 class="card-title">Total Jobs</h5>
                        <h2><?php echo $total_jobs; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Completed</h5>
                        <h2><?php echo $completed; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <h2><?php echo $pending; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total Amount</h5>
                        <h2>₹<?php echo number_format($total_amount, 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Jobs Table -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Job No</th>
                                <th>Book Name</th>
                                <th>Customer</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($jobs) > 0): ?>
                                <?php foreach($jobs as $row): ?>
                                    <tr>
                                        <td><strong><?php echo $row['job_no']; ?></strong></td>
                                        <td><?php echo $row['book_name']; ?></td>
                                        <td><?php echo $row['party_name']; ?></td>
                                        <td><?php echo $row['total_quantity']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['status'] == 'completed' ? 'success' : 
                                                    ($row['status'] == 'pending' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                            </span>
                                        </td>
                                        <td>₹<?php echo number_format($row['total_amount'] ?? 0, 2); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['booking_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No jobs found for the selected criteria</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>