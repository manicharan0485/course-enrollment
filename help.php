<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';
requireLogin();

// Initialize arrays to avoid "undefined variable" errors
$applications = [];
$courses = [];
$enrolled_courses = [];
$notifications = [];

// Example function to get logged-in user from session or database
function getLoggedInUser() {
    // Assuming you store the user ID in session after login
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Fetch user data from the 'users' table
        global $pdo; // Assuming $pdo is your PDO database connection
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Return user data as an associative array
    }
    return null; // If no user is logged in, return null
}

$user = getLoggedInUser();

// Get ALL applications for the user
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        c.title as course_title,
        c.description as course_description,
        c.registration_fee,
        c.programme_fee
    FROM applications a
    JOIN courses c ON a.course_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$user['id']]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ONLY ENROLLED courses (status = 'enrolled')
$stmt = $pdo->prepare("
    SELECT c.*, a.created_at as enrolled_at 
    FROM courses c
    JOIN applications a ON a.course_id = c.id
    WHERE a.user_id = ? AND a.status = 'enrolled'
    ORDER BY a.created_at DESC
");
$stmt->execute([$user['id']]);
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total_applications = count($applications);
$total_enrolled = count($enrolled_courses);

// Count approved applications (pending enrollment)
$approved_count = 0;
foreach ($applications as $app) {
    if ($app['status'] == 'approved') {
        $approved_count++;
    }
}

// Count certificates (courses where status is enrolled)
$certificates = $total_enrolled; // Assuming enrolled = certificate eligible

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/welcome.css">
    <link rel="stylesheet" href="assets/css/help.css">
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
                <h1>Help & Support Center</h1>
                <p>We're here to help you succeed in your learning journey</p>
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

            <!-- Main Help Content -->
            <div class="help-container">
                <!-- Tab Navigation -->
                <div class="help-tabs">
                    <button class="help-tab-btn active" onclick="showTab('faq')">
                        ❓ FAQ
                    </button>
                    <button class="help-tab-btn" onclick="showTab('contact')">
                        📞 Contact Us
                    </button>
                </div>

                <!-- FAQ Tab -->
                <div class="help-tab-content active" id="faq-tab">
                    <div class="card">
                        <div class="card-header">
                            <h3>Frequently Asked Questions</h3>
                            <p>Find quick answers to the most common questions</p>
                        </div>
                        <div class="card-body">
                            <div class="search-box">
                                <input type="text" placeholder="🔍 Search FAQs..." class="form-control">
                            </div>

                            <div class="faq-categories">
                                <button class="faq-category active" onclick="filterFAQ('all')">All</button>
                                <button class="faq-category" onclick="filterFAQ('enrollment')">Enrollment</button>
                                <button class="faq-category" onclick="filterFAQ('payment')">Payment</button>
                                <button class="faq-category" onclick="filterFAQ('courses')">Courses</button>
                                <button class="faq-category" onclick="filterFAQ('technical')">Technical</button>
                            </div>

                            <div class="faq-list">
                                <div class="faq-item" data-category="enrollment">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <h4>How do I enroll in a course?</h4>
                                        <span class="faq-toggle">+</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p>To enroll in a course, browse our course catalog, select the course you're interested in, and click "Apply Now". Complete the application form, upload required documents, and proceed with payment. Once payment is confirmed, you'll receive enrollment confirmation via email.</p>
                                    </div>
                                </div>

                                <div class="faq-item" data-category="payment">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <h4>What payment methods do you accept?</h4>
                                        <span class="faq-toggle">+</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p>We accept various payment methods including credit/debit cards (Visa, Mastercard, American Express), PayPal, bank transfers, and UPI payments. All transactions are secure and encrypted.</p>
                                    </div>
                                </div>

                                <div class="faq-item" data-category="courses">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <h4>Can I access course materials after completion?</h4>
                                        <span class="faq-toggle">+</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p>Yes! Once you complete a course, you have lifetime access to all course materials, including videos, documents, and resources. You can revisit the content anytime to refresh your knowledge.</p>
                                    </div>
                                </div>

                                <div class="faq-item" data-category="technical">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <h4>I'm having trouble accessing my account. What should I do?</h4>
                                        <span class="faq-toggle">+</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p>Try resetting your password using the "Forgot Password" link on the login page. If you still can't access your account, contact our support team via live chat or submit a support ticket, and we'll help you regain access.</p>
                                    </div>
                                </div>

                                <div class="faq-item" data-category="enrollment">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <h4>What documents are required for enrollment?</h4>
                                        <span class="faq-toggle">+</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p>Required documents typically include: 1) Government-issued ID (passport/driver's license), 2) Educational certificates, 3) Passport-size photograph. Specific requirements may vary by course.</p>
                                    </div>
                                </div>

                                <div class="faq-item" data-category="payment">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <h4>Is there a refund policy?</h4>
                                        <span class="faq-toggle">+</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p>Yes, we offer a 14-day money-back guarantee for most courses. If you're not satisfied within the first 14 days of enrollment, you can request a full refund. Please refer to our refund policy for complete details.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Knowledge Base Tab -->
                <div class="help-tab-content" id="knowledge-tab">
                    <div class="card">
                        <div class="card-header">
                            <h3>Knowledge Base</h3>
                            <p>Comprehensive guides and tutorials</p>
                        </div>
                        <div class="card-body">
                            <div class="knowledge-categories">
                                <div class="knowledge-category-card">
                                    <div class="knowledge-icon">🎓</div>
                                    <h4>Getting Started</h4>
                                    <ul>
                                        <li><a href="#">Creating your account</a></li>
                                        <li><a href="#">Navigating the dashboard</a></li>
                                        <li><a href="#">Choosing the right course</a></li>
                                        <li><a href="#">Setting up your profile</a></li>
                                    </ul>
                                </div>

                                <div class="knowledge-category-card">
                                    <div class="knowledge-icon">📝</div>
                                    <h4>Course Enrollment</h4>
                                    <ul>
                                        <li><a href="#">How to apply for courses</a></li>
                                        <li><a href="#">Document upload guidelines</a></li>
                                        <li><a href="#">Payment process explained</a></li>
                                        <li><a href="#">Enrollment confirmation</a></li>
                                    </ul>
                                </div>

                                <div class="knowledge-category-card">
                                    <div class="knowledge-icon">🎯</div>
                                    <h4>Learning Resources</h4>
                                    <ul>
                                        <li><a href="#">Accessing course materials</a></li>
                                        <li><a href="#">Downloading resources</a></li>
                                        <li><a href="#">Tracking your progress</a></li>
                                        <li><a href="#">Completing assessments</a></li>
                                    </ul>
                                </div>

                                <div class="knowledge-category-card">
                                    <div class="knowledge-icon">🏆</div>
                                    <h4>Certificates & Achievements</h4>
                                    <ul>
                                        <li><a href="#">Earning certificates</a></li>
                                        <li><a href="#">Downloading certificates</a></li>
                                        <li><a href="#">Sharing achievements</a></li>
                                        <li><a href="#">Certificate verification</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tickets Tab -->
                <div class="help-tab-content" id="ticket-tab">
                    <div class="card">
                        <div class="card-header">
                            <h3>Support Tickets</h3>
                            <button class="btn btn-primary" onclick="showTicketForm()">
                                ➕ Create New Ticket
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- New Ticket Form (Hidden by default) -->
                            <div id="ticket-form" style="display: none; margin-bottom: 2rem;">
                                <h4>Submit a Support Ticket</h4>
                                <form method="POST" class="ticket-form">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="subject">Subject *</label>
                                            <input type="text" id="subject" name="subject" class="form-control" 
                                                   placeholder="Brief description of your issue" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="category">Category *</label>
                                            <select id="category" name="category" class="form-control" required>
                                                <option value="">Select a category</option>
                                                <option value="technical">Technical Issue</option>
                                                <option value="billing">Billing & Payment</option>
                                                <option value="enrollment">Course Enrollment</option>
                                                <option value="content">Course Content</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="priority">Priority *</label>
                                        <select id="priority" name="priority" class="form-control" required>
                                            <option value="low">Low - General inquiry</option>
                                            <option value="medium" selected>Medium - Normal issue</option>
                                            <option value="high">High - Urgent matter</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="message">Message *</label>
                                        <textarea id="message" name="message" class="form-control" rows="6" 
                                                  placeholder="Please describe your issue in detail..." required></textarea>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" name="submit_ticket" class="btn btn-primary">
                                            📤 Submit Ticket
                                        </button>
                                        <button type="button" class="btn btn-outline" onclick="hideTicketForm()">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Existing Tickets -->
                            <div class="tickets-list">
                                <?php if (empty($tickets)): ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">🎫</div>
                                        <h4>No Support Tickets</h4>
                                        <p>You haven't created any support tickets yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($tickets as $ticket): ?>
                                    <div class="ticket-item">
                                        <div class="ticket-header">
                                            <div>
                                                <h4><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                                                <p class="ticket-meta">
                                                    Ticket #<?php echo $ticket['id']; ?> • 
                                                    <?php echo ucfirst($ticket['category']); ?> • 
                                                    Created <?php echo formatDate($ticket['created_at']); ?>
                                                </p>
                                            </div>
                                            <span class="status-badge <?php echo $ticket['status']; ?>">
                                                <?php echo ucfirst($ticket['status']); ?>
                                            </span>
                                        </div>
                                        <p class="ticket-preview"><?php echo htmlspecialchars(substr($ticket['message'], 0, 150)) . '...'; ?></p>
                                        <button class="btn btn-outline btn-sm">View Details</button>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Tab -->
                <div class="help-tab-content" id="contact-tab">
                    <div class="card">
                        <div class="card-header">
                            <h3>Contact Information</h3>
                            <p>Multiple ways to reach our support team</p>
                        </div>
                        <div class="card-body">
                            <div class="contact-methods">
                                <div class="contact-method">
                                    <div class="contact-icon">📧</div>
                                    <h4>Email Support</h4>
                                    <p>support@indoeuro.com</p>
                                    <small>Response within 24 hours</small>
                                </div>

                                <div class="contact-method">
                                    <div class="contact-icon">📞</div>
                                    <h4>Phone Support</h4>
                                    <p>+49 123 456 7890</p>
                                    <small>Mon-Fri, 9AM-6PM CET</small>
                                </div>

                                <div class="contact-method">
                                    <div class="contact-icon">💬</div>
                                    <h4>Live Chat</h4>
                                    <p>Available 24/7</p>
                                    <button class="btn btn-primary btn-sm" onclick="startLiveChat()">Start Chat</button>
                                </div>

                                <div class="contact-method">
                                    <div class="contact-icon">🏢</div>
                                    <h4>Office Address</h4>
                                    <p>Indo-Euro Synchronization<br>
                                    Mainz, Germany</p>
                                </div>
                            </div>

                            <div class="social-support">
                                <h4>Follow Us</h4>
                                <div class="social-links">
                                    <a href="#" class="social-link">Facebook</a>
                                    <a href="#" class="social-link">Twitter</a>
                                    <a href="#" class="social-link">LinkedIn</a>
                                    <a href="#" class="social-link">Instagram</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabName) {
            // Remove active class from all tabs
            document.querySelectorAll('.help-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.help-tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        function toggleFAQ(element) {
            const faqItem = element.closest('.faq-item');
            const isActive = faqItem.classList.contains('active');
            
            // Close all FAQs
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
                item.querySelector('.faq-toggle').textContent = '+';
            });
            
            // Open clicked FAQ if it wasn't active
            if (!isActive) {
                faqItem.classList.add('active');
                element.querySelector('.faq-toggle').textContent = '−';
            }
        }

        function filterFAQ(category) {
            document.querySelectorAll('.faq-category').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            document.querySelectorAll('.faq-item').forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function showTicketForm() {
            document.getElementById('ticket-form').style.display = 'block';
        }

        function hideTicketForm() {
            document.getElementById('ticket-form').style.display = 'none';
        }

        function startLiveChat() {
            alert('Live chat feature will be integrated with your chat system');
        }
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>