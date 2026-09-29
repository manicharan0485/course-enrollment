<?php
/**
 * Payment Processing Page
 * Handles registration and program fee payments
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get application ID from URL
$application_id = $_GET['id'] ?? 0;

// Get application details
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title, c.registration_fee, c.program_fee 
    FROM applications a 
    JOIN courses c ON a.course_id = c.id 
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->execute([$application_id, $user_id]);
$application = $stmt->fetch();

if (!$application) {
    $_SESSION['error'] = 'Application not found';
    header('Location: dashboard.php');
    exit();
}

// Get existing payments
$stmt = $pdo->prepare("
    SELECT * FROM payments 
    WHERE application_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$application_id]);
$payments = $stmt->fetchAll();

// Determine payment stage
$registration_paid = false;
$program_paid = false;

foreach ($payments as $payment) {
    if ($payment['payment_type'] == 'registration' && $payment['status'] == 'completed') {
        $registration_paid = true;
    }
    if ($payment['payment_type'] == 'program' && $payment['status'] == 'completed') {
        $program_paid = true;
    }
}

// Generate CSRF token
$csrf_token = getCSRFToken();

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $payment_type = $_POST['payment_type'] ?? '';
        $transaction_id = $_POST['transaction_id'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $payment_method = $_POST['payment_method'] ?? '';
        $payment_proof = '';
        
        // Debug: Log the submission
        error_log("Payment submission received:");
        error_log("User ID: $user_id");
        error_log("Application ID: $application_id");
        error_log("Payment Type: $payment_type");
        error_log("Amount: $amount");
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
            error_log("Created upload directory: $upload_dir");
        }
        
        // Handle file upload
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
            $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $file_name = 'payment_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
                $payment_proof = $upload_path;
                error_log("File uploaded successfully: $upload_path");
            } else {
                error_log("File upload failed!");
                $_SESSION['error'] = 'Failed to upload payment proof. Please try again.';
            }
        } else {
            $upload_error = $_FILES['payment_proof']['error'] ?? 'No file';
            error_log("File upload error: $upload_error");
        }
        
        // Insert payment record
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, application_id, payment_type, amount, payment_method, transaction_id, payment_proof, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                $user_id, 
                $application_id, 
                $payment_type, 
                $amount, 
                $payment_method, 
                $transaction_id, 
                $payment_proof
            ]);
            
            if ($result) {
                $payment_id = $pdo->lastInsertId();
                error_log("Payment inserted successfully! ID: $payment_id");
                $_SESSION['success'] = 'Payment submitted successfully! Waiting for admin approval. (Payment ID: #' . $payment_id . ')';
                header('Location: payment.php?id=' . $application_id);
                exit();
            } else {
                error_log("Payment insert failed but no exception");
                $_SESSION['error'] = 'Failed to submit payment. Please contact support.';
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/welcome.css">
    <link rel="stylesheet" href="assets/css/payment.css">
    <link rel="stylesheet" href="assets/css/course.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Payment Gateway Scripts -->
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=GBP"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    
    <style>
        body {
            overflow-x: hidden;
        }
        
        .payment-gateway-section {
            background: linear-gradient(135deg, rgba(255, 107, 61, 0.05), rgba(255, 138, 92, 0.05));
            border: 2px dashed #FF6B3D;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .gateway-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .gateway-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .gateway-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border-color: #FF6B3D;
        }
        
        .gateway-btn img {
            height: 40px;
            margin-bottom: 0.5rem;
        }
        
        .gateway-btn .gateway-name {
            font-weight: 600;
            color: #1E293B;
            margin-top: 0.5rem;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 2rem 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 2px solid #E2E8F0;
        }
        
        .divider span {
            padding: 0 1rem;
            color: #64748B;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .payment-info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid #3B82F6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-info-box i {
            color: #3B82F6;
            margin-right: 0.5rem;
        }
        
        /* Bank Popup */
        .bank-popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .bank-popup-box {
            width: 95%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            animation: popupSlide .3s ease;
        }
        
        @keyframes popupSlide {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .bank-popup-header {
            background: linear-gradient(135deg, #FF6B3D, #FF8A5C);
            color: white;
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .bank-popup-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .bank-close {
            font-size: 22px;
            cursor: pointer;
        }
        
        .bank-popup-content {
            padding: 20px;
        }
        
        .bank-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #F8FAFC;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 12px;
            transition: .2s;
        }
        
        .bank-row:hover {
            background: #EEF2FF;
        }
        
        .bank-row strong {
            display: block;
            font-size: 13px;
            color: #64748B;
        }
        
        .bank-row p {
            margin: 2px 0 0;
            font-weight: 600;
            color: #1E293B;
        }
        
        .highlight {
            border: 2px solid #FF6B3D;
        }
        
        .copy-btn {
            background: white;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            cursor: pointer;
            color: #FF6B3D;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            transition: .2s;
        }
        
        .copy-btn:hover {
            transform: scale(1.1);
            background: #FF6B3D;
            color: white;
        }
        
        .bank-note {
            margin-top: 15px;
            font-size: 14px;
            color: #64748B;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        
        #copyToast {
            position: fixed;
            top: 30px;
            right: 30px;
            background: #10B981;
            color: white;
            padding: 14px 22px;
            border-radius: 10px;
            display: none;
            font-weight: 500;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            animation: slideIn .3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(50px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
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
            <br>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
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
                    <a href="my-application.php" class="active">
                        <span class="icon">📝</span>
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
            <section class="welcome-section">
                <div class="welcome-content">
                    <h1><i class="fas fa-credit-card"></i> Course Payment</h1>
                    <p><?php echo htmlspecialchars($application['course_title']); ?></p>
                </div>
                <div class="welcome-actions">
                    <a href="my-application.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Applications
                    </a>
                </div>
            </section>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 2rem; padding: 1rem; background: rgba(16, 185, 129, 0.1); border: 1px solid #10B981; border-radius: 8px; color: #059669;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error" style="margin-bottom: 2rem; padding: 1rem; background: rgba(239, 68, 68, 0.1); border: 1px solid #EF4444; border-radius: 8px; color: #EF4444;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Payment Progress -->
            <section class="card">
                <div class="card-header">
                    <h2><i class="fas fa-tasks"></i> Payment Progress</h2>
                </div>
                <div class="card-content">
                    <div style="display: flex; gap: 2rem; align-items: center; justify-content: space-around; padding: 2rem; flex-wrap: wrap;">
                        <!-- Step 1 -->
                        <div style="text-align: center; flex: 1; min-width: 150px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: <?php echo $registration_paid ? 'linear-gradient(135deg, #10B981, #059669)' : 'rgba(255, 107, 61, 0.2)'; ?>; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; border: 3px solid <?php echo $registration_paid ? '#10B981' : '#FF6B3D'; ?>;">
                                <?php if ($registration_paid): ?>
                                    <i class="fas fa-check" style="color: white;"></i>
                                <?php else: ?>
                                    <span style="color: #FF6B3D; font-weight: bold;">1</span>
                                <?php endif; ?>
                            </div>
                            <h4 style="color:#1E293B;">Registration Fee</h4>
                            <p style="color: #1E293B; font-size: 1.2rem; font-weight: bold;">€<?php echo number_format($application['registration_fee'], 2); ?></p>
                            <span class="badge <?php echo $registration_paid ? 'approved' : 'pending'; ?>">
                                <?php echo $registration_paid ? 'Paid ✓' : 'Pending'; ?>
                            </span>
                        </div>
                        
                        <!-- Arrow -->
                        <div style="font-size: 2rem;">
                            <i class="fas fa-arrow-right" style="color: #64748B;"></i>
                        </div>
                        
                        <!-- Step 2 -->
                        <div style="text-align: center; flex: 1; min-width: 150px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: <?php echo $program_paid ? 'linear-gradient(135deg, #10B981, #059669)' : ($registration_paid ? 'rgba(255, 107, 61, 0.2)' : 'rgba(148, 163, 184, 0.1)'); ?>; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; border: 3px solid <?php echo $program_paid ? '#10B981' : ($registration_paid ? '#FF6B3D' : '#94A3B8'); ?>;">
                                <?php if ($program_paid): ?>
                                    <i class="fas fa-check" style="color: white;"></i>
                                <?php else: ?>
                                    <span style="color: <?php echo $registration_paid ? '#FF6B3D' : '#94A3B8'; ?>; font-weight: bold;">2</span>
                                <?php endif; ?>
                            </div>
                            <h4 style="color:#1E293B;">Program Fee</h4>
                            <p style="color: #1E293B; font-size: 1.2rem; font-weight: bold;">€<?php echo number_format($application['program_fee'], 2); ?></p>
                            <span class="badge <?php echo $program_paid ? 'approved' : ($registration_paid ? 'pending' : 'rejected'); ?>">
                                <?php echo $program_paid ? 'Paid ✓' : ($registration_paid ? 'Ready to Pay' : 'Locked'); ?>
                            </span>
                        </div>
                        
                        <!-- Arrow -->
                        <div style="font-size: 2rem;">
                            <i class="fas fa-arrow-right" style="color: #64748B;"></i>
                        </div>
                        
                        <!-- Step 3 -->
                        <div style="text-align: center; flex: 1; min-width: 150px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: <?php echo ($registration_paid && $program_paid) ? 'linear-gradient(135deg, #10B981, #059669)' : 'rgba(148, 163, 184, 0.1)'; ?>; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; border: 3px solid <?php echo ($registration_paid && $program_paid) ? '#10B981' : '#94A3B8'; ?>;">
                                <?php if ($registration_paid && $program_paid): ?>
                                    <i class="fas fa-graduation-cap" style="color: white;"></i>
                                <?php else: ?>
                                    <i class="fas fa-lock" style="color: #94A3B8;"></i>
                                <?php endif; ?>
                            </div>
                            <h4 style="color:#1E293B;">Enrollment</h4>
                            <p style="color: #1E293B;">Complete</p>
                            <span class="badge <?php echo ($registration_paid && $program_paid) ? 'approved' : 'pending'; ?>">
                                <?php echo ($registration_paid && $program_paid) ? 'Enrolled ✓' : 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="dashboard-grid">
                <!-- Payment Form -->
                <section class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-credit-card"></i> Make Payment</h2>
                    </div>
                    <div class="card-content">
                        <?php if (!$registration_paid): ?>
                            <!-- Registration Fee Payment -->
                            <div class="payment-info-box">
                                <i class="fas fa-info-circle"></i>
                                <strong>Payment Required:</strong> Registration Fee - €<?php echo number_format($application['registration_fee'], 2); ?>
                            </div>
                            
                            <!-- Payment Gateway Section -->
                            <div class="payment-gateway-section">
                                <h3 style="text-align: center; color: #1E293B; margin-bottom: 0.5rem;">
                                    Payment Options
                                </h3>
                                <p style="text-align: center; color: #64748B; margin-bottom: 1.5rem;">Pay securely with your preferred payment method</p>
                                
                                <div class="gateway-buttons">
                                    <!-- Stripe -->
                                    <button class="gateway-btn" onclick="payWithStripe('registration', <?php echo $application['registration_fee']; ?>)">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg" alt="Stripe">
                                        <span class="gateway-name">Pay with Stripe</span>
                                        <small style="color: #64748B;">Cards & More</small>
                                    </button>
                                    
                                    <!-- PayPal -->
                                    <div class="gateway-btn" id="paypal-button-container-registration">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal">
                                        <span class="gateway-name">Pay with PayPal</span>
                                        <small style="color: #64748B;">Fast & Secure</small>
                                    </div>
                                    
                                    <!-- Razorpay -->
                                    <button class="gateway-btn" onclick="payWithRazorpay('registration', <?php echo $application['registration_fee']; ?>)">
                                        <img src="https://razorpay.com/assets/razorpay-glyph.svg" alt="Razorpay">
                                        <span class="gateway-name">Pay with Razorpay</span>
                                        <small style="color: #64748B;">UPI, Cards & More</small>
                                    </button>
                                    
                                    <!-- Bank Transfer -->
                                    <button class="gateway-btn" onclick="openBankPopup()">
                                        <i class="fas fa-university" style="font-size: 2rem; color: #FF6B3D; margin-bottom: 0.5rem;"></i>
                                        <span class="gateway-name">Bank Transfer</span>
                                        <small style="color: #64748B;">Upload Receipt Below</small>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="divider">
                                <span>OR UPLOAD PAYMENT PROOF</span>
                            </div>
                            
                            <!-- Registration Fee Form (Always Visible) -->
                            <form method="POST" enctype="multipart/form-data" class="auth-form" onsubmit="return validateForm(this)">
                                <?php csrfField(); ?>
                                <input type="hidden" name="payment_type" value="registration">
                                <input type="hidden" name="amount" value="<?php echo $application['registration_fee']; ?>">
                                
                                <div class="form-group">
                                    <label>Payment Type</label>
                                    <input type="text" value="Registration Fee" readonly style="background: rgba(255,255,255,0.05);">
                                </div>
                                
                                <div class="form-group">
                                    <label>Amount</label>
                                    <input type="text" value="€<?php echo number_format($application['registration_fee'], 2); ?>" readonly style="background: rgba(255,255,255,0.05); font-size: 1.2rem; font-weight: bold; color: #FF6B3D;">
                                </div>
                                
                                <div class="form-group">
                                    <label>Payment Method *</label>
                                    <select name="payment_method" required>
                                        <option value="">Select Method</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="debit_card">Debit Card</option>
                                        <option value="paypal">PayPal</option>
                                        <option value="stripe">Stripe</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Transaction ID / Reference Number *</label>
                                    <input type="text" name="transaction_id" placeholder="Enter transaction ID or reference number" required>
                                    <small style="color: #94A3B8;">Enter the transaction ID from your bank or payment gateway</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Upload Payment Proof * (Screenshot/Receipt)</label>
                                    <input type="file" name="payment_proof" accept="image/*,.pdf" required onchange="previewFile(this)">
                                    <small style="color: #94A3B8;">Upload screenshot or PDF of payment receipt (Max 10MB)</small>
                                    <div id="file-preview" style="margin-top: 1rem;"></div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                                    <i class="fas fa-check"></i> Submit Registration Fee Payment
                                </button>
                            </form>
                            
                        <?php elseif (!$program_paid): ?>
                            <!-- Program Fee Payment -->
                            <div style="padding: 1rem; background: rgba(16, 185, 129, 0.1); border: 1px solid #10B981; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
                                <i class="fas fa-check-circle" style="font-size: 2rem; color: #10B981;"></i>
                                <p style="color: #10B981; font-weight: bold; margin: 0.5rem 0;">Registration Fee Approved!</p>
                                <p style="color: #64748B; font-size: 0.9rem;">You can now proceed with the program fee payment</p>
                            </div>
                            
                            <div class="payment-info-box">
                                <i class="fas fa-info-circle"></i>
                                <strong>Payment Required:</strong> Program Fee - €<?php echo number_format($application['program_fee'], 2); ?>
                            </div>
                            
                            <!-- Payment Gateway Section -->
                            <div class="payment-gateway-section">
                                <h3 style="text-align: center; color: #1E293B; margin-bottom: 0.5rem;">
                                    <i class="fas fa-bolt" style="color: #FF6B3D;"></i> Quick Payment Options
                                </h3>
                                <p style="text-align: center; color: #64748B; margin-bottom: 1.5rem;">Pay securely with your preferred payment method</p>
                                
                                <div class="gateway-buttons">
                                    <!-- Stripe -->
                                    <button class="gateway-btn" onclick="payWithStripe('program', <?php echo $application['program_fee']; ?>)">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg" alt="Stripe">
                                        <span class="gateway-name">Pay with Stripe</span>
                                        <small style="color: #64748B;">Cards & More</small>
                                    </button>
                                    
                                    <!-- PayPal -->
                                    <div class="gateway-btn" id="paypal-button-container-program">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal">
                                        <span class="gateway-name">Pay with PayPal</span>
                                        <small style="color: #64748B;">Fast & Secure</small>
                                    </div>
                                    
                                    <!-- Razorpay -->
                                    <button class="gateway-btn" onclick="payWithRazorpay('program', <?php echo $application['program_fee']; ?>)">
                                        <img src="https://razorpay.com/assets/razorpay-glyph.svg" alt="Razorpay">
                                        <span class="gateway-name">Pay with Razorpay</span>
                                        <small style="color: #64748B;">UPI, Cards & More</small>
                                    </button>
                                    
                                    <!-- Bank Transfer -->
                                    <button class="gateway-btn" onclick="openBankPopup()">
                                        <i class="fas fa-university" style="font-size: 2rem; color: #FF6B3D; margin-bottom: 0.5rem;"></i>
                                        <span class="gateway-name">Bank Transfer</span>
                                        <small style="color: #64748B;">Upload Receipt Below</small>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="divider">
                                <span>OR UPLOAD PAYMENT PROOF</span>
                            </div>
                            
                            <!-- Program Fee Form (Always Visible) -->
                            <form method="POST" enctype="multipart/form-data" class="auth-form" onsubmit="return validateForm(this)">
                                <?php csrfField(); ?>
                                <input type="hidden" name="payment_type" value="program">
                                <input type="hidden" name="amount" value="<?php echo $application['program_fee']; ?>">
                                
                                <div class="form-group">
                                    <label>Payment Type</label>
                                    <input type="text" value="Program Fee" readonly style="background: rgba(255,255,255,0.05);">
                                </div>
                                
                                <div class="form-group">
                                    <label>Amount</label>
                                    <input type="text" value="€<?php echo number_format($application['program_fee'], 2); ?>" readonly style="background: rgba(255,255,255,0.05); font-size: 1.2rem; font-weight: bold; color: #FF6B3D;">
                                </div>
                                
                                <div class="form-group">
                                    <label>Payment Method *</label>
                                    <select name="payment_method" required>
                                        <option value="">Select Method</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="debit_card">Debit Card</option>
                                        <option value="paypal">PayPal</option>
                                        <option value="stripe">Stripe</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Transaction ID / Reference Number *</label>
                                    <input type="text" name="transaction_id" placeholder="Enter transaction ID or reference number" required>
                                    <small style="color: #94A3B8;">Enter the transaction ID from your bank or payment gateway</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Upload Payment Proof * (Screenshot/Receipt)</label>
                                    <input type="file" name="payment_proof" accept="image/*,.pdf" required onchange="previewFile(this)">
                                    <small style="color: #94A3B8;">Upload screenshot or PDF of payment receipt (Max 10MB)</small>
                                    <div id="file-preview" style="margin-top: 1rem;"></div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                                    <i class="fas fa-check"></i> Submit Program Fee Payment
                                </button>
                            </form>
                            
                        <?php else: ?>
                            <!-- All Paid -->
                            <div style="text-align: center; padding: 3rem;">
                                <i class="fas fa-check-circle" style="font-size: 5rem; color: #10B981; margin-bottom: 1rem;"></i>
                                <h2 style="color: #10B981;">Payment Complete!</h2>
                                <p style="color: #64748B; margin-bottom: 2rem;">You have successfully completed all payments for this course.</p>
                                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                                    <a href="dashboard.php" class="btn btn-primary">
                                        <i class="fas fa-home"></i> Go to Dashboard
                                    </a>
                                    <a href="application-detail.php?id=<?php echo $application['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-file-alt"></i> View Applications
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Payment History -->
                <section class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Payment History</h2>
                    </div>
                    <div class="card-content">
                        <?php if (empty($payments)): ?>
                            <div style="text-align: center; padding: 2rem; color: #94A3B8;">
                                <i class="fas fa-file-invoice" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                                <p>No payments submitted yet</p>
                            </div>
                        <?php else: ?>
                            <div class="user-list">
                                <?php foreach ($payments as $payment): ?>
                                    <div class="user-item" style="align-items: flex-start; border: 1px solid rgba(255,255,255,0.1); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                        <i class="fas fa-receipt" style="font-size: 1.5rem; color: #FF6B3D;"></i>
                                        <div class="user-info" style="flex: 1;">
                                            <h4 style="color: #FF6B3D;"><?php echo ucfirst($payment['payment_type']); ?> Fee</h4>
                                            <p><strong>Amount:</strong> €<?php echo number_format($payment['amount'], 2); ?></p>
                                            <p><strong>Method:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?></p>
                                            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($payment['transaction_id']); ?></p>
                                            <?php if ($payment['payment_proof']): ?>
                                                <p><strong>Proof:</strong> <a href="<?php echo htmlspecialchars($payment['payment_proof']); ?>" target="_blank" style="color: #10B981;">View Receipt</a></p>
                                            <?php endif; ?>
                                            <span class="date"><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></span>
                                        </div>
                                        <span class="badge <?php echo $payment['status']; ?>" style="font-size: 1rem;">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script>
        // Stripe Payment
        const stripe = Stripe('YOUR_STRIPE_PUBLISHABLE_KEY');
        
        function payWithStripe(paymentType, amount) {
            alert('Stripe Payment Integration\n\nPayment Type: ' + paymentType + '\nAmount: €' + amount + '\n\nThis is a demo. Please configure your Stripe keys in the code.');
        }
        
        // Razorpay Payment
        function payWithRazorpay(paymentType, amount) {
            const options = {
                "key": "YOUR_RAZORPAY_KEY",
                "amount": amount * 100,
                "currency": "GBP",
                "name": "Indo-Euro Synchronization",
                "description": paymentType + " Fee Payment",
                "image": "assets/images/logo.png",
                "handler": function (response) {
                    alert("Payment successful!\nPayment ID: " + response.razorpay_payment_id);
                    window.location.href = 'payment.php?id=<?php echo $application_id; ?>&payment_success=1';
                },
                "prefill": {
                    "name": "<?php echo htmlspecialchars($user_name); ?>",
                    "email": "",
                    "contact": ""
                },
                "theme": {
                    "color": "#FF6B3D"
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        }
        
        // PayPal Integration
        if (document.getElementById('paypal-button-container-registration')) {
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: '<?php echo $application['registration_fee']; ?>'
                            }
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        alert('Payment completed by ' + details.payer.name.given_name);
                        window.location.href = 'payment.php?id=<?php echo $application_id; ?>&payment_success=1';
                    });
                }
            }).render('#paypal-button-container-registration');
        }
        
        if (document.getElementById('paypal-button-container-program')) {
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: '<?php echo $application['program_fee']; ?>'
                            }
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        alert('Payment completed by ' + details.payer.name.given_name);
                        window.location.href = 'payment.php?id=<?php echo $application_id; ?>&payment_success=1';
                    });
                }
            }).render('#paypal-button-container-program');
        }
        
        function validateForm(form) {
            const file = form.querySelector('input[type="file"]').files[0];
            if (!file) {
                alert('Please select a payment proof file');
                return false;
            }
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                return false;
            }
            return confirm('Are you sure you want to submit this payment? Make sure all details are correct.');
        }
        
        function previewFile(input) {
            const preview = document.getElementById('file-preview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        preview.innerHTML = `<img src="${e.target.result}" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 2px solid #FF6B3D;">`;
                    } else {
                        preview.innerHTML = `<p style="color: #10B981;"><i class="fas fa-file-pdf"></i> ${file.name} (${(file.size / 1024).toFixed(2)} KB)</p>`;
                    }
                };
                reader.readAsDataURL(file);
            }
        }
        
        // Bank Popup
        function openBankPopup() {
            document.getElementById("bankPopup").style.display = "flex";
        }
        
        function closeBankPopup() {
            document.getElementById("bankPopup").style.display = "none";
        }
        
        function copyText(id) {
            const text = document.getElementById(id).innerText.trim();
            navigator.clipboard.writeText(text)
                .then(() => {
                    showToast("Copied successfully!");
                })
                .catch(() => {
                    showToast("Copy failed!");
                });
        }
        
        function showToast(message) {
            const toast = document.getElementById("copyToast");
            toast.innerHTML = `<i class="fas fa-check"></i> ${message}`;
            toast.style.display = "block";
            
            setTimeout(() => {
                toast.style.display = "none";
            }, 2000);
        }
    </script>
    
    <!-- Bank Popup -->
    <div id="bankPopup" class="bank-popup-overlay">
        <div class="bank-popup-box">
            <div class="bank-popup-header">
                <h3><i class="fas fa-university"></i> Bank Transfer Details</h3>
                <span class="bank-close" onclick="closeBankPopup()">&times;</span>
            </div>
            
            <div class="bank-popup-content">
                <div class="bank-row">
                    <div>
                        <strong>Account Name</strong>
                        <p id="accName">Indo Euro Synchronization</p>
                    </div>
                    <button class="copy-btn" onclick="copyText('accName')" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <div class="bank-row">
                    <div>
                        <strong>Bank Name</strong>
                        <p id="bankName">ICICI Bank</p>
                    </div>
                    <button class="copy-btn" onclick="copyText('bankName')" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <div class="bank-row highlight">
                    <div>
                        <strong>Account Number</strong>
                        <p id="accNumber">180305002202</p>
                    </div>
                    <button class="copy-btn" onclick="copyText('accNumber')" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <div class="bank-row">
                    <div>
                        <strong>IFSC Code</strong>
                        <p id="ifsc">ICIC0001803</p>
                    </div>
                    <button class="copy-btn" onclick="copyText('ifsc')" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <div class="bank-row">
                    <div>
                        <strong>Branch</strong>
                        <p id="branch">Eluru Road, Vijayawada</p>
                    </div>
                    <button class="copy-btn" onclick="copyText('branch')" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <div class="bank-note">
                    <i class="fas fa-info-circle"></i>
                    After transferring, upload the payment receipt below and submit the form.
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="copyToast"></div>
</body>
</html>
