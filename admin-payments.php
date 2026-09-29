<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';


// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if logged-in user is an admin (check users table with role='admin')
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Check if this user exists in users table and has admin role
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$admin = $stmt->fetch();

// If not found in users table or not an admin, redirect to regular dashboard
if (!$admin || $admin['role'] !== 'admin') {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    header('Location: dashboard.php');
    exit;
}
// Define admin name for display
$admin_name = $admin['full_name'] ?? $_SESSION['user_name'] ?? 'Admin';

// Handle payment approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payment_id = $_POST['payment_id'] ?? 0;
    $action = $_POST['action'];
    
    $stmt = $pdo->prepare("SELECT p.*, u.email, u.full_name 
                           FROM payments p 
                           LEFT JOIN users u ON p.user_id = u.id 
                           WHERE p.id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        $new_status = ($action === 'approve') ? 'completed' : 'rejected';
        
        // Update payment status
        $stmt = $pdo->prepare("UPDATE payments SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $_SESSION['user_id'], $payment_id]);
        
        if ($action === 'approve' && $payment['application_id']) {
            // Check if BOTH registration AND programme fees are completed
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(CASE WHEN payment_type = 'registration' AND status = 'completed' THEN 1 ELSE 0 END) as reg_paid,
                    SUM(CASE WHEN payment_type IN ('program', 'programme') AND status = 'completed' THEN 1 ELSE 0 END) as prog_paid
                FROM payments 
                WHERE application_id = ?
            ");
            $stmt->execute([$payment['application_id']]);
            $payment_status = $stmt->fetch();
            
            // If both payments are completed, enroll the student
            if ($payment_status['reg_paid'] >= 1 && $payment_status['prog_paid'] >= 1) {
                $stmt = $pdo->prepare("UPDATE applications SET status = 'enrolled', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$payment['application_id']]);
                
                $_SESSION['success'] = 'Payment approved! Student has been enrolled successfully.';
            } else {
                $_SESSION['success'] = 'Payment approved successfully!';
            }
        } else {
            $_SESSION['success'] = $action === 'approve' ? 'Payment approved!' : 'Payment rejected.';
        }
    }
    
    header('Location: admin-payments.php');
    exit;
}

// Get search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Add this line to define admin_name
$admin_name = $admin['full_name'] ?? $_SESSION['user_name'] ?? 'Admin';

// Get payments with joins
try {
    $query = "SELECT 
            p.*,
            u.full_name,
            u.email,
            a.id as application_id,
            c.title as course_title
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN applications a ON p.application_id = a.id
        LEFT JOIN courses c ON a.course_id = c.id
        WHERE 1=1";
    
    $params = [];
    
    if ($search) {
        $query .= " AND (u.full_name LIKE ? OR c.title LIKE ? OR p.transaction_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($filter !== 'all') {
        $query .= " AND p.status = ?";
        $params[] = $filter;
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    $payments = [];
}

$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
$pending_applications = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
$pending_payments = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cloudflare.com">

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h2 {
            margin: 0;
            color: #fff;
            font-size: 1.5rem;
        }

        .close-modal {
            background: rgba(239, 68, 68, 0.1);
            border: none;
            color: #ef4444;
            font-size: 1.5rem;
            cursor: pointer;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .close-modal:hover {
            background: #ef4444;
            color: white;
            transform: rotate(90deg);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }

        .detail-label {
            color: #94a3b8;
            font-weight: 500;
        }

        .detail-value {
            color: #fff;
            font-weight: 600;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .badge.pending {
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.3);
            color: #eab308;
        }

        .badge.completed {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .badge.rejected {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .filter-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 0.6rem 2.5rem 0.6rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <aside class="sidebar admin-sidebar">
        <div class="sidebar-header">
            <div class="logo-icon">ðŸŽ“</div>
            <h2 class="logo-text">Admin Panel</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="admin-dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
            <a href="admin-users.php" class="nav-item"><i class="fas fa-users"></i><span>Users</span></a>
            <a href="admin-courses.php" class="nav-item"><i class="fas fa-book"></i><span>Courses</span></a>
            <a href="admin-applications.php" class="nav-item"><i class="fas fa-file-alt"></i><span>Applications</span><?php if($pending_applications>0): ?><span class="badge"><?php echo $pending_applications; ?></span><?php endif; ?></a>
            <a href="admin-payments.php" class="nav-item active"><i class="fas fa-credit-card"></i><span>Payments</span><?php if($pending_payments>0): ?><span class="badge"><?php echo $pending_payments; ?></span><?php endif; ?></a>
            <a href="admin-documents.php" class="nav-item"><i class="fas fa-file"></i><span>Documents</span></a>
            <a href="admin-notifications.php" class="nav-item"><i class="fas fa-bell"></i><span>Notifications</span></a>
        </nav>
        <div class="sidebar-footer">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i><span>User View</span></a>
            <a href="logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
            <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Search payments..." id="searchInput"></div>
            <div class="top-bar-actions">
                <button class="icon-btn" title="Notifications"><i class="fas fa-bell"></i><?php if($pending_payments>0): ?><span class="badge"><?php echo $pending_payments; ?></span><?php endif; ?></button>
                <div class="user-menu">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=4F46E5&color=fff" class="user-avatar">
                    <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="user-role">Admin</span>
                </div>
            </div>
        </header>

        <section class="welcome-section">
            <div class="welcome-content">
                <h1><i class="fas fa-credit-card"></i> Payment Management</h1>
                <p>Approve or reject payment submissions.</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-secondary" id="exportBtn">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </section>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-content"><h3><?php echo $pending_payments; ?></h3><p>Pending</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content"><h3><?php echo count(array_filter($payments, fn($p) => $p['status'] == 'completed')); ?></h3><p>Approved</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="stat-content"><h3><?php echo count(array_filter($payments, fn($p) => $p['status'] == 'rejected')); ?></h3><p>Rejected</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-euro-sign"></i></div>
                <div class="stat-content"><h3>€<?php echo number_format(array_sum(array_column(array_filter($payments, fn($p) => $p['status'] == 'completed'), 'amount')), 2); ?></h3><p>Total Revenue</p></div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2><i class="fas fa-table"></i> All Payments</h2>
                <select class="filter-select" id="statusFilter" style="background:rgba(30, 41, 59, 0.8)">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="card-content">
                <?php if (empty($payments)): ?>
                    <div style="text-align:center;padding:3rem;color:#94A3B8;">
                        <i class="fas fa-credit-card" style="font-size:3rem;opacity:0.3;margin-bottom:1rem;"></i>
                        <p>No payments yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>ID</th><th>STUDENT</th><th>COURSE</th><th>TYPE</th><th>AMOUNT</th><th>METHOD</th><th>TRANS ID</th><th>STATUS</th><th>PROOF</th><th>ACTIONS</th></tr></thead>
                            <tbody id="paymentsTable">
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><strong>#<?php echo $payment['id']; ?></strong></td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($payment['full_name'] ?? 'Unknown'); ?>&size=32">
                                                <div>
                                                    <span><?php echo htmlspecialchars($payment['full_name'] ?? 'Unknown User'); ?></span>
                                                    <?php if (!empty($payment['email'])): ?>
                                                        <br><small style="color:#94A3B8;"><?php echo htmlspecialchars($payment['email']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['course_title'] ?? 'N/A'); ?></td>
                                        <td><span class="badge pending"><?php echo ucfirst($payment['payment_type']); ?></span></td>
                                        <td><strong style="color:#10B981;">€<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                                        <td><code style="background:rgba(255,255,255,0.1);padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem;"><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></code></td>
                                        <td><span class="badge <?php echo $payment['status']; ?>"><?php echo ucfirst($payment['status']); ?></span></td>
                                        <td>
                                            <?php if (!empty($payment['payment_proof']) && file_exists($payment['payment_proof'])): ?>
                                                <a href="<?php echo htmlspecialchars($payment['payment_proof']); ?>" target="_blank" class="btn-icon" title="View Proof" style="background:#10B981;">
                                                    <i class="fas fa-image"></i>
                                                </a>
                                            <?php else: ?>
                                                <span style="color:#94A3B8;">No proof</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-cell">
                                            <?php if ($payment['status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn-icon" title="Approve" style="background: #10B981; color: white;" onclick="return confirm('Approve this payment?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn-icon" title="Reject" style="background: #EF4444; color: white;" onclick="return confirm('Reject this payment?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn-icon" title="View Details" onclick="viewPayment(<?php echo htmlspecialchars(json_encode($payment)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- View Payment Modal -->
    <div id="viewPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-credit-card"></i> Payment Details</h2>
                <button class="close-modal" onclick="closeModal('viewPaymentModal')">&times;</button>
            </div>
            <div id="viewPaymentContent"></div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Filter handling
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            // Set current filter value
            const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'all';
            statusFilter.value = currentFilter;
            
            statusFilter.addEventListener('change', function() {
                const currentSearch = new URLSearchParams(window.location.search);
                const searchValue = currentSearch.get('search');
                
                let url = '?filter=' + this.value;
                if (searchValue) {
                    url += '&search=' + encodeURIComponent(searchValue);
                }
                
                window.location.href = url;
            });
        }

        function viewPayment(payment) {
            const content = document.getElementById('viewPaymentContent');
            content.innerHTML = `
                <div>
                    <div class="detail-row">
                        <span class="detail-label">Payment ID:</span>
                        <span class="detail-value">#${payment.id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Student:</span>
                        <span class="detail-value">${payment.full_name || 'Unknown'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Course:</span>
                        <span class="detail-value">${payment.course_title || 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value">${payment.payment_type.toUpperCase()}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Amount:</span>
                        <span class="detail-value">€${parseFloat(payment.amount).toFixed(2)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Method:</span>
                        <span class="detail-value">${payment.payment_method || 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Transaction ID:</span>
                        <span class="detail-value">${payment.transaction_id || 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="badge ${payment.status}">${payment.status.toUpperCase()}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Created:</span>
                        <span class="detail-value">${new Date(payment.created_at).toLocaleString()}</span>
                    </div>
                </div>
            `;
            document.getElementById('viewPaymentModal').classList.add('active');
        }

        // Export
        document.getElementById('exportBtn').addEventListener('click', function() {
            const payments = <?php echo json_encode($payments); ?>;
            
            let csvContent = "ID,Student,Course,Type,Amount,Method,Transaction ID,Status,Created\n";
            
            payments.forEach(p => {
                const row = [
                    '#' + p.id,
                    '"' + (p.full_name || 'Unknown') + '"',
                    '"' + (p.course_title || 'N/A') + '"',
                    p.payment_type.toUpperCase(),
                    p.amount,
                    p.payment_method || 'N/A',
                    p.transaction_id || 'N/A',
                    p.status.toUpperCase(),
                    new Date(p.created_at).toLocaleDateString()
                ].join(',');
                csvContent += row + "\n";
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'payments_export_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Search
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const searchValue = searchInput.value.trim();
                const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'all';
                
                let url = 'admin-payments.php';
                if (searchValue) {
                    url += '?search=' + encodeURIComponent(searchValue);
                    if (currentFilter !== 'all') {
                        url += '&filter=' + currentFilter;
                    }
                } else if (currentFilter !== 'all') {
                    url += '?filter=' + currentFilter;
                }
                
                window.location.href = url;
            }, 500);
        });

        // Auto-hide alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    </script>
</body>
</html>