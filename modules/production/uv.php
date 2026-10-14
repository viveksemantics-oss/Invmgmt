<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Get jobs for dropdown
$jobs = $pdo->query("SELECT id, job_no, book_name FROM printing_jobs WHERE status != 'completed' ORDER BY created_at DESC");

// Get sizes and GSM
$sizes = $pdo->query("SELECT id, size_name FROM paper_sizes WHERE is_active = 1");
$gsmList = $pdo->query("SELECT id, gsm_value FROM gsm_master WHERE is_active = 1");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO uv_printing 
                (job_id, size_id, gsm_id, quantity, uv_type, rate, total_amount, created_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
            $total_amount = $_POST['quantity'] * $_POST['rate'];
            
            $stmt->execute([
                $_POST['job_id'],
                $_POST['size_id'],
                $_POST['gsm_id'],
                $_POST['quantity'],
                $_POST['uv_type'],
                $_POST['rate'],
                $total_amount,
                $_POST['created_date'],
                $_POST['status']
            ]);
            
            // Update job status
            $stmt = $pdo->prepare("UPDATE printing_jobs SET status = 'uv_printing' WHERE id = ?");
            $stmt->execute([$_POST['job_id']]);
            
            $success = "UV Printing record added successfully!";
        } catch(Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get all UV printing records
$uvRecords = $pdo->query("
    SELECT u.*, j.job_no, j.book_name, ps.size_name, g.gsm_value 
    FROM uv_printing u
    LEFT JOIN printing_jobs j ON u.job_id = j.id
    LEFT JOIN paper_sizes ps ON u.size_id = ps.id
    LEFT JOIN gsm_master g ON u.gsm_id = g.id
    ORDER BY u.created_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UV Printing - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="col-md-10 p-3">
        <h2>UV Printing</h2>
        <hr>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h5>Add UV Printing Record</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo getCsrfInput(); ?>
                            <div class="mb-3">
                                <label>Job</label>
                                <select name="job_id" class="form-control" required>
                                    <option value="">Select Job</option>
                                    <?php while($row = $jobs->fetch()): ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo $row['job_no'] . ' - ' . $row['book_name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Paper Size</label>
                                <select name="size_id" class="form-control" required>
                                    <option value="">Select Size</option>
                                    <?php while($row = $sizes->fetch()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['size_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>GSM</label>
                                <select name="gsm_id" class="form-control" required>
                                    <option value="">Select GSM</option>
                                    <?php while($row = $gsmList->fetch()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['gsm_value']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Quantity</label>
                                <input type="number" name="quantity" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>UV Type</label>
                                <select name="uv_type" class="form-control">
                                    <option value="spot">Spot UV</option>
                                    <option value="full">Full UV</option>
                                    <option value="partial">Partial UV</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Rate</label>
                                <input type="number" step="0.01" name="rate" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Date</label>
                                <input type="date" name="created_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Record</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h5>UV Printing Records</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Job</th>
                                        <th>Size</th>
                                        <th>GSM</th>
                                        <th>Qty</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $uvRecords->fetch()): ?>
                                        <tr>
                                            <td><?php echo $row['job_no']; ?></td>
                                            <td><?php echo $row['size_name']; ?></td>
                                            <td><?php echo $row['gsm_value']; ?></td>
                                            <td><?php echo $row['quantity']; ?></td>
                                            <td><?php echo ucfirst($row['uv_type']); ?></td>
                                            <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $row['status'] == 'completed' ? 'success' : 
                                                        ($row['status'] == 'in_progress' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html> 