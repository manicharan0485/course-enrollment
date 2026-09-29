<?php
/**
 * Configuration File - DO NOT COMMIT TO VERSION CONTROL
 */

// Environment variables are injected directly by Docker Compose (or the CI runner).
// (require_once __DIR__ . '/load-env.php'; — removed, no longer needed)

// Define constants from environment variables
define('SITE_NAME', getenv('SITE_NAME') ?: 'Course Enrollment System');
define('BASE_URL', getenv('APP_URL') ?: 'http://localhost/course-enrollment/');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Database Configuration (from environment variables)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'course_enrollment');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Security Configuration
define('PASSWORD_MIN_LENGTH', getenv('PASSWORD_MIN_LENGTH') ?: 12);
define('CSRF_TOKEN_LENGTH', getenv('CSRF_TOKEN_LENGTH') ?: 32);
define('LOGIN_MAX_ATTEMPTS', getenv('LOGIN_MAX_ATTEMPTS') ?: 5);
define('LOGIN_LOCKOUT_TIME', getenv('LOGIN_LOCKOUT_TIME') ?: 900); // 15 minutes

// File Upload Configuration
define('MAX_FILE_SIZE', getenv('MAX_FILE_SIZE') ?: 5242880); // 5MB
define('UPLOAD_PATH', getenv('UPLOAD_PATH') ?: __DIR__ . '/../uploads/');
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png'
]);

// Session Configuration - MUST be set BEFORE session_start() in calling scripts
// Uncomment and configure if needed:
// session_save_path(sys_get_temp_dir());
// ini_set('session.gc_maxlifetime', getenv('SESSION_TIMEOUT') ?: 3600);
// ini_set('session.cookie_lifetime', getenv('SESSION_TIMEOUT') ?: 3600);
// ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_secure', APP_ENV === 'production' ? 1 : 0);
// ini_set('session.cookie_samesite', 'Lax');

// Error Handling - Don't expose errors in production
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection using PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
} catch (PDOException $e) {
    if (APP_ENV === 'production') {
        die("Database connection error. Please contact support.");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}

if (!is_dir(UPLOAD_PATH . 'documents/')) {
    @mkdir(UPLOAD_PATH . 'documents/', 0755, true);
}

if (!is_dir(UPLOAD_PATH . 'payments/')) {
    @mkdir(UPLOAD_PATH . 'payments/', 0755, true);
}
?>
