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

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM courses");
$total_courses = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM applications");
$total_applications = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
$pending_applications = $stmt->fetchColumn();

// Calculate total revenue from payments table
try {
    $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'");
    $total_revenue = $stmt->fetchColumn() ?? 0;
} catch (PDOException $e) {
    $total_revenue = 0;
}

// Recent users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// Recent applications - FIXED: Auto-detect date column
try {
    // Get column info
    $columns = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_ASSOC);
    $dateColumn = 'id'; // Default fallback
    
    // Look for date columns in order of preference
    foreach ($columns as $col) {
        $colName = $col['Field'];
        if (in_array($colName, ['created_at', 'application_date', 'date_applied', 'submitted_at', 'applied_at'])) {
            $dateColumn = $colName;
            break;
        }
    }
    
    $stmt = $pdo->query("
        SELECT a.*, u.full_name, c.title as course_title 
        FROM applications a 
        JOIN users u ON a.user_id = u.id 
        JOIN courses c ON a.course_id = c.id 
        ORDER BY a.$dateColumn DESC 
        LIMIT 10
    ");
    $recent_applications = $stmt->fetchAll();
} catch (PDOException $e) {
    // If join fails, just get empty array
    $recent_applications = [];
    error_log("Applications query error: " . $e->getMessage());
}

// Course statistics
try {
    $stmt = $pdo->query("
        SELECT c.title, COUNT(a.id) as application_count 
        FROM courses c 
        LEFT JOIN applications a ON c.id = a.course_id 
        GROUP BY c.id 
        ORDER BY application_count DESC 
        LIMIT 5
    ");
    $popular_courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $popular_courses = [];
    error_log("Popular courses query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar admin-sidebar">
        <div class="sidebar-header">
            <div class="logo-icon">🎓</div>
            <h2 class="logo-text">Admin Panel</h2>
        </div>
        
        <nav class="sidebar-nav">
            <a href="admin-dashboard.php" class="nav-item active">
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
                <input type="text" placeholder="Search users, courses, applications...">
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
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </header>

        <!-- Welcome Section -->
        <section class="welcome-section admin-welcome">
            <div class="welcome-content">
                <h1>Admin Dashboard 🚀</h1>
                <p>Manage your education platform with ease.</p>
            </div>
        </section>

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_users); ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 15%
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_courses); ?></h3>
                    <p>Total Courses</p>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 8%
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_applications); ?></h3>
                    <p>Applications</p>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 22%
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 18%
                </div>
            </div>
        </section>

        <!-- Charts Row -->
        <!--<div class="dashboard-grid">-->
            <!-- Applications Chart -->
        <!--    <section class="card chart-card">-->
        <!--        <div class="card-header">-->
        <!--            <h2>-->
        <!--                <i class="fas fa-chart-line"></i>-->
        <!--                Application Trends-->
        <!--            </h2>-->
        <!--            <select class="time-filter">-->
        <!--                <option>Last 7 Days</option>-->
        <!--                <option>Last Month</option>-->
        <!--                <option>Last Year</option>-->
        <!--            </select>-->
        <!--        </div>-->
        <!--        <div class="card-content">-->
        <!--            <canvas id="applicationsChart" height="250"></canvas>-->
        <!--        </div>-->
        <!--    </section>-->

            <!-- Popular Courses -->
            <section class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-fire"></i>
                        Popular Courses
                    </h2>
                </div>
                <div class="card-content">
                    <?php if (empty($popular_courses)): ?>
                        <div style="text-align: center; padding: 3rem; color: #94A3B8;">
                            <i class="fas fa-book" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                            <p>No course data available yet.</p>
                            <a href="admin-courses.php?action=add" class="btn btn-primary" style="margin-top: 1rem;">Add First Course</a>
                        </div>
                    <?php else: ?>
                        <div class="popular-courses-list">
                            <?php foreach ($popular_courses as $index => $course): ?>
                                <div class="popular-course-item">
                                    <span class="rank">#<?php echo $index + 1; ?></span>
                                    <div class="course-info">
                                        <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <p><?php echo $course['application_count']; ?> application<?php echo $course['application_count'] != 1 ? 's' : ''; ?></p>
                                    </div>
                                    <div class="progress-bar">
                                        <?php 
                                        $maxCount = $popular_courses[0]['application_count'] ?? 1;
                                        $percentage = $maxCount > 0 ? ($course['application_count'] / $maxCount) * 100 : 0;
                                        ?>
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Data Tables -->
        <div class="dashboard-grid">
            <!-- Recent Applications -->
            <section class="card table-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-clock"></i>
                        Recent Applications
                    </h2>
                    <a href="admin-applications.php" class="btn-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-content">
                    <?php if (empty($recent_applications)): ?>
                        <div style="text-align: center; padding: 3rem; color: #94A3B8;">
                            <i class="fas fa-file-alt" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                            <p>No applications yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_applications as $app): ?>
                                        <tr>
                                            <td>
                                                <div class="user-cell">
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($app['full_name']); ?>&size=32" alt="">
                                                    <span><?php echo htmlspecialchars($app['full_name']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($app['course_title']); ?></td>
                                            <td>
                                                <?php 
                                                // Try different date columns
                                                $displayDate = 'N/A';
                                                if (isset($app[$dateColumn])) {
                                                    $displayDate = date('M d, Y', strtotime($app[$dateColumn]));
                                                } elseif (isset($app['id'])) {
                                                    $displayDate = 'ID: ' . $app['id'];
                                                }
                                                echo $displayDate;
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $app['status']; ?>">
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            </td>
                                            <td class="actions-cell">
                                                <button class="btn-icon" title="View" onclick="alert('View application #<?php echo $app['id']; ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-icon" title="Edit" onclick="alert('Edit application #<?php echo $app['id']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recent Users -->
            <section class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-user-plus"></i>
                        Recent Users
                    </h2>
                    <a href="admin-users.php" class="btn-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-content">
                    <?php if (empty($recent_users)): ?>
                        <div style="text-align: center; padding: 3rem; color: #94A3B8;">
                            <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                            <p>No users yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="user-list">
                            <?php foreach ($recent_users as $user): ?>
                                <div class="user-item">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&size=40" alt="" class="user-avatar">
                                    <div class="user-info">
                                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                                        <span class="date">Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                    <button class="btn-icon" title="View Profile" onclick="alert('View user: <?php echo htmlspecialchars($user['full_name']); ?>')">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/admin-charts.js"></script>
</body>
</html>