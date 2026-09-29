<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';
requireLogin();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user's applications
try {
    // Detect date column
    $columns = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_ASSOC);
    $dateColumn = 'id';
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['created_at', 'application_date', 'date_applied', 'submitted_at', 'applied_at'])) {
            $dateColumn = $col['Field'];
            break;
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT a.*, c.title as course_title, c.description, c.registration_fee, c.program_fee
        FROM applications a
        JOIN courses c ON a.course_id = c.id
        WHERE a.user_id = ?
        ORDER BY a.$dateColumn DESC
    ");
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    $applications = [];
}

// Get payment status for each application
foreach ($applications as &$app) {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE application_id = ? ORDER BY created_at DESC");
    $stmt->execute([$app['id']]);
    $app['payments'] = $stmt->fetchAll();
    
    // Determine payment stage
    $app['registration_paid'] = false;
    $app['program_paid'] = false;
    
    foreach ($app['payments'] as $payment) {
        if ($payment['payment_type'] == 'registration' && $payment['status'] == 'completed') {
            $app['registration_paid'] = true;
        }
        if ($payment['payment_type'] == 'program' && $payment['status'] == 'completed') {
            $app['program_paid'] = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/welcome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/course.css">
    <style>
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
            background: white;
            color: #FF6B3D;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
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
                    <h1><i class="fas fa-file-alt"></i> My Applications</h1>
                    <p>Track your course applications and payment status.</p>
                </div>
                <div class="welcome-actions">
                    <a href="courses.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Apply for New Course
                    </a>
                </div>
            </section>
            <br>

            <!-- Applications List -->
            <?php if (empty($applications)): ?>
                <section class="card">
                    <div class="card-content">
                        <div style="text-align: center; padding: 4rem 2rem;">
                            <div style="width: 120px; height: 120px; margin: 0 auto 2rem; background: linear-gradient(135deg, rgba(255,107,61,0.1), rgba(255,107,61,0.05)); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-file-alt" style="font-size: 3rem; color: var(--primary-orange); opacity: 0.5;"></i>
                            </div>
                            <h2 style="color: #1E293B; margin-bottom: 1rem; font-size: 1.75rem;">No Applications Yet</h2>
                            <p style="color: #000000; margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">You haven't applied for any courses yet. Browse our available courses and start your learning journey today!</p>
                            <a href="courses.php" class="btn btn-primary" style="padding: 0.875rem 2rem; font-size: 1rem;">
                                <i class="fas fa-search"></i> Browse Courses
                            </a>
                        </div>
                    </div>
                </section>

            <?php else: ?>
                <div style="display: grid; gap: 1.5rem;">
                    <?php foreach ($applications as $app): ?>
                        
                        <!-- Course Info Banner -->

                        <div class="card" style="overflow: hidden; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                            <!-- Card Header with Course Title -->
                            <div style="background: linear-gradient(135deg, #FF6B3D 0%, #FF8C61 100%); padding: 1.5rem; color: white;">
                                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                                    <div style="flex: 1; min-width: 250px;">
                                        <h2 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; color: white;">
                                            <?php echo htmlspecialchars($app['course_title']); ?>
                                        </h2>
                                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.875rem; opacity: 0.95;">
                                            <span><i class="fas fa-id-card"></i> App #<?php echo $app['id']; ?></span>
                                            <span><i class="fas fa-calendar"></i> <?php echo isset($app[$dateColumn]) ? date('M d, Y', strtotime($app[$dateColumn])) : 'N/A'; ?></span>
                                        </div>
                                    </div>
                                    <span class="badge <?php echo $app['status']; ?>" style="background: white; color: #FF6B3D; font-weight: 600; padding: 0.5rem 1rem; border-radius: 20px;">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="card-content">
                                <!-- Description -->
                                <p style="color: #64748B; margin-bottom: 2rem; line-height: 1.6; font-size: 0.95rem;">
                                    <?php echo htmlspecialchars(substr($app['description'], 0, 200)) . '...'; ?>
                                </p>

                                <!-- Payment Cards - Side by Side -->
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                                    <!-- Registration Fee Card -->
                                    <div style="background: <?php echo $app['registration_paid'] ? 'rgba(16,185,129,0.08)' : 'rgba(255,107,61,0.08)'; ?>; border: 1px solid <?php echo $app['registration_paid'] ? '#10B981' : 'rgba(255,107,61,0.2)'; ?>; border-radius: 12px; padding: 2rem 1.5rem; position: relative;">
                                        <!-- Icon and Amount -->
                                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 3rem;">
                                            <div style="width: 50px; height: 50px; background: <?php echo $app['registration_paid'] ? '#10B981' : '#FF7A59'; ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                                <i class="fas <?php echo $app['registration_paid'] ? 'fa-check' : 'fa-credit-card'; ?>"></i>
                                            </div>
                                            <div>
                                                <p style="margin: 0; font-size: 0.875rem; color: #64748B; font-weight: 500;">Registration Fee</p>
                                                <p style="margin: 0.25rem 0 0 0; font-size: 1.5rem; font-weight: 700; color: #1E293B;">€<?php echo number_format($app['registration_fee'], 2); ?></p>
                                            </div>
                                        </div>

                                        <!-- Status Badge -->
                                        <div style="position: absolute; top: 1.5rem; right: 1.5rem;">
                                            <div style="width: 50px; height: 50px; background: <?php echo $app['registration_paid'] ? 'rgba(16,185,129,0.15)' : 'rgba(203,213,225,0.3)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; color: <?php echo $app['registration_paid'] ? '#10B981' : '#CBD5E1'; ?>;">
                                                <i class="fas <?php echo $app['registration_paid'] ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Text -->
                                        <div style="text-align: center; margin-bottom: 1rem;">
                                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.875rem; color: <?php echo $app['registration_paid'] ? '#10B981' : '#64748B'; ?>; font-weight: 500;">
                                                <?php echo $app['registration_paid'] ? '✓ Paid & Verified' : '○ Payment Pending'; ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Action Button -->
                                        <?php if (!$app['registration_paid']): ?>
                                            <a href="payment.php?id=<?php echo $app['id']; ?>" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 1rem; background: linear-gradient(135deg, #FF6B3D, #FF7A59); color: white; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 0.95rem; transition: all 0.3s ease;">
                                                <i class="fas fa-upload"></i> Submit Payment
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Program Fee Card -->
                                    <div style="background: <?php echo $app['program_paid'] ? 'rgba(16,185,129,0.08)' : ($app['registration_paid'] ? 'rgba(148,163,184,0.05)' : 'rgba(148,163,184,0.05)'); ?>; border: 1px solid <?php echo $app['program_paid'] ? '#10B981' : '#E2E8F0'; ?>; border-radius: 12px; padding: 2rem 1.5rem; position: relative; <?php echo !$app['registration_paid'] ? 'opacity: 0.6;' : ''; ?>">
                                        <!-- Icon and Amount -->
                                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 3rem;">
                                            <div style="width: 50px; height: 50px; background: <?php echo $app['program_paid'] ? '#10B981' : ($app['registration_paid'] ? '#94A3B8' : '#CBD5E1'); ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                                <i class="fas <?php echo $app['program_paid'] ? 'fa-check' : ($app['registration_paid'] ? 'fa-graduation-cap' : 'fa-lock'); ?>"></i>
                                            </div>
                                            <div>
                                                <p style="margin: 0; font-size: 0.875rem; color: #64748B; font-weight: 500;">Program Fee</p>
                                                <p style="margin: 0.25rem 0 0 0; font-size: 1.5rem; font-weight: 700; color: #1E293B;">€<?php echo number_format($app['program_fee'], 2); ?></p>
                                            </div>
                                        </div>

                                        <!-- Status Badge -->
                                        <div style="position: absolute; top: 1.5rem; right: 1.5rem;">
                                            <div style="width: 50px; height: 50px; background: <?php echo $app['program_paid'] ? 'rgba(16,185,129,0.15)' : ($app['registration_paid'] ? 'rgba(203,213,225,0.3)' : 'rgba(203,213,225,0.2)'); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; color: <?php echo $app['program_paid'] ? '#10B981' : ($app['registration_paid'] ? '#CBD5E1' : '#E2E8F0'); ?>;">
                                                <i class="fas <?php echo $app['program_paid'] ? 'fa-check-circle' : ($app['registration_paid'] ? 'fa-clock' : 'fa-lock'); ?>"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Text -->
                                        <div style="text-align: center; margin-bottom: 1rem;">
                                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.875rem; color: <?php echo $app['program_paid'] ? '#10B981' : ($app['registration_paid'] ? '#64748B' : '#F59E0B'); ?>; font-weight: 500;">
                                                <?php echo $app['program_paid'] ? '✓ Paid & Verified' : ($app['registration_paid'] ? '○ Ready to Pay' : '🔒 Locked'); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Action Button or Message -->
                                        <?php if ($app['registration_paid'] && !$app['program_paid']): ?>
                                            <a href="payment.php?id=<?php echo $app['id']; ?>" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 1rem; background: linear-gradient(135deg, #94A3B8, #64748B); color: white; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 0.95rem; transition: all 0.3s ease;">
                                                <i class="fas fa-upload"></i> Submit Payment
                                            </a>
                                        <?php elseif (!$app['registration_paid']): ?>
                                            <div style="padding: 1rem; background: rgba(251,191,36,0.1); border-radius: 8px; text-align: center;">
                                                <p style="margin: 0; color: #F59E0B; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                                    <i class="fas fa-info-circle"></i> Complete registration fee first
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Enrollment Success Banner -->
                                <?php if ($app['registration_paid'] && $app['program_paid']): ?>
                                    <div style="background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(5,150,105,0.05)); border: 2px solid #10B981; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; text-align: center;">
                                        <div style="width: 60px; height: 60px; background: #10B981; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-trophy" style="font-size: 1.75rem; color: white;"></i>
                                        </div>
                                        <h3 style="margin: 0 0 0.5rem 0; color: #059669; font-size: 1.25rem;">🎉 Enrollment Complete!</h3>
                                        <p style="margin: 0; color: #64748B; font-size: 0.9rem;">All payments have been verified. You now have full access to course materials.</p>
                                    </div>
                                <?php endif; ?>

                                <!-- Action Buttons -->
                                <div style="display: flex; gap: 0.75rem; padding: 1.5rem 0; border-top: 1px solid #E2E8F0;">
                                    <a href="payment.php?id=<?php echo $app['id']; ?>" style="flex: 1; min-width: 180px; display: inline-flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.875rem; text-decoration: none; color: #1E293B; font-weight: 600; font-size: 0.9rem; border: 1px solid #E2E8F0; border-radius: 10px; transition: all 0.3s ease; background: white;">
                                        <i class="fas fa-eye"></i> View Payment Details
                                    </a>
                                    <?php if ($app['registration_paid'] && $app['program_paid']): ?>
                                        <a href="course-progress.php?course_id=<?php echo $app['course_id']; ?>" style="flex: 1; min-width: 180px; display: flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.875rem; background: linear-gradient(135deg, #FF6B3D, #FF7A59); color: white; border: none; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 0.9rem; transition: all 0.3s ease; box-shadow: 0 2px 6px rgba(255,107,61,0.3);">
                                            <i class="fas fa-play-circle"></i> Continue Learning
                                        </a>
                                    <?php else: ?>
                                        <button style="flex: 1; min-width: 180px; display: flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.875rem; background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: not-allowed; opacity: 0.7;">
                                            <i class="fas fa-check-circle"></i> Applied
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script>
        // Add hover effects for application cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
                this.style.boxShadow = '0 8px 20px rgba(0,0,0,0.12)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            });
        });
    </script>
</body>
</html>