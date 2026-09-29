<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['id'] ?? 0;

// Verify application belongs to user
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title 
    FROM applications a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->execute([$application_id, $user_id]);
$application = $stmt->fetch();

if (!$application) {
    $_SESSION['error'] = 'Application not found!';
    header('Location: my-applications.php');
    exit;
}

// Get documents
$stmt = $pdo->prepare("
    SELECT 
        id,
        application_id,
        document_type,
        file_name,
        file_path,
        file_size,
        uploaded_at
    FROM documents 
    WHERE application_id = ?
    ORDER BY uploaded_at DESC
");
$stmt->execute([$application_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .documents-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .documents-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #E2E8F0;
        }

        .documents-table {
            width: 100%;
            border-collapse: collapse;
        }

        .documents-table th {
            background: #F8F9FA;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #1A1A1A;
            border-bottom: 2px solid #E2E8F0;
        }

        .documents-table td {
            padding: 1rem;
            border-bottom: 1px solid #E2E8F0;
            color: #4A5568;
        }

        .documents-table tr:hover {
            background: #F8F9FA;
        }

        .doc-type-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #EFF6FF;
            color: #1E40AF;
        }

        .btn-download {
            background: #FF6B3D;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .btn-download:hover {
            background: #E55A2C;
            transform: translateY(-2px);
        }

        .no-documents {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .no-documents i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <span>Indo-Euro Synchronization</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="icon">📊</span><span>Dashboard</span></a></li>
                <li><a href="courses.php"><span class="icon">📚</span><span>Courses</span></a></li>
                <li><a href="my-applications.php" class="active"><span class="icon">📋</span><span>My Applications</span></a></li>
                <li><a href="profile.php"><span class="icon">👤</span><span>Profile</span></a></li>
                <li><a href="logout.php"><span class="icon">🚪</span><span>Logout</span></a></li>
                <li><a href="help.php"><span class="icon">❓</span><span>Help & Support</span></a></li>
            </ul>
        </aside>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</button>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <h2>My Documents</h2>
                <div class="top-bar-actions">
                    <a href="application-detail.php?id=<?php echo $application_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Application
                    </a>
                    <a href="upload-documents.php?id=<?php echo $application_id; ?>" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload More
                    </a>
                </div>
            </header>

            <!-- Course Info -->
            <div class="alert alert-info" style="margin-bottom: 2rem;">
                <i class="fas fa-info-circle"></i>
                <span>
                    <strong>Application for:</strong> <?php echo htmlspecialchars($application['course_title']); ?>
                    <br><strong>Application ID:</strong> #<?php echo $application_id; ?>
                </span>
            </div>

            <!-- Documents Container -->
            <div class="documents-container">
                <div class="documents-header">
                    <h3>Uploaded Documents (<?php echo count($documents); ?>)</h3>
                </div>

                <?php if (count($documents) > 0): ?>
                    <table class="documents-table">
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th>File Name</th>
                                <th>File Size</th>
                                <th>Uploaded At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <span class="doc-type-badge">
                                            <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-file-pdf" style="color: #DC2626; margin-right: 0.5rem;"></i>
                                        <?php echo htmlspecialchars($doc['file_name']); ?>
                                    </td>
                                    <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($doc['uploaded_at'])); ?></td>
                                    <td>
                                        <a href="download-document.php?id=<?php echo $doc['id']; ?>" class="btn-download">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-documents">
                        <i class="fas fa-folder-open"></i>
                        <h3>No Documents Uploaded Yet</h3>
                        <p>You haven't uploaded any documents for this application.</p>
                        <a href="upload-documents.php?id=<?php echo $application_id; ?>" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-upload"></i> Upload Documents
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>