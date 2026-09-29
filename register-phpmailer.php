<?php
/**
 * ALTERNATIVE register.php with PHPMailer
 * Use this if the basic mail() function doesn't work on your server
 * 
 * Installation:
 * 1. Run: composer require phpmailer/phpmailer
 * 2. Update the SMTP settings below
 * 3. Replace your register.php with this file
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
$conn = require __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader (if using Composer)
// require 'vendor/autoload.php';

// OR manually include PHPMailer files if not using Composer:
// require 'path/to/PHPMailer/src/Exception.php';
// require 'path/to/PHPMailer/src/PHPMailer.php';
// require 'path/to/PHPMailer/src/SMTP.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Registration handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $college = trim($_POST['college'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
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
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email already registered';
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $approved = 1; // Auto-approve user
                $role = 'student'; // Default role
                
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, Phone_Number, date_of_birth, college, approved, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssssssis", $full_name, $email, $hashed_password, $phone_number, $date_of_birth, $college, $approved, $role);
                
                if ($stmt->execute()) {
                    // Send notification email to admin using PHPMailer
                    try {
                        $mail = new PHPMailer(true);
                        
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // CHANGE THIS to your SMTP server
                        $mail->SMTPAuth = true;
                        $mail->Username = 'your-email@gmail.com'; // CHANGE THIS to your email
                        $mail->Password = 'your-app-password'; // CHANGE THIS to your app password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        
                        // Recipients
                        $mail->setFrom('noreply@' . $_SERVER['HTTP_HOST'], SITE_NAME);
                        $mail->addAddress('m.ravi@rpsonline.de'); // Admin email - CORRECTED
                        $mail->addReplyTo($email, $full_name); // User can reply to new student
                        
                        // Content
                        $mail->isHTML(false); // Set to plain text
                        $mail->Subject = 'New User Registration - ' . SITE_NAME;
                        $mail->Body = "New User Registration\n\n" .
                                     "Full Name: " . $full_name . "\n" .
                                     "Email: " . $email . "\n" .
                                     "Phone: " . $phone_number . "\n" .
                                     "Date of Birth: " . $date_of_birth . "\n" .
                                     "College: " . $college . "\n" .
                                     "Registration Date: " . date('Y-m-d H:i:s') . "\n\n" .
                                     "Login to admin panel to view more details.\n";
                        
                        $mail->send();
                    } catch (Exception $e) {
                        // Log error but don't show to user
                        error_log("Email sending failed: {$mail->ErrorInfo}");
                        // Registration still succeeds even if email fails
                    }
                    
                    $success = 'Registration successful! You can now login to your account.';
                    
                    // Clear form fields on success
                    $full_name = '';
                    $email = '';
                    $phone_number = '';
                    $date_of_birth = '';
                    $college = '';
                } else {
                    $error = 'Registration failed. Please try again. Error: ' . $stmt->error;
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
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
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .auth-left::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .auth-content {
            display: block;
            margin-top:200px;
            overflow-y: auto;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .auth-content::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
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
                        <label for="full_name">FULL NAME</label>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            placeholder="John Doe"
                            value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
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
                            value="<?php echo htmlspecialchars($email ?? ''); ?>"
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
                            value="<?php echo htmlspecialchars($phone_number ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">DATE OF BIRTH</label>
                        <input 
                            type="date" 
                            id="date_of_birth" 
                            name="date_of_birth" 
                            value="<?php echo htmlspecialchars($date_of_birth ?? ''); ?>"
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
                            value="<?php echo htmlspecialchars($college ?? ''); ?>"
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

    <script src="assets/js/auth.js"></script>
</body>
</html>