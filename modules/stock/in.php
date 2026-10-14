<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Get data for dropdowns
$parties = $pdo->query("SELECT id, party_name FROM parties WHERE party_type IN ('supplier', 'both') AND is_active = 1 ORDER BY party_name");
$sizes = $pdo->query("SELECT id, size_name FROM paper_sizes WHERE is_active = 1 ORDER BY size_name");
$gsmList = $pdo->query("SELECT id, gsm_value FROM gsm_master WHERE is_active = 1 ORDER BY gsm_value");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO paper_stock_in 
            (party_id, size_id, gsm_id, quantity, weight_kg, rate_per_unit, total_amount, invoice_no, receipt_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $total_amount = $_POST['quantity'] * $_POST['rate_per_unit'];
        
        $stmt->execute([
            $_POST['party_id'],
            $_POST['size_id'],
            $_POST['gsm_id'],
            $_POST['quantity'],
            $_POST['weight_kg'],
            $_POST['rate_per_unit'],
            $total_amount,
            $_POST['invoice_no'],
            $_POST['receipt_date']
        ]);
        
        $success = "Stock added successfully!";
        } catch(Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get stock entries
$stockEntries = $pdo->query("
    SELECT si.*, p.party_name, ps.size_name, g.gsm_value 
    FROM paper_stock_in si
    LEFT JOIN parties p ON si.party_id = p.id
    LEFT JOIN paper_sizes ps ON si.size_id = ps.id
    LEFT JOIN gsm_master g ON si.gsm_id = g.id
    ORDER BY si.created_at DESC LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In - <?php echo APP_NAME; ?></title>
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
        <h2>Stock In</h2>
        <hr>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Add Stock</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo getCsrfInput(); ?>
                            <div class="mb-3">
                                <label>Supplier</label>
                                <select name="party_id" class="form-control" required>
                                    <option value="">Select Supplier</option>
                                    <?php while($row = $parties->fetch()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['party_name']; ?></option>
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
                                <label>Weight (kg)</label>
                                <input type="number" step="0.01" name="weight_kg" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Rate Per Unit</label>
                                <input type="number" step="0.01" name="rate_per_unit" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Invoice No</label>
                                <input type="text" name="invoice_no" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Receipt Date</label>
                                <input type="date" name="receipt_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Add Stock</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Stock Entries</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Size</th>
                                        <th>GSM</th>
                                        <th>Qty</th>
                                        <th>Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $stockEntries->fetch()): ?>
                                        <tr>
                                            <td><?php echo date('d-m-Y', strtotime($row['receipt_date'])); ?></td>
                                            <td><?php echo $row['size_name']; ?></td>
                                            <td><?php echo $row['gsm_value']; ?></td>
                                            <td><?php echo $row['quantity']; ?></td>
                                            <td>₹<?php echo number_format($row['rate_per_unit'], 2); ?></td>
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