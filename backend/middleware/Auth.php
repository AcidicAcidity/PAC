<?php
class Auth {
    private $db;
    private $jwt;
    public $currentUser = null;

    public function __construct($db) {
        $this->db = $db;
        $this->jwt = new JWTHandler();
    }

    public function validateToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (!$authHeader) return ['role' => 'guest'];

        $token = str_replace('Bearer ', '', $authHeader);
        $payload = $this->jwt->validateToken($token);
        if (!$payload) return ['role' => 'guest'];

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? AND is_blocked = FALSE");
        $stmt->execute([$payload['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return ['role' => 'guest'];

        $this->currentUser = $user;
        return $user;
    }

    public function requireRole($requiredRole) {
        $user = $this->validateToken();
        if ($user['role'] === 'guest' && $requiredRole !== 'guest') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        if ($requiredRole === 'admin' && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        return $user;
    }
}
