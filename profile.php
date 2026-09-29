<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';
requireLogin();
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $Phone_Number = trim($_POST['Phone_Number']);
    $address = trim($_POST['address']);
    $date_of_birth = trim($_POST['date_of_birth']);
    $college = trim($_POST['college']);
    $city = trim($_POST['city']);
    $Postcode = trim($_POST['Postcode']);
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, Phone_Number = ?, address = ?, college = ?, date_of_birth = ?, city = ?, Postcode = ? WHERE id = ?");
    if ($stmt->execute([$full_name, $email, $Phone_Number, $address, $college, $date_of_birth, $city, $Postcode, $user_id])) {
        setFlashMessage('Profile updated successfully!', 'success');
        header('Location: profile.php');
        exit;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password_hash'])) {
        if ($new_password === $confirm_password) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                setFlashMessage('Password changed successfully!', 'success');
                header('Location: profile.php');
                exit;
            }
        } else {
            setFlashMessage('Passwords do not match!', 'error');
        }
    } else {
        setFlashMessage('Current password is incorrect!', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/profile.css">
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
                <h1>My Profile</h1>
                <p>Manage your personal information and account settings</p>
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

            <!-- Profile Container -->
            <div class="profile-container">
                <!-- Profile Header Card -->
                <div class="profile-header-card">
                    <div class="profile-avatar-section">
                        <div class="profile-avatar">
                            <?php echo strtoupper($user['username'] ?? 'User'); ?>
                        </div>
                        <button class="btn-change-photo">
                            📷 Change Photo
                        </button>
                    </div>
                    <div class="profile-header-info">
                        <h2><?php echo htmlspecialchars($user['username'] ?? 'User'); ?></h2>
                        <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="profile-badges">
                            <span class="badge badge-success">✓ Verified</span>
                            <span class="badge badge-info">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="profile-tabs">
                    <button class="tab-btn active" onclick="switchTab('personal')">
                        👤 Personal Information
                    </button>
                    <button class="tab-btn" onclick="switchTab('security')">
                        🔒 Security
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="tab-content active" id="personal-tab">
                    <div class="card">
                        <div class="card-header">
                            <h3>Personal Information</h3>
                            <p>Update your personal details and contact information</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="profile-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="full_name">Full Name *</label>
                                        <input type="text" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>" 
                                               class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email Address *</label>
                                        <input type="email" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                                               class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="date">Date of Birth</label>
                                        <input type="date" id="date" name="date_of_birth" 
                                               value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>" 
                                               class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="college">College *</label>
                                        <input type="text" id="college" name="college" 
                                               value="<?php echo htmlspecialchars($user['college'] ?? ''); ?>" 
                                               class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" name="city" 
                                               value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" 
                                               class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="Postcode">Postcode</label>
                                        <input type="text" id="Postcode" name="Postcode" 
                                               value="<?php echo htmlspecialchars($user['Postcode'] ?? ''); ?>" 
                                               class="form-control">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="Phone_Number">Phone Number</label>
                                        <input type="tel" id="Phone_Number" name="Phone_Number" 
                                               value="<?php echo htmlspecialchars($user['Phone_Number'] ?? ''); ?>" 
                                               class="form-control" placeholder="+1 (555) 000-0000">
                                    </div>
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <input type="text" id="address" name="address" 
                                               value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" 
                                               class="form-control" placeholder="City, Country">
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        💾 Save Changes
                                    </button>
                                    <button type="button" class="btn btn-outline">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="security-tab">
                    <div class="card">
                        <div class="card-header">
                            <h3>Change Password</h3>
                            <p>Ensure your account is using a strong password</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="profile-form">
                                <div class="form-group">
                                    <label for="current_password">Current Password *</label>
                                    <input type="password" id="current_password" name="current_password" 
                                           class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="new_password">New Password *</label>
                                    <input type="password" id="new_password" name="new_password" 
                                           class="form-control" required>
                                    <small class="form-help">Must be at least 8 characters long</small>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           class="form-control" required>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        🔒 Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card" style="margin-top: 1.5rem;">
                        <div class="card-header">
                            <h3>Two-Factor Authentication</h3>
                            <p>Add an extra layer of security to your account</p>
                        </div>
                        <div class="card-body">
                            <div class="security-option">
                                <div>
                                    <h4>SMS Authentication</h4>
                                    <p>Receive verification codes via SMS</p>
                                </div>
                                <button class="btn btn-outline">Enable</button>
                            </div>
                            <div class="security-option">
                                <div>
                                    <h4>Email Authentication</h4>
                                    <p>Receive verification codes via email</p>
                                </div>
                                <button class="btn btn-outline">Enable</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="activity-tab">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Activity</h3>
                            <p>Track your account activity and login history</p>
                        </div>
                        <div class="card-body">
                            <div class="activity-list">
                                <div class="activity-item">
                                    <div class="activity-icon">🔐</div>
                                    <div class="activity-details">
                                        <h4>Logged in</h4>
                                        <p>From Chrome on Windows • <?php echo date('M d, Y • g:i A'); ?></p>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">📝</div>
                                    <div class="activity-details">
                                        <h4>Profile updated</h4>
                                        <p>Changed email address • <?php echo date('M d, Y', strtotime('-2 days')); ?></p>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">📚</div>
                                    <div class="activity-details">
                                        <h4>Enrolled in course</h4>
                                        <p>Advanced React Patterns • <?php echo date('M d, Y', strtotime('-5 days')); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function switchTab(tabName) {
            // Remove active class from all tabs and content
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>