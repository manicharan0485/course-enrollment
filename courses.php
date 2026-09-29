<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get all courses
$stmt = $pdo->query("SELECT * FROM courses WHERE status = 'active' ORDER BY created_at DESC");
$courses = $stmt->fetchAll();

// Get user's applications
$stmt = $pdo->prepare("SELECT course_id FROM applications WHERE user_id = ?");
$stmt->execute([$user_id]);
$applied_courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $course_id = $_POST['course_id'] ?? 0;
    
    try {
        // Check if already applied
        $stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'You have already applied for this course!';
            header('Location: courses.php');
            exit;
        }
        
        // Check if created_at column exists in applications table
        $columns = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_COLUMN);
        $has_created_at = in_array('created_at', $columns);
        
        // Create application with or without created_at
        if ($has_created_at) {
            $stmt = $pdo->prepare("INSERT INTO applications (user_id, course_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
        } else {
            $stmt = $pdo->prepare("INSERT INTO applications (user_id, course_id, status) VALUES (?, ?, 'pending')");
        }
        
        if ($stmt->execute([$user_id, $course_id])) {
            $application_id = $pdo->lastInsertId();
            
            $_SESSION['success'] = 'Application submitted successfully!';
            
            // Redirect to application progress page
            header('Location: application-detail.php?id=' . $application_id);
            exit;
        } else {
            throw new Exception('Failed to create application');
        }
    } catch (Exception $e) {
        error_log("Application creation error: " . $e->getMessage());
        $_SESSION['error'] = 'Error creating application: ' . $e->getMessage();
        header('Location: courses.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/course.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/welcome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="dashboard.php">
                        <span class="icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="courses.php" class="active">
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
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search courses..." id="searchInput">
                </div>
            </header>
            <br>

            <!-- Page Header -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2><i class="fas fa-book"></i> Browse Courses</h2>
                    <p>Explore our available courses and start your learning journey.</p>
                </div>
                <div class="welcome-actions">
                    <a href="my-application.php" class="btn btn-secondary">
                        <i class="fas fa-file-alt"></i> My Applications
                    </a>
                </div>
            </section>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 2rem; padding: 1rem; background: rgba(16, 185, 129, 0.1); border: 1px solid #10B981; border-radius: 8px; color: #10B981;">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error" style="margin-bottom: 2rem; padding: 1rem; background: rgba(255, 81, 0, 0.1); border: 1px solid #EF4444; border-radius: 8px; color: #EF4444;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Courses Grid -->
            <?php if (empty($courses)): ?>
                <section class="card">
                    <div class="card-content">
                        <div style="text-align: center; padding: 3rem; color: #000000;">
                            <i class="fas fa-book" style="font-size: 5rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                            <h2>No Courses Available</h2>
                            <p>Check back later for new courses!</p>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <div class="courses-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card" style="background: #fff; border-radius: 12px; border: 2px solid rgb(255, 81, 0); transition: transform 0.3s, box-shadow 0.3s;">
                            <!-- Course Image -->
                            <div style="height: 200px; background: linear-gradient(135deg, #FF6B3D, #FF8A5C); position: static; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-graduation-cap" style="font-size: 4rem; color: rgba(255, 255, 255, 0.9);"></i>
                            </div>
                            
                            <!-- Course Content -->
                            <div style="padding: 1.5rem;">
                                <h3 style="color: #FF6B3D; margin-bottom: 0.5rem; font-size: 1.3rem;"><?php echo htmlspecialchars($course['title']); ?></h3>
                                
                                <p style="color: #000000; margin-bottom: 1.5rem; line-height: 1.6;">
                                    <?php echo htmlspecialchars(substr($course['description'], 0, 150)) . '...'; ?>
                                </p>
                                
                                <!-- Fees -->
                                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: rgba(255, 107, 61, 0.05); border-radius: 8px;">
                                    <div style="flex: 1;">
                                        <p style="color: #94A3B8; font-size: 0.85rem; margin-bottom: 0.25rem;">Registration Fee</p>
                                        <p style="color: #10B981; font-weight: bold; font-size: 1.2rem;">€<?php echo number_format($course['registration_fee'], 2); ?></p>
                                    </div>
                                    <div style="flex: 1;">
                                        <p style="color: #94A3B8; font-size: 0.85rem; margin-bottom: 0.25rem;">Programme Fee</p>
                                        <p style="color: #FF6B3D; font-weight: bold; font-size: 1.2rem;">€<?php echo number_format($course['programme_fee'], 2); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Apply Button -->
                                <?php if (in_array($course['id'], $applied_courses)): ?>
                                    <?php
                                    // Get application ID for this course
                                    $stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = ? AND course_id = ?");
                                    $stmt->execute([$user_id, $course['id']]);
                                    $app = $stmt->fetch();
                                    if ($app):
                                    ?>
                                        <a href="application-detail.php?id=<?php echo $app['id']; ?>" class="btn btn-primary" style="width: 100%; text-align: center; padding: 0.75rem; text-decoration: none; display: block; background: linear-gradient(135deg, #FF6B3D 0%, #FF8A5C 100%); color: white; border-radius: 8px; font-weight: 600;">
                                            <i class="fas fa-chart-line"></i> View Progress
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" name="apply" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 1rem; background: linear-gradient(135deg, #FF6B3D 0%, #FF8A5C 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                            <i class="fas fa-paper-plane"></i> Apply Now
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const courseCards = document.querySelectorAll('.course-card');
            
            courseCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Hover effect
        document.querySelectorAll('.course-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 30px rgba(255, 107, 61, 0.3)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>