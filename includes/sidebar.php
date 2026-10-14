<?php if(isLoggedIn()): ?>
<div class="col-md-2 p-0 sidebar">
    <div class="list-group list-group-flush">
        <a href="<?php echo BASE_URL; ?>modules/dashboard/" class="list-group-item list-group-item-action <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        
        <!-- Paper Master with working dropdown -->
        <a href="#paperMenu" class="list-group-item list-group-item-action <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'paper' ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" role="button" aria-expanded="<?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'paper' ? 'true' : 'false'; ?>">
            <i class="bi bi-file-text"></i> Paper Master <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div id="paperMenu" class="collapse <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'paper' ? 'show' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>modules/paper/sizes.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'sizes.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-right"></i> Paper Sizes
            </a>
            <a href="<?php echo BASE_URL; ?>modules/paper/gsm.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'gsm.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-right"></i> GSM Types
            </a>
            <a href="<?php echo BASE_URL; ?>modules/paper/parties.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'parties.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-right"></i> Parties
            </a>
        </div>
        
        <!-- Stock Management with working dropdown -->
        <a href="#stockMenu" class="list-group-item list-group-item-action <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'stock' ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" role="button" aria-expanded="<?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'stock' ? 'true' : 'false'; ?>">
            <i class="bi bi-box"></i> Stock Management <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div id="stockMenu" class="collapse <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'stock' ? 'show' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>modules/stock/in.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'in.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-right"></i> Stock In
            </a>
            <a href="<?php echo BASE_URL; ?>modules/stock/out.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'out.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-right"></i> Stock Out
            </a>
            <a href="<?php echo BASE_URL; ?>modules/stock/balance.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'balance.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-right"></i> Stock Balance
            </a>
        </div>
        
        <!-- Jobs with working dropdown -->
        <a href="#jobMenu" class="list-group-item list-group-item-action <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'jobs' ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" role="button" aria-expanded="<?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'jobs' ? 'true' : 'false'; ?>">
            <i class="bi bi-file-earmark-text"></i> Jobs <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div id="jobMenu" class="collapse <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'jobs' ? 'show' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>modules/jobs/create.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'create.php' ? 'active' : ''; ?>">
                <i class="bi bi-plus-circle"></i> New Job
            </a>
            <a href="<?php echo BASE_URL; ?>modules/jobs/index.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="bi bi-list"></i> All Jobs
            </a>
        </div>
        
        <!-- Production with working dropdown -->
        <a href="#prodMenu" class="list-group-item list-group-item-action <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'production' ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" role="button" aria-expanded="<?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'production' ? 'true' : 'false'; ?>">
            <i class="bi bi-gear"></i> Production <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div id="prodMenu" class="collapse <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'production' ? 'show' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>modules/production/plate.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'plate.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-right"></i> Plate Making
            </a>
            <a href="<?php echo BASE_URL; ?>modules/production/uv.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'uv.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-right"></i> UV Printing
            </a>
            <a href="<?php echo BASE_URL; ?>modules/production/lamination.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'lamination.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-right"></i> Lamination
            </a>
        </div>
        
        <!-- Billing with working dropdown -->
        <a href="#billMenu" class="list-group-item list-group-item-action <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'billing' ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" role="button" aria-expanded="<?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'billing' ? 'true' : 'false'; ?>">
            <i class="bi bi-currency-rupee"></i> Billing <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div id="billMenu" class="collapse <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'billing' ? 'show' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>modules/billing/invoice.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'invoice.php' ? 'active' : ''; ?>">
                <i class="bi bi-receipt"></i> Invoices
            </a>
            <a href="<?php echo BASE_URL; ?>modules/billing/payment.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">
                <i class="bi bi-credit-card"></i> Payments
            </a>
        </div>
        
        <!-- Reports with working dropdown -->
        <a href="#reportMenu" class="list-group-item list-group-item-action <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'reports' ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" role="button" aria-expanded="<?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'reports' ? 'true' : 'false'; ?>">
            <i class="bi bi-bar-chart"></i> Reports <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div id="reportMenu" class="collapse <?php echo basename(dirname($_SERVER['PHP_SELF'])) == 'reports' ? 'show' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>modules/reports/stock_report.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'stock_report.php' ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> Stock Report
            </a>
            <a href="<?php echo BASE_URL; ?>modules/reports/job_report.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'job_report.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-text"></i> Job Report
            </a>
            <a href="<?php echo BASE_URL; ?>modules/reports/financial_report.php" class="list-group-item list-group-item-action ps-4 <?php echo basename($_SERVER['PHP_SELF']) == 'financial_report.php' ? 'active' : ''; ?>">
                <i class="bi bi-graph-up"></i> Financial Report
            </a>
        </div>
    </div>
</div>
<div class="col-md-10 p-3">
<?php else: ?>
<div class="col-md-12 p-3">
<?php endif; ?>