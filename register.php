<?php
/**
 * User Registration Page
 * Includes CSRF protection, strong password validation, and no auto-approval
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $college = trim($_POST['college'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($full_name)) {
            $error = 'Full name is required';
        } elseif (empty($email)) {
            $error = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif (empty($phone_number)) {
            $error = 'Phone number is required';
        } elseif (empty($date_of_birth)) {
            $error = 'Date of birth is required';
        } elseif (empty($college)) {
            $error = 'College name is required';
        } elseif (empty($password)) {
            $error = 'Password is required';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number';
        } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\\/]/', $password)) {
            $error = 'Password must contain at least one special character (!@#$%^&*)';
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = 'Email already in use or registration error';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);
                    
                    // DO NOT auto-approve - requires admin approval
                    $approved = 0;
                    $role = 'student';
                    
                    // Insert user
                    $stmt = $pdo->prepare("
                        INSERT INTO users (full_name, email, password_hash, Phone_Number, date_of_birth, college, approved, role, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    if ($stmt->execute([$full_name, $email, $hashed_password, $phone_number, $date_of_birth, $college, $approved, $role])) {
                        // Send notification email to admin
                        $admin_email = getenv('ADMIN_EMAIL') ?: 'admin@example.com';
                        $subject = 'New User Registration - ' . SITE_NAME;
                        
                        $message = "New User Registration\n\n";
                        $message .= "Full Name: " . sanitize($full_name) . "\n";
                        $message .= "Email: " . sanitize($email) . "\n";
                        $message .= "Phone: " . sanitize($phone_number) . "\n";
                        $message .= "Date of Birth: " . sanitize($date_of_birth) . "\n";
                        $message .= "College: " . sanitize($college) . "\n";
                        $message .= "Registration Date: " . date('Y-m-d H:i:s') . "\n\n";
                        $message .= "Action Required: Login to admin panel to approve/reject this user.\n";
                        
                        $headers = "From: " . SITE_NAME . " <noreply@" . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost', ENT_QUOTES) . ">\r\n";
                        $headers .= "Reply-To: " . sanitize($email) . "\r\n";
                        $headers .= "X-Mailer: PHP/" . phpversion();
                        
                        @mail($admin_email, $subject, $message, $headers);
                        
                        $success = 'Registration successful! Your account is pending admin approval. You will receive an email once approved.';
                        
                        // Clear form
                        $full_name = '';
                        $email = '';
                        $phone_number = '';
                        $date_of_birth = '';
                        $college = '';
                    } else {
                        $error = 'Registration failed. Please try again later.';
                    }
                }
            } catch (Exception $e) {
                if (APP_ENV === 'production') {
                    $error = 'Registration error. Please contact support.';
                } else {
                    $error = 'Database error: ' . $e->getMessage();
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
    <title>Register - <?php echo SITE_NAME; ?></title>
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
            display: block;
            margin-top: 200px;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .auth-content::-webkit-scrollbar {
            display: none;
        }

        .password-requirements {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: #f5f5f5;
            border-radius: 6px;
        }

        .requirement {
            margin: 0.25rem 0;
            color: #999;
        }

        .requirement.met {
            color: #10b981;
        }

        .requirement:before {
            content: '✗ ';
            margin-right: 0.25rem;
        }

        .requirement.met:before {
            content: '✓ ';
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
                <h1 class="auth-title">Register</h1>
                <p class="auth-subtitle">Create your account to get started.</p>

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
                        <label for="full_name">FULL NAME</label>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            placeholder="John Doe"
                            value="<?php echo htmlspecialchars($full_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">EMAIL</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone_number">PHONE NUMBER</label>
                        <input 
                            type="tel" 
                            id="phone_number" 
                            name="phone_number" 
                            placeholder="+1 234 567 8900"
                            value="<?php echo htmlspecialchars($phone_number ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">DATE OF BIRTH</label>
                        <input 
                            type="date" 
                            id="date_of_birth" 
                            name="date_of_birth" 
                            value="<?php echo htmlspecialchars($date_of_birth ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="college">COLLEGE</label>
                        <input 
                            type="text" 
                            id="college" 
                            name="college" 
                            placeholder="Your College Name"
                            value="<?php echo htmlspecialchars($college ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                            onchange="validatePassword()"
                            oninput="validatePassword()"
                        >
                        <div class="password-requirements">
                            <div class="requirement" id="req-length">At least <?php echo PASSWORD_MIN_LENGTH; ?> characters</div>
                            <div class="requirement" id="req-upper">One uppercase letter (A-Z)</div>
                            <div class="requirement" id="req-lower">One lowercase letter (a-z)</div>
                            <div class="requirement" id="req-number">One number (0-9)</div>
                            <div class="requirement" id="req-special">One special character (!@#$%^&*)</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">CONFIRM PASSWORD</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="••••••••"
                            required
                        >
                    </div>

                    <button type="submit" class="btn-submit">Register</button>

                    <div class="auth-links">
                        <a href="login.php" class="link-secondary">Already have an account?</a>
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

    <script>
        function validatePassword() {
            const password = document.getElementById('password').value;
            
            const requirements = {
                'req-length': password.length >= <?php echo PASSWORD_MIN_LENGTH; ?>,
                'req-upper': /[A-Z]/.test(password),
                'req-lower': /[a-z]/.test(password),
                'req-number': /[0-9]/.test(password),
                'req-special': /[!@#$%^&*()_+\-=\[\]{};:'"",.<>?\/]/.test(password)
            };
            
            for (const [id, met] of Object.entries(requirements)) {
                const el = document.getElementById(id);
                if (met) {
                    el.classList.add('met');
                } else {
                    el.classList.remove('met');
                }
            }
        }
    </script>

    <script src="assets/js/auth.js"></script>
</body>
</html>
