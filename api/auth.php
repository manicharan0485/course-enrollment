<?php
/**
 * OAuth API Endpoints
 * RESTful API for OAuth authentication
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/OAuthHandler.php';
require_once __DIR__ . '/../includes/JWTHandler.php';

$response = ['success' => false];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = trim(str_replace('/api/auth.php', '', $request_uri), '/');
    
    // Handle query string path parameter
    if (empty($path) && isset($_GET['path'])) {
        $path = $_GET['path'];
    }

    error_log("API Request: $method $path");

    $jwt = new JWTHandler();

    // Google OAuth Callback
    if ($path === 'google/callback') {
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        if (!$code || !$state || $state !== ($_SESSION['oauth_state'] ?? null)) {
            throw new Exception('Invalid state parameter');
        }

        $oauth = new OAuthHandler($pdo, 'google');
        $tokenData = $oauth->getAccessToken($code);

        if (!isset($tokenData['access_token'])) {
            throw new Exception('Failed to get access token');
        }

        $userInfo = $oauth->getUserInfo($tokenData['access_token']);
        $userId = $oauth->upsertUser($userInfo);

        $token = $jwt->generateToken([
            'user_id' => $userId,
            'email' => $userInfo['email'],
            'provider' => 'google',
        ]);

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $userInfo['email'];
        
        header('Location: ' . rtrim(getenv('APP_URL'), '/') . "/dashboard.php?token=$token");
        exit;
    }

    // GitHub OAuth Callback
    if ($path === 'github/callback') {
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        if (!$code || !$state || $state !== ($_SESSION['oauth_state'] ?? null)) {
            throw new Exception('Invalid state parameter');
        }

        $oauth = new OAuthHandler($pdo, 'github');
        $tokenData = $oauth->getAccessToken($code);

        if (!isset($tokenData['access_token'])) {
            throw new Exception('Failed to get access token');
        }

        $userInfo = $oauth->getUserInfo($tokenData['access_token']);
        $userId = $oauth->upsertUser($userInfo);

        $token = $jwt->generateToken([
            'user_id' => $userId,
            'email' => $userInfo['email'],
            'provider' => 'github',
        ]);

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $userInfo['email'];
        
        header('Location: ' . rtrim(getenv('APP_URL'), '/') . "/dashboard.php?token=$token");
        exit;
    }

    // Get Google Authorization URL
    if ($path === 'google/authorize' && $method === 'POST') {
        $oauth = new OAuthHandler($pdo, 'google');
        $authUrl = $oauth->getAuthorizationUrl();

        $response = [
            'success' => true,
            'authorization_url' => $authUrl,
            'provider' => 'google',
        ];
    }

    // Get GitHub Authorization URL
    elseif ($path === 'github/authorize' && $method === 'POST') {
        $oauth = new OAuthHandler($pdo, 'github');
        $authUrl = $oauth->getAuthorizationUrl();

        $response = [
            'success' => true,
            'authorization_url' => $authUrl,
            'provider' => 'github',
        ];
    }

    // Email/Password Login
    elseif ($path === 'login' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            throw new Exception('Email and password required');
        }

        $stmt = $pdo->prepare("SELECT id, full_name, password_hash FROM users WHERE email = ? AND approved = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid credentials');
        }

        $token = $jwt->generateToken([
            'user_id' => $user['id'],
            'email' => $email,
            'name' => $user['full_name'],
        ]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $email;

        $response = [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $email,
            ],
        ];
    }

    // Email/Password Signup
    elseif ($path === 'signup' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $fullName = $data['full_name'] ?? null;

        if (!$email || !$password || !$fullName) {
            throw new Exception('Email, password, and full name required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters");
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already registered');
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, email, password_hash, approved, role, created_at)
            VALUES (?, ?, ?, 1, 'user', NOW())
        ");
        $stmt->execute([$fullName, $email, $passwordHash]);
        $userId = $pdo->lastInsertId();

        $token = $jwt->generateToken([
            'user_id' => $userId,
            'email' => $email,
            'name' => $fullName,
        ]);

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;

        $response = [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $userId,
                'name' => $fullName,
                'email' => $email,
            ],
        ];
    }

    // Verify Token
    elseif ($path === 'verify' && $method === 'POST') {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new Exception('Missing authorization token');
        }

        $token = $matches[1];
        $payload = $jwt->verifyToken($token);

        $response = [
            'success' => true,
            'user' => $payload,
        ];
    }

    // Logout
    elseif ($path === 'logout' && $method === 'POST') {
        session_destroy();
        $response = [
            'success' => true,
            'message' => 'Logged out successfully',
        ];
    }

    else {
        throw new Exception("Endpoint not found: $path");
    }

} catch (Exception $e) {
    http_response_code(400);
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
    ];
    error_log("API Error: " . $e->getMessage());
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
