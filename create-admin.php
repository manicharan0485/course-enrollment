<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';
requireLogin();

$username = 'Admin User';
$email = 'admin@example.com';
$password = 'your-secure-password'; // Change this!
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO admin_users (username, email, password, role, created_at) 
    VALUES (?, ?, ?, 'admin', NOW())
");

if ($stmt->execute([$username, $email, $hashed])) {
    echo "Admin created successfully!<br>";
    echo "Email: $email<br>";
    echo "Password: $password<br>";
    echo "<strong>IMPORTANT: Delete this file after use!</strong>";
} else {
    echo "Error creating admin.";
}
?>