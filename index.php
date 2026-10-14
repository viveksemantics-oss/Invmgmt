<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect to dashboard
header('Location: modules/dashboard/index.php');
exit();
?>