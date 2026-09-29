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

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = trim($_POST['phone_number'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $college = trim($_POST['college'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $present_enrolled_course = trim($_POST['present_enrolled_course'] ?? '');
    $approved = $_POST['approved'] ?? 0;
    
    $errors = [];
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, Phone_Number, date_of_birth, college, address, city, postcode, present_enrolled_course, approved, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $full_name, 
                $email, 
                $hashed_password, 
                $phone_number ?: null,
                $date_of_birth ?: null,
                $college ?: null,
                $address ?: null,
                $city ?: null,
                $postcode ?: null,
                $present_enrolled_course ?: null,
                $approved
            ]);
            
            $_SESSION['success'] = 'User added successfully!';
            header('Location: admin-users.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Error adding user: ' . $e->getMessage();
        }
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $user_id_to_edit = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $college = trim($_POST['college'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $present_enrolled_course = trim($_POST['present_enrolled_course'] ?? '');
    $approved = $_POST['approved'] ?? 0;
    
    $errors = [];
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    // Check if email already exists for another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id_to_edit]);
    if ($stmt->fetch()) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password_hash = ?, Phone_Number = ?, date_of_birth = ?, college = ?, address = ?, city = ?, postcode = ?, present_enrolled_course = ?, approved = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([
                    $full_name, 
                    $email, 
                    $hashed_password, 
                    $phone_number ?: null,
                    $date_of_birth ?: null,
                    $college ?: null,
                    $address ?: null,
                    $city ?: null,
                    $postcode ?: null,
                    $present_enrolled_course ?: null,
                    $approved,
                    $user_id_to_edit
                ]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, Phone_Number = ?, date_of_birth = ?, college = ?, address = ?, city = ?, postcode = ?, present_enrolled_course = ?, approved = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([
                    $full_name, 
                    $email, 
                    $phone_number ?: null,
                    $date_of_birth ?: null,
                    $college ?: null,
                    $address ?: null,
                    $city ?: null,
                    $postcode ?: null,
                    $present_enrolled_course ?: null,
                    $approved,
                    $user_id_to_edit
                ]);
            }
            
            $_SESSION['success'] = 'User updated successfully!';
            header('Location: admin-users.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Error updating user: ' . $e->getMessage();
        }
    }
}

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id_to_delete]);
        
        $_SESSION['success'] = 'User deleted successfully!';
        header('Location: admin-users.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting user: ' . $e->getMessage();
    }
}

// Get all users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_query = '';
$search_params = [];

if ($search) {
    $search_query = " WHERE full_name LIKE ? OR email LIKE ?";
    $search_params = ["%$search%", "%$search%"];
}

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users" . $search_query);
$stmt->execute($search_params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Get users for current page
$stmt = $pdo->prepare("SELECT * FROM users" . $search_query . " ORDER BY created_at DESC LIMIT ? OFFSET ?");
$params = array_merge($search_params, [$per_page, $offset]);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$new_today = $stmt->fetchColumn();

// Approved users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE approved = 1");
$active_users = $stmt->fetchColumn();

// Pending approval
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE approved = 0");
$pending_approval = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
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
            max-width: 500px;
            width: 90%;
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

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
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

        /* Action buttons in table */
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

        /* Filter Dropdown Styling */
        .filter-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 0.6rem 2.5rem 0.6rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            background-size: 12px;
        }

        .filter-select:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .filter-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filter-select option {
            background: #1e293b;
            color: #fff;
            padding: 0.5rem;
        }

        /* Badge styles for approval status */
        .badge.approved {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .badge.pending {
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.3);
            color: #eab308;
        }

        .badge.active {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        /* View User Modal */
        .user-details {
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

        .user-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: block;
        }

        .modal-content.large {
            max-width: 600px;
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
            <a href="admin-users.php" class="nav-item active">
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
                <input type="text" placeholder="Search users..." id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
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
                <h1><i class="fas fa-users"></i> User Management</h1>
                <p>Manage all registered users on the platform.</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-primary" id="addUserBtn">
                    <i class="fas fa-user-plus"></i> Add User
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

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_count); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($active_users); ?></h3>
                    <p>Approved Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($pending_approval); ?></h3>
                    <p>Pending Approval</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($new_today); ?></h3>
                    <p>New Today</p>
                </div>
            </div>
        </section>

        <!-- Users Table -->
        <section class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-table"></i>
                    All Users
                </h2>
                <select class="filter-select" onchange="window.location.href='?filter='+this.value">
                    <option value="all">All Users</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="card-content">
                <?php if (empty($users)): ?>
                    <div style="text-align: center; padding: 3rem; color: #94A3B8;">
                        <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                        <p>No users found.</p>
                        <button class="btn btn-primary" style="margin-top: 1rem;" onclick="document.getElementById('addUserBtn').click()">
                            Add First User
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>USER</th>
                                    <th>EMAIL</th>
                                    <th>JOINED</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="mono">#<?php echo $user['id']; ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&size=32&background=random" alt="">
                                                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($user['approved'] == 1) ? 'approved' : 'pending'; ?>">
                                                <?php echo ($user['approved'] == 1) ? 'APPROVED' : 'PENDING'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn view" title="View" onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn edit" title="Edit" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn delete" title="Delete" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <span class="pagination-info">
                                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                            </span>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New User</h2>
                <button class="close-modal" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_user">
                
                <div style="max-height: 60vh; overflow-y: auto; padding-right: 0.5rem;">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required placeholder="Enter full name">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="Enter email address">
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required placeholder="Enter password" minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" placeholder="Enter phone number">
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth">
                    </div>

                    <div class="form-group">
                        <label for="college">College/University</label>
                        <input type="text" id="college" name="college" placeholder="Enter college/university name">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" placeholder="Enter address">
                    </div>

                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" placeholder="Enter city">
                    </div>

                    <div class="form-group">
                        <label for="postcode">Postcode</label>
                        <input type="text" id="postcode" name="postcode" placeholder="Enter postcode">
                    </div>

                    <div class="form-group">
                        <label for="present_enrolled_course">Present Enrolled Course</label>
                        <input type="text" id="present_enrolled_course" name="present_enrolled_course" placeholder="Enter course name">
                    </div>

                    <div class="form-group">
                        <label for="approved">Approval Status</label>
                        <select id="approved" name="approved" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; background: rgba(255, 255, 255, 0.05); color: #fff;">
                            <option value="0">Pending Approval</option>
                            <option value="1">Approved</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModal('addUserModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modal btn-modal-primary">
                        <i class="fas fa-check"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> User Details</h2>
                <button class="close-modal" onclick="closeModal('viewUserModal')">&times;</button>
            </div>
            <div id="viewUserContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit User</h2>
                <button class="close-modal" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form method="POST" action="" id="editUserForm">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div style="max-height: 60vh; overflow-y: auto; padding-right: 0.5rem;">
                    <div class="form-group">
                        <label for="edit_full_name">Full Name *</label>
                        <input type="text" id="edit_full_name" name="full_name" required placeholder="Enter full name">
                    </div>

                    <div class="form-group">
                        <label for="edit_email">Email Address *</label>
                        <input type="email" id="edit_email" name="email" required placeholder="Enter email address">
                    </div>

                    <div class="form-group">
                        <label for="edit_password">New Password (leave blank to keep current)</label>
                        <input type="password" id="edit_password" name="password" placeholder="Enter new password" minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="edit_phone_number">Phone Number</label>
                        <input type="tel" id="edit_phone_number" name="phone_number" placeholder="Enter phone number">
                    </div>

                    <div class="form-group">
                        <label for="edit_date_of_birth">Date of Birth</label>
                        <input type="date" id="edit_date_of_birth" name="date_of_birth">
                    </div>

                    <div class="form-group">
                        <label for="edit_college">College/University</label>
                        <input type="text" id="edit_college" name="college" placeholder="Enter college/university name">
                    </div>

                    <div class="form-group">
                        <label for="edit_address">Address</label>
                        <input type="text" id="edit_address" name="address" placeholder="Enter address">
                    </div>

                    <div class="form-group">
                        <label for="edit_city">City</label>
                        <input type="text" id="edit_city" name="city" placeholder="Enter city">
                    </div>

                    <div class="form-group">
                        <label for="edit_postcode">Postcode</label>
                        <input type="text" id="edit_postcode" name="postcode" placeholder="Enter postcode">
                    </div>

                    <div class="form-group">
                        <label for="edit_present_enrolled_course">Present Enrolled Course</label>
                        <input type="text" id="edit_present_enrolled_course" name="present_enrolled_course" placeholder="Enter course name">
                    </div>

                    <div class="form-group">
                        <label for="edit_approved">Approval Status</label>
                        <select id="edit_approved" name="approved" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; background: rgba(255, 255, 255, 0.05); color: #fff;">
                            <option value="0">Pending Approval</option>
                            <option value="1">Approved</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModal('editUserModal')">
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
        // Add User Modal
        const addUserBtn = document.getElementById('addUserBtn');
        const addUserModal = document.getElementById('addUserModal');
        const viewUserModal = document.getElementById('viewUserModal');
        const editUserModal = document.getElementById('editUserModal');

        addUserBtn.addEventListener('click', function() {
            addUserModal.classList.add('active');
        });

        function closeModal(modalId) {
            if (modalId) {
                document.getElementById(modalId).classList.remove('active');
            } else {
                // Close all modals
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

        // View User Function
        function viewUser(user) {
            const content = document.getElementById('viewUserContent');
            const joinedDate = new Date(user.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            const updatedDate = user.updated_at ? new Date(user.updated_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) : 'N/A';
            
            const dob = user.date_of_birth ? new Date(user.date_of_birth).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) : 'Not provided';
            
            content.innerHTML = `
                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(user.full_name)}&size=80&background=random" 
                     alt="${user.full_name}" class="user-avatar-large">
                
                <div class="user-details">
                    <div class="detail-row">
                        <span class="detail-label">User ID:</span>
                        <span class="detail-value">#${user.id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Full Name:</span>
                        <span class="detail-value">${user.full_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">${user.email}</span>
                    </div>
                    ${user.Phone_Number ? `
                    <div class="detail-row">
                        <span class="detail-label">Phone Number:</span>
                        <span class="detail-value">${user.Phone_Number}</span>
                    </div>
                    ` : ''}
                    <div class="detail-row">
                        <span class="detail-label">Date of Birth:</span>
                        <span class="detail-value">${dob}</span>
                    </div>
                    ${user.college ? `
                    <div class="detail-row">
                        <span class="detail-label">College/University:</span>
                        <span class="detail-value">${user.college}</span>
                    </div>
                    ` : ''}
                    ${user.address ? `
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value">${user.address}</span>
                    </div>
                    ` : ''}
                    ${user.city ? `
                    <div class="detail-row">
                        <span class="detail-label">City:</span>
                        <span class="detail-value">${user.city}</span>
                    </div>
                    ` : ''}
                    ${user.postcode ? `
                    <div class="detail-row">
                        <span class="detail-label">Postcode:</span>
                        <span class="detail-value">${user.postcode}</span>
                    </div>
                    ` : ''}
                    ${user.present_enrolled_course ? `
                    <div class="detail-row">
                        <span class="detail-label">Enrolled Course:</span>
                        <span class="detail-value">${user.present_enrolled_course}</span>
                    </div>
                    ` : ''}
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="badge ${user.approved == 1 ? 'approved' : 'pending'}">${user.approved == 1 ? 'APPROVED' : 'PENDING APPROVAL'}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Joined:</span>
                        <span class="detail-value">${joinedDate}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Updated:</span>
                        <span class="detail-value">${updatedDate}</span>
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 1.5rem;">
                    <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModal('viewUserModal')">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="button" class="btn-modal btn-modal-primary" onclick="closeModal('viewUserModal'); editUser(${JSON.stringify(user).replace(/"/g, '&quot;')})">
                        <i class="fas fa-edit"></i> Edit User
                    </button>
                </div>
            `;
            
            viewUserModal.classList.add('active');
        }

        // Edit User Function
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_phone_number').value = user.Phone_Number || '';
            document.getElementById('edit_date_of_birth').value = user.date_of_birth || '';
            document.getElementById('edit_college').value = user.college || '';
            document.getElementById('edit_address').value = user.address || '';
            document.getElementById('edit_city').value = user.city || '';
            document.getElementById('edit_postcode').value = user.postcode || '';
            document.getElementById('edit_present_enrolled_course').value = user.present_enrolled_course || '';
            document.getElementById('edit_approved').value = user.approved || 0;
            
            editUserModal.classList.add('active');
        }

        // Export functionality
        const exportBtn = document.getElementById('exportBtn');
        exportBtn.addEventListener('click', function() {
            // Create CSV content
            const users = <?php echo json_encode($users); ?>;
            
            let csvContent = "ID,Name,Email,Joined Date,Status\n";
            
            users.forEach(user => {
                const row = [
                    '#' + user.id,
                    '"' + user.full_name + '"',
                    user.email,
                    new Date(user.created_at).toLocaleDateString(),
                    (user.status || 'ACTIVE').toUpperCase()
                ].join(',');
                csvContent += row + "\n";
            });

            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'users_export_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show success message
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success';
            successAlert.innerHTML = '<i class="fas fa-check-circle"></i> Users exported successfully!';
            document.querySelector('.welcome-section').after(successAlert);
            
            setTimeout(() => {
                successAlert.style.transition = 'opacity 0.5s ease';
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }, 3000);
        });

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
                    window.location.href = 'admin-users.php';
                }
            }, 500);
        });

        // Delete confirmation
        function confirmDelete(userId, userName) {
            if (confirm(`Are you sure you want to delete user "${userName}"?\n\nThis action cannot be undone.`)) {
                window.location.href = '?action=delete&id=' + userId;
            }
        }

        // Auto-hide alerts after 5 seconds
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