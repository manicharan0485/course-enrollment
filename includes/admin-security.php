<?php
/**
 * Admin Authentication & Security Middleware
 * Ensures admin access and handles CSRF for all admin files
 */

// This should be included at the top of every admin file after requires

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

/**
 * Require Admin Access
 * Checks if user is logged in and has admin role
 */
function requireAdminAccess() {
    // Check if user is logged in
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }

    // Get user role from session
    $user_email = $_SESSION['user_email'] ?? null;
    
    if (!$user_email) {
        header('Location: login.php');
        exit();
    }

    // Check if this user exists and has admin role
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$user_email]);
    $admin = $stmt->fetch();

    // If not found or not an admin, deny access
    if (!$admin || $admin['role'] !== 'admin') {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        header('Location: dashboard.php');
        exit();
    }

    return $admin;
}

/**
 * Generate CSRF token for admin forms
 */
function getAdminCSRFToken() {
    return generateCSRFToken();
}

/**
 * Verify CSRF token in admin requests
 */
function verifyAdminCSRFToken() {
    return verifyCSRFToken();
}

/**
 * Output CSRF token field for admin forms
 */
function adminCSRFField() {
    csrfField();
}
?>
