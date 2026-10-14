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

// Get customers for dropdown
$customers = $pdo->query("SELECT id, party_name FROM parties WHERE party_type IN ('customer', 'both') AND is_active = 1 ORDER BY party_name");

// Get sizes for dropdown
$sizes = $pdo->query("SELECT id, size_name FROM paper_sizes WHERE is_active = 1 ORDER BY size_name");

// Get GSM for dropdown
$gsmList = $pdo->query("SELECT id, gsm_value FROM gsm_master WHERE is_active = 1 ORDER BY gsm_value");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        try {
            $pdo->beginTransaction();
        
        // Insert Job
        $job_no = generateJobNo();
        $stmt = $pdo->prepare("INSERT INTO printing_jobs 
            (job_no, po_no, booking_date, customer_id, job_type, book_name, total_quantity, delivery_date, delivery_to, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $job_no,
            $_POST['po_no'],
            $_POST['booking_date'],
            $_POST['customer_id'],
            $_POST['job_type'],
            $_POST['book_name'],
            $_POST['total_quantity'],
            $_POST['delivery_date'],
            $_POST['delivery_to'],
            $_SESSION['user_id']
        ]);
        
        $job_id = $pdo->lastInsertId();
        
        // Calculate totals
        $plate_total = $_POST['plate_count'] * $_POST['plate_rate'];
        $title_total = $_POST['title_print_qty'] * $_POST['title_rate'];
        $total_amount = $plate_total + $title_total;
        $gst_amount = $total_amount * ($_POST['gst_percentage'] / 100);
        $net_amount = $total_amount + $gst_amount;
        
        // Insert Printing Details
        $stmt = $pdo->prepare("INSERT INTO printing_details 
            (job_id, size_id, gsm_id, quantity, min_cap, plate_count, plate_size, plate_rate, plate_total,
             title_print_qty, title_rate, title_total, total_amount, gst_percentage, gst_amount, net_amount,
             tat_required, waste_sheets, total_sheets_consumed, back_flap_apply)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $job_id,
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
            isset($_POST['back_flap_apply']) ? 1 : 0
        ]);
        
        // Update stock out
        $stmt = $pdo->prepare("INSERT INTO paper_stock_out 
            (size_id, gsm_id, quantity_used, remaining_stock, job_id, usage_date, purpose)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $remaining = getCurrentStock($_POST['size_id'], $_POST['gsm_id']) - $_POST['total_sheets_consumed'];
        $stmt->execute([
            $_POST['size_id'],
            $_POST['gsm_id'],
            $_POST['total_sheets_consumed'],
            $remaining,
            $job_id,
            date('Y-m-d'),
            'Printing Job ' . $job_no
        ]);
        
        $pdo->commit();
        $success = "Job created successfully! Job No: $job_no";
        
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
    <title>Create Job - <?php echo APP_NAME; ?></title>
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
                <h2>Create New Printing Job</h2>
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
                                <div class="card-header">
                                    <h5>Job Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label>PO Number</label>
                                        <input type="text" name="po_no" class="form-control" value="PO26-1125-B">
                                    </div>
                                    <div class="mb-3">
                                        <label>Booking Date</label>
                                        <input type="date" name="booking_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label>Customer</label>
                                        <select name="customer_id" class="form-control" required>
                                            <option value="">Select Customer</option>
                                            <?php while($row = $customers->fetch()): ?>
                                                <option value="<?php echo $row['id']; ?>"><?php echo $row['party_name']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Job Type</label>
                                        <select name="job_type" class="form-control">
                                            <option value="new">New</option>
                                            <option value="reprint">Reprint</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Book Name</label>
                                        <input type="text" name="book_name" class="form-control" value="ICSE Book Paper 2">
                                    </div>
                                    <div class="mb-3">
                                        <label>Total Quantity</label>
                                        <input type="number" name="total_quantity" class="form-control" value="2008">
                                    </div>
                                    <div class="mb-3">
                                        <label>Delivery Date</label>
                                        <input type="date" name="delivery_date" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label>Deliver To</label>
                                        <input type="text" name="delivery_to" class="form-control" value="SRG traders">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Printing Details</h5>
                                </div>
                                <div class="card-body">
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
                                        <input type="number" name="quantity" class="form-control" value="2000" id="quantity">
                                    </div>
                                    <div class="mb-3">
                                        <label>Min Cap</label>
                                        <input type="number" name="min_cap" class="form-control" value="2000">
                                    </div>
                                    <div class="mb-3">
                                        <label>Plate Count</label>
                                        <input type="number" name="plate_count" class="form-control" value="8" id="plate_count">
                                    </div>
                                    <div class="mb-3">
                                        <label>Plate Size</label>
                                        <input type="text" name="plate_size" class="form-control" value="4 605x760">
                                    </div>
                                    <div class="mb-3">
                                        <label>Plate Rate</label>
                                        <input type="number" step="0.01" name="plate_rate" class="form-control" value="370" id="plate_rate">
                                    </div>
                                    <div class="mb-3">
                                        <label>Title Print Quantity</label>
                                        <input type="number" name="title_print_qty" class="form-control" value="2000" id="title_qty">
                                    </div>
                                    <div class="mb-3">
                                        <label>Title Rate</label>
                                        <input type="number" step="0.01" name="title_rate" class="form-control" value="0.5" id="title_rate">
                                    </div>
                                    <div class="mb-3">
                                        <label>GST Percentage</label>
                                        <input type="number" step="0.01" name="gst_percentage" class="form-control" value="5" id="gst_percent">
                                    </div>
                                    <div class="mb-3">
                                        <label>Waste Sheets</label>
                                        <input type="number" name="waste_sheets" class="form-control" value="30">
                                    </div>
                                    <div class="mb-3">
                                        <label>Total Sheets Consumed</label>
                                        <input type="number" name="total_sheets_consumed" class="form-control" value="1030">
                                    </div>
                                    <div class="mb-3">
                                        <label>
                                            <input type="checkbox" name="tat_required" value="1"> TAT Required
                                        </label>
                                    </div>
                                    <div class="mb-3">
                                        <label>
                                            <input type="checkbox" name="back_flap_apply" value="1"> Back Flap Apply
                                        </label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-info" onclick="calculateTotals()">
                                            <i class="bi bi-calculator"></i> Calculate
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h5>Summary</h5>
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
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Job
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