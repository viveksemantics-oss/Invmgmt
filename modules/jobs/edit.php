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

// Fetch job and details
$stmt = $pdo->prepare("
    SELECT j.*, jd.size_id, jd.gsm_id, jd.quantity, jd.min_cap, jd.plate_count, jd.plate_size, jd.plate_rate, jd.plate_total,
           jd.title_print_qty, jd.title_rate, jd.title_total, jd.total_amount, jd.gst_percentage, jd.gst_amount, jd.net_amount,
           jd.tat_required, jd.waste_sheets, jd.total_sheets_consumed, jd.back_flap_apply
    FROM printing_jobs j
    LEFT JOIN printing_details jd ON j.id = jd.job_id
    WHERE j.id = ?
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    redirect('index.php');
}

// Get dropdown data
$customers = $pdo->query("SELECT id, party_name FROM parties WHERE party_type IN ('customer', 'both') AND is_active = 1 ORDER BY party_name");
$sizes = $pdo->query("SELECT id, size_name FROM paper_sizes WHERE is_active = 1 ORDER BY size_name");
$gsmList = $pdo->query("SELECT id, gsm_value FROM gsm_master WHERE is_active = 1 ORDER BY gsm_value");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Update printing_jobs
            $stmt = $pdo->prepare("UPDATE printing_jobs SET 
                po_no = ?, booking_date = ?, customer_id = ?, job_type = ?, book_name = ?, total_quantity = ?, 
                delivery_date = ?, delivery_to = ?, status = ?
                WHERE id = ?");
            
            $stmt->execute([
                $_POST['po_no'],
                $_POST['booking_date'],
                $_POST['customer_id'],
                $_POST['job_type'],
                $_POST['book_name'],
                $_POST['total_quantity'],
                $_POST['delivery_date'] ?: null,
                $_POST['delivery_to'],
                $_POST['status'],
                $job_id
            ]);
            
            // Calculate totals
            $plate_total = $_POST['plate_count'] * $_POST['plate_rate'];
            $title_total = $_POST['title_print_qty'] * $_POST['title_rate'];
            $total_amount = $plate_total + $title_total;
            $gst_amount = $total_amount * ($_POST['gst_percentage'] / 100);
            $net_amount = $total_amount + $gst_amount;
            
            // 2. Update printing_details
            $stmt = $pdo->prepare("UPDATE printing_details SET 
                size_id = ?, gsm_id = ?, quantity = ?, min_cap = ?, plate_count = ?, plate_size = ?, 
                plate_rate = ?, plate_total = ?, title_print_qty = ?, title_rate = ?, title_total = ?, 
                total_amount = ?, gst_percentage = ?, gst_amount = ?, net_amount = ?, 
                tat_required = ?, waste_sheets = ?, total_sheets_consumed = ?, back_flap_apply = ?
                WHERE job_id = ?");
            
            $stmt->execute([
                $_POST['size_id'],
                $_POST['gsm_id'],
                $_POST['quantity'],
                $_POST['min_cap'],
                $_POST['plate_count'],
                $_POST['plate_size'],
                $_POST['plate_rate'],
                $plate_total,
                $_POST['title_print_qty'],
                $_POST['title_rate'],
                $title_total,
                $total_amount,
                $_POST['gst_percentage'],
                $gst_amount,
                $net_amount,
                isset($_POST['tat_required']) ? 1 : 0,
                $_POST['waste_sheets'],
                $_POST['total_sheets_consumed'],
                isset($_POST['back_flap_apply']) ? 1 : 0,
                $job_id
            ]);
            
            // 3. Update stock out logic
            // First check if a stock record exists for this job
            $stmt = $pdo->prepare("SELECT id, quantity_used FROM paper_stock_out WHERE job_id = ?");
            $stmt->execute([$job_id]);
            $stock_record = $stmt->fetch();
            
            // Recalculate remaining stock based on difference
            $quantity_diff = $_POST['total_sheets_consumed'] - ($stock_record ? $stock_record['quantity_used'] : 0);
            $current_balance = getCurrentStock($_POST['size_id'], $_POST['gsm_id']);
            $remaining = $current_balance - $quantity_diff;
            
            if ($stock_record) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE paper_stock_out SET 
                    size_id = ?, gsm_id = ?, quantity_used = ?, remaining_stock = ?, usage_date = ?, purpose = ?
                    WHERE job_id = ?");
                $stmt->execute([
                    $_POST['size_id'],
                    $_POST['gsm_id'],
                    $_POST['total_sheets_consumed'],
                    $remaining,
                    $_POST['booking_date'],
                    'Job consumption: ' . $job['job_no'],
                    $job_id
                ]);
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO paper_stock_out 
                    (size_id, gsm_id, quantity_used, remaining_stock, job_id, usage_date, purpose)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['size_id'],
                    $_POST['gsm_id'],
                    $_POST['total_sheets_consumed'],
                    $remaining,
                    $job_id,
                    $_POST['booking_date'],
                    'Job consumption: ' . $job['job_no']
                ]);
            }
            
            $pdo->commit();
            $success = "Job updated successfully!";
            
            // Refresh local job variable
            $stmt = $pdo->prepare("
                SELECT j.*, jd.size_id, jd.gsm_id, jd.quantity, jd.min_cap, jd.plate_count, jd.plate_size, jd.plate_rate, jd.plate_total,
                       jd.title_print_qty, jd.title_rate, jd.title_total, jd.total_amount, jd.gst_percentage, jd.gst_amount, jd.net_amount,
                       jd.tat_required, jd.waste_sheets, jd.total_sheets_consumed, jd.back_flap_apply
                FROM printing_jobs j
                LEFT JOIN printing_details jd ON j.id = jd.job_id
                WHERE j.id = ?
            ");
            $stmt->execute([$job_id]);
            $job = $stmt->fetch();
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .summary-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .summary-row:last-child {
            border-bottom: none;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 1.1em;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <h2>Edit Printing Job: <?php echo htmlspecialchars($job['job_no']); ?></h2>
    <hr>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" id="jobForm">
        <?php echo getCsrfInput(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Job Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">PO Number</label>
                            <input type="text" name="po_no" class="form-control" value="<?php echo htmlspecialchars($job['po_no']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Booking Date <span class="text-danger">*</span></label>
                            <input type="date" name="booking_date" class="form-control" required value="<?php echo htmlspecialchars($job['booking_date']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php 
                                $customers->execute();
                                while($row = $customers->fetch()): 
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo ($job['customer_id'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['party_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Job Type</label>
                            <select name="job_type" class="form-select">
                                <option value="new" <?php echo ($job['job_type'] == 'new') ? 'selected' : ''; ?>>New</option>
                                <option value="reprint" <?php echo ($job['job_type'] == 'reprint') ? 'selected' : ''; ?>>Reprint</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Book Name</label>
                            <input type="text" name="book_name" class="form-control" value="<?php echo htmlspecialchars($job['book_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Quantity</label>
                            <input type="number" name="total_quantity" class="form-control" value="<?php echo htmlspecialchars($job['total_quantity'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Delivery Date</label>
                            <input type="date" name="delivery_date" class="form-control" value="<?php echo htmlspecialchars($job['delivery_date'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deliver To</label>
                            <input type="text" name="delivery_to" class="form-control" value="<?php echo htmlspecialchars($job['delivery_to'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Job Status</label>
                            <select name="status" class="form-select">
                                <?php foreach(getJobStatusOptions() as $val => $lbl): ?>
                                    <option value="<?php echo $val; ?>" <?php echo ($job['status'] == $val) ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Printing Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Paper Size <span class="text-danger">*</span></label>
                            <select name="size_id" class="form-select" required>
                                <option value="">Select Size</option>
                                <?php 
                                $sizes->execute();
                                while($row = $sizes->fetch()): 
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo ($job['size_id'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['size_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">GSM <span class="text-danger">*</span></label>
                            <select name="gsm_id" class="form-select" required>
                                <option value="">Select GSM</option>
                                <?php 
                                $gsmList->execute();
                                while($row = $gsmList->fetch()): 
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo ($job['gsm_id'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['gsm_value']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="<?php echo htmlspecialchars($job['quantity'] ?? '0'); ?>" id="quantity">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Min Cap</label>
                            <input type="number" name="min_cap" class="form-control" value="<?php echo htmlspecialchars($job['min_cap'] ?? '0'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Plate Count</label>
                            <input type="number" name="plate_count" class="form-control" value="<?php echo htmlspecialchars($job['plate_count'] ?? '0'); ?>" id="plate_count">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Plate Size</label>
                            <input type="text" name="plate_size" class="form-control" value="<?php echo htmlspecialchars($job['plate_size'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Plate Rate</label>
                            <input type="number" step="0.01" name="plate_rate" class="form-control" value="<?php echo htmlspecialchars($job['plate_rate'] ?? '0'); ?>" id="plate_rate">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title Print Quantity</label>
                            <input type="number" name="title_print_qty" class="form-control" value="<?php echo htmlspecialchars($job['title_print_qty'] ?? '0'); ?>" id="title_qty">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title Rate</label>
                            <input type="number" step="0.01" name="title_rate" class="form-control" value="<?php echo htmlspecialchars($job['title_rate'] ?? '0'); ?>" id="title_rate">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">GST Percentage</label>
                            <input type="number" step="0.01" name="gst_percentage" class="form-control" value="<?php echo htmlspecialchars($job['gst_percentage'] ?? '5'); ?>" id="gst_percent">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Waste Sheets</label>
                            <input type="number" name="waste_sheets" class="form-control" value="<?php echo htmlspecialchars($job['waste_sheets'] ?? '0'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Sheets Consumed</label>
                            <input type="number" name="total_sheets_consumed" class="form-control" value="<?php echo htmlspecialchars($job['total_sheets_consumed'] ?? '0'); ?>" id="total_sheets_consumed">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="tat_required" class="form-check-input" id="tat_required" value="1" <?php echo ($job['tat_required']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tat_required">TAT Required</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="back_flap_apply" class="form-check-input" id="back_flap_apply" value="1" <?php echo ($job['back_flap_apply']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="back_flap_apply">Back Flap Apply</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="button" class="btn btn-info" onclick="calculateTotals()">
                                <i class="bi bi-calculator"></i> Calculate
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Summary</h5>
                    </div>
                    <div class="card-body summary-card">
                        <div class="summary-row">
                            <span>Plate Total:</span>
                            <span id="plate_total_display">₹0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Title Total:</span>
                            <span id="title_total_display">₹0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Total Amount:</span>
                            <span id="total_amount_display">₹0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>GST Amount:</span>
                            <span id="gst_amount_display">₹0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Net Amount:</span>
                            <span id="net_amount_display">₹0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12 mb-5">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Save Changes
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
    <script>
        function calculateTotals() {
            var plateCount = parseFloat(document.getElementById('plate_count').value) || 0;
            var plateRate = parseFloat(document.getElementById('plate_rate').value) || 0;
            var titleQty = parseFloat(document.getElementById('title_qty').value) || 0;
            var titleRate = parseFloat(document.getElementById('title_rate').value) || 0;
            var gstPercent = parseFloat(document.getElementById('gst_percent').value) || 0;
            
            var plateTotal = plateCount * plateRate;
            var titleTotal = titleQty * titleRate;
            var totalAmount = plateTotal + titleTotal;
            var gstAmount = totalAmount * (gstPercent / 100);
            var netAmount = totalAmount + gstAmount;
            
            document.getElementById('plate_total_display').textContent = '₹' + plateTotal.toFixed(2);
            document.getElementById('title_total_display').textContent = '₹' + titleTotal.toFixed(2);
            document.getElementById('total_amount_display').textContent = '₹' + totalAmount.toFixed(2);
            document.getElementById('gst_amount_display').textContent = '₹' + gstAmount.toFixed(2);
            document.getElementById('net_amount_display').textContent = '₹' + netAmount.toFixed(2);
        }
        
        // Auto calculate on load
        window.onload = function() {
            calculateTotals();
            
            // Auto calculate on change
            document.querySelectorAll('#plate_count, #plate_rate, #title_qty, #title_rate, #gst_percent').forEach(function(element) {
                element.addEventListener('change', calculateTotals);
                element.addEventListener('keyup', calculateTotals);
            });
        };
    </script>
</body>
</html>
