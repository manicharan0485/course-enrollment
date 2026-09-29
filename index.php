<?php
/**
 * Landing Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?> - Student Enrolment Journey</title>

    <link rel="stylesheet" href="assets/css/welcome.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Minimal Navbar -->
<nav class="navbar-minimal">
    <div class="container navbar-content" style="width:100vw; max-width:1200px;">
        <a href="index.php" 
           style="display:flex; align-items:center;">
            
            <img src="images/GIP GERMANY.png" 
                 alt="GIP Germany"
                 style="width:100px; height:auto; object-fit:contain; display:block;">
        </a>
        <div class="nav-actions">
            <?php if (isLoggedIn()): ?>
                <a href="dashboard.php" class="btn-nav">Dashboard</a>
                <a href="logout.php" class="btn-nav">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-nav">Login</a>
                <a href="register.php" class="btn-nav">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero Landing Section -->
<section class="hero-landing">
    <div class="hero-landing-content">

        <div class="hero-icon">
            <div class="hexagon">
                <div class="graduation-cap">🎓</div>
            </div>
        </div>

        <h1 class="hero-title">The Student Enrolment Journey:</h1>
        <h2 class="hero-subtitle">A Blueprint for a Seamless User Experience</h2>

        <p class="hero-description">
            Visualising the end-to-end workflow for the Course Registration and Payment Portal.
        </p>

        <a href="<?php echo isLoggedIn() ? 'dashboard.php' : 'register.php'; ?>" class="btn-cta">
            GET STARTED
        </a>
    </div>

    <div class="float-element float-1"></div>
    <div class="float-element float-2"></div>
    <div class="float-element float-3"></div>
</section>

<!-- Features Preview -->
<section class="features-minimal">
    <div class="container">
        <div class="features-grid-minimal">

            <div class="feature-item">
                <div class="feature-icon">📝</div>
                <h3>Simple Registration</h3>
                <p>Quick and easy sign-up process in minutes</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">🎯</div>
                <h3>Choose Your Path</h3>
                <p>Browse and select from various courses</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">💳</div>
                <h3>Secure Payment</h3>
                <p>Safe and encrypted payment processing</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">🚀</div>
                <h3>Start Learning</h3>
                <p>Begin your educational journey instantly</p>
            </div>

        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer-minimal">
    <div class="container">
        <div class="footer-content-minimal">
            <div class="footer-left">
                <p>&copy; 2026 GIIP Germany. All rights reserved.</p>
            </div>
            <div class="footer-links">
                <a href="courses.php">Courses</a>
                <a href="#">About</a>
                <a href="#">Contact</a>
                <a href="#">Privacy Policy</a>
            </div>
        </div>
    </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
