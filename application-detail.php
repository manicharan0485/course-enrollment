<?php
// Add these lines at the very top
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get application ID from URL
$application_id = $_GET['id'] ?? 0;

// Get application details with course info
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title, c.description, c.registration_fee, c.programme_fee, c.duration
    FROM applications a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->execute([$application_id, $user_id]);
$application = $stmt->fetch();

if (!$application) {
    // Add error logging
    error_log("Application not found - ID: $application_id, User: $user_id");
    
    $_SESSION['error'] = 'Application not found!';
    header('Location: courses.php');
    exit;
}

// Get payment status
$stmt = $pdo->prepare("
    SELECT payment_type, status 
    FROM payments 
    WHERE application_id = ?
");
$stmt->execute([$application_id]);
$payment_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process payments
$registration_paid = false;
$programme_paid = false;

foreach ($payment_records as $payment) {
    if ($payment['payment_type'] === 'registration' && $payment['status'] === 'completed') {
        $registration_paid = true;
    }
    if (in_array($payment['payment_type'], ['program', 'programme']) && $payment['status'] === 'completed') {
        $programme_paid = true;
    }
}

// Get document status
$stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE application_id = ?");
$stmt->execute([$application_id]);
$documents_count = $stmt->fetchColumn();

// Calculate progress (6 steps)
$progress = 0;
$current_step = 1;

// Step 1: Registration (user registered - always complete if they're logged in)
$progress = 16.67; // 1/6 = 16.67%
$current_step = 2;

// Step 2: Application submitted (always complete if application exists)
if ($application) {
    $progress = 33.33; // 2/6 = 33.33%
    $current_step = 3;
}

// Step 3: Documents uploaded
if ($documents_count > 0) {
    $progress = 50; // 3/6 = 50%
    $current_step = 4;
}

// Step 4: Registration fee paid
if ($registration_paid) {
    $progress = 66.67; // 4/6 = 66.67%
    $current_step = 5;
}

// Step 5: Programme fee paid
if ($programme_paid) {
    $progress = 83.33; // 5/6 = 83.33%
    $current_step = 6;
}

// Step 6: Enrolled
if ($application['status'] === 'enrolled') {
    $progress = 100; // 6/6 = 100%
    $current_step = 7;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Progress - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Progress Container */
        .progress-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid #E2E8F0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .progress-header h2 {
            color: #1A1A1A;
            margin: 0;
            font-size: 1.75rem;
        }

        .progress-percentage {
            font-size: 2.5rem;
            font-weight: 700;
            color: #FF6B3D;
        }

        .progress-bar-wrapper {
            height: 10px;
            background: #F1F3F5;
            border-radius: 10px;
            margin-bottom: 3rem;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #FF6B3D 0%, #FF8A5C 100%);
            border-radius: 10px;
            transition: width 0.8s ease;
            box-shadow: 0 2px 8px rgba(255, 107, 61, 0.3);
        }

        /* Progress Steps - 6 steps layout */
        .progress-steps {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
            position: relative;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .step-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            z-index: 2;
            position: relative;
        }

        .step-circle.completed {
            background: #FF6B3D;
            color: white;
            border: 4px solid #FF6B3D;
            box-shadow: 0 4px 12px rgba(255, 107, 61, 0.3);
        }

        .step-circle.current {
            background: white;
            color: #FF6B3D;
            border: 4px solid #FF6B3D;
            animation: pulse 2s infinite;
            box-shadow: 0 4px 12px rgba(255, 107, 61, 0.3);
        }

        .step-circle.pending {
            background: #F8F9FA;
            color: #94A3B8;
            border: 4px solid #E2E8F0;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 4px 12px rgba(255, 107, 61, 0.3), 0 0 0 0 rgba(255, 107, 61, 0.4);
            }
            50% {
                box-shadow: 0 4px 12px rgba(255, 107, 61, 0.3), 0 0 0 15px rgba(255, 107, 61, 0);
            }
        }

        .step-label {
            text-align: center;
            font-weight: 600;
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.3;
        }

        .step-circle.completed ~ .step-label {
            color: #FF6B3D;
        }

        .step-circle.current ~ .step-label {
            color: #FF6B3D;
            font-weight: 700;
        }

        /* Course Info Banner */
        .course-info {
            background: linear-gradient(135deg, #FF6B3D 0%, #FF8A5C 100%);
            padding: 2.5rem;
            border-radius: 20px;
            color: white;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px rgba(255, 107, 61, 0.3);
        }

        .course-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }

        .course-info p {
            opacity: 0.95;
            margin: 0 0 1rem 0;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Action Cards */
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .action-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #E2E8F0;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .action-card h3 {
            color: #1A1A1A;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
        }

        .action-card p {
            color: #4A5568;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        /* Success Message */
        .success-message {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            padding: 3rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        }

        .success-message i {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: successPop 0.6s ease;
        }

        @keyframes successPop {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .success-message h2 {
            margin: 0 0 1rem 0;
            font-size: 2.5rem;
        }

        .success-message p {
            font-size: 1.2rem;
            margin: 0;
            opacity: 0.95;
        }

        /* Alert Box */
        .alert {
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-info {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: #1E40AF;
        }

        .alert i {
            font-size: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .progress-steps {
                grid-template-columns: repeat(3, 1fr);
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .progress-steps {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .step-circle {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .step-label {
                font-size: 0.85rem;
            }
            
            .course-info h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .progress-container {
                padding: 1.5rem;
            }
            
            .progress-steps {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
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
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="icon">📊</span><span>Dashboard</span></a></li>
                <li><a href="courses.php"><span class="icon">📚</span><span>Courses</span></a></li>
                <li><a href="my-application.php" class="active"><span class="icon">📋</span><span>My Applications</span></a></li>
                <li><a href="profile.php"><span class="icon">👤</span><span>Profile</span></a></li>
                <li><a href="logout.php"><span class="icon">🚪</span><span>Logout</span></a></li>
                <li><a href="help.php"><span class="icon">❓</span><span>Help & Support</span></a></li>
            </ul>
        </aside>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</button>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <h2>Application Progress</h2>
                <div class="top-bar-actions">
                    <a href="my-application.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Applications
                    </a>
                </div>
            </header>

            <!-- Course Info -->
            <div class="course-info">
                <h1><?php echo htmlspecialchars($application['course_title']); ?></h1>
                <p><?php echo htmlspecialchars($application['description']); ?></p>
                <span class="status-badge">
                    <?php echo strtoupper($application['status']); ?>
                </span>
            </div>

            <!-- Progress Container -->
            <div class="progress-container">
                <div class="progress-header">
                    <h2>Application Progress</h2>
                    <div class="progress-percentage"><?php echo number_format($progress, 0); ?>%</div>
                </div>

                <div class="progress-bar-wrapper">
                    <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%;"></div>
                </div>

                <div class="progress-steps">
                    <!-- Step 1: Registration -->
                    <div class="progress-step">
                        <div class="step-circle completed">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-label">Registration</div>
                    </div>

                    <!-- Step 2: Application Submitted -->
                    <div class="progress-step">
                        <div class="step-circle completed">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-label">Applied</div>
                    </div>

                    <!-- Step 3: Documents -->
                    <div class="progress-step">
                        <div class="step-circle <?php echo $documents_count > 0 ? 'completed' : ($current_step == 3 ? 'current' : 'pending'); ?>">
                            <?php if ($documents_count > 0): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                3
                            <?php endif; ?>
                        </div>
                        <div class="step-label">Documents</div>
                    </div>

                    <!-- Step 4: Registration Fee -->
                    <div class="progress-step">
                        <div class="step-circle <?php echo $registration_paid ? 'completed' : ($current_step == 4 ? 'current' : 'pending'); ?>">
                            <?php if ($registration_paid): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                4
                            <?php endif; ?>
                        </div>
                        <div class="step-label">Registration Fee</div>
                    </div>

                    <!-- Step 5: Programme Fee -->
                    <div class="progress-step">
                        <div class="step-circle <?php echo $programme_paid ? 'completed' : ($current_step == 5 ? 'current' : 'pending'); ?>">
                            <?php if ($programme_paid): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                5
                            <?php endif; ?>
                        </div>
                        <div class="step-label">Programme Fee</div>
                    </div>

                    <!-- Step 6: Enrolled -->
                    <div class="progress-step">
                        <div class="step-circle <?php echo $application['status'] === 'enrolled' ? 'completed' : ($current_step == 6 ? 'current' : 'pending'); ?>">
                            <?php if ($application['status'] === 'enrolled'): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                6
                            <?php endif; ?>
                        </div>
                        <div class="step-label">Enrolled</div>
                    </div>
                </div>
            </div>

           <!-- Success Message or Next Steps -->
            <?php if ($application['status'] === 'enrolled'): ?>
                <div class="success-message">
                    <i class="fas fa-graduation-cap"></i>
                    <h2>🎉 Congratulations!</h2>
                    <p>You have successfully enrolled in <?php echo htmlspecialchars($application['course_title']); ?></p>
                    <p style="margin-top: 1rem;">You can now access all course materials from your dashboard.</p>
                    <div style="margin-top: 2rem;">
                        <a href="dashboard.php" class="btn btn-primary" style="background: white; color: #10B981; text-decoration: none; display: inline-block; padding: 0.75rem 1.5rem; border-radius: 10px;">
                            <i class="fas fa-arrow-right"></i> Go to Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Next Steps -->
                <div class="action-cards">
                    <?php if ($documents_count == 0): ?>
                        <div class="action-card">
                            <h3><i class="fas fa-upload"></i> Upload Documents</h3>
                            <p>Please upload your required documents to proceed with your application.</p>
                            <a href="upload-documents.php?id=<?php echo $application_id; ?>" class="btn btn-primary">
                                <i class="fas fa-file-upload"></i> Upload Now
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($documents_count > 0 && !$registration_paid): ?>
                        <div class="action-card">
                            <h3><i class="fas fa-credit-card"></i> Pay Registration Fee</h3>
                            <p>Complete your registration by paying £<?php echo number_format($application['registration_fee'], 2); ?></p>
                            <a href="payment.php?id=<?php echo $application_id; ?>&type=registration" class="btn btn-primary">
                                <i class="fas fa-pound-sign"></i> Pay Now
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($registration_paid && !$programme_paid): ?>
                        <div class="action-card">
                            <h3><i class="fas fa-credit-card"></i> Pay Programme Fee</h3>
                            <p>Complete your enrollment by paying £<?php echo number_format($application['programme_fee'], 2); ?></p>
                            <a href="payment.php?id=<?php echo $application_id; ?>&type=program" class="btn btn-primary">
                                <i class="fas fa-pound-sign"></i> Pay Now
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Application Details -->
                    <div class="action-card">
                        <h3><i class="fas fa-info-circle"></i> Application Details</h3>
                        <p><strong>Application ID:</strong> #<?php echo $application_id; ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($application['status']); ?></p>
                        <p><strong>Registration Fee:</strong> £<?php echo number_format($application['registration_fee'], 2); ?></p>
                        <p><strong>Programme Fee:</strong> £<?php echo number_format($application['programme_fee'], 2); ?></p>
                        <?php if ($application['duration']): ?>
                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($application['duration']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($current_step <= 6): ?>
                    <div class="alert alert-info" style="margin-top: 2rem;">
                        <i class="fas fa-info-circle"></i>
                        <span><strong>Next Step:</strong> 
                            <?php 
                            if ($current_step == 3) echo "Upload your required documents";
                            elseif ($current_step == 4) echo "Pay the registration fee";
                            elseif ($current_step == 5) echo "Pay the programme fee";
                            elseif ($current_step == 6) echo "Wait for admin approval to complete enrollment";
                            else echo "Complete all steps to get enrolled";
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>