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

// Get all jobs
$jobs = $pdo->query("
    SELECT j.*, p.party_name 
    FROM printing_jobs j
    LEFT JOIN parties p ON j.customer_id = p.id
    ORDER BY j.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Jobs - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
        .badge {
            padding: 5px 12px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center">
                    <h2>All Printing Jobs</h2>
                    <a href="create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New Job
                    </a>
                </div>
                <hr>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Job No</th>
                                        <th>PO No</th>
                                        <th>Customer</th>
                                        <th>Book Name</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($jobs->rowCount() > 0): ?>
                                        <?php while($row = $jobs->fetch()): ?>
                                            <tr>
                                                <td><strong><?php echo $row['job_no']; ?></strong></td>
                                                <td><?php echo $row['po_no']; ?></td>
                                                <td><?php echo $row['party_name']; ?></td>
                                                <td><?php echo $row['book_name']; ?></td>
                                                <td><?php echo $row['total_quantity']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $row['status'] == 'completed' ? 'success' : 
                                                            ($row['status'] == 'pending' ? 'warning' : 'info'); 
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d-m-Y', strtotime($row['booking_date'])); ?></td>
                                                <td>
                                                    <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No jobs found. <a href="create.php">Create your first job</a></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>