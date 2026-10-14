<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

$invoice_id = $_GET['id'] ?? null;
if (!$invoice_id) {
    redirect('invoice.php');
}

// Fetch invoice and associated job details
$stmt = $pdo->prepare("
    SELECT i.*, j.job_no, j.book_name, j.total_quantity, j.booking_date as job_date,
           p.party_name as customer_name, p.phone as customer_phone, p.email as customer_email, p.address as customer_address, p.gst_no as customer_gst,
           jd.plate_count, jd.plate_rate, jd.plate_total, jd.title_print_qty, jd.title_rate, jd.title_total, jd.gst_percentage
    FROM invoices i
    LEFT JOIN printing_jobs j ON i.job_id = j.id
    LEFT JOIN printing_details jd ON j.id = jd.job_id
    LEFT JOIN parties p ON j.customer_id = p.id
    WHERE i.id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    redirect('invoice.php');
}

// Fetch payments recorded for this invoice
$stmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC");
$stmt->execute([$invoice_id]);
$payments = $stmt->fetchAll();

$total_paid = 0;
foreach ($payments as $payment) {
    $total_paid += $payment['amount'];
}
$balance_due = $invoice['total_amount'] - $total_paid;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice: <?php echo htmlspecialchars($invoice['invoice_no']); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
        
        /* Invoice Print Styling */
        @media print {
            .no-print {
                display: none !important;
            }
            .sidebar, nav {
                display: none !important;
            }
            .col-md-10 {
                width: 100% !important;
                flex: 0 0 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .card-header {
                background: none !important;
                color: black !important;
                border-bottom: 2px solid #000 !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .table td, .table th {
                border-color: #000 !important;
            }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center no-print">
        <h2>Invoice Details: <?php echo htmlspecialchars($invoice['invoice_no']); ?></h2>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Invoice
            </button>
            <?php if ($invoice['payment_status'] != 'paid'): ?>
                <a href="payment.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-success">
                    <i class="bi bi-currency-rupee"></i> Record Payment
                </a>
            <?php endif; ?>
            <a href="invoice.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Invoices
            </a>
        </div>
    </div>
    <hr class="no-print">
    
    <div class="card my-3">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">INVOICE</h4>
            <span class="badge bg-<?php 
                echo $invoice['payment_status'] == 'paid' ? 'success' : 
                    ($invoice['payment_status'] == 'partial' ? 'warning' : 'danger'); 
            ?> font-monospace" style="font-size: 1.1em;">
                Status: <?php echo strtoupper($invoice['payment_status']); ?>
            </span>
        </div>
        <div class="card-body">
            <!-- Company Info & Invoice Metadata -->
            <div class="row mb-4">
                <div class="col-sm-6">
                    <h5>From:</h5>
                    <h4><strong><?php echo htmlspecialchars(APP_NAME); ?></strong></h4>
                    <p class="mb-0">Industrial Estate, Phase III</p>
                    <p class="mb-0">New Delhi - 110020</p>
                    <p class="mb-0"><strong>Email:</strong> billing@printingcorp.com</p>
                    <p class="mb-0"><strong>GSTIN:</strong> 07AAAAA1111A1Z1</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <h5>Invoice Info:</h5>
                    <h3 class="text-primary font-monospace"><strong>#<?php echo htmlspecialchars($invoice['invoice_no']); ?></strong></h3>
                    <p class="mb-1"><strong>Invoice Date:</strong> <?php echo date('d-m-Y', strtotime($invoice['invoice_date'])); ?></p>
                    <p class="mb-1"><strong>Due Date:</strong> <?php echo date('d-m-Y', strtotime($invoice['due_date'])); ?></p>
                    <p class="mb-0"><strong>Associated Job No:</strong> <?php echo htmlspecialchars($invoice['job_no']); ?></p>
                </div>
            </div>
            
            <!-- Customer Information -->
            <div class="row mb-4 border-top pt-3">
                <div class="col-sm-6">
                    <h5>To:</h5>
                    <h4><strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong></h4>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($invoice['customer_address'] ?: '')); ?></p>
                    <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($invoice['customer_phone'] ?: 'N/A'); ?></p>
                    <p class="mb-0"><strong>Email:</strong> <?php echo htmlspecialchars($invoice['customer_email'] ?: 'N/A'); ?></p>
                    <p class="mb-0"><strong>GSTIN:</strong> <?php echo htmlspecialchars($invoice['customer_gst'] ?: 'N/A'); ?></p>
                </div>
            </div>
            
            <!-- Invoice Details Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr class="table-dark">
                            <th>#</th>
                            <th>Description / Specifications</th>
                            <th class="text-end" width="15%">Quantity</th>
                            <th class="text-end" width="15%">Rate</th>
                            <th class="text-end" width="20%">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>
                                <strong>Plate Making Services</strong><br>
                                <small class="text-muted">Count: <?php echo $invoice['plate_count']; ?> plates</small>
                            </td>
                            <td class="text-end"><?php echo $invoice['plate_count']; ?></td>
                            <td class="text-end"><?php echo formatCurrency($invoice['plate_rate']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($invoice['plate_total']); ?></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>
                                <strong>Title Printing (Cover page)</strong><br>
                                <small class="text-muted">Book Name: <?php echo htmlspecialchars($invoice['book_name']); ?></small>
                            </td>
                            <td class="text-end"><?php echo number_format($invoice['title_print_qty']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($invoice['title_rate']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($invoice['title_total']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Totals & Balances -->
            <div class="row">
                <div class="col-sm-6">
                    <h5>Payment History:</h5>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr class="table-secondary">
                                <th>Date</th>
                                <th>Mode</th>
                                <th>Ref/Cheque No</th>
                                <th class="text-end">Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($payments) > 0): ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><?php echo ucfirst($payment['payment_mode']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['cheque_no'] ?: $payment['transaction_id'] ?: 'N/A'); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($payment['amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No payments recorded</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <table class="table table-borderless">
                        <tr>
                            <th class="text-end" width="60%">Subtotal:</th>
                            <td><?php echo formatCurrency($invoice['subtotal']); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end">GST (<?php echo $invoice['gst_percentage']; ?>%):</th>
                            <td><?php echo formatCurrency($invoice['gst_amount']); ?></td>
                        </tr>
                        <tr class="border-top" style="font-size: 1.2em;">
                            <th class="text-end">Grand Total:</th>
                            <td><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></td>
                        </tr>
                        <tr class="text-success">
                            <th class="text-end">Total Paid:</th>
                            <td><strong><?php echo formatCurrency($total_paid); ?></strong></td>
                        </tr>
                        <tr class="text-danger border-top">
                            <th class="text-end">Balance Due:</th>
                            <td><strong><?php echo formatCurrency($balance_due); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="row mt-5 border-top pt-4">
                <div class="col-12 text-center text-muted">
                    <p>Thank you for your business! Please pay within 15 days of invoice date.</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
