<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';
requireLogin();

// Initialize arrays to avoid "undefined variable" errors
$applications = [];
$courses = [];
$enrolled_courses = [];
$notifications = [];

// Example function to get logged-in user from session or database
function getLoggedInUser() {
    // Assuming you store the user ID in session after login
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Fetch user data from the 'users' table
        global $pdo; // Assuming $pdo is your PDO database connection
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Return user data as an associative array
    }
    return null; // If no user is logged in, return null
}

$user = getLoggedInUser();

// Get ALL applications for the user
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        c.title as course_title,
        c.description as course_description,
        c.registration_fee,
        c.programme_fee
    FROM applications a
    JOIN courses c ON a.course_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$user['id']]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ONLY ENROLLED courses (status = 'enrolled')
$stmt = $pdo->prepare("
    SELECT c.*, a.created_at as enrolled_at 
    FROM courses c
    JOIN applications a ON a.course_id = c.id
    WHERE a.user_id = ? AND a.status = 'enrolled'
    ORDER BY a.created_at DESC
");
$stmt->execute([$user['id']]);
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total_applications = count($applications);
$total_enrolled = count($enrolled_courses);

// Count approved applications (pending enrollment)
$approved_count = 0;
foreach ($applications as $app) {
    if ($app['status'] == 'approved') {
        $approved_count++;
    }
}
// Count certificates (assuming a 'certificates' table exists)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/welcome.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="index.php" 
                   style="display:flex; justify-content:center; align-items:center; padding:25px 0; width:100%; border-bottom:1px solid #eee;">
            
                    <img src="images/GIP GERMANY.png" 
                         alt="GIIP Germany"
                         style="width:150px; max-width:90%; height:auto; object-fit:contain; display:block;">
                         
                </a>
            </a>
            <br>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <span class="icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="courses.php">
                        <span class="icon">📚</span>
                        <span>Courses</span>
                    </a>
                </li>
                 <li>
                    <a href="my-application.php">
                        <span class="icon">📋</span>
                        <span>My Applications</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <span class="icon">👤</span>
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <span class="icon">🚪</span>
                        <span>Logout</span>
                    </a>
                </li>
                <li>
                    <a href="help.php">
                        <span class="icon">❓</span>
                        <span>Help & Support</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('active')">
            ☰
        </button>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Welcome Back, <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>! 👋</h1>
                <p>Explore your courses and keep progressing today</p>
            </div>

            <!-- Flash Messages -->
            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon orange">📝</div>
                        <div>
                            <div class="stat-number"><?php echo $total_applications; ?></div>
                            <div class="stat-label">Total Applications</div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon blue">📚</div>
                        <div>
                            <div class="stat-number"><?php echo $approved_count; ?></div>
                            <div class="stat-label">Approved Applications</div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon green">✓</div>
                        <div>
                            <div class="stat-number"><?php echo $total_enrolled; ?></div>
                            <div class="stat-label">Enrolled Courses</div>
                        </div>
                    </div>
                </div>
</div>
            <!-- Main Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Left Column: Applications -->
                <div>
                    <div class="section-header">
                        <h2>Track All Ongoing and Completed Courses</h2>
                    </div>

                    <?php if (empty($applications)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📚</div>
                            <h3>No Applications Yet</h3>
                            <p>Start your learning journey by applying to a course</p>
                            <a href="courses.php" class="btn btn-primary">Explore Courses</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($applications as $app): 
                            $progress = getApplicationProgress($app['id'], $pdo);
                            $documents_uploaded = $progress['documents_count'] >= 3;
                            $reg_paid = $progress['reg_paid'] > 0;
                            $prog_paid = $progress['prog_paid'] > 0;
                            
                            // Calculate completion percentage
                            $completion = 20; // Applied
                            if ($documents_uploaded) $completion = 40;
                            if ($reg_paid) $completion = 60;
                            if ($prog_paid) $completion = 80;
                            if ($app['status'] == 'enrolled') $completion = 100;
                        ?>
                        <div class="course-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <h3><?php echo htmlspecialchars($app['course_title'] ?? 'Unknown Course'); ?></h3>
                                <span class="status-badge <?php echo getStatusBadgeClass($app['status']); ?>">
                                    <?php echo getStatusText($app['status']); ?>
                                </span>
                            </div>

                            <p>Applied on <?php echo formatDate($app['submitted_at']); ?>. Complete all steps to get enrolled in this course.</p>

                            <div class="course-meta">
                                <div class="course-meta-item">
                                    <span>📄</span>
                                    <span>Documents: <?php echo $progress['documents_count']; ?>/3</span>
                                </div>
                                <div class="course-meta-item">
                                    <span>💳</span>
                                    <span>Reg Fee: €<?php echo number_format($app['registration_fee'] ?? 0, 2); ?></span>
                                </div>
                                <div class="course-meta-item">
                                    <span>💰</span>
                                    <span>Programme Fee: €<?php echo number_format($app['programme_fee'] ?? 0, 2); ?></span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress-wrapper">
                                <div class="progress-header">
                                    <span class="progress-label">Application Progress</span>
                                    <span class="progress-percentage"><?php echo $completion; ?>%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?php echo $completion; ?>%;"></div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                <?php if ($app['status'] == 'enrolled'): ?>
                                    <a href="courses.php?course_id=<?php echo $app['course_id']; ?>" class="btn btn-primary" style="flex: 1;">
                                        Next Module
                                    </a>
                                <?php elseif (!$documents_uploaded): ?>
                                    <a href="apply.php?id=<?php echo $app['id']; ?>" class="btn btn-primary" style="flex: 1;">
                                        Upload Documents
                                    </a>
                                <?php elseif (!$reg_paid): ?>
                                    <a href="payment.php?id=<?php echo $app['id']; ?>&type=registration" class="btn btn-primary" style="flex: 1;">
                                        Pay Here!
                                    </a>
                                <?php elseif (!$prog_paid): ?>
                                    <a href="payment.php?id=<?php echo $app['id']; ?>&type=programme" class="btn btn-primary" style="flex: 1;">
                                        Pay Here!
                                    </a>
                                <?php endif; ?>
                                <a href="application-detail.php?id=<?php echo $app['id']; ?>" class="btn btn-outline">Details</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Enrolled Courses Section -->
                    <?php if (!empty($enrolled_courses)): ?>
                        <div class="section-header" style="margin-top: 2rem;">
                            <h2>Your Enrolled Courses (<?php echo count($enrolled_courses); ?>)</h2>
                        </div>
                        <?php foreach ($enrolled_courses as $course): ?>
                        <div class="course-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <span class="status-badge" style="background: #10B981; color: white;">
                                    ✓ Enrolled
                                </span>
                            </div>
                            <p><?php echo htmlspecialchars(substr($course['description'], 0, 150)) . '...'; ?></p>
                            
                            <div class="course-meta">
                                <div class="course-meta-item">
                                    <span>📅</span>
                                    <span>Enrolled: <?php echo formatDate($course['enrolled_at']); ?></span>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                                <a href="courses.php" class="btn btn-primary" style="flex: 1;">Continue Learning</a>
                                <a href="application-detail.php?id=<?php echo $app['id']; ?>" class="btn btn-outline">Details</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Sidebar Info -->
                <div>
                    <!-- Learning Progress -->
                    <div class="sidebar-card">
                        <h3>Your Learning Progress</h3>
                        <div class="chart-placeholder">
                            <p style="text-align: center; color: var(--medium-text);">
                                <strong style="font-size: 2rem; display: block; color: var(--primary-orange);"><?php echo $total_enrolled; ?></strong>
                                Courses Enrolled
                            </p>
                            <p style="text-align: center; color: var(--medium-text); margin-top: 1rem;">
                                <strong style="font-size: 2rem; display: block; color: var(--primary-blue);"><?php echo $approved_count; ?></strong>
                                Pending Enrollment
                            </p>
                        </div>
                    </div>

                    <!-- Learning Preferences -->
                    <div class="sidebar-card">
                        <h3>Learning Preferences</h3>
                        <p style="color: var(--medium-text); font-size: 0.9rem; margin-bottom: 1rem;">
                            Track your favorite subjects and interests
                        </p>
                        
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.9rem;">Business & Marketing</span>
                                <span style="font-weight: 600; color: var(--primary-orange);">50%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 50%;"></div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.9rem;">Data Science</span>
                                <span style="font-weight: 600; color: var(--primary-orange);">40%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 40%;"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.9rem;">Others</span>
                                <span style="font-weight: 600; color: var(--primary-orange);">10%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 10%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Notifications -->
                    <?php if (!empty($notifications)): ?>
                    <div class="sidebar-card">
                        <h3>Recent Notifications</h3>
                        <?php foreach ($notifications as $notif): ?>
                        <div class="alert alert-<?php echo $notif['type']; ?>" style="margin-bottom: 0.75rem;">
                            <strong style="display: block; margin-bottom: 0.25rem;">
                                <?php echo htmlspecialchars($notif['title']); ?>
                            </strong>
                            <p style="margin: 0; font-size: 0.875rem;">
                                <?php echo htmlspecialchars($notif['message']); ?>
                            </p>
                            <small style="opacity: 0.7; font-size: 0.75rem;">
                                <?php echo formatDate($notif['created_at']); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>