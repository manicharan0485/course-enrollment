<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';


// Check if user is admin
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ?");
$stmt->execute([$_SESSION['user_email']]);
$admin = $stmt->fetch();

if (!$admin) {
    $_SESSION['error'] = 'Access denied.';
    header('Location: dashboard.php');
    exit;
}

$admin_name = $admin['username'] ?? $_SESSION['user_name'];

// Handle Approve Application
if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    $app_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE applications SET status = 'approved', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$app_id]);
        
        $_SESSION['success'] = 'Application approved successfully!';
        header('Location: admin-applications.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error approving application: ' . $e->getMessage();
    }
}

//handel enroll application
if (isset($_GET['action']) && $_GET['action'] === 'enroll' && isset($_GET['id'])) {
    $app_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE applications SET status = 'enrolled', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$app_id]);
        
        $_SESSION['success'] = 'Application enrolled successfully!';
        header('Location: admin-applications.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error enrolling application: ' . $e->getMessage();
    }
}

// Handle Reject Application
if (isset($_GET['action']) && $_GET['action'] === 'reject' && isset($_GET['id'])) {
    $app_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE applications SET status = 'rejected', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$app_id]);
        
        $_SESSION['success'] = 'Application rejected successfully!';
        header('Location: admin-applications.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error rejecting application: ' . $e->getMessage();
    }
}

// Handle Delete Application
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $app_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$app_id]);
        
        $_SESSION['success'] = 'Application deleted successfully!';
        header('Location: admin-applications.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting application: ' . $e->getMessage();
    }
}

// Get search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get applications with joins
$query = "SELECT a.*, u.full_name as student_name, c.title as course_title 
          FROM applications a 
          JOIN users u ON a.user_id = u.id 
          JOIN courses c ON a.course_id = c.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (u.full_name LIKE ? OR c.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter !== 'all') {
    $query .= " AND a.status = ?";
    $params[] = $filter;
}

$query .= " ORDER BY a.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
$pending_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'approved'");
$approved_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'");
$rejected_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'enrolled'");
$enrolled_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM applications");
$total_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Management - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
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

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }

        .detail-label {
            color: #94a3b8;
            font-weight: 500;
        }

        .detail-value {
            color: #fff;
            font-weight: 600;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
        }

        .action-btn.view {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .action-btn.view:hover {
            background: #3b82f6;
            color: white;
        }

        .action-btn.approve {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .action-btn.approve:hover {
            background: #22c55e;
            color: white;
        }

        .action-btn.reject {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .action-btn.reject:hover {
            background: #ef4444;
            color: white;
        }

        .badge.pending {
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.3);
            color: #eab308;
        }

        .badge.approved, .badge.enrolled {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .badge.rejected {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .filter-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 0.6rem 2.5rem 0.6rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-modal {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-modal-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-modal-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-modal-success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }

        .btn-modal-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
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
            <a href="admin-applications.php" class="nav-item active">
                <i class="fas fa-file-alt"></i>
                <span>Applications</span>
                <?php if ($pending_count > 0): ?>
                    <span class="badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="admin-payments.php" class="nav-item">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
            <a href="admin-documents.php" class="nav-item">
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
                <input type="text" placeholder="Search applications..." id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="top-bar-actions">
                <button class="icon-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($pending_count > 0): ?>
                        <span class="badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </button>
                <div class="user-menu">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=4F46E5&color=fff" alt="Admin" class="user-avatar">
                    <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="user-role">Admin</span>
                </div>
            </div>
        </header>

        <!-- Page Header -->
        <section class="welcome-section">
            <div class="welcome-content">
                <h1><i class="fas fa-file-alt"></i> Application Management</h1>
                <p>Review and manage all course applications.</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-secondary" id="exportBtn">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </section>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($pending_count); ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($enrolled_count); ?></h3>
                    <p>Enrolled</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($approved_count); ?></h3>
                    <p>Approved</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($rejected_count); ?></h3>
                    <p>Rejected</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_count); ?></h3>
                    <p>Total</p>
                </div>
            </div>
        </section>

        <!-- Applications Table -->
        <section class="card">
            <div class="card-header">
                <h2><i class="fas fa-table"></i> All Applications</h2>
                <select class="filter-select" onchange="window.location.href='?filter='+this.value">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="enrolled" <?php echo $filter === 'enrolled' ? 'selected' : ''; ?>>Enrolled</option>
                    <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="card-content">
                <?php if (empty($applications)): ?>
                    <div style="text-align: center; padding: 3rem; color: #94A3B8;">
                        <i class="fas fa-file-alt" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                        <p>No applications found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>STUDENT</th>
                                    <th>COURSE</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="applicationsTable">
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td class="mono">#<?php echo $app['id']; ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($app['student_name']); ?>&size=32&background=random" alt="">
                                                <span><?php echo htmlspecialchars($app['student_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($app['course_title']); ?></td>
                                        <td>
                                            <span class="badge <?php echo strtolower($app['status']); ?>">
                                                <?php echo strtoupper($app['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn view" title="View" onclick="viewApplication(<?php echo htmlspecialchars(json_encode($app)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($app['status'] !== 'approved'): ?>
                                                <button class="action-btn approve" title="Approve" onclick="approveApplication(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars($app['student_name']); ?>')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($app['status'] === 'enrolled'): ?>
                                                <button class="action-btn approve" title="Enroll" onclick="enrollApplication(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars($app['student_name']); ?>')">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($app['status'] !== 'rejected'): ?>
                                                <button class="action-btn reject" title="Reject" onclick="rejectApplication(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars($app['student_name']); ?>')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- View Application Modal -->
    <div id="viewAppModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-alt"></i> Application Details</h2>
                <button class="close-modal" onclick="closeModal('viewAppModal')">&times;</button>
            </div>
            <div id="viewAppContent">
                <!-- Content populated by JavaScript -->
            </div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal(modal.id);
                }
            });
        });

        // View Application
        function viewApplication(app) {
            const content = document.getElementById('viewAppContent');
            
            content.innerHTML = `
                <div>
                    <div class="detail-row">
                        <span class="detail-label">Application ID:</span>
                        <span class="detail-value">#${app.id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Student:</span>
                        <span class="detail-value">${app.student_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Course:</span>
                        <span class="detail-value">${app.course_title}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="badge ${app.status.toLowerCase()}">${app.status.toUpperCase()}</span>
                        </span>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModal('viewAppModal')">
                        <i class="fas fa-times"></i> Close
                    </button>
                    ${app.status !== 'approved' ? `
                        <button type="button" class="btn-modal btn-modal-success" onclick="closeModal('viewAppModal'); approveApplication(${app.id}, '${app.student_name}')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    ` : ''}
                    
                    ${app.status !== 'enrolled' ? `
                        <button type="button" class="btn-modal btn-modal-info" onclick="closeModal('viewAppModal'); enrollApplication(${app.id}, '${app.student_name}')">
                            <i class="fas fa-user-check"></i> Enroll
                        </button>
                    ` : ''}
                    ${app.status !== 'rejected' ? `
                        <button type="button" class="btn-modal btn-modal-danger" onclick="closeModal('viewAppModal'); rejectApplication(${app.id}, '${app.student_name}')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    ` : ''}

                </div>
            `;
            
            document.getElementById('viewAppModal').classList.add('active');
        }

        function approveApplication(appId, studentName) {
            if (confirm(`Approve application from ${studentName}?`)) {
                window.location.href = '?action=approve&id=' + appId;
            }
        }

        function rejectApplication(appId, studentName) {
            if (confirm(`Reject application from ${studentName}?`)) {
                window.location.href = '?action=reject&id=' + appId;
            }
        }

        // Export
        const exportBtn = document.getElementById('exportBtn');
        exportBtn.addEventListener('click', function() {
            const applications = <?php echo json_encode($applications); ?>;
            
            let csvContent = "ID,Student,Course,Status\n";
            
            applications.forEach(app => {
                const row = [
                    '#' + app.id,
                    '"' + app.student_name + '"',
                    '"' + app.course_title + '"',
                    app.status.toUpperCase()
                ].join(',');
                csvContent += row + "\n";
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'applications_export_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Search
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const searchValue = searchInput.value.trim();
                if (searchValue) {
                    window.location.href = '?search=' + encodeURIComponent(searchValue);
                } else {
                    window.location.href = 'admin-applications.php';
                }
            }, 500);
        });

        // Auto-hide alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    </script>
</body>
</html>