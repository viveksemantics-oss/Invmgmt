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
    <title>Print Job Card: <?php echo htmlspecialchars($job['job_no']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: white;
            font-size: 13px;
            color: black;
        }
        .print-container {
            padding: 20px;
        }
        .header-title {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .table th, .table td {
            padding: 6px 12px;
            border-color: #000 !important;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .print-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Print Header Button -->
        <div class="no-print text-end mb-3">
            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
            <button onclick="window.close()" class="btn btn-secondary">Close</button>
        </div>
        
        <div class="row header-title align-items-center">
            <div class="col-8">
                <h3><?php echo htmlspecialchars(APP_NAME); ?></h3>
                <p class="mb-0 text-muted">Internal Production Job Card</p>
            </div>
            <div class="col-4 text-end">
                <h4>JOB CARD</h4>
                <p class="mb-0"><strong>Job No:</strong> <?php echo htmlspecialchars($job['job_no']); ?></p>
                <p class="mb-0"><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($job['booking_date'])); ?></p>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-6">
                <h5>Customer Information</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">Name</th>
                        <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td><?php echo htmlspecialchars($job['customer_phone'] ?: 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?php echo nl2br(htmlspecialchars($job['customer_address'] ?: 'N/A')); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="col-6">
                <h5>Job Overview</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">PO Number</th>
                        <td><?php echo htmlspecialchars($job['po_no'] ?: 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Book Name</th>
                        <td><strong><?php echo htmlspecialchars($job['book_name'] ?: 'N/A'); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Total Qty</th>
                        <td><?php echo number_format($job['total_quantity']); ?> books</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><?php echo strtoupper($job['status']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-12">
                <h5>Paper & Printing Specifications</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr class="table-light">
                            <th>Paper Size</th>
                            <th>GSM / Type</th>
                            <th>Quantity</th>
                            <th>Min Cap</th>
                            <th>Plate Count</th>
                            <th>Plate Size</th>
                            <th>Waste Allowed</th>
                            <th>Total Sheets Consumed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($job['size_name']); ?></td>
                            <td><?php echo htmlspecialchars($job['gsm_value']); ?></td>
                            <td><?php echo number_format($job['quantity']); ?></td>
                            <td><?php echo number_format($job['min_cap']); ?></td>
                            <td><?php echo number_format($job['plate_count']); ?></td>
                            <td><?php echo htmlspecialchars($job['plate_size']); ?></td>
                            <td><?php echo number_format($job['waste_sheets']); ?></td>
                            <td><strong><?php echo number_format($job['total_sheets_consumed']); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-6 offset-6">
                <h5>Costing Summary</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Plate Making Charge</th>
                        <td class="text-end"><?php echo formatCurrency($job['plate_total']); ?></td>
                    </tr>
                    <tr>
                        <th>Title Print Charge</th>
                        <td class="text-end"><?php echo formatCurrency($job['title_total']); ?></td>
                    </tr>
                    <tr>
                        <th>Subtotal</th>
                        <td class="text-end"><strong><?php echo formatCurrency($job['total_amount']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>GST (<?php echo $job['gst_percentage']; ?>%)</th>
                        <td class="text-end"><?php echo formatCurrency($job['gst_amount']); ?></td>
                    </tr>
                    <tr class="table-dark text-white">
                        <th>Net Total Cost</th>
                        <td class="text-end"><strong><?php echo formatCurrency($job['net_amount']); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="row mt-5">
            <div class="col-4 text-center">
                <hr style="border-top: 1px solid #000; width: 80%; margin: auto;">
                <p class="mt-2">Prepared By: <?php echo htmlspecialchars($job['creator_name']); ?></p>
            </div>
            <div class="col-4 text-center">
                <hr style="border-top: 1px solid #000; width: 80%; margin: auto;">
                <p class="mt-2">Verified By Store Manager</p>
            </div>
            <div class="col-4 text-center">
                <hr style="border-top: 1px solid #000; width: 80%; margin: auto;">
                <p class="mt-2">Authorized Signatory</p>
            </div>
        </div>
    </div>
    
    <script>
        // Trigger print dialog on load
        window.addEventListener('DOMContentLoaded', (event) => {
            window.print();
        });
    </script>
</body>
</html>
