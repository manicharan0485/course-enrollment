<?php
/**
 * OAuth Helper Class
 * Handles OAuth authentication with Google and GitHub
 */

class OAuthHandler {
    private $config;
    private $pdo;
    private $provider;

    public function __construct($pdo, $provider) {
        $this->pdo = $pdo;
        $this->provider = $provider;
        $this->config = require __DIR__ . '/../config/oauth.php';
        
        if (!isset($this->config[$provider])) {
            throw new Exception("Unsupported OAuth provider: $provider");
        }
    }

    /**
     * Get authorization URL
     */
    public function getAuthorizationUrl($state = null) {
        $state = $state ?? bin2hex(random_bytes(16));
        $config = $this->config[$this->provider];
        
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'state' => $state,
        ];
        
        if ($this->provider === 'google') {
            $params['scope'] = 'openid email profile';
        } elseif ($this->provider === 'github') {
            $params['scope'] = 'user:email';
        }
        
        $_SESSION['oauth_state'] = $state;
        return $config['auth_url'] . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($code) {
        $config = $this->config[$this->provider];
        
        $params = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $config['token_url'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }

    /**
     * Get user info from OAuth provider
     */
    public function getUserInfo($accessToken) {
        $config = $this->config[$this->provider];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $config['userinfo_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }

    /**
     * Create or update user from OAuth data
     */
    public function upsertUser($userData) {
        $email = $userData['email'];
        $name = $userData['name'] ?? $userData['login'] ?? 'OAuth User';
        $provider = $this->provider;
        $provider_id = $userData['id'] ?? $userData['sub'];
        
        try {
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Update existing user
                $stmt = $this->pdo->prepare("UPDATE users SET full_name = ?, oauth_provider = ?, oauth_id = ? WHERE email = ?");
                $stmt->execute([$name, $provider, $provider_id, $email]);
                return $user['id'];
            } else {
                // Create new user
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (full_name, email, oauth_provider, oauth_id, approved, role, created_at)
                    VALUES (?, ?, ?, ?, 1, 'user', NOW())
                ");
                $stmt->execute([$name, $email, $provider, $provider_id]);
                return $this->pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
}
