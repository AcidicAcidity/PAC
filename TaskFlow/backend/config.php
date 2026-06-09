<?php
/**
 * TaskFlow — Конфигурация приложения
 * Загружает переменные окружения и предоставляет функции подключения к БД и Redis.
 */

declare(strict_types=1);

// Загрузка .env (простой парсер)
function loadEnv(string $path): void
{
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Загружаем .env из корня проекта
loadEnv(__DIR__ . '/../.env');

// Конфигурация БД
define('DB_HOST', $_ENV['DB_HOST'] ?? 'db');
define('DB_PORT', $_ENV['DB_PORT'] ?? '5432');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'taskflow');
define('DB_USER', $_ENV['DB_USER'] ?? 'taskflow');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? 'taskflow_secret');

// Redis
define('REDIS_HOST', $_ENV['REDIS_HOST'] ?? 'redis');
define('REDIS_PORT', (int)($_ENV['REDIS_PORT'] ?? 6379));

// JWT
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'super_secret_change_me');
define('JWT_ACCESS_TTL', (int)($_ENV['JWT_ACCESS_TTL'] ?? 900));
define('JWT_REFRESH_TTL', (int)($_ENV['JWT_REFRESH_TTL'] ?? 604800));

// Приложение
define('APP_DEBUG', ($_ENV['APP_DEBUG'] ?? 'false') === 'true');

// Подключение к PostgreSQL через PDO
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

// Подключение к Redis через Predis
function getRedis(): Predis\Client
{
    static $redis = null;
    if ($redis === null) {
        $redis = new Predis\Client([
            'scheme' => 'tcp',
            'host' => REDIS_HOST,
            'port' => REDIS_PORT,
        ]);
    }
    return $redis;
}

// CORS-заголовки
function setCorsHeaders(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
}
