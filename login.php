<?php
/**
 * User Login Page
 * Includes CSRF protection, rate limiting, and generic error messages
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email)) {
            $error = 'Email is required';
        } elseif (empty($password)) {
            $error = 'Password is required';
        } else {
            // Check if account is locked due to too many attempts
            if (isLoginLocked($email)) {
                $remaining = getLoginLockoutTimeRemaining($email);
                $minutes = ceil($remaining / 60);
                $error = "Too many login attempts. Please try again in {$minutes} minute(s).";
            } else {
                try {
                    // Fetch user from database
                    $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, approved, role FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && password_verify($password, $user['password_hash'])) {
                        // Check if account is approved
                        if (!$user['approved']) {
                            $error = 'Your account is pending admin approval. You will receive an email once approved.';
                            recordFailedLogin($email);
                        } else {
                            // Login successful
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['full_name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_role'] = $user['role'] ?? 'student';
                            
                            // Regenerate session ID for security
                            session_regenerate_id(true);

                            // Clear login attempts
                            clearLoginAttempts($email);

                            // Redirect based on role
                            if ($user['role'] === 'admin') {
                                redirect('admin-dashboard.php');
                            } else {
                                redirect('dashboard.php');
                            }
                        }
                    } else {
                        // Invalid credentials - use generic message to prevent email enumeration
                        $error = 'Invalid email or password';
                        recordFailedLogin($email);
                    }
                } catch (Exception $e) {
                    if (APP_ENV === 'production') {
                        $error = 'Login error. Please try again later.';
                    } else {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                    recordFailedLogin($email);
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        /* Hide scrollbar but keep scroll functionality */
        .auth-left {
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .auth-left::-webkit-scrollbar {
            display: none;
        }
        
        .auth-content {
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .auth-content::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <!-- Left Side - Form -->
        <div class="auth-left">
            <button class="menu-toggle" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="auth-logo">
                <div class="logo-hexagon">🎓</div>
            </div>

            <div class="auth-content">
                <h1 class="auth-title">Login</h1>
                <p class="auth-subtitle">Enter your account to continue.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">
                    <?php csrfField(); ?>

                    <div class="form-group">
                        <label for="email">EMAIL</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">PASSWORD</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••"
                            required
                        >
                    </div>

                    <button type="submit" class="btn-submit">Login</button>

                    <div class="auth-links">
                        <a href="register.php" class="link-secondary">Don't have an account?</a>
                        <a href="index.php" class="link-primary">Back to Home</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side - Image -->
        <div class="auth-right">
            <div class="auth-image"></div>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>
