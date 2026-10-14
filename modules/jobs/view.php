<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

$job_id = $_GET['id'] ?? null;
if (!$job_id) {
    redirect('index.php');
}

// Fetch job, details, customer info, and creator info
$stmt = $pdo->prepare("
    SELECT j.*, p.party_name as customer_name, p.phone as customer_phone, p.email as customer_email, p.address as customer_address,
           jd.size_id, jd.gsm_id, jd.quantity, jd.min_cap, jd.plate_count, jd.plate_size, jd.plate_rate, jd.plate_total,
           jd.title_print_qty, jd.title_rate, jd.title_total, jd.total_amount, jd.gst_percentage, jd.gst_amount, jd.net_amount,
           jd.tat_required, jd.waste_sheets, jd.total_sheets_consumed, jd.back_flap_apply,
           ps.size_name, g.gsm_value, u.full_name as creator_name
    FROM printing_jobs j
    LEFT JOIN printing_details jd ON j.id = jd.job_id
    LEFT JOIN parties p ON j.customer_id = p.id
    LEFT JOIN paper_sizes ps ON jd.size_id = ps.id
    LEFT JOIN gsm_master g ON jd.gsm_id = g.id
    LEFT JOIN users u ON j.created_by = u.id
    WHERE j.id = ?
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Card Details: <?php echo htmlspecialchars($job['job_no']); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center">
        <h2>Job Card: <?php echo htmlspecialchars($job['job_no']); ?></h2>
        <div>
            <a href="print.php?id=<?php echo $job['id']; ?>" target="_blank" class="btn btn-secondary">
                <i class="bi bi-printer"></i> Print Job Card
            </a>
            <a href="edit.php?id=<?php echo $job['id']; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit Job
            </a>
            <a href="index.php" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Back to All Jobs
            </a>
        </div>
    </div>
    <hr>
    
    <div class="row">
        <!-- Left Column: Job Details & Client Info -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> General Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Job Number:</th>
                            <td><strong class="text-primary"><?php echo htmlspecialchars($job['job_no']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>PO Number:</th>
                            <td><?php echo htmlspecialchars($job['po_no'] ?: 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Booking Date:</th>
                            <td><?php echo date('d-m-Y', strtotime($job['booking_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Job Type:</th>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($job['job_type']); ?></span></td>
                        </tr>
                        <tr>
                            <th>Book Name:</th>
                            <td><strong><?php echo htmlspecialchars($job['book_name'] ?: 'N/A'); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Total Quantity (Books):</th>
                            <td><?php echo number_format($job['total_quantity']); ?></td>
                        </tr>
                        <tr>
                            <th>Delivery Date:</th>
                            <td><?php echo $job['delivery_date'] ? date('d-m-Y', strtotime($job['delivery_date'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Deliver To:</th>
                            <td><?php echo htmlspecialchars($job['delivery_to'] ?: 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td><?php echo getStatusBadge($job['status']); ?></td>
                        </tr>
                        <tr>
                            <th>Created By:</th>
                            <td><?php echo htmlspecialchars($job['creator_name']); ?> (<?php echo date('d-m-Y H:i', strtotime($job['created_at'])); ?>)</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Customer / Client Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Customer Name:</th>
                            <td><strong><?php echo htmlspecialchars($job['customer_name']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td><?php echo htmlspecialchars($job['customer_phone'] ?: 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($job['customer_email'] ?: 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Billing/Shipping Address:</th>
                            <td><?php echo nl2br(htmlspecialchars($job['customer_address'] ?: 'N/A')); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Paper Specs & Costing Summary -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-journal-text"></i> Paper & Printing Specifications</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Paper Size:</th>
                            <td><?php echo htmlspecialchars($job['size_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Paper GSM / Type:</th>
                            <td><?php echo htmlspecialchars($job['gsm_value']); ?></td>
                        </tr>
                        <tr>
                            <th>Target Print Qty:</th>
                            <td><?php echo number_format($job['quantity']); ?></td>
                        </tr>
                        <tr>
                            <th>Min Cap:</th>
                            <td><?php echo number_format($job['min_cap']); ?></td>
                        </tr>
                        <tr>
                            <th>Plate Count:</th>
                            <td><?php echo number_format($job['plate_count']); ?></td>
                        </tr>
                        <tr>
                            <th>Plate Size:</th>
                            <td><?php echo htmlspecialchars($job['plate_size']); ?></td>
                        </tr>
                        <tr>
                            <th>Waste Sheets Allowed:</th>
                            <td><?php echo number_format($job['waste_sheets']); ?> sheets</td>
                        </tr>
                        <tr>
                            <th>Total Sheets Consumed:</th>
                            <td><strong><?php echo number_format($job['total_sheets_consumed']); ?></strong> sheets</td>
                        </tr>
                        <tr>
                            <th>Back Flap Apply:</th>
                            <td><?php echo $job['back_flap_apply'] ? '<span class="text-success">Yes</span>' : 'No'; ?></td>
                        </tr>
                        <tr>
                            <th>TAT Required:</th>
                            <td><?php echo $job['tat_required'] ? '<span class="text-success">Yes</span>' : 'No'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-currency-rupee"></i> Costing & Pricing Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Plate Making (<?php echo $job['plate_count']; ?> plates @ <?php echo formatCurrency($job['plate_rate']); ?>):</th>
                            <td class="text-end"><?php echo formatCurrency($job['plate_total']); ?></td>
                        </tr>
                        <tr>
                            <th>Title Print (<?php echo number_format($job['title_print_qty']); ?> pages @ <?php echo formatCurrency($job['title_rate']); ?>):</th>
                            <td class="text-end"><?php echo formatCurrency($job['title_total']); ?></td>
                        </tr>
                        <tr class="table-info">
                            <th>Subtotal:</th>
                            <td class="text-end"><strong><?php echo formatCurrency($job['total_amount']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>GST (<?php echo $job['gst_percentage']; ?>%):</th>
                            <td class="text-end"><?php echo formatCurrency($job['gst_amount']); ?></td>
                        </tr>
                        <tr class="table-success" style="font-size: 1.1em;">
                            <th>Net Total Cost:</th>
                            <td class="text-end"><strong><?php echo formatCurrency($job['net_amount']); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
