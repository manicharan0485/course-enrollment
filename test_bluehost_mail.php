<?php
error_reporting(E_ALL);

$test_emails = [
    "digital@indoeurosync.com",        // Your own domain - should work!
    "manicharan.ravi999@gmail.com",    // Gmail
    "m.ravi@rpsonline.de",             // Different provider
];

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: no-reply@indoeurosync.com\r\n";

echo "<h2>Testing Multiple Recipients</h2>";

foreach ($test_emails as $email) {
    $subject = "Test Email - " . date('H:i:s');
    $message = "<h2>Test Successful!</h2><p>Sent at " . date('Y-m-d H:i:s') . "</p>";
    
    $result = mail($email, $subject, $message, $headers);
    
    echo "To: <strong>$email</strong> - " . ($result ? "✓ Sent" : "✗ Failed") . "<br>";
}

echo "<br><p>Check all inboxes in 5-10 minutes (including spam folders)</p>";
?>