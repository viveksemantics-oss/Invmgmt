<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

$error = '';
$success = '';

// Generate Invoice
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_invoice'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        try {
        $pdo->beginTransaction();
        
        $job_id = $_POST['job_id'];
        $invoice_date = $_POST['invoice_date'];
        $due_date = $_POST['due_date'];
        
        // Get job details
        $stmt = $pdo->prepare("SELECT * FROM printing_details WHERE job_id = ?");
        $stmt->execute([$job_id]);
        $job_details = $stmt->fetch();
        
        if (!$job_details) {
            throw new Exception("Job details not found");
        }
        
        $invoice_no = generateInvoiceNo();
        $subtotal = $job_details['total_amount'];
        $gst_amount = $job_details['gst_amount'];
        $total_amount = $job_details['net_amount'];
        
        // Insert invoice
        $stmt = $pdo->prepare("INSERT INTO invoices 
            (invoice_no, job_id, invoice_date, due_date, subtotal, gst_amount, total_amount, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([$invoice_no, $job_id, $invoice_date, $due_date, $subtotal, $gst_amount, $total_amount, 'pending']);
        
        $pdo->commit();
        $success = "Invoice generated successfully! Invoice No: $invoice_no";
        
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get all invoices
$invoices = $pdo->query("
    SELECT i.*, j.job_no, j.book_name, p.party_name 
    FROM invoices i
    LEFT JOIN printing_jobs j ON i.job_id = j.id
    LEFT JOIN parties p ON j.customer_id = p.id
    ORDER BY i.created_at DESC
");

// Get completed jobs for invoice generation
$completedJobs = $pdo->query("
    SELECT j.id, j.job_no, j.book_name, p.party_name, pd.net_amount, pd.gst_amount, pd.total_amount
    FROM printing_jobs j
    LEFT JOIN parties p ON j.customer_id = p.id
    LEFT JOIN printing_details pd ON j.id = pd.job_id
    WHERE j.status = 'completed' 
    AND j.id NOT IN (SELECT job_id FROM invoices)
    ORDER BY j.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - <?php echo APP_NAME; ?></title>
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
        <div class="d-flex justify-content-between align-items-center">
            <h2>Invoice Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#invoiceModal">
                <i class="bi bi-plus-circle"></i> Generate Invoice
            </button>
        </div>
        <hr>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Job No</th>
                                <th>Customer</th>
                                <th>Book Name</th>
                                <th>Subtotal</th>
                                <th>GST</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($invoices->rowCount() > 0): ?>
                                <?php while($row = $invoices->fetch()): ?>
                                    <tr>
                                        <td><strong><?php echo $row['invoice_no']; ?></strong></td>
                                        <td><?php echo $row['job_no']; ?></td>
                                        <td><?php echo $row['party_name']; ?></td>
                                        <td><?php echo $row['book_name']; ?></td>
                                        <td>₹<?php echo number_format($row['subtotal'], 2); ?></td>
                                        <td>₹<?php echo number_format($row['gst_amount'], 2); ?></td>
                                        <td><strong>₹<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['payment_status'] == 'paid' ? 'success' : 
                                                    ($row['payment_status'] == 'partial' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($row['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d-m-Y', strtotime($row['invoice_date'])); ?></td>
                                        <td>
                                            <a href="view_invoice.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="payment.php?invoice_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-currency-rupee"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">No invoices found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Generate Invoice Modal -->
    <div class="modal fade" id="invoiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php echo getCsrfInput(); ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Select Completed Job</label>
                            <select name="job_id" class="form-control" required>
                                <option value="">Select Job</option>
                                <?php while($row = $completedJobs->fetch()): ?>
                                    <option value="<?php echo $row['id']; ?>">
                                        <?php echo $row['job_no'] . ' - ' . $row['book_name'] . ' (₹' . number_format($row['net_amount'], 2) . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="generate_invoice" class="btn btn-primary">Generate Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>