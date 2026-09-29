<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';

// ============================================
// SMTP CONFIGURATION
// ============================================
define('SMTP_HOST',       'mail.giipgermany.com');
define('SMTP_PORT',        465);
define('SMTP_USER',       'consult@giipgermany.com');
define('SMTP_PASS',       '9@Indoeurosync');
define('SMTP_FROM_NAME',  'Indo-Euro Synchronization');
define('SMTP_FROM_EMAIL', 'consult@giipgermany.com');
define('SMTP_REPLY_TO',   'consult@giipgermany.com');

// ============================================
// SMTP MAIL FUNCTION (improved)
// ============================================
function sendSmtpMail($to, $subject, $body, &$debug) {
    $debug[] = "Connecting to " . SMTP_HOST . ":" . SMTP_PORT . " via SSL...";
    $socket = @stream_socket_client(
        "ssl://" . SMTP_HOST . ":" . SMTP_PORT,
        $errno, $errstr, 30,
        STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]])
    );
    if (!$socket) {
        $debug[] = "✗ Connection failed: $errstr ($errno)";
        return false;
    }
    $debug[] = "✓ Connected";

    $read = function($label) use ($socket, &$debug) {
        $last = '';
        while ($line = fgets($socket, 1024)) {
            $debug[] = "$label: " . trim($line);
            $last = $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $last;
    };

    $read('Greeting');
    fwrite($socket, "EHLO mail.giipgermany.com\r\n");
    $read('EHLO');

    fwrite($socket, "AUTH LOGIN\r\n");
    $r = $read('AUTH');
    if (strpos($r, '334') === false) {
        $debug[] = "✗ AUTH LOGIN failed";
        fclose($socket);
        return false;
    }

    fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
    $r = $read('Username');
    if (strpos($r, '334') === false) {
        $debug[] = "✗ Username rejected";
        fclose($socket);
        return false;
    }

    fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
    $r = $read('Password');
    if (strpos($r, '235') === false) {
        $debug[] = "✗ Authentication failed";
        fclose($socket);
        return false;
    }
    $debug[] = "✓ Authenticated";

    fwrite($socket, "MAIL FROM:<" . SMTP_FROM_EMAIL . ">\r\n");
    $read('MAIL FROM');

    fwrite($socket, "RCPT TO:<$to>\r\n");
    $r = $read('RCPT TO');
    if (strpos($r, '250') === false) {
        $debug[] = "✗ RCPT TO rejected";
        fclose($socket);
        return false;
    }

    fwrite($socket, "DATA\r\n");
    $read('DATA');

    $messageId = '<' . uniqid('ies_', true) . '@giipgermany.com>';
    $msg  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $msg .= "To: $to\r\n";
    $msg .= "Subject: $subject\r\n";
    $msg .= "Message-ID: $messageId\r\n";
    $msg .= "Date: " . date('r') . "\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Reply-To: " . SMTP_REPLY_TO . "\r\n";
    $msg .= "X-Mailer: IES-CourseEnrollment/1.0\r\n";
    $msg .= "\r\n";
    $msg .= $body . "\r\n.\r\n";

    fwrite($socket, $msg);

    $sent = '';
    while ($line = fgets($socket, 1024)) {
        $sent .= $line;
        $debug[] = "SERVER: " . trim($line);
        if (isset($line[3]) && $line[3] === ' ') break;
    }

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    if (strpos($sent, '250') !== false) {
        $debug[] = "✓ Email SENT to $to";
        return true;
    }
    $debug[] = "✗ Send failed: " . trim($sent);
    return false;
}

// ============================================
// SEND EMAILS SIMULTANEOUSLY
// ============================================
function sendNotificationEmails($studentEmail, $studentSubject, $studentBody,
                                 $inchargeEmail, $inchargeSubject, $inchargeBody,
                                 &$debug) {
    $results = ['student' => false, 'incharge' => false];

    $debug[] = "--- Sending to Student: $studentEmail ---";
    $results['student'] = sendSmtpMail($studentEmail, $studentSubject, $studentBody, $debug);
    $debug[] = $results['student'] ? "✓ Student email sent" : "✗ Student email failed";

    if (!empty($inchargeEmail) && filter_var($inchargeEmail, FILTER_VALIDATE_EMAIL)) {
        $debug[] = "--- Sending to Incharge: $inchargeEmail ---";
        $results['incharge'] = sendSmtpMail($inchargeEmail, $inchargeSubject, $inchargeBody, $debug);
        $debug[] = $results['incharge'] ? "✓ Incharge email sent" : "✗ Incharge email failed";
    } else {
        $debug[] = "ℹ Incharge email skipped (not set or invalid)";
        $results['incharge'] = null;
    }

    return $results;
}

// ============================================
// EMAIL TEMPLATES
// ============================================

function emailWrapper($content) {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head><body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:30px 0;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);"><tr><td style="background:linear-gradient(135deg,#4F46E5 0%,#7C3AED 100%);padding:32px 40px;text-align:center;"><h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;letter-spacing:1px;">Indo-Euro Synchronization</h1><p style="margin:6px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Course Enrollment Platform</p></td></tr><tr><td style="padding:40px;">' . $content . '</td></tr><tr><td style="background:#f8f9fc;padding:24px 40px;text-align:center;border-top:1px solid #eaecf0;"><p style="margin:0;color:#9ca3af;font-size:12px;">&copy; ' . date('Y') . ' Indo-Euro Synchronization. All rights reserved.</p><p style="margin:6px 0 0;color:#9ca3af;font-size:12px;"><a href="https://giipgermany.com" style="color:#4F46E5;text-decoration:none;">giipgermany.com</a></p></td></tr></table></td></tr></table></body></html>';
}

function emailButton($url, $text, $color = '#4F46E5') {
    return '<table cellpadding="0" cellspacing="0" style="margin:24px 0;"><tr><td style="background:' . $color . ';border-radius:8px;padding:14px 28px;"><a href="' . $url . '" style="color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;">' . $text . '</a></td></tr></table>';
}

function emailApprovedStudent($name, $course) {
    $content = '<h2 style="margin:0 0 8px;color:#111827;font-size:22px;">Great News, ' . htmlspecialchars($name) . '!</h2><p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">Your application has been reviewed and we are thrilled to inform you that it has been <strong style="color:#16a34a;">APPROVED</strong>.</p><table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr><td style="padding:16px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;"><p style="margin:0;color:#15803d;font-size:14px;"><strong>Application Status:</strong> Approved</p><p style="margin:6px 0 0;color:#15803d;font-size:14px;"><strong>Course:</strong> ' . htmlspecialchars($course) . '</p></td></tr></table><p style="margin:0 0 8px;color:#374151;font-size:15px;line-height:1.6;">You can now log in to your dashboard to view your application status and next steps.</p>' . emailButton('https://indoeurosync.com/course-enrollment/login.php', 'Go to My Dashboard', '#16a34a') . '<p style="margin:24px 0 0;color:#9ca3af;font-size:13px;">If you have any questions, feel free to reply to this email or contact us at <a href="mailto:consult@giipgermany.com" style="color:#4F46E5;">consult@giipgermany.com</a></p>';
    return emailWrapper($content);
}

function emailApprovedIncharge($incharge, $student, $studentEmail, $course) {
    $content = '<h2 style="margin:0 0 8px;color:#111827;font-size:22px;">Application Approved</h2><p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">Dear <strong>' . htmlspecialchars($incharge) . '</strong>, an application for your course has been approved.</p><table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;"><tr style="background:#f8f9fc;"><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:35%;">Student Name</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . htmlspecialchars($student) . '</td></tr><tr><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Student Email</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . htmlspecialchars($studentEmail) . '</td></tr><tr style="background:#f8f9fc;"><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Course</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . htmlspecialchars($course) . '</td></tr><tr><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Status</td><td style="padding:12px 16px;border:1px solid #e5e7eb;"><span style="background:#dcfce7;color:#16a34a;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:600;">APPROVED</span></td></tr><tr style="background:#f8f9fc;"><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Date</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . date('d M Y, h:i A') . '</td></tr></table>' . emailButton('https://indoeurosync.com/course-enrollment/admin-applications.php', 'View in Admin Panel', '#4F46E5');
    return emailWrapper($content);
}

function emailRejectedStudent($name, $course) {
    $content = '<h2 style="margin:0 0 8px;color:#111827;font-size:22px;">Update on Your Application</h2><p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">Dear <strong>' . htmlspecialchars($name) . '</strong>, thank you for your interest in joining our programme.</p><table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr><td style="padding:16px;background:#fef2f2;border-radius:8px;border:1px solid #fecaca;"><p style="margin:0;color:#dc2626;font-size:14px;"><strong>Course Applied:</strong> ' . htmlspecialchars($course) . '</p><p style="margin:6px 0 0;color:#dc2626;font-size:14px;"><strong>Application Status:</strong> Not Approved</p></td></tr></table><p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">After careful review, we regret to inform you that your application for <strong>' . htmlspecialchars($course) . '</strong> has not been approved at this time.</p><p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.6;">We encourage you to explore other available courses in our catalogue — there may be a programme that is a great fit for you.</p>' . emailButton('https://indoeurosync.com/course-enrollment/login.php', 'Explore Other Courses', '#4F46E5') . '<p style="margin:24px 0 0;color:#9ca3af;font-size:13px;">If you have questions or would like feedback, please contact us at <a href="mailto:consult@giipgermany.com" style="color:#4F46E5;">consult@giipgermany.com</a></p>';
    return emailWrapper($content);
}

function emailRejectedIncharge($incharge, $student, $studentEmail, $course) {
    $content = '<h2 style="margin:0 0 8px;color:#111827;font-size:22px;">Application Rejected</h2><p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">Dear <strong>' . htmlspecialchars($incharge) . '</strong>, an application for your course has been rejected.</p><table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;"><tr style="background:#f8f9fc;"><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:35%;">Student Name</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . htmlspecialchars($student) . '</td></tr><tr><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Student Email</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . htmlspecialchars($studentEmail) . '</td></tr><tr style="background:#f8f9fc;"><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Course</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . htmlspecialchars($course) . '</td></tr><tr><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Status</td><td style="padding:12px 16px;border:1px solid #e5e7eb;"><span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:600;">REJECTED</span></td></tr><tr style="background:#f8f9fc;"><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Date</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . date('d M Y, h:i A') . '</td></tr></table>' . emailButton('https://indoeurosync.com/course-enrollment/admin-applications.php', 'View in Admin Panel', '#4F46E5');
    return emailWrapper($content);
}

function emailEnrolledStudent($name, $course) {
    $content = '<h2 style="margin:0 0 8px;color:#111827;font-size:22px;">Welcome Aboard, ' . htmlspecialchars($name) . '!</h2><p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">Congratulations! You have been officially enrolled in the following course:</p><table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr><td style="padding:20px;background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:8px;border:1px solid #c4b5fd;text-align:center;"><p style="margin:0;color:#5b21b6;font-size:13px;text-transform:uppercase;letter-spacing:1px;">You are now enrolled in</p><p style="margin:8px 0 0;color:#3b0764;font-size:20px;font-weight:700;">' . htmlspecialchars($course) . '</p></td></tr></table><p style="margin:0 0 8px;color:#374151;font-size:15px;line-height:1.6;">Your journey starts now. Log in to your dashboard to access your course materials and get started.</p><table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0 24px;"><tr><td style="padding:10px 0;border-bottom:1px solid #f3f4f6;">Access your course dashboard</td></tr><tr><td style="padding:10px 0;border-bottom:1px solid #f3f4f6;">View and upload your documents</td></tr><tr><td style="padding:10px 0;">Complete your payment steps</td></tr></table>' . emailButton('https://indoeurosync.com/course-enrollment/login.php', 'Go to My Dashboard', '#7C3AED') . '<p style="margin:24px 0 0;color:#9ca3af;font-size:13px;">Need help? Contact us at <a href="mailto:consult@giipgermany.com" style="color:#4F46E5;">consult@giipgermany.com</a></p>';
    return emailWrapper($content);
}

function emailEnrolledIncharge($incharge, $student, $studentEmail, $course) {
    $content = '<h2 style="margin:0 0 8px;color:#111827;font-size:22px;">New Student Enrolled</h2><p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">Dear <strong>' . htmlspecialchars($incharge) . '</strong>, a new student has been enrolled in your course.</p><table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;"><tr style="background:#f8f9fc;"><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:35%;">Student Name</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . htmlspecialchars($student) . '</td></tr><tr><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Student Email</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . htmlspecialchars($studentEmail) . '</td></tr><tr style="background:#f8f9fc;"><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Course</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . htmlspecialchars($course) . '</td></tr><tr><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Status</td><td style="padding:12px 16px;border:1px solid #e5e7eb;"><span style="background:#ede9fe;color:#7C3AED;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:600;">ENROLLED</span></td></tr><tr style="background:#f8f9fc;"><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Enrolled On</td><td style="padding:12px 16px;border:1px solid #e5e7eb;color:#111827;font-weight:600;">' . date('d M Y, h:i A') . '</td></tr></table>' . emailButton('https://indoeurosync.com/course-enrollment/admin-applications.php', 'View in Admin Panel', '#4F46E5');
    return emailWrapper($content);
}

// ============================================
// AUTH CHECK
// ============================================
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user_email'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$admin = $stmt->fetch();

if (!$admin || $admin['role'] !== 'admin') {
    $_SESSION['error'] = 'Access denied.';
    header('Location: dashboard.php');
    exit;
}

$admin_name = $admin['full_name'] ?? $_SESSION['user_name'] ?? 'Admin';
$csrf_token = generateCSRFToken();

// ============================================
// SHARED: Get application + student + course data
// ============================================
function getAppData($pdo, $app_id) {
    $stmt = $pdo->prepare("
        SELECT u.email, u.full_name, c.title AS course_title,
               c.incharge_email, c.incharge_name
        FROM applications a
        JOIN users   u ON u.id = a.user_id
        JOIN courses c ON c.id = a.course_id
        WHERE a.id = ?
    ");
    $stmt->execute([$app_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ============================================
// PROCESS POST ACTIONS (CSRF-protected)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'], $_POST['csrf_token'])) {

    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        header('Location: admin-applications.php');
        exit;
    }

    $action = $_POST['action'];
    $app_id = intval($_POST['id']);

    if ($action === 'approve') {
        try {
            $pdo->prepare("UPDATE applications SET status='approved', updated_at=NOW() WHERE id=?")->execute([$app_id]);
            $data  = getAppData($pdo, $app_id);
            $debug = ["=== APPROVE App#$app_id ===", "Time: " . date('Y-m-d H:i:s'), "Admin: $admin_name"];

            if ($data) {
                $results = sendNotificationEmails(
                    $data['email'],
                    "Your Application Has Been Approved!",
                    emailApprovedStudent($data['full_name'], $data['course_title']),
                    $data['incharge_email'] ?? '',
                    "Application Approved: " . $data['course_title'],
                    emailApprovedIncharge(
                        $data['incharge_name'] ?? 'Incharge',
                        $data['full_name'],
                        $data['email'],
                        $data['course_title']
                    ),
                    $debug
                );
                $debug[] = "Results: Student=" . ($results['student'] ? 'OK' : 'FAIL')
                         . " | Incharge=" . ($results['incharge'] === null ? 'SKIPPED' : ($results['incharge'] ? 'OK' : 'FAIL'));
            }

            file_put_contents(__DIR__ . '/mail_debug.log', implode("\n", $debug) . "\n\n", FILE_APPEND | LOCK_EX);
            $_SESSION['success'] = 'Application #' . $app_id . ' approved! Emails sent.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'DB Error: ' . $e->getMessage();
        }
        header('Location: admin-applications.php');
        exit;
    }

    if ($action === 'reject') {
        try {
            $pdo->prepare("UPDATE applications SET status='rejected', updated_at=NOW() WHERE id=?")->execute([$app_id]);
            $data  = getAppData($pdo, $app_id);
            $debug = ["=== REJECT App#$app_id ===", "Time: " . date('Y-m-d H:i:s'), "Admin: $admin_name"];

            if ($data) {
                $results = sendNotificationEmails(
                    $data['email'],
                    "Update on Your Application",
                    emailRejectedStudent($data['full_name'], $data['course_title']),
                    $data['incharge_email'] ?? '',
                    "Application Rejected: " . $data['course_title'],
                    emailRejectedIncharge(
                        $data['incharge_name'] ?? 'Incharge',
                        $data['full_name'],
                        $data['email'],
                        $data['course_title']
                    ),
                    $debug
                );
                $debug[] = "Results: Student=" . ($results['student'] ? 'OK' : 'FAIL')
                         . " | Incharge=" . ($results['incharge'] === null ? 'SKIPPED' : ($results['incharge'] ? 'OK' : 'FAIL'));
            }

            file_put_contents(__DIR__ . '/mail_debug.log', implode("\n", $debug) . "\n\n", FILE_APPEND | LOCK_EX);
            $_SESSION['success'] = 'Application #' . $app_id . ' rejected. Emails sent.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'DB Error: ' . $e->getMessage();
        }
        header('Location: admin-applications.php');
        exit;
    }

    if ($action === 'delete') {
        try {
            $pdo->prepare("DELETE FROM applications WHERE id=?")->execute([$app_id]);
            $_SESSION['success'] = 'Application #' . $app_id . ' deleted.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'DB Error: ' . $e->getMessage();
        }
        header('Location: admin-applications.php');
        exit;
    }

    if ($action === 'enroll') {
        $debug = ["=== ENROLLMENT App#$app_id ===", "Time: " . date('Y-m-d H:i:s'), "Admin: $admin_name"];
        try {
            $pdo->prepare("UPDATE applications SET status='enrolled', updated_at=NOW() WHERE id=?")->execute([$app_id]);
            $debug[] = "Step 1: ✓ Status updated to enrolled";

            $student = getAppData($pdo, $app_id);

            if (!$student || empty($student['email'])) {
                $debug[] = "✗ Student data not found";
                file_put_contents(__DIR__ . '/mail_debug.log', implode("\n", $debug) . "\n\n", FILE_APPEND);
                $_SESSION['error'] = 'Student data not found!';
                header('Location: admin-applications.php');
                exit;
            }

            $debug[] = "  - Name: "   . $student['full_name'];
            $debug[] = "  - Email: "  . $student['email'];
            $debug[] = "  - Course: " . $student['course_title'];

            $results = sendNotificationEmails(
                $student['email'],
                "You're Enrolled in " . $student['course_title'],
                emailEnrolledStudent($student['full_name'], $student['course_title']),
                $student['incharge_email'] ?? '',
                "New Student Enrolled: " . $student['course_title'],
                emailEnrolledIncharge(
                    $student['incharge_name'] ?? 'Incharge',
                    $student['full_name'],
                    $student['email'],
                    $student['course_title']
                ),
                $debug
            );

            $debug[] = "Results: Student=" . ($results['student'] ? 'OK' : 'FAIL')
                     . " | Incharge=" . ($results['incharge'] === null ? 'SKIPPED' : ($results['incharge'] ? 'OK' : 'FAIL'));
            $debug[] = "=== END ===";

            file_put_contents(__DIR__ . '/mail_debug.log', implode("\n", $debug) . "\n\n", FILE_APPEND | LOCK_EX);
            $_SESSION['success'] = 'Student enrolled successfully! Emails sent.';
        } catch (Exception $e) {
            $debug[] = "ERROR: " . $e->getMessage();
            file_put_contents(__DIR__ . '/mail_debug.log', implode("\n", $debug) . "\n\n", FILE_APPEND);
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        header('Location: admin-applications.php');
        exit;
    }
}

// ============================================
// VIEW LOG
// ============================================
if (isset($_GET['viewlog'])) {
    $log = @file_get_contents(__DIR__ . '/mail_debug.log');
    header('Content-Type: text/plain');
    echo $log ?: 'Log file is empty or not found.';
    exit;
}

// ============================================
// FETCH APPLICATIONS FOR DISPLAY
// ============================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$query  = "SELECT a.*, u.full_name AS student_name, c.title AS course_title
           FROM applications a
           JOIN users   u ON a.user_id   = u.id
           JOIN courses c ON a.course_id = c.id
           WHERE 1=1";
$params = [];

if ($search) {
    $query   .= " AND (u.full_name LIKE ? OR c.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter !== 'all') {
    $query   .= " AND a.status = ?";
    $params[] = $filter;
}
$query .= " ORDER BY a.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$pending_count  = $pdo->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$enrolled_count = $pdo->query("SELECT COUNT(*) FROM applications WHERE status='enrolled'")->fetchColumn();
$approved_count = $pdo->query("SELECT COUNT(*) FROM applications WHERE status='approved'")->fetchColumn();
$rejected_count = $pdo->query("SELECT COUNT(*) FROM applications WHERE status='rejected'")->fetchColumn();
$total_count    = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Management - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.7);backdrop-filter:blur(5px)}
        .modal.active{display:flex;align-items:center;justify-content:center}
        .modal-content{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);border-radius:20px;padding:2rem;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);border:1px solid rgba(255,255,255,0.1)}
        .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid rgba(255,255,255,0.1)}
        .modal-header h2{margin:0;color:#fff;font-size:1.5rem;display:flex;align-items:center;gap:.5rem}
        .close-modal{background:rgba(239,68,68,0.1);border:none;color:#ef4444;font-size:1.5rem;cursor:pointer;width:35px;height:35px;border-radius:10px;display:flex;align-items:center;justify-content:center;transition:all .2s ease}
        .close-modal:hover{background:#ef4444;color:white;transform:rotate(90deg)}
        .detail-row{display:flex;justify-content:space-between;padding:.75rem;background:rgba(255,255,255,0.03);border-radius:8px;margin-bottom:.75rem}
        .detail-label{color:#94a3b8;font-weight:500}.detail-value{color:#fff;font-weight:600}
        .alert{padding:1rem 1.25rem;border-radius:10px;margin-bottom:1.5rem;display:flex;align-items:center;gap:.75rem}
        .alert-success{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#22c55e}
        .alert-error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444}
        .action-buttons{display:flex;gap:.5rem}
        .action-btn{padding:.5rem;border:none;border-radius:8px;cursor:pointer;transition:all .2s ease;display:flex;align-items:center;justify-content:center;width:35px;height:35px}
        .action-btn.view{background:rgba(59,130,246,0.1);color:#3b82f6}.action-btn.view:hover{background:#3b82f6;color:white}
        .action-btn.approve{background:rgba(34,197,94,0.1);color:#22c55e}.action-btn.approve:hover{background:#22c55e;color:white}
        .action-btn.enroll{background:rgba(139,92,246,0.1);color:#8b5cf6}.action-btn.enroll:hover{background:#8b5cf6;color:white}
        .action-btn.reject{background:rgba(239,68,68,0.1);color:#ef4444}.action-btn.reject:hover{background:#ef4444;color:white}
        .action-btn.delete{background:rgba(239,68,68,0.1);color:#ef4444}.action-btn.delete:hover{background:#ef4444;color:white}
        .badge.pending{background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.3);color:#eab308}
        .badge.approved,.badge.enrolled{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#22c55e}
        .badge.rejected{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444}
        .filter-select{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#fff;padding:.6rem 2.5rem .6rem 1rem;border-radius:10px;font-size:.9rem;cursor:pointer}
        .form-actions{display:flex;gap:1rem;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,0.1)}
        .btn-modal{flex:1;padding:.75rem 1.5rem;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;transition:all .3s ease;display:flex;align-items:center;justify-content:center;gap:.5rem}
        .btn-modal-secondary{background:rgba(255,255,255,0.05);color:#cbd5e1;border:1px solid rgba(255,255,255,0.1)}
        .btn-modal-success{background:linear-gradient(135deg,#22c55e 0%,#16a34a 100%);color:white}
        .btn-modal-info{background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:white}
        .btn-modal-danger{background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);color:white}
        .confirm-modal-body{text-align:center;padding:1rem 0}
        .confirm-modal-body .icon{font-size:3rem;margin-bottom:1rem}
        .confirm-modal-body h3{color:#fff;margin:0 0 .5rem}
        .confirm-modal-body p{color:#94a3b8;font-size:.95rem}
    </style>
</head>
<body>

<aside class="sidebar admin-sidebar">
    <div class="sidebar-header">
        <div class="logo-icon">🎓</div>
        <h2 class="logo-text">Admin Panel</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="admin-dashboard.php"    class="nav-item"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
        <a href="admin-users.php"        class="nav-item"><i class="fas fa-users"></i><span>Users</span></a>
        <a href="admin-courses.php"      class="nav-item"><i class="fas fa-book"></i><span>Courses</span></a>
        <a href="admin-applications.php" class="nav-item active">
            <i class="fas fa-file-alt"></i><span>Applications</span>
            <?php if ($pending_count > 0): ?><span class="badge"><?php echo $pending_count; ?></span><?php endif; ?>
        </a>
        <a href="admin-payments.php"      class="nav-item"><i class="fas fa-credit-card"></i><span>Payments</span></a>
        <a href="admin-documents.php"     class="nav-item"><i class="fas fa-file"></i><span>Documents</span></a>
        <a href="admin-notifications.php" class="nav-item"><i class="fas fa-bell"></i><span>Notifications</span></a>
    </nav>
    <div class="sidebar-footer">
        <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i><span>User View</span></a>
        <a href="logout.php"    class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</aside>

<main class="main-content">

    <header class="top-bar">
        <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search applications..." id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="top-bar-actions">
            <button class="icon-btn">
                <i class="fas fa-bell"></i>
                <?php if ($pending_count > 0): ?><span class="badge"><?php echo $pending_count; ?></span><?php endif; ?>
            </button>
            <div class="user-menu">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=4F46E5&color=fff" alt="Admin" class="user-avatar">
                <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                <span class="user-role">Admin</span>
            </div>
        </div>
    </header>

    <section class="welcome-section">
        <div class="welcome-content">
            <h1><i class="fas fa-file-alt"></i> Application Management</h1>
            <p>Review and manage all course applications.</p>
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
            <div class="stat-content"><h3><?php echo $pending_count; ?></h3><p>Pending</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content"><h3><?php echo $approved_count; ?></h3><p>Approved</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-content"><h3><?php echo $enrolled_count; ?></h3><p>Enrolled</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
            <div class="stat-content"><h3><?php echo $rejected_count; ?></h3><p>Rejected</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-content"><h3><?php echo $total_count; ?></h3><p>Total</p></div>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <h2><i class="fas fa-table"></i> All Applications</h2>
            <select class="filter-select" id="statusFilter" style="background:rgba(30,41,59,0.8)">
                <option value="all"      <?php echo $filter==='all'      ?'selected':''; ?>>All Status</option>
                <option value="pending"  <?php echo $filter==='pending'  ?'selected':''; ?>>Pending</option>
                <option value="approved" <?php echo $filter==='approved' ?'selected':''; ?>>Approved</option>
                <option value="rejected" <?php echo $filter==='rejected' ?'selected':''; ?>>Rejected</option>
                <option value="enrolled" <?php echo $filter==='enrolled' ?'selected':''; ?>>Enrolled</option>
            </select>
        </div>
        <div class="card-content">
            <?php if (empty($applications)): ?>
                <div style="text-align:center;padding:3rem;color:#94A3B8;">
                    <i class="fas fa-file-alt" style="font-size:3rem;opacity:0.3;margin-bottom:1rem;"></i>
                    <p>No applications found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>STUDENT</th><th>COURSE</th><th>STATUS</th><th>ACTIONS</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                            <tr>
                                <td class="mono">#<?php echo $app['id']; ?></td>
                                <td>
                                    <div class="user-cell">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($app['student_name']); ?>&size=32&background=random" alt="">
                                        <span><?php echo htmlspecialchars($app['student_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($app['course_title']); ?></td>
                                <td>
                                    <span class="badge <?php echo strtolower($app['status']); ?>">
                                        <?php echo strtoupper($app['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                            <?php if ($app['status'] !== 'approved' && $app['status'] !== 'enrolled'): ?>
                                            <button class="action-btn approve" title="Approve" type="submit" onclick="return confirm('Approve this application?');">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
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

<script src="assets/js/dashboard.js"></script>
</body>
</html>
