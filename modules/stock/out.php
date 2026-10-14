<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Get data for dropdowns
$sizes = $pdo->query("SELECT id, size_name FROM paper_sizes WHERE is_active = 1 ORDER BY size_name");
$gsmList = $pdo->query("SELECT id, gsm_value FROM gsm_master WHERE is_active = 1 ORDER BY gsm_value");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        $size_id = $_POST['size_id'];
        $gsm_id = $_POST['gsm_id'];
        $quantity_used = intval($_POST['quantity_used']);
        $usage_date = $_POST['usage_date'];
        $purpose = trim($_POST['purpose'] ?? 'Manual Stock Out');
        
        if ($quantity_used <= 0) {
            $error = "Quantity must be greater than zero.";
        } else {
            // Check current stock balance
            $current_balance = getCurrentStock($size_id, $gsm_id);
            if ($quantity_used > $current_balance) {
                $error = "Insufficient stock! Current balance is $current_balance sheets.";
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    $remaining = $current_balance - $quantity_used;
                    
                    $stmt = $pdo->prepare("INSERT INTO paper_stock_out 
                        (size_id, gsm_id, quantity_used, remaining_stock, job_id, usage_date, purpose)
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $size_id,
                        $gsm_id,
                        $quantity_used,
                        $remaining,
                        null, // manual stock out has no job_id
                        $usage_date,
                        $purpose
                    ]);
                    
                    $pdo->commit();
                    $success = "Stock out recorded successfully!";
                } catch(Exception $e) {
                    $pdo->rollBack();
                    $error = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// Get stock out entries
$stockOutEntries = $pdo->query("
    SELECT so.*, ps.size_name, g.gsm_value, j.job_no 
    FROM paper_stock_out so
    LEFT JOIN paper_sizes ps ON so.size_id = ps.id
    LEFT JOIN gsm_master g ON so.gsm_id = g.id
    LEFT JOIN printing_jobs j ON so.job_id = j.id
    ORDER BY so.created_at DESC LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-dash-circle"></i> Record Stock Out / Usage</h5>
                </div>
                <div class="card-body">
                    <?php if($error && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?php echo getCsrfInput(); ?>
                        <div class="mb-3">
                            <label class="form-label">Paper Size <span class="text-danger">*</span></label>
                            <select name="size_id" id="size_id" class="form-select" required onchange="checkStockBalance()">
                                <option value="">Select Size</option>
                                <?php while($row = $sizes->fetch()): ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['size_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">GSM Type <span class="text-danger">*</span></label>
                            <select name="gsm_id" id="gsm_id" class="form-select" required onchange="checkStockBalance()">
                                <option value="">Select GSM</option>
                                <?php while($row = $gsmList->fetch()): ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['gsm_value']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted">Available Stock:</small> 
                                <strong id="available_stock" class="text-primary">0</strong> sheets
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Quantity (Sheets) <span class="text-danger">*</span></label>
                            <input type="number" name="quantity_used" class="form-control" required min="1">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="usage_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Purpose / Reason <span class="text-danger">*</span></label>
                            <input type="text" name="purpose" class="form-control" placeholder="e.g. Damaged, Sample, Manual Adjustment" required>
                        </div>
                        
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-dash-circle"></i> Deduct Stock
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-list-task"></i> Recent Stock Out Entries</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Specification</th>
                                    <th>Quantity</th>
                                    <th>Remaining</th>
                                    <th>Associated Job</th>
                                    <th>Purpose</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($stockOutEntries->rowCount() > 0): ?>
                                    <?php while($row = $stockOutEntries->fetch()): ?>
                                        <tr>
                                            <td><?php echo date('d-m-Y', strtotime($row['usage_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['size_name'] . ' / ' . $row['gsm_value'] . ' GSM'); ?></td>
                                            <td class="text-danger"><strong>-<?php echo number_format($row['quantity_used']); ?></strong></td>
                                            <td><?php echo number_format($row['remaining_stock']); ?></td>
                                            <td>
                                                <?php if($row['job_no']): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($row['job_no']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">None (Manual)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($row['purpose']); ?></small></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No stock out transactions recorded</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
    
    <script>
        function checkStockBalance() {
            var sizeId = document.getElementById('size_id').value;
            var gsmId = document.getElementById('gsm_id').value;
            
            if (sizeId && gsmId) {
                // Fetch stock balance via AJAX helper or a direct call
                fetch('../stock/balance.php?api=1&size_id=' + sizeId + '&gsm_id=' + gsmId)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('available_stock').textContent = data.balance;
                    })
                    .catch(err => {
                        console.error('Error fetching stock:', err);
                    });
            } else {
                document.getElementById('available_stock').textContent = '0';
            }
        }
    </script>
</body>
</html>
