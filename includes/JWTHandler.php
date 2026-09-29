<?php
/**
 * JWT Token Helper
 * Generate and verify JWT tokens for API authentication
 */

class JWTHandler {
    private $secret;
    private $algorithm = 'HS256';

    public function __construct($secret = null) {
        $this->secret = $secret ?? getenv('JWT_SECRET') ?: 'your-secret-key-change-this';
    }

    /**
     * Generate JWT token
     */
    public function generateToken($data, $expiresIn = 3600) {
        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT',
        ];

        $payload = array_merge($data, [
            'iat' => time(),
            'exp' => time() + $expiresIn,
        ]);

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->generateSignature("$headerEncoded.$payloadEncoded");

        return "$headerEncoded.$payloadEncoded.$signature";
    }

    /**
     * Verify and decode JWT token
     */
    public function verifyToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verify signature
        $signature = $this->generateSignature("$headerEncoded.$payloadEncoded");
        $expectedSignature = $this->base64UrlEncode($signature);

        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            throw new Exception('Invalid token signature');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token expired');
        }

        return $payload;
    }

    private function generateSignature($input) {
        return hash_hmac('sha256', $input, $this->secret, true);
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }
}
