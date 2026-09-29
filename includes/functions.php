<?php
/**
 * Helper Functions
 */
require_once __DIR__ . '/../config/config.php';

// ========== AUTHENTICATION ==========

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// ========== CSRF TOKEN FUNCTIONS ==========

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token for forms
 */
function getCSRFToken() {
    return generateCSRFToken();
}

/**
 * Verify CSRF token from POST/GET request
 */
function verifyCSRFToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }
    
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF token as hidden form field
 */
function csrfField() {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// ========== RATE LIMITING ==========

/**
 * Check if user has exceeded login attempts
 */
function isLoginLocked($email) {
    $key = "login_attempt_{$email}";
    
    if (!isset($_SESSION[$key])) {
        return false;
    }
    
    $attempts = $_SESSION[$key];
    
    if ($attempts['count'] >= LOGIN_MAX_ATTEMPTS) {
        // Check if lockout time has expired
        if (time() - $attempts['last_attempt'] < LOGIN_LOCKOUT_TIME) {
            return true;
        } else {
            // Reset lock
            unset($_SESSION[$key]);
            return false;
        }
    }
    
    return false;
}

/**
 * Record failed login attempt
 */
function recordFailedLogin($email) {
    $key = "login_attempt_{$email}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'last_attempt' => time()];
    }
    
    $_SESSION[$key]['count']++;
    $_SESSION[$key]['last_attempt'] = time();
}

/**
 * Clear login attempts for user
 */
function clearLoginAttempts($email) {
    $key = "login_attempt_{$email}";
    unset($_SESSION[$key]);
}

/**
 * Get remaining time for lockout
 */
function getLoginLockoutTimeRemaining($email) {
    $key = "login_attempt_{$email}";
    
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    
    $remaining = LOGIN_LOCKOUT_TIME - (time() - $_SESSION[$key]['last_attempt']);
    return max(0, $remaining);
}

// ========== DATA SANITIZATION ==========

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function sanitizeFilename($filename) {
    // Remove special characters and path traversal attempts
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Remove leading dots
    $filename = ltrim($filename, '.');
    // Limit length
    return substr($filename, 0, 255);
}

// ========== FILE UPLOAD FUNCTIONS ==========

/**
 * Validate file before upload
 */
function validateUploadFile($file) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file provided'];
    }
    
    // Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File upload was incomplete',
            UPLOAD_ERR_NO_FILE => 'No file provided',
            UPLOAD_ERR_NO_TMP_DIR => 'Server error: upload directory',
            UPLOAD_ERR_CANT_WRITE => 'Server error: cannot write file',
            UPLOAD_ERR_EXTENSION => 'File extension not allowed'
        ];
        $message = $errors[$file['error']] ?? 'Unknown upload error';
        return ['success' => false, 'message' => $message];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB limit'];
    }
    
    // Check file type by extension
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, ALLOWED_FILE_TYPES)) {
        return ['success' => false, 'message' => 'File type not allowed. Allowed: ' . implode(', ', ALLOWED_FILE_TYPES)];
    }
    
    // Check MIME type
    if (function_exists('finfo_file')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
            return ['success' => false, 'message' => 'Invalid file content. MIME type not allowed'];
        }
    }
    
    return ['success' => true];
}

/**
 * Generate safe filename
 */
function generateSafeFilename($originalName, $prefix = '') {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $sanitized = preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($originalName, PATHINFO_FILENAME));
    
    if (empty($sanitized)) {
        $sanitized = 'file';
    }
    
    $filename = ($prefix ? $prefix . '_' : '') . substr($sanitized, 0, 50) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    return $filename;
}

/**
 * Upload file safely
 */
function uploadFile($file, $subdirectory = 'documents') {
    // Validate file
    $validation = validateUploadFile($file);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Create directory if needed
    $uploadDir = rtrim(UPLOAD_PATH, '/') . '/' . ltrim($subdirectory, '/');
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory'];
        }
    }
    
    // Generate safe filename
    $fileName = generateSafeFilename($file['name']);
    $filePath = $uploadDir . '/' . $fileName;
    
    // Ensure path doesn't escape upload directory
    $realUploadDir = realpath($uploadDir);
    $realFilePath = realpath(dirname($filePath));
    
    if ($realFilePath === false || strpos($realFilePath, $realUploadDir) !== 0) {
        return ['success' => false, 'message' => 'Invalid file path'];
    }
    
    // Move uploaded file
    if (!@move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    // Set proper permissions
    @chmod($filePath, 0644);
    
    return [
        'success' => true,
        'filename' => $fileName,
        'path' => $filePath,
        'size' => filesize($filePath)
    ];
}

// ========== FORMATTING FUNCTIONS ==========

function formatCurrency($amount) {
    return '£' . number_format($amount, 2);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// ========== FLASH MESSAGES ==========

function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// ========== DATABASE HELPERS ==========

function getUserApplications($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            c.title as course_title,
            c.image_url,
            c.registration_fee,
            c.programme_fee,
            COUNT(DISTINCT d.id) as documents_count,
            SUM(CASE WHEN p.status = 'confirmed' AND p.payment_type = 'registration_fee' THEN 1 ELSE 0 END) as reg_paid,
            SUM(CASE WHEN p.status = 'confirmed' AND p.payment_type = 'programme_fee' THEN 1 ELSE 0 END) as prog_paid
        FROM applications a
        JOIN courses c ON c.id = a.course_id
        LEFT JOIN documents d ON d.application_id = a.id
        LEFT JOIN payments p ON p.application_id = a.id
        WHERE a.user_id = ?
        GROUP BY a.id
        ORDER BY a.submitted_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getEnrolledCourses($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            a.id as application_id,
            a.submitted_at as enrolled_at
        FROM applications a
        JOIN courses c ON c.id = a.course_id
        WHERE a.user_id = ? AND a.status = 'enrolled'
        ORDER BY a.submitted_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function createNotification($userId, $title, $message, $type, $pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $title, $message, $type]);
}

function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'status-pending',
        'documents_uploaded' => 'status-info',
        'payment_pending' => 'status-warning',
        'registration_paid' => 'status-warning',
        'enrolled' => 'status-success',
        'rejected' => 'status-danger'
    ];
    return $classes[$status] ?? 'status-default';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'Pending',
        'documents_uploaded' => 'Documents Uploaded',
        'payment_pending' => 'Payment Pending',
        'registration_paid' => 'Registration Paid',
        'enrolled' => 'Enrolled',
        'rejected' => 'Rejected'
    ];
    return $texts[$status] ?? ucfirst($status);
}

// ========== SESSION MANAGEMENT ==========

function checkLogin(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User'
    ];
}

function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: login.php');
    exit();
}

function flash(string $name, string $message = ''): ?string {
    if ($message) {
        $_SESSION['flash'][$name] = $message;
        return null;
    } elseif (!empty($_SESSION['flash'][$name])) {
        $msg = $_SESSION['flash'][$name];
        unset($_SESSION['flash'][$name]);
        return $msg;
    }
    return null;
}
?>
