<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Documents - Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        h1 { color: #22c55e; }
        h2 { color: #3b82f6; border-bottom: 2px solid #3b82f6; padding-bottom: 5px; }
        .error { color: #ef4444; background: rgba(239,68,68,0.1); padding: 10px; border-left: 3px solid #ef4444; }
        .success { color: #22c55e; background: rgba(34,197,94,0.1); padding: 10px; border-left: 3px solid #22c55e; }
        pre { background: #2a2a2a; padding: 15px; border-radius: 5px; overflow-x: auto; }
        a { color: #3b82f6; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>🔍 Admin Documents - Debug Mode</h1>
    
    <h2>1. GET Parameters</h2>
    <pre><?php print_r($_GET); ?></pre>
    
    <?php
    $application_id = $_GET['id'] ?? 0;
    ?>
    
    <h2>2. Application ID</h2>
    <p><strong>Value:</strong> <?php echo $application_id; ?></p>
    <p><strong>Type:</strong> <?php echo gettype($application_id); ?></p>
    
    <?php if (!$application_id): ?>
        <div class="error">
            <strong>❌ Application ID is missing or zero!</strong><br>
            Make sure you're accessing the page with: admin-documents.php?id=12
        </div>
    <?php else: ?>
        <div class="success">
            <strong>✅ Application ID received: <?php echo $application_id; ?></strong>
        </div>
        
        <h2>3. Database Check</h2>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$application_id]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($app) {
                echo '<div class="success"><strong>✅ Application found in database!</strong></div>';
                echo '<pre>';
                print_r($app);
                echo '</pre>';
            } else {
                echo '<div class="error"><strong>❌ Application NOT found with ID: ' . $application_id . '</strong></div>';
                
                // Show available IDs
                echo '<h3>Available Application IDs:</h3>';
                $stmt = $pdo->query("SELECT id, user_id, course_id, status, created_at FROM applications ORDER BY id DESC LIMIT 10");
                $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($apps) > 0) {
                    echo '<table style="border-collapse: collapse; width: 100%; background: #2a2a2a;">';
                    echo '<tr style="background: #3a3a3a;"><th style="padding: 10px; text-align: left;">ID</th><th style="padding: 10px; text-align: left;">User ID</th><th style="padding: 10px; text-align: left;">Course ID</th><th style="padding: 10px; text-align: left;">Status</th><th style="padding: 10px; text-align: left;">Test Link</th></tr>';
                    foreach ($apps as $a) {
                        echo '<tr style="border-bottom: 1px solid #4a4a4a;">';
                        echo '<td style="padding: 10px;">' . $a['id'] . '</td>';
                        echo '<td style="padding: 10px;">' . $a['user_id'] . '</td>';
                        echo '<td style="padding: 10px;">' . $a['course_id'] . '</td>';
                        echo '<td style="padding: 10px;">' . $a['status'] . '</td>';
                        echo '<td style="padding: 10px;"><a href="admin-documents-debug.php?id=' . $a['id'] . '">Test with ID ' . $a['id'] . '</a></td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="error">No applications found in database!</div>';
                }
            }
        } catch (PDOException $e) {
            echo '<div class="error"><strong>Database Error:</strong> ' . $e->getMessage() . '</div>';
        }
        ?>
    <?php endif; ?>
    
    <h2>4. Session Information</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h2>5. User Authentication</h2>
    <?php
    if (!isset($_SESSION['user_email'])) {
        echo '<div class="error">❌ No user email in session</div>';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, email, full_name, role FROM users WHERE email = ?");
            $stmt->execute([$_SESSION['user_email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                if ($user['role'] === 'admin') {
                    echo '<div class="success">✅ Admin user authenticated: ' . htmlspecialchars($user['full_name']) . '</div>';
                } else {
                    echo '<div class="error">❌ User is not an admin. Role: ' . $user['role'] . '</div>';
                }
                echo '<pre>';
                print_r($user);
                echo '</pre>';
            } else {
                echo '<div class="error">❌ User not found in database</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">Database Error: ' . $e->getMessage() . '</div>';
        }
    }
    ?>
    
    <h2>6. Next Steps</h2>
    <div style="background: #2a2a2a; padding: 15px; border-radius: 5px;">
        <p><strong>To test the actual page:</strong></p>
        <ol>
            <li>Look at the table above to find a valid application ID</li>
            <li>Visit: <code>admin-documents.php?id=YOUR_ID</code></li>
            <li>Replace YOUR_ID with an actual ID from the table above</li>
        </ol>
        
        <p style="margin-top: 20px;"><strong>Example:</strong></p>
        <?php
        $stmt = $pdo->query("SELECT id FROM applications ORDER BY id DESC LIMIT 1");
        $first_app = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($first_app) {
            echo '<p><a href="admin-documents.php?id=' . $first_app['id'] . '" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; display: inline-block;">Test admin-documents.php with ID ' . $first_app['id'] . '</a></p>';
        }
        ?>
    </div>
</body>
</html>