<?php
/**
 * TaskFlow — Middleware
 * Проверка JWT, rate limiting, получение текущего пользователя.
 */

declare(strict_types=1);

// Rate limiting: 60 запросов в минуту с одного IP
function checkRateLimit(): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $key = "rate_limit:{$ip}";

    try {
        $redis = getRedis();
        $current = $redis->incr($key);
        if ($current === 1) {
            $redis->expire($key, 60);
        }
        if ($current > 60) {
            sendError('Rate limit exceeded. Try again later.', 429);
        }
    } catch (\Exception $e) {
        // Redis недоступен — пропускаем (не блокируем приложение)
    }
}

// Проверка JWT из заголовка Authorization
function authenticate(): array
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!str_starts_with($authHeader, 'Bearer ')) {
        sendError('Authentication required', 401);
    }

    $token = substr($authHeader, 7);

    try {
        $payload = verifyJWT($token);
        $user = getUserById($payload->sub);

        if (!$user) {
            sendError('User not found', 401);
        }
        if ($user['is_blocked']) {
            sendError('Account is blocked', 403);
        }

        return $user;
    } catch (\Firebase\JWT\ExpiredException $e) {
        sendError('Token expired', 401);
    } catch (\Exception $e) {
        sendError('Invalid token', 401);
    }
}

// Получение пользователя по ID
function getUserById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare('
        SELECT u.*, s.theme, s.language
        FROM users u
        LEFT JOIN user_settings s ON u.id = s.user_id
        WHERE u.id = ?
    ');
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

// Проверка роли администратора портала
function requirePortalAdmin(array $user): void
{
    if ($user['role'] !== 'admin') {
        sendError('Admin access required', 403);
    }
}

// Проверка иерархии: можно ли назначать задачу подчинённому
function canAssignTo(array $assigner, array $assignee): bool
{
    // Админ портала может всё
    if ($assigner['role'] === 'admin') return true;

    // Нельзя назначать самому себе
    if ($assigner['id'] === $assignee['id']) return true;

    // Проверяем иерархию portal_roles
    $db = getDB();
    $stmt = $db->prepare('SELECT hierarchy_level FROM portal_roles WHERE id = ?');
    $stmt->execute([$assigner['portal_role_id']]);
    $assignerLevel = $stmt->fetchColumn();

    $stmt->execute([$assignee['portal_role_id']]);
    $assigneeLevel = $stmt->fetchColumn();

    // Назначать можно только тем, у кого уровень выше (число больше = ниже по иерархии)
    if ($assignerLevel === false || $assigneeLevel === false) return false;
    return $assignerLevel < $assigneeLevel;
}
