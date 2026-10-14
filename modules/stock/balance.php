<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

// API endpoint for fetching balance of a specific size and GSM
if (isset($_GET['api']) && isset($_GET['size_id']) && isset($_GET['gsm_id'])) {
    header('Content-Type: application/json');
    $size_id = intval($_GET['size_id']);
    $gsm_id = intval($_GET['gsm_id']);
    $bal = getCurrentStock($size_id, $gsm_id);
    echo json_encode(['success' => true, 'balance' => $bal]);
    exit();
}

// Get stock balance
$balance = $pdo->query("
    SELECT 
        ps.size_name,
        g.gsm_value,
        COALESCE(SUM(si.quantity), 0) as total_in,
        COALESCE(SUM(so.quantity_used), 0) as total_out,
        COALESCE(SUM(si.quantity), 0) - COALESCE(SUM(so.quantity_used), 0) as balance
    FROM paper_stock_in si
    LEFT JOIN paper_stock_out so ON si.size_id = so.size_id AND si.gsm_id = so.gsm_id
    LEFT JOIN paper_sizes ps ON si.size_id = ps.id
    LEFT JOIN gsm_master g ON si.gsm_id = g.id
    GROUP BY si.size_id, si.gsm_id
    ORDER BY ps.size_name, g.gsm_value
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Balance - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
        .balance-low {
            color: #dc3545;
            font-weight: bold;
        }
        .balance-medium {
            color: #ffc107;
            font-weight: bold;
        }
        .balance-high {
            color: #198754;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="col-md-10 p-3">
        <h2>Stock Balance</h2>
        <hr>
        
        <div class="card">
            <div class="card-header">
                <h5>Current Stock Position</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="stockTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Paper Size</th>
                                <th>GSM</th>
                                <th>Total In</th>
                                <th>Total Out</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            if($balance->rowCount() > 0): 
                            ?>
                                <?php while($row = $balance->fetch()): ?>
                                    <?php 
                                    $balance_class = '';
                                    $status_text = '';
                                    if($row['balance'] <= 0) {
                                        $balance_class = 'balance-low';
                                        $status_text = 'Out of Stock';
                                    } elseif($row['balance'] < 100) {
                                        $balance_class = 'balance-medium';
                                        $status_text = 'Low Stock';
                                    } else {
                                        $balance_class = 'balance-high';
                                        $status_text = 'In Stock';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td><strong><?php echo $row['size_name']; ?></strong></td>
                                        <td><?php echo $row['gsm_value']; ?></td>
                                        <td><?php echo $row['total_in']; ?></td>
                                        <td><?php echo $row['total_out']; ?></td>
                                        <td class="<?php echo $balance_class; ?>"><?php echo $row['balance']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['balance'] <= 0 ? 'danger' : 
                                                    ($row['balance'] < 100 ? 'warning' : 'success'); 
                                            ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No stock data available</td>
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