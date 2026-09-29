<?php
// NO session_start() here - let database.php/functions.php handle it
require_once 'includes/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please log in']);
    exit;
}

// Check if user is admin
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();

    $isAdmin = ($currentUser && $currentUser['role'] === 'admin');

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Get application ID
$applicationId = intval($_GET['application_id'] ?? 0);
if (!$applicationId) {
    http_response_code(400);
    echo json_encode(['error' => 'Application ID required']);
    exit;
}

// If not admin, verify ownership
if (!$isAdmin) {
    $stmt = $pdo->prepare("SELECT user_id FROM applications WHERE id = ?");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch();

    if (!$app || $app['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
}

// Fetch documents
try {
    $stmt = $pdo->prepare("
        SELECT 
            d.id,
            d.application_id,
            d.document_type,
            d.file_name,
            d.file_path,
            d.file_size,
            d.mime_type,
            d.uploaded_at,
            d.status
        FROM documents d
        WHERE d.application_id = ?
        ORDER BY d.uploaded_at DESC
    ");
    $stmt->execute([$applicationId]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($documents);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>