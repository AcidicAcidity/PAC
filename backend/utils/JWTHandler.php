<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    private $secret;
    private $algo = 'HS256';

    public function __construct() {
        $this->secret = getenv('JWT_SECRET') ?: 'change-me-!';
    }

    public function generateAccessToken($userId, $portalId, $role) {
        $payload = [
            'user_id' => $userId,
            'portal_id' => $portalId,
            'role' => $role,
            'exp' => time() + 900 // 15 минут
        ];
        return JWT::encode($payload, $this->secret, $this->algo);
    }

    public function generateRefreshToken($userId) {
        $payload = [
            'user_id' => $userId,
            'type' => 'refresh',
            'exp' => time() + 604800
        ];
        return JWT::encode($payload, $this->secret, $this->algo);
    }

    public function validateToken($token) {
        try {
            return (array) JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (Exception $e) {
            return null;
        }
    }
}
