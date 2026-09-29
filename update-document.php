<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!file_exists('includes/database.php')) {
    die(json_encode(['success' => false, 'error' => 'Configuration file not found']));
}

require_once 'includes/database.php';
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Access denied']));
    }
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Database error']));
}

// Get POST data
$document_id = $_POST['document_id'] ?? null;
$status = $_POST['status'] ?? null;
$reason = $_POST['reason'] ?? null;

if (!$document_id || !$status) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Missing required fields']));
}

// Validate status
if (!in_array($status, ['pending', 'approved', 'rejected'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid status']));
}

try {
    // Check if status column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'status'");
    if ($stmt->rowCount() === 0) {
        die(json_encode(['success' => false, 'error' => 'Status column does not exist in database']));
    }
    
    // Check if verified_by and verified_at columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM documents");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasVerifiedBy = in_array('verified_by', $columns);
    $hasVerifiedAt = in_array('verified_at', $columns);
    $hasRejectionReason = in_array('rejection_reason', $columns);
    
    // Build update query
    $updateFields = ['status = ?'];
    $params = [$status];
    
    if ($hasVerifiedBy) {
        $updateFields[] = 'verified_by = ?';
        $params[] = $_SESSION['user_id'];
    }
    
    if ($hasVerifiedAt) {
        $updateFields[] = 'verified_at = NOW()';
    }
    
    if ($hasRejectionReason) {
        $updateFields[] = 'rejection_reason = ?';
        $params[] = ($status === 'rejected' && $reason) ? $reason : null;
    }
    
    $params[] = $document_id;
    
    $sql = "UPDATE documents SET " . implode(', ', $updateFields) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Get document details for notification
    $stmt = $pdo->prepare("
        SELECT d.*, a.user_id, u.email, u.full_name
        FROM documents d
        JOIN applications a ON d.application_id = a.id
        JOIN users u ON a.user_id = u.id
        WHERE d.id = ?
    ");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "Document {$status} successfully",
        'document_id' => $document_id,
        'status' => $status
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>