<?php
// Common functions used across the application

function getStatusBadge($status) {
    $colors = [
        'pending' => 'warning',
        'plate_making' => 'info',
        'printing' => 'primary',
        'uv_printing' => 'secondary',
        'lamination' => 'dark',
        'completed' => 'success'
    ];
    
    $color = $colors[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

function getJobStatusOptions() {
    return [
        'pending' => 'Pending',
        'plate_making' => 'Plate Making',
        'printing' => 'Printing',
        'uv_printing' => 'UV Printing',
        'lamination' => 'Lamination',
        'completed' => 'Completed'
    ];
}

// CSRF Protection Helpers
function getCsrfToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCsrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken()) . '">';
}
?>