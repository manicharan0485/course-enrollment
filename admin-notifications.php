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
$admin_name = $admin['username'] ?? $_SESSION['user_name'];
try { $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC"); $notifications = $stmt->fetchAll(); } catch (PDOException $e) { $notifications = []; }
$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
$pending_applications = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Notifications - Admin</title><link rel="stylesheet" href="assets/css/admin-dashboard.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body>
<aside class="sidebar admin-sidebar"><div class="sidebar-header"><div class="logo-icon">🎓</div><h2 class="logo-text">Admin Panel</h2></div><nav class="sidebar-nav"><a href="admin-dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Dashboard</span></a><a href="admin-users.php" class="nav-item"><i class="fas fa-users"></i><span>Users</span></a><a href="admin-courses.php" class="nav-item"><i class="fas fa-book"></i><span>Courses</span></a><a href="admin-applications.php" class="nav-item"><i class="fas fa-file-alt"></i><span>Applications</span><?php if($pending_applications>0): ?><span class="badge"><?php echo $pending_applications; ?></span><?php endif; ?></a><a href="admin-payments.php" class="nav-item"><i class="fas fa-credit-card"></i><span>Payments</span></a><a href="admin-documents.php" class="nav-item"><i class="fas fa-file"></i><span>Documents</span></a><a href="admin-notifications.php" class="nav-item active"><i class="fas fa-bell"></i><span>Notifications</span></a></nav><div class="sidebar-footer"><a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i><span>User View</span></a><a href="logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div></aside>
<main class="main-content"><header class="top-bar"><button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button><div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Search..."></div><div class="top-bar-actions"><button class="icon-btn"><i class="fas fa-bell"></i></button><div class="user-menu"><img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=4F46E5&color=fff" class="user-avatar"><span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span><span class="user-role">Admin</span></div></div></header>
<section class="welcome-section"><div class="welcome-content"><h1><i class="fas fa-bell"></i> Notification Center</h1><p>Send and manage system notifications.</p></div><div class="welcome-actions"><button class="btn btn-primary" onclick="alert('Send Notification feature coming soon!')"><i class="fas fa-paper-plane"></i> Send Notification</button></div></section>
<section class="card"><div class="card-header"><h2><i class="fas fa-list"></i> All Notifications</h2></div><div class="card-content"><?php if (empty($notifications)): ?><div style="text-align:center;padding:3rem;color:#94A3B8;"><i class="fas fa-bell" style="font-size:3rem;opacity:0.3;margin-bottom:1rem;"></i><p>No notifications yet.</p></div><?php else: ?><div class="user-list"><?php foreach ($notifications as $notif): ?><div class="user-item" style="align-items:flex-start;"><i class="fas fa-bell" style="font-size:1.5rem;color:var(--primary);"></i><div class="user-info" style="flex:1;"><h4><?php echo htmlspecialchars($notif['title'] ?? 'Notification'); ?></h4><p><?php echo htmlspecialchars($notif['message'] ?? 'No message'); ?></p><span class="date"><?php echo date('M d, Y', strtotime($notif['created_at'])); ?></span></div><button class="btn-icon"><i class="fas fa-trash"></i></button></div><?php endforeach; ?></div><?php endif; ?></div></section></main>
<script src="assets/js/dashboard.js"></script></body></html>

