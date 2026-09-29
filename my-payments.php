<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';
requireLogin();
if (!isLoggedIn()) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$stmt = $pdo->prepare("SELECT p.*, c.title as course_title FROM payments p JOIN applications a ON p.application_id = a.id JOIN courses c ON a.course_id = c.id WHERE p.user_id = ? ORDER BY p.created_at DESC");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>My Payments</title><link rel="stylesheet" href="assets/css/dashboard.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body>
<aside class="sidebar"><div class="sidebar-header"><div class="logo-icon">🎓</div><h2 class="logo-text">EduPortal</h2></div><nav class="sidebar-nav"><a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a><a href="courses.php" class="nav-item"><i class="fas fa-book"></i><span>Courses</span></a><a href="my-applications.php" class="nav-item"><i class="fas fa-file-alt"></i><span>My Applications</span></a><a href="my-payments.php" class="nav-item active"><i class="fas fa-credit-card"></i><span>Payments</span></a><a href="profile.php" class="nav-item"><i class="fas fa-user"></i><span>Profile</span></a></nav><div class="sidebar-footer"><a href="logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div></aside>
<main class="main-content"><header class="top-bar"><button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button><div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Search..."></div><div class="top-bar-actions"><div class="user-menu"><img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=FF6B3D&color=fff" class="user-avatar"><span class="user-name"><?php echo htmlspecialchars($user_name); ?></span></div></div></header>
<section class="welcome-section"><div class="welcome-content"><h1><i class="fas fa-credit-card"></i> My Payments</h1><p>View all your payment history.</p></div></section>
<section class="card"><div class="card-header"><h2><i class="fas fa-history"></i> Payment History</h2></div><div class="card-content"><?php if(empty($payments)): ?><div style="text-align:center;padding:3rem;color:#94A3B8;"><i class="fas fa-receipt" style="font-size:3rem;opacity:0.3;margin-bottom:1rem;"></i><p>No payments yet.</p></div><?php else: ?><div class="table-responsive"><table class="data-table"><thead><tr><th>ID</th><th>Course</th><th>Type</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody><?php foreach($payments as $p): ?><tr><td>#<?php echo $p['id']; ?></td><td><?php echo htmlspecialchars($p['course_title']); ?></td><td><span class="badge pending"><?php echo ucfirst($p['payment_type']); ?></span></td><td>$<?php echo number_format($p['amount'],2); ?></td><td><?php echo htmlspecialchars($p['payment_method']); ?></td><td><span class="badge <?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td><td><?php echo date('M d, Y',strtotime($p['created_at'])); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></section></main>
<script src="assets/js/dashboard.js"></script></body></html>