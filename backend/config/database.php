<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'db';
        $this->port = getenv('DB_PORT') ?: '5432';
        $this->db_name = getenv('DB_DATABASE') ?: 'taskflow';
        $this->username = getenv('DB_USERNAME') ?: 'postgres';
        $this->password = getenv('DB_PASSWORD') ?: 'secret';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'DB connection failed']);
            exit;
        }
        return $this->conn;
    }
}
