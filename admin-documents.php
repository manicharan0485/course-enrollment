<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';


// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if logged-in user is an admin (check users table with role='admin')
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Check if this user exists in users table and has admin role
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$admin = $stmt->fetch();

// If not found in users table or not an admin, redirect to regular dashboard
if (!$admin || $admin['role'] !== 'admin') {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    header('Location: dashboard.php');
    exit;
}

// Update last login time for admin
try {
    $stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$admin['id']]);
} catch (PDOException $e) {
    // Ignore if column doesn't exist
}

$admin_name = $admin['full_name'] ?? $_SESSION['user_name'];

// Get search parameters
$search = $_GET['search'] ?? '';
$course_filter = $_GET['course'] ?? '';

// First, check which date column exists in applications table
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM applications");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Determine which date column to use
    $dateColumn = 'created_at'; // default
    if (in_array('applied_at', $columns)) {
        $dateColumn = 'applied_at';
    } elseif (in_array('submission_date', $columns)) {
        $dateColumn = 'submission_date';
    } elseif (in_array('created_at', $columns)) {
        $dateColumn = 'created_at';
    } elseif (in_array('application_date', $columns)) {
        $dateColumn = 'application_date';
    }
} catch (PDOException $e) {
    $dateColumn = 'created_at'; // fallback
}

// Build query to get applications with user and course details
try {
    $sql = "
        SELECT 
            a.id as application_id,
            a.user_id,
            a.status,
            a.$dateColumn as application_date,
            u.full_name,
            u.email,
            c.title as course_title,
            c.id as course_id,
            COUNT(DISTINCT d.id) as document_count
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN courses c ON a.course_id = c.id
        LEFT JOIN documents d ON a.id = d.application_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR c.title LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($course_filter)) {
        $sql .= " AND c.id = ?";
        $params[] = $course_filter;
    }
    
    $sql .= " GROUP BY a.id, a.user_id, a.status, a.$dateColumn, u.full_name, u.email, c.title, c.id";
    $sql .= " ORDER BY a.$dateColumn DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();
    
    // Get statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT a.id) as total_applications,
            COUNT(DISTINCT d.id) as total_documents,
            COUNT(DISTINCT CASE WHEN d.id IS NOT NULL THEN a.id END) as applications_with_docs
        FROM applications a
        LEFT JOIN documents d ON a.id = d.application_id
    ");
    $stats = $stmt->fetch();
    
    // Get all courses for filter
    $stmt = $pdo->query("SELECT id, title FROM courses ORDER BY title");
    $courses = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .document-count {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            font-weight: 600;
        }

        .document-count.complete {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .document-count.incomplete {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        /* Modal Styles - Dark Theme Admin */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            padding: 2rem;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h2 {
            margin: 0;
            color: #fff;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-modal {
            background: rgba(239, 68, 68, 0.1);
            border: none;
            color: #ef4444;
            font-size: 1.5rem;
            cursor: pointer;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .close-modal:hover {
            background: #ef4444;
            color: white;
            transform: rotate(90deg);
        }

        .user-info-card {
            background: rgba(255, 255, 255, 0.03);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #3b82f6;
        }

        .user-info-card h4 {
            margin: 0 0 1rem 0;
            color: #fff;
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .user-info-item {
            display: flex;
            flex-direction: column;
        }

        .user-info-label {
            font-size: 0.85rem;
            color: #94a3b8;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .user-info-value {
            font-size: 1rem;
            color: #fff;
            font-weight: 600;
        }

        .documents-list {
            display: grid;
            gap: 1.5rem;
        }

        .document-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .document-card:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .document-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .document-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
        }

        .document-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .document-info {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
        }

        .document-info-item {
            display: flex;
            flex-direction: column;
        }

        .document-info-item label {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }

        .document-info-item span {
            font-weight: 600;
            color: #fff;
        }

        .document-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-view, .btn-download {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-view {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(34, 197, 94, 0.5);
            color: white;
        }

        .btn-download {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(59, 130, 246, 0.5);
            color: white;
        }

        .empty-documents {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .empty-documents i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .action-btn.view {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .action-btn.view:hover {
            background: #3b82f6;
            color: white;
        }

        @media (max-width: 768px) {
            .user-info-grid {
                grid-template-columns: 1fr;
            }

            .document-actions {
                flex-direction: column;
            }

            .modal-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar admin-sidebar">
        <div class="sidebar-header">
            <div class="logo-icon">🎓</div>
            <h2 class="logo-text">Admin Panel</h2>
        </div>
        
        <nav class="sidebar-nav">
            <a href="admin-dashboard.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin-users.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="admin-courses.php" class="nav-item">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
            <a href="admin-applications.php" class="nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Applications</span>
            </a>
            <a href="admin-payments.php" class="nav-item">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
            <a href="admin-documents.php" class="nav-item active">
                <i class="fas fa-file"></i>
                <span>Documents</span>
            </a>
            <a href="admin-notifications.php" class="nav-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>User View</span>
            </a>
            <a href="logout.php" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="top-bar">
            <button class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search documents..." id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="top-bar-actions">
                <button class="icon-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                </button>
                <div class="user-menu">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=4F46E5&color=fff" alt="Admin" class="user-avatar">
                    <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="user-role">Admin</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </header>

        <!-- Page Header -->
        <section class="welcome-section">
            <div class="welcome-content">
                <h1><i class="fas fa-folder"></i> Document Management</h1>
                <p>View and manage all student application documents.</p>
            </div>
        </section>

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_documents']; ?></h3>
                    <p>Total Documents</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['applications_with_docs']; ?></h3>
                    <p>Applications with Docs</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_applications']; ?></h3>
                    <p>Total Applications</p>
                </div>
            </div>
        </section>

        <!-- Search and Filter -->
        <section class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Filter Documents</h2>
            </div>
            <div class="card-content">
                <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <input type="text" 
                           name="search" 
                           placeholder="Search by name, email, or course..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="flex: 1; min-width: 250px; padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; background: rgba(255, 255, 255, 0.05); color: #000;">
                    
                    <select name="course" style="padding: 0.75rem 1rem;border: 1px solid rgba(255, 255, 255, 0.1);border-radius: 10px;background:rgba(30, 41, 59, 0.8);color: #fff;min-width: 200px;">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="admin-documents.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>
        </section>
<br>
        <!-- Applications Table -->
        <section class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h2><i class="fas fa-table"></i> Student Applications & Documents</h2>
            </div>
            <div class="card-content">
                <?php if (count($applications) > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>USER ID</th>
                                    <th>USER NAME</th>
                                    <th>COURSE ENROLLED</th>
                                    <th>EMAIL</th>
                                    <th>DOCUMENTS</th>
                                    <th>STATUS</th>
                                    <th>APPLIED DATE</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td class="mono">#<?php echo $app['user_id']; ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($app['full_name']); ?>&size=32&background=random" alt="">
                                                <span><?php echo htmlspecialchars($app['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($app['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($app['email']); ?></td>
                                        <td>
                                            <span class="document-count <?php echo $app['document_count'] >= 3 ? 'complete' : 'incomplete'; ?>">
                                                <i class="fas fa-file-alt"></i>
                                                <?php echo $app['document_count']; ?>/3
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_badges = [
                                                'pending' => '<span class="badge pending"><i class="fas fa-clock"></i> PENDING</span>',
                                                'approved' => '<span class="badge approved"><i class="fas fa-check"></i> APPROVED</span>',
                                                'rejected' => '<span class="badge" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444;"><i class="fas fa-times"></i> REJECTED</span>',
                                                'enrolled' => '<span class="badge active"><i class="fas fa-graduation-cap"></i> ENROLLED</span>'
                                            ];
                                            echo $status_badges[$app['status']] ?? '<span class="badge">' . htmlspecialchars(strtoupper($app['status'])) . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary" title="View Documents" 
                                                        onclick="viewDocuments(<?php echo $app['application_id']; ?>, '<?php echo htmlspecialchars($app['full_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($app['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($app['course_title'], ENT_QUOTES); ?>', <?php echo $app['user_id']; ?>)">
                                                    <i class="fas fa-folder-open"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <i class="fas fa-folder-open" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                        <p>No applications found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Documents Modal -->
    <div id="documentsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-folder-open"></i> Student Documents</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="user-info-card">
                <h4>Student Information</h4>
                <div class="user-info-grid">
                    <div class="user-info-item">
                        <span class="user-info-label">User ID</span>
                        <span class="user-info-value" id="modalUserId"></span>
                    </div>
                    <div class="user-info-item">
                        <span class="user-info-label">Full Name</span>
                        <span class="user-info-value" id="modalUserName"></span>
                    </div>
                    <div class="user-info-item">
                        <span class="user-info-label">Email Address</span>
                        <span class="user-info-value" id="modalUserEmail"></span>
                    </div>
                    <div class="user-info-item">
                        <span class="user-info-label">Course Enrolled</span>
                        <span class="user-info-value" id="modalCourseName"></span>
                    </div>
                </div>
            </div>

            <h4 style="margin-bottom: 1rem; color: #fff;">
                <i class="fas fa-paperclip"></i> Uploaded Documents
            </h4>
            <div id="documentsContainer" class="documents-list">
                <div style="text-align: center; padding: 2rem; color: #64748b;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                    <p>Loading documents...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script>
        async function viewDocuments(application_id, user_name, email, title, userId) {
            // Show modal
            document.getElementById('documentsModal').classList.add('active');
            
            // Set user info
            document.getElementById('modalUserId').textContent = '#' + userId;
            document.getElementById('modalUserName').textContent = user_name;
            document.getElementById('modalUserEmail').textContent = email;
            document.getElementById('modalCourseName').textContent = title;
            
            // Fetch documents
            try {
                // Change to absolute path
                const response = await fetch(`/course-enrollment/get-documents.php?application_id=${application_id}`);
                const documents = await response.json();
                
                const container = document.getElementById('documentsContainer');
                
                if (documents.length === 0) {
                    container.innerHTML = `
                        <div class="empty-documents">
                            <i class="fas fa-inbox"></i>
                            <p>No documents uploaded yet</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = documents.map(doc => `
                    <div class="document-card">
                        <div class="document-header">
                            <div class="document-title">
                                <div class="document-icon">
                                    <i class="fas ${getDocumentIcon(doc.document_type)}"></i>
                                </div>
                                <span>${formatDocumentType(doc.document_type)}</span>
                            </div>
                        </div>
                        <div class="document-info">
                            <div class="document-info-item">
                                <label>File Name</label>
                                <span>${doc.file_name}</span>
                            </div>
                            <div class="document-info-item">
                                <label>File Size</label>
                                <span>${formatFileSize(doc.file_size)}</span>
                            </div>
                            <div class="document-info-item">
                                <label>Uploaded</label>
                                <span>${formatDate(doc.uploaded_at)}</span>
                            </div>
                        </div>
                        <div class="document-actions">
                            <a href="${doc.file_path}" target="_blank" class="btn-view">
                                <i class="fas fa-eye"></i> View Document
                            </a>
                            <a href="${doc.file_path}" download class="btn-download">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Error loading documents:', error);
                document.getElementById('documentsContainer').innerHTML = `
                    <div class="empty-documents">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error loading documents</p>
                    </div>
                `;
            }
        }

        function closeModal() {
            document.getElementById('documentsModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('documentsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        function getDocumentIcon(type) {
            const icons = {
                'university_transcript': 'fa-file-alt',
                'personal_statement': 'fa-file-text',
                'proof_of_identity': 'fa-id-card'
            };
            return icons[type] || 'fa-file';
        }

        function formatDocumentType(type) {
            return type.split('_').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
</body>
</html>