<?php
/**
 * TaskFlow — Аутентификация и авторизация
 * Генерация/проверка JWT-токенов, хранение refresh-токенов в Redis.
 */

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Генерация access-токена (короткоживущий, 15 минут)
function generateAccessToken(array $user): string
{
    $payload = [
        'iss' => 'taskflow',
        'sub' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'portal_id' => $user['portal_id'],
        'iat' => time(),
        'exp' => time() + JWT_ACCESS_TTL,
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

// Генерация refresh-токена (долгоживущий, 7 дней)
function generateRefreshToken(array $user): string
{
    $payload = [
        'iss' => 'taskflow',
        'sub' => $user['id'],
        'type' => 'refresh',
        'iat' => time(),
        'exp' => time() + JWT_REFRESH_TTL,
    ];
    $token = JWT::encode($payload, JWT_SECRET, 'HS256');

    // Сохраняем в Redis для возможности инвалидации
    try {
        $redis = getRedis();
        $redis->setex("refresh_token:{$user['id']}:{$token}", JWT_REFRESH_TTL, '1');
    } catch (\Exception $e) {
        // Логируем, но не прерываем
        error_log("Redis error: " . $e->getMessage());
    }

    return $token;
}

// Проверка JWT-токена (возвращает payload или выбрасывает исключение)
function verifyJWT(string $token): object
{
    return JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
}

// Инвалидация refresh-токена (при выходе)
function invalidateRefreshToken(int $userId, string $token): void
{
    try {
        $redis = getRedis();
        $redis->del("refresh_token:{$userId}:{$token}");
    } catch (\Exception $e) {
        error_log("Redis error: " . $e->getMessage());
    }
}

// Проверка, что refresh-токен не отозван
function isRefreshTokenValid(int $userId, string $token): bool
{
    try {
        $redis = getRedis();
        return $redis->exists("refresh_token:{$userId}:{$token}") > 0;
    } catch (\Exception $e) {
        // Если Redis недоступен — разрешаем (грациозная деградация)
        return true;
    }
}

// Хеширование пароля
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Проверка пароля
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

// Генерация 6-значного кода подтверждения
function generateVerificationCode(): string
{
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
