<?php
session_start();

// 1. Clear all session variables
$_SESSION = [];

// 2. Destroy the session on the server
session_destroy();

// 3. Delete the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 4. Redirect to login
header("Location: login.php");
exit();