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
$invoice_id = $_GET['invoice_id'] ?? null;

// Get invoice details if invoice_id is provided
$invoice = null;
if ($invoice_id) {
    $stmt = $pdo->prepare("
        SELECT i.*, j.job_no, j.book_name, p.party_name 
        FROM invoices i
        LEFT JOIN printing_jobs j ON i.job_id = j.id
        LEFT JOIN parties p ON j.customer_id = p.id
        WHERE i.id = ?
    ");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_payment'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        try {
        $pdo->beginTransaction();
        
        $invoice_id = $_POST['invoice_id'];
        $amount = $_POST['amount'];
        $payment_date = $_POST['payment_date'];
        $payment_mode = $_POST['payment_mode'];
        $cheque_no = $_POST['cheque_no'] ?? null;
        $bank_name = $_POST['bank_name'] ?? null;
        $transaction_id = $_POST['transaction_id'] ?? null;
        
        // Insert payment
        $stmt = $pdo->prepare("INSERT INTO payments 
            (invoice_id, payment_date, amount, payment_mode, cheque_no, bank_name, transaction_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([$invoice_id, $payment_date, $amount, $payment_mode, $cheque_no, $bank_name, $transaction_id]);
        
        // Update invoice payment status
        $stmt = $pdo->prepare("SELECT SUM(amount) as total_paid FROM payments WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        $total_paid = $stmt->fetch()['total_paid'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT total_amount FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $total_amount = $stmt->fetch()['total_amount'] ?? 0;
        
        if ($total_paid >= $total_amount) {
            $status = 'paid';
        } else if ($total_paid > 0) {
            $status = 'partial';
        } else {
            $status = 'pending';
        }
        
        $stmt = $pdo->prepare("UPDATE invoices SET payment_status = ? WHERE id = ?");
        $stmt->execute([$status, $invoice_id]);
        
        $pdo->commit();
        $success = "Payment recorded successfully!";
        
        // Refresh invoice data
        $stmt = $pdo->prepare("
            SELECT i.*, j.job_no, j.book_name, p.party_name 
            FROM invoices i
            LEFT JOIN printing_jobs j ON i.job_id = j.id
            LEFT JOIN parties p ON j.customer_id = p.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch();
        
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get all payments
$payments = $pdo->query("
    SELECT p.*, i.invoice_no, j.job_no, pa.party_name 
    FROM payments p
    LEFT JOIN invoices i ON p.invoice_id = i.id
    LEFT JOIN printing_jobs j ON i.job_id = j.id
    LEFT JOIN parties pa ON j.customer_id = pa.id
    ORDER BY p.created_at DESC LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - <?php echo APP_NAME; ?></title>
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
        <h2>Payment Management</h2>
        <hr>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($invoice): ?>
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5>Invoice Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Invoice No:</strong> <?php echo $invoice['invoice_no']; ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Job No:</strong> <?php echo $invoice['job_no']; ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Customer:</strong> <?php echo $invoice['party_name']; ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Book Name:</strong> <?php echo $invoice['book_name']; ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Total Amount:</strong> ₹<?php echo number_format($invoice['total_amount'], 2); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                echo $invoice['payment_status'] == 'paid' ? 'success' : 
                                    ($invoice['payment_status'] == 'partial' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($invoice['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Record Payment</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo getCsrfInput(); ?>
                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Amount</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" 
                                           value="<?php echo $invoice['total_amount']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Payment Mode</label>
                                    <select name="payment_mode" class="form-control" required>
                                        <option value="cash">Cash</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Cheque No (if applicable)</label>
                                    <input type="text" name="cheque_no" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Bank Name</label>
                                    <input type="text" name="bank_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Transaction ID</label>
                                    <input type="text" name="transaction_id" class="form-control">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="record_payment" class="btn btn-success">
                            <i class="bi bi-currency-rupee"></i> Record Payment
                        </button>
                        <a href="invoice.php" class="btn btn-secondary">Back to Invoices</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5>Recent Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice No</th>
                                <th>Job No</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Mode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($payments->rowCount() > 0): ?>
                                <?php while($row = $payments->fetch()): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y', strtotime($row['payment_date'])); ?></td>
                                        <td><strong><?php echo $row['invoice_no']; ?></strong></td>
                                        <td><?php echo $row['job_no']; ?></td>
                                        <td><?php echo $row['party_name']; ?></td>
                                        <td><strong>₹<?php echo number_format($row['amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['payment_mode'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No payments recorded</td>
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