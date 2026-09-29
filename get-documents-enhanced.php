<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Function to send error response
function sendError($message, $statusCode = 400, $errorCode = null) {
    $response = ['error' => $message];
    if ($errorCode) {
        $response['error_code'] = $errorCode;
    }
    sendResponse($response, $statusCode);
}

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        sendError('Unauthorized access', 401, 'AUTH_REQUIRED');
    }

    $user_id = $_SESSION['user_id'];
    
    // Get application ID from query string
    $application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;
    
    if ($application_id <= 0) {
        sendError('Invalid application ID', 400, 'INVALID_APP_ID');
    }

    // Verify the application belongs to the logged-in user
    $stmt = $pdo->prepare("
        SELECT a.id, a.status, c.title as course_title 
        FROM applications a
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND a.user_id = ?
    ");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        sendError('Application not found or access denied', 404, 'APP_NOT_FOUND');
    }

    // Get documents for this application
    $stmt = $pdo->prepare("
        SELECT 
            id,
            document_type,
            file_name,
            file_path,
            file_size,
            uploaded_at,
            CASE 
                WHEN file_path IS NOT NULL AND file_path != '' THEN 1
                ELSE 0
            END as is_uploaded
        FROM documents 
        WHERE application_id = ?
        ORDER BY 
            CASE document_type
                WHEN 'passport' THEN 1
                WHEN 'cv' THEN 2
                WHEN 'qualification' THEN 3
                WHEN 'photo' THEN 4
                WHEN 'other' THEN 5
            END,
            uploaded_at DESC
    ");
    $stmt->execute([$application_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format file sizes and dates
    foreach ($documents as &$doc) {
        // Format file size
        if (isset($doc['file_size']) && $doc['file_size'] > 0) {
            $doc['file_size_formatted'] = formatFileSize($doc['file_size']);
        } else {
            $doc['file_size_formatted'] = 'N/A';
        }
        
        // Format upload date
        if ($doc['uploaded_at']) {
            $doc['uploaded_at_formatted'] = date('M d, Y H:i', strtotime($doc['uploaded_at']));
        }
        
        // Add download URL if file exists
        if ($doc['is_uploaded']) {
            $doc['download_url'] = 'download-document.php?id=' . $doc['id'];
        }
        
        // Format document type for display
        $doc['document_type_display'] = ucfirst(str_replace('_', ' ', $doc['document_type']));
    }

    // Get required document types for this course (if you have this feature)
    $requiredDocs = ['passport', 'cv', 'qualification', 'photo'];
    $uploadedTypes = array_column($documents, 'document_type');
    
    $missingDocs = array_diff($requiredDocs, $uploadedTypes);

    // Prepare response
    $response = [
        'success' => true,
        'application' => [
            'id' => $application['id'],
            'status' => $application['status'],
            'course_title' => $application['course_title']
        ],
        'documents' => $documents,
        'statistics' => [
            'total_count' => count($documents),
            'uploaded_count' => count(array_filter($documents, function($doc) {
                return $doc['is_uploaded'] == 1;
            })),
            'required_count' => count($requiredDocs),
            'missing_count' => count($missingDocs),
            'missing_types' => array_values($missingDocs)
        ]
    ];

    sendResponse($response, 200);

} catch (PDOException $e) {
    // Log database errors
    error_log("Database error in get-documents.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    sendError('Database error occurred', 500, 'DB_ERROR');
    
} catch (Exception $e) {
    // Log general errors
    error_log("Error in get-documents.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    sendError('An unexpected error occurred', 500, 'SERVER_ERROR');
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>