<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Get stock data
$stockData = $pdo->query("
    SELECT 
        ps.size_name,
        g.gsm_value,
        COALESCE(SUM(si.quantity), 0) as total_in,
        COALESCE(SUM(si.weight_kg), 0) as total_weight,
        COALESCE(SUM(si.total_amount), 0) as total_value,
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
    <title>Stock Report - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
        @media print {
            .no-print { display: none !important; }
            .col-md-10 { width: 100% !important; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="col-md-10 p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Stock Report</h2>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print Report
                </button>
                <button onclick="exportCSV()" class="btn btn-success">
                    <i class="bi bi-file-excel"></i> Export CSV
                </button>
            </div>
        </div>
        <hr>
        
        <div class="card">
            <div class="card-header">
                <h5>Current Stock Position</h5>
                <small>Report Date: <?php echo date('d-m-Y H:i:s'); ?></small>
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
                                <th>Weight (kg)</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            $total_balance = 0;
                            $total_value = 0;
                            if($stockData->rowCount() > 0): 
                            ?>
                                <?php while($row = $stockData->fetch()): 
                                    $total_balance += $row['balance'];
                                    $total_value += $row['total_value'];
                                ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td><strong><?php echo $row['size_name']; ?></strong></td>
                                        <td><?php echo $row['gsm_value']; ?></td>
                                        <td><?php echo $row['total_in']; ?></td>
                                        <td><?php echo $row['total_out']; ?></td>
                                        <td><strong><?php echo $row['balance']; ?></strong></td>
                                        <td><?php echo number_format($row['total_weight'], 2); ?></td>
                                        <td>₹<?php echo number_format($row['total_value'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No stock data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if($stockData->rowCount() > 0): ?>
                            <tfoot>
                                <tr style="font-weight: bold; background: #f8f9fa;">
                                    <td colspan="5" class="text-end">Totals:</td>
                                    <td><?php echo $total_balance; ?></td>
                                    <td></td>
                                    <td>₹<?php echo number_format($total_value, 2); ?></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function exportCSV() {
            var table = document.getElementById('stockTable');
            var csv = [];
            var rows = table.querySelectorAll('tr');
            
            for (var i = 0; i < rows.length; i++) {
                var row = [];
                var cols = rows[i].querySelectorAll('td, th');
                for (var j = 0; j < cols.length; j++) {
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                }
                csv.push(row.join(','));
            }
            
            var csvContent = csv.join('\n');
            var link = document.createElement('a');
            link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent));
            link.setAttribute('download', 'stock_report_' + new Date().toISOString().slice(0,10) + '.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>