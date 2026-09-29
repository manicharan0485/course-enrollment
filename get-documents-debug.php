<?php
// Debug version - use this to find the exact error
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

echo json_encode(['debug' => 'Starting script...']) . "\n";

try {
    // Test 1: Check if includes exist
    if (!file_exists('includes/database.php')) {
        throw new Exception('database.php not found');
    }
    if (!file_exists('includes/functions.php')) {
        throw new Exception('functions.php not found');
    }
    
    echo json_encode(['debug' => 'Include files found']) . "\n";
    
    require_once 'includes/database.php';
    require_once 'includes/functions.php';
    
    echo json_encode(['debug' => 'Includes loaded']) . "\n";
    
    // Test 2: Check database connection
    if (!isset($pdo)) {
        throw new Exception('Database connection not established');
    }
    
    echo json_encode(['debug' => 'Database connected']) . "\n";
    
    // Test 3: Check session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo json_encode(['debug' => 'Session started', 'session_id' => session_id()]) . "\n";
    
    // Test 4: Check if user is logged in
    if (!function_exists('isLoggedIn')) {
        throw new Exception('isLoggedIn function not found');
    }
    
    $loggedIn = isLoggedIn();
    echo json_encode(['debug' => 'Login check', 'is_logged_in' => $loggedIn]) . "\n";
    
    if (!$loggedIn) {
        http_response_code(401);
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'] ?? 0;
    echo json_encode(['debug' => 'User ID', 'user_id' => $user_id]) . "\n";
    
    // Test 5: Get application ID
    $application_id = $_GET['application_id'] ?? 0;
    echo json_encode(['debug' => 'Application ID', 'application_id' => $application_id]) . "\n";
    
    if (!$application_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Application ID required']);
        exit;
    }
    
    // Test 6: Check application exists
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND user_id = ?");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch();
    
    echo json_encode(['debug' => 'Application check', 'found' => (bool)$application]) . "\n";
    
    if (!$application) {
        http_response_code(404);
        echo json_encode(['error' => 'Application not found']);
        exit;
    }
    
    // Test 7: Get documents
    $stmt = $pdo->prepare("
        SELECT 
            id,
            document_type,
            file_name,
            file_path,
            uploaded_at
        FROM documents 
        WHERE application_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$application_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['debug' => 'Documents fetched', 'count' => count($documents)]) . "\n";
    
    // Final response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'documents' => $documents,
        'count' => count($documents)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Exception caught',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>