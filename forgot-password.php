<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expires, $email]);
            
            // In production, send email here
            // mail($email, "Password Reset", "Reset link: " . BASE_URL . "/reset-password.php?token=" . $token);
            
            $success = 'Password reset instructions have been sent to your email.';
        } else {
            // Don't reveal if email exists (security)
            $success = 'If an account exists with this email, password reset instructions have been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <!-- Left Side - Form -->
        <div class="auth-left">
            <!-- Mobile Menu -->
            <button class="menu-toggle" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <!-- Logo Icon -->
            <div class="auth-logo">
                <div class="logo-hexagon">🎓</div>
            </div>
            
            <div class="auth-content">
                <h1 class="auth-title">Forgot Password?</h1>
                <p class="auth-subtitle">Enter your email to receive reset instructions.</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form">
                    <div class="form-group">
                        <label for="email">EMAIL</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn-submit">Send Reset Link</button>
                    
                    <div class="auth-links">
                        <a href="login.php" class="link-secondary">Back to Login</a>
                        <a href="register.php" class="link-primary">Create Account</a>
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