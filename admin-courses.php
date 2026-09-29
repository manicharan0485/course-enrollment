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

// Handle Add Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_course') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration = trim($_POST['duration'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $registration_fee = $_POST['registration_fee'] ?? 0;
    $programme_fee = $_POST['programme_fee'] ?? 2000.00;
    $start_date = $_POST['start_date'] ?? null;
    $image_url = trim($_POST['image_url'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $incharge_name = trim($_POST['incharge_name'] ?? '');
    $incharge_email = trim($_POST['incharge_email'] ?? '');
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Course title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Course description is required';
    }
    
    if (empty($errors)) {
        try {
            // FIX 1: Added incharge_name, incharge_email to INSERT columns and placeholders
            $stmt = $pdo->prepare("INSERT INTO courses (title, description, image_url, registration_fee, programme_fee, duration, start_date, status, category, incharge_name, incharge_email, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $title, 
                $description, 
                $image_url ?: null,
                $registration_fee,
                $programme_fee,
                $duration ?: null, 
                $start_date ?: null, 
                $status,
                $category,
                $incharge_name ?: null,
                $incharge_email ?: null
            ]);
            
            $_SESSION['success'] = 'Course added successfully!';
            header('Location: admin-courses.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Error adding course: ' . $e->getMessage();
        }
    }
}

// Handle Edit Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_course') {
    $course_id = $_POST['course_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration = trim($_POST['duration'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $registration_fee = $_POST['registration_fee'] ?? 0;
    $programme_fee = $_POST['programme_fee'] ?? 2000.00;
    $start_date = $_POST['start_date'] ?? null;
    $image_url = trim($_POST['image_url'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $incharge_name = trim($_POST['incharge_name'] ?? '');
    $incharge_email = trim($_POST['incharge_email'] ?? '');
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Course title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Course description is required';
    }
    
    if (empty($errors)) {
        try {
            // FIX 2: Added incharge_name, incharge_email to UPDATE SET clause with correct parameter order
            $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, image_url = ?, registration_fee = ?, programme_fee = ?, duration = ?, start_date = ?, status = ?, category = ?, incharge_name = ?, incharge_email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                $title, 
                $description, 
                $image_url ?: null,
                $registration_fee,
                $programme_fee,
                $duration ?: null, 
                $start_date ?: null, 
                $status,
                $category,
                $incharge_name ?: null,
                $incharge_email ?: null,
                $course_id
            ]);
            
            $_SESSION['success'] = 'Course updated successfully!';
            header('Location: admin-courses.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Error updating course: ' . $e->getMessage();
        }
    }
}

// Handle Delete Course
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $course_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        
        $_SESSION['success'] = 'Course deleted successfully!';
        header('Location: admin-courses.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting course: ' . $e->getMessage();
    }
}

// Get all courses with search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$query = "SELECT * FROM courses WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM courses");
$total_courses = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'active'");
$active_courses = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'inactive'");
$inactive_courses = $stmt->fetchColumn();

// Get average enrollments
try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) / NULLIF(COUNT(DISTINCT course_id), 0) FROM applications");
    $avg_enrollments = round($stmt->fetchColumn(), 1);
} catch (PDOException $e) {
    $avg_enrollments = 0;
}

$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
$pending_applications = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modal Styles */
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
            max-width: 600px;
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

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #cbd5e1;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

        .btn-modal-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(59, 130, 246, 0.5);
        }

        .btn-modal-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-modal-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Action buttons */
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

        .action-btn.edit {
            background: rgba(234, 179, 8, 0.1);
            color: #eab308;
        }

        .action-btn.edit:hover {
            background: #eab308;
            color: white;
        }

        .action-btn.delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .action-btn.delete:hover {
            background: #ef4444;
            color: white;
        }

        .filter-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 0.6rem 2.5rem 0.6rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .course-details {
            display: grid;
            gap: 1rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
        }

        .detail-label {
            color: #94a3b8;
            font-weight: 500;
        }

        .detail-value {
            color: #fff;
            font-weight: 600;
        }

        .badge.active {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .badge.inactive {
            background: rgba(148, 163, 184, 0.1);
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: #94a3b8;
        }

        .badge.draft {
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.3);
            color: #eab308;
        }

        .badge.archived {
            background: rgba(148, 163, 184, 0.1);
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: #94a3b8;
        }

        /* Incharge section divider */
        .incharge-divider {
            border: none;
            border-top: 1px solid rgba(139, 92, 246, 0.3);
            margin: 0.5rem 0;
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
            <a href="admin-courses.php" class="nav-item active">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
            <a href="admin-applications.php" class="nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Applications</span>
                <?php if ($pending_applications > 0): ?>
                    <span class="badge"><?php echo $pending_applications; ?></span>
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
                <input type="text" placeholder="Search courses..." id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="top-bar-actions">
                <button class="icon-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($pending_applications > 0): ?>
                        <span class="badge"><?php echo $pending_applications; ?></span>
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
                <h1><i class="fas fa-book"></i> Course Management</h1>
                <p>Manage all courses and programs.</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-primary" id="addCourseBtn">
                    <i class="fas fa-plus"></i> Add Course
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

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <ul style="margin: 0; padding-left: 1.25rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_courses); ?></h3>
                    <p>Total Courses</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($active_courses); ?></h3>
                    <p>Active Courses</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-pause-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($inactive_courses); ?></h3>
                    <p>Inactive Courses</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $avg_enrollments > 0 ? number_format($avg_enrollments, 1) : '-'; ?></h3>
                    <p>Avg Enrollments</p>
                </div>
            </div>
        </section>

        <!-- Courses Table -->
        <section class="card">
            <div class="card-header" style="background:rgba(30, 41, 59, 0.8)">
                <h2><i class="fas fa-table"></i> All Courses</h2>
                <select class="filter-select" onchange="window.location.href='?filter='+this.value" style="background:rgba(30, 41, 59, 0.8)">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Courses</option>
                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="archived" <?php echo $filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>
            <div class="card-content">
                <?php if (empty($courses)): ?>
                    <div style="text-align: center; padding: 3rem; color: #94A3B8;">
                        <i class="fas fa-book" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                        <p>No courses found.</p>
                        <button class="btn btn-primary" onclick="document.getElementById('addCourseBtn').click()" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Add First Course
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>COURSE NAME</th>
                                    <th>DESCRIPTION</th>
                                    <th>INCHARGE</th>
                                    <th>STATUS</th>
                                    <th>CREATED</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="coursesTable">
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td class="mono">#<?php echo $course['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($course['description'], 0, 60)) . '...'; ?></td>
                                        <td>
                                            <?php if (!empty($course['incharge_name'])): ?>
                                                <div style="font-weight:600;"><?php echo htmlspecialchars($course['incharge_name']); ?></div>
                                                <div style="font-size:0.8rem; color:#8b5cf6;"><?php echo htmlspecialchars($course['incharge_email'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <span style="color:#94a3b8; font-size:0.85rem;">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo strtolower($course['status'] ?? 'active'); ?>">
                                                <?php echo strtoupper($course['status'] ?? 'ACTIVE'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn view" title="View" onclick="viewCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn edit" title="Edit" onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn delete" title="Delete" onclick="confirmDelete(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['title']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

    <!-- Add Course Modal -->
    <div id="addCourseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Add New Course</h2>
                <button class="close-modal" onclick="closeModal('addCourseModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_course">
                
                <div class="form-group">
                    <label for="title">Course Title *</label>
                    <input type="text" id="title" name="title" required placeholder="Enter course title">
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required placeholder="Enter course description"></textarea>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="General" placeholder="Enter category">
                </div>

                <div class="form-group">
                    <label for="duration">Duration</label>
                    <input type="text" id="duration" name="duration" placeholder="e.g., 12 weeks, 3 months">
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date">
                </div>

                <div class="form-group">
                    <label for="registration_fee">Registration Fee (£)</label>
                    <input type="number" id="registration_fee" name="registration_fee" step="0.01" min="0" value="0" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="programme_fee">Programme Fee (£)</label>
                    <input type="number" id="programme_fee" name="programme_fee" step="0.01" min="0" value="2000.00" placeholder="2000.00">
                </div>

                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="text" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <!-- FIX 3: Added incharge fields to Add modal form -->
                <div class="form-group">
                    <label for="add_incharge_name">Incharge Name</label>
                    <input type="text" id="add_incharge_name" name="incharge_name" placeholder="e.g. Dr. John Smith">
                </div>

                <div class="form-group">
                    <label for="add_incharge_email">Incharge Email</label>
                    <input type="email" id="add_incharge_email" name="incharge_email" placeholder="incharge@email.com">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModal('addCourseModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modal btn-modal-primary">
                        <i class="fas fa-check"></i> Add Course
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Course Modal -->
    <div id="viewCourseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-book"></i> Course Details</h2>
                <button class="close-modal" onclick="closeModal('viewCourseModal')">&times;</button>
            </div>
            <div id="viewCourseContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editCourseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Course</h2>
                <button class="close-modal" onclick="closeModal('editCourseModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_course">
                <input type="hidden" name="course_id" id="edit_course_id">
                
                <div class="form-group">
                    <label for="edit_title">Course Title *</label>
                    <input type="text" id="edit_title" name="title" required placeholder="Enter course title">
                </div>

                <div class="form-group">
                    <label for="edit_description">Description *</label>
                    <textarea id="edit_description" name="description" required placeholder="Enter course description"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <input type="text" id="edit_category" name="category" placeholder="Enter category">
                </div>

                <div class="form-group">
                    <label for="edit_duration">Duration</label>
                    <input type="text" id="edit_duration" name="duration" placeholder="e.g., 12 weeks, 3 months">
                </div>

                <div class="form-group">
                    <label for="edit_start_date">Start Date</label>
                    <input type="date" id="edit_start_date" name="start_date">
                </div>

                <div class="form-group">
                    <label for="edit_registration_fee">Registration Fee (£)</label>
                    <input type="number" id="edit_registration_fee" name="registration_fee" step="0.01" min="0" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="edit_programme_fee">Programme Fee (£)</label>
                    <input type="number" id="edit_programme_fee" name="programme_fee" step="0.01" min="0" placeholder="2000.00">
                </div>

                <div class="form-group">
                    <label for="edit_image_url">Image URL</label>
                    <input type="text" id="edit_image_url" name="image_url" placeholder="https://example.com/image.jpg">
                </div>

                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <!-- FIX 3: Added incharge fields to Edit modal form -->
                <div class="form-group">
                    <label for="edit_incharge_name">Incharge Name</label>
                    <input type="text" id="edit_incharge_name" name="incharge_name" placeholder="e.g. Dr. John Smith">
                </div>

                <div class="form-group">
                    <label for="edit_incharge_email">Incharge Email</label>
                    <input type="email" id="edit_incharge_email" name="incharge_email" placeholder="incharge@email.com">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModal('editCourseModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modal btn-modal-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script>
        // Modals
        const addCourseBtn = document.getElementById('addCourseBtn');
        const addCourseModal = document.getElementById('addCourseModal');
        const viewCourseModal = document.getElementById('viewCourseModal');
        const editCourseModal = document.getElementById('editCourseModal');

        addCourseBtn.addEventListener('click', function() {
            addCourseModal.classList.add('active');
        });

        function closeModal(modalId) {
            if (modalId) {
                document.getElementById(modalId).classList.remove('active');
            } else {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        }

        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal(modal.id);
                }
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // View Course
        function viewCourse(course) {
            const content = document.getElementById('viewCourseContent');
            const createdDate = new Date(course.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            const startDate = course.start_date ? new Date(course.start_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) : 'Not set';

            // Store for the Edit button
            viewCourse._current = course;
            
            content.innerHTML = `
                <div class="course-details">
                    <div class="detail-row">
                        <span class="detail-label">Course ID:</span>
                        <span class="detail-value">#${course.id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Title:</span>
                        <span class="detail-value">${course.title}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Category:</span>
                        <span class="detail-value">${course.category || 'General'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Description:</span>
                        <span class="detail-value">${course.description}</span>
                    </div>
                    ${course.duration ? `
                    <div class="detail-row">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value">${course.duration}</span>
                    </div>
                    ` : ''}
                    <div class="detail-row">
                        <span class="detail-label">Start Date:</span>
                        <span class="detail-value">${startDate}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Registration Fee:</span>
                        <span class="detail-value">£${parseFloat(course.registration_fee || 0).toFixed(2)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Programme Fee:</span>
                        <span class="detail-value">£${parseFloat(course.programme_fee || course.program_fee || 2000).toFixed(2)}</span>
                    </div>
                    ${course.image_url ? `
                    <div class="detail-row">
                        <span class="detail-label">Image URL:</span>
                        <span class="detail-value"><a href="${course.image_url}" target="_blank" style="color: #3b82f6;">View Image</a></span>
                    </div>
                    ` : ''}
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="badge ${(course.status || 'active').toLowerCase()}">${(course.status || 'ACTIVE').toUpperCase()}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Created:</span>
                        <span class="detail-value">${createdDate}</span>
                    </div>

                    <hr class="incharge-divider">

                    <!-- FIX 4: Fixed viewCourse to use course.incharge_name / course.incharge_email -->
                    <div class="detail-row">
                        <span class="detail-label" style="color:#8b5cf6;"><i class="fas fa-user-tie"></i> Incharge Name:</span>
                        <span class="detail-value">${course.incharge_name || '<span style="color:#94a3b8;font-weight:400;">Not assigned</span>'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label" style="color:#8b5cf6;"><i class="fas fa-envelope"></i> Incharge Email:</span>
                        <span class="detail-value">${course.incharge_email
                            ? '<a href="mailto:' + course.incharge_email + '" style="color:#8b5cf6;">' + course.incharge_email + '</a>'
                            : '<span style="color:#94a3b8;font-weight:400;">Not assigned</span>'}</span>
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 1.5rem;">
                    <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModal('viewCourseModal')">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="button" class="btn-modal btn-modal-primary" id="viewToEditBtn">
                        <i class="fas fa-edit"></i> Edit Course
                    </button>
                </div>
            `;

            // Safe Edit button — no inline JSON serialisation
            document.getElementById('viewToEditBtn').addEventListener('click', function() {
                closeModal('viewCourseModal');
                editCourse(viewCourse._current);
            });
            
            viewCourseModal.classList.add('active');
        }

        // Edit Course
        function editCourse(course) {
            document.getElementById('edit_course_id').value = course.id;
            document.getElementById('edit_title').value = course.title;
            document.getElementById('edit_description').value = course.description;
            document.getElementById('edit_category').value = course.category || 'General';
            document.getElementById('edit_duration').value = course.duration || '';
            document.getElementById('edit_start_date').value = (course.start_date || '').substring(0, 10);
            document.getElementById('edit_registration_fee').value = course.registration_fee || 0;
            document.getElementById('edit_programme_fee').value = course.programme_fee || course.program_fee || 2000.00;
            document.getElementById('edit_image_url').value = course.image_url || '';
            document.getElementById('edit_status').value = course.status || 'active';
            // FIX 4: Correct element IDs matching the edit modal inputs above
            document.getElementById('edit_incharge_name').value = course.incharge_name || '';
            document.getElementById('edit_incharge_email').value = course.incharge_email || '';
            
            editCourseModal.classList.add('active');
        }

        // Delete Course
        function confirmDelete(courseId, courseTitle) {
            if (confirm(`Are you sure you want to delete "${courseTitle}"?\n\nThis action cannot be undone.`)) {
                window.location.href = '?action=delete&id=' + courseId;
            }
        }

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const searchValue = searchInput.value.trim();
                if (searchValue) {
                    window.location.href = '?search=' + encodeURIComponent(searchValue);
                } else {
                    window.location.href = 'admin-courses.php';
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