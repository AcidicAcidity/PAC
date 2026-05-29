<?php
class RateLimiter {
    private $redis;
    private $maxRequests = 60; // в минуту

    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect(getenv('REDIS_HOST') ?: 'redis', 6379);
    }

    public function check($ip) {
        $key = "rate_limit:$ip";
        $current = $this->redis->get($key);
        if ($current !== false && $current >= $this->maxRequests) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests']);
            exit;
        }
        $this->redis->incr($key);
        $this->redis->expire($key, 60);
    }
}
