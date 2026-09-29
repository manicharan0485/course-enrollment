<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in 
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$id = $_POST['id'];
$action = $_POST['action'];

$stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
$stmt->execute([$action, $id]);

header("Location: admin-application-view.php?id=$id");
exit;
