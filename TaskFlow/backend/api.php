<?php
/**
 * TaskFlow — Единая точка входа API
 * Стиль Битрикс24: POST-запрос с JSON {"method": "entity.action", "params": {...}}
 */

declare(strict_types=1);

// Автозагрузка Composer
require_once __DIR__ . '/vendor/autoload.php';

// Загружаем конфигурацию и модули
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/mail.php';

// CORS
setCorsHeaders();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Только POST (с исключением для публичных GET-методов)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Only POST requests are accepted', 405);
}

// Читаем тело запроса
$rawBody = file_get_contents('php://input');
$request = json_decode($rawBody, true);

if (!$request || empty($request['method'])) {
    sendError('Invalid request. Expected: {"method": "...", "params": {...}}', 400);
}

$method = $request['method'];
$params = $request['params'] ?? [];

// Rate limiting для всех запросов
checkRateLimit();

// Список публичных методов (не требуют авторизации)
$publicMethods = ['auth.register', 'auth.verify', 'auth.resend', 'auth.login', 'auth.refresh', 'tasks.list', 'reviews.public'];

$requiresAuth = !in_array($method, $publicMethods);
$currentUser = null;

if ($requiresAuth) {
    $currentUser = authenticate();
}

// Маршрутизация методов
try {
    switch (true) {
        // ============ AUTH ============
        case $method === 'auth.register':
            handleAuthRegister($params);
            break;
        case $method === 'auth.verify':
            handleAuthVerify($params);
            break;
        case $method === 'auth.resend':
            handleAuthResend($params);
            break;
        case $method === 'auth.login':
            handleAuthLogin($params);
            break;
        case $method === 'auth.refresh':
            handleAuthRefresh($params);
            break;

        // ============ USERS ============
        case $method === 'users.me':
            handleUsersMe($currentUser);
            break;
        case $method === 'users.updateProfile':
            handleUsersUpdateProfile($currentUser, $params);
            break;
        case $method === 'users.list':
            handleUsersList($currentUser);
            break;

        // ============ TASKS ============
        case $method === 'tasks.list':
            handleTasksList($currentUser, $params);
            break;
        case $method === 'tasks.create':
            handleTasksCreate($currentUser, $params);
            break;
        case $method === 'tasks.update':
            handleTasksUpdate($currentUser, $params);
            break;
        case $method === 'tasks.delete':
            handleTasksDelete($currentUser, $params);
            break;

        // ============ BOARD COLUMNS ============
        case $method === 'board.columns.list':
            handleBoardColumnsList($currentUser, $params);
            break;
        case $method === 'board.columns.create':
            handleBoardColumnsCreate($currentUser, $params);
            break;
        case $method === 'board.columns.update':
            handleBoardColumnsUpdate($currentUser, $params);
            break;
        case $method === 'board.columns.delete':
            handleBoardColumnsDelete($currentUser, $params);
            break;

        // ============ COLLABS ============
        case $method === 'funnels.list':
            handleFunnelsList($currentUser);
            break;

        case $method === 'collabs.list':
            handleCollabsList($currentUser);
            break;
        case $method === 'collabs.get':
            handleCollabsGet($currentUser, $params);
            break;
        case $method === 'collabs.create':
            handleCollabsCreate($currentUser, $params);
            break;
        case $method === 'collabs.members.list':
            handleCollabsMembersList($currentUser, $params);
            break;
        case $method === 'collabs.addMember':
            handleCollabsAddMember($currentUser, $params);
            break;
        case $method === 'collabs.removeMember':
            handleCollabsRemoveMember($currentUser, $params);
            break;
        case $method === 'collabs.getMessages':
            handleCollabsGetMessages($currentUser, $params);
            break;

        // ============ MESSAGES ============
        case $method === 'messages.private.list':
            handleMessagesPrivateList($currentUser, $params);
            break;
        case $method === 'messages.send':
            handleMessagesSend($currentUser, $params);
            break;

        // ============ REVIEWS ============
        case $method === 'reviews.public':
            handleReviewsPublic($params);
            break;
        case $method === 'reviews.list':
            handleReviewsList($currentUser, $params);
            break;
        case $method === 'reviews.create':
            handleReviewsCreate($currentUser, $params);
            break;

        // ============ ADMIN ============
        case str_starts_with($method, 'admin.'):
            requirePortalAdmin($currentUser);
            handleAdminMethods($currentUser, $method, $params);
            break;

        default:
            sendError("Unknown method: {$method}", 404);
    }
} catch (\PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    sendError('Internal server error', 500);
} catch (\Exception $e) {
    error_log("Error: " . $e->getMessage());
    sendError($e->getMessage(), 400);
}

// ============================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================

function sendJson(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function toPgBool(mixed $value): string
{
    if ($value === true || $value === 1 || $value === '1' || $value === 'true' || $value === 't') {
        return 'true';
    }
    return 'false';
}

function isPgTrue(mixed $value): bool
{
    return $value === true || $value === 't' || $value === 'true' || $value === 1 || $value === '1';
}

function sendResult(mixed $result): void
{
    sendJson(['result' => $result]);
}

function sendError(string $message, int $code = 400): void
{
    sendJson(['error' => $message], $code);
}

// ============================================================
// AUTH HANDLERS
// ============================================================

function handleAuthRegister(array $params): void
{
    $db = getDB();
    $email = trim($params['email'] ?? '');
    $password = $params['password'] ?? '';
    $username = trim($params['username'] ?? explode('@', $email)[0]);

    if (!$email || !$password) {
        sendError('Email and password are required');
    }
    if (strlen($password) < 6) {
        sendError('Password must be at least 6 characters');
    }

    // Проверяем, не занят ли email
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        sendError('Email already registered');
    }

    // Администратор (создаёт портал) или сотрудник (по приглашению)
    if (!empty($params['company'])) {
        // Создание портала и администратора
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('INSERT INTO portals (name, invite_code) VALUES (?, ?) RETURNING id');
            $inviteCode = substr(bin2hex(random_bytes(10)), 0, 20);
            $stmt->execute([$params['company'], $inviteCode]);
            $portalId = $stmt->fetchColumn();

            $passwordHash = hashPassword($password);
            $stmt = $db->prepare("INSERT INTO users (email, username, password_hash, role, portal_id) VALUES (?, ?, ?, 'admin', ?) RETURNING id");
            $stmt->execute([$email, $username, $passwordHash, $portalId]);
            $userId = $stmt->fetchColumn();

            // Обновляем owner_id портала
            $db->prepare('UPDATE portals SET owner_id = ? WHERE id = ?')->execute([$userId, $portalId]);

            // Создаём дефолтные роли портала
            $defaultRoles = [
                ['Директор', 1],
                ['Менеджер', 2],
                ['Супервайзер', 3],
                ['Техспециалист', 4],
            ];
            $stmtRole = $db->prepare('INSERT INTO portal_roles (portal_id, name, hierarchy_level) VALUES (?, ?, ?) RETURNING id');
            $directorRoleId = null;
            foreach ($defaultRoles as [$name, $level]) {
                $stmtRole->execute([$portalId, $name, $level]);
                if ($name === 'Директор') {
                    $directorRoleId = (int)$stmtRole->fetchColumn();
                }
            }

            // Назначаем администратору портала роль «Директор»
            if ($directorRoleId) {
                $db->prepare('UPDATE users SET portal_role_id = ? WHERE id = ?')
                    ->execute([$directorRoleId, $userId]);
            }

            // Главная воронка канбан-доски
            $db->prepare('INSERT INTO funnels (portal_id, name, is_main) VALUES (?, ?, TRUE)')
                ->execute([$portalId, 'Главная']);

            // Создаём дефолтные колонки канбан-доски
            $defaultColumns = [
                ['Новые', '#3b82f6'],
                ['В работе', '#f59e0b'],
                ['На проверке', '#8b5cf6'],
                ['Готово', '#22c55e'],
            ];
            $stmtCol = $db->prepare('INSERT INTO board_columns (portal_id, title, position, color) VALUES (?, ?, ?, ?)');
            foreach ($defaultColumns as $i => [$title, $color]) {
                $stmtCol->execute([$portalId, $title, $i, $color]);
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } else {
        // Регистрация сотрудника по коду приглашения
        $inviteCode = trim($params['invite_code'] ?? '');
        if (!$inviteCode) {
            sendError('Invite code is required for employee registration');
        }

        $stmt = $db->prepare("SELECT id, portal_id, max_uses, used_count, expires FROM portal_invitations WHERE code = ? AND is_active = TRUE");
        $stmt->execute([$inviteCode]);
        $invitation = $stmt->fetch();

        if (!$invitation) {
            sendError('Invalid or expired invitation code');
        }
        if ($invitation['used_count'] >= $invitation['max_uses']) {
            sendError('Invitation code has reached maximum uses');
        }
        if ($invitation['expires'] && strtotime($invitation['expires']) < time()) {
            sendError('Invitation code has expired');
        }

        $passwordHash = hashPassword($password);
        $stmt = $db->prepare("INSERT INTO users (email, username, password_hash, role, portal_id) VALUES (?, ?, ?, 'user', ?) RETURNING id");
        $stmt->execute([$email, $username, $passwordHash, $invitation['portal_id']]);
        $userId = $stmt->fetchColumn();

        // Обновляем счётчик использований
        $db->prepare('UPDATE portal_invitations SET used_count = used_count + 1 WHERE id = ?')->execute([$invitation['id']]);
    }

    // Генерируем код подтверждения email
    $code = generateVerificationCode();
    $stmt = $db->prepare('INSERT INTO email_verifications (user_id, code, expires) VALUES (?, ?, ?)
        ON CONFLICT (user_id) DO UPDATE SET code = ?, expires = ?');
    $expires = date('Y-m-d H:i:s', time() + 3600);
    $stmt->execute([$userId, $code, $expires, $code, $expires]);

    $emailSent = sendVerificationEmail($email, $code, $username);

    sendResult([
        'message' => $emailSent
            ? 'Registration successful. Verification code sent to your email.'
            : 'Registration successful. Check your email for the verification code.',
        'email_sent' => $emailSent,
        'verification_code' => (!$emailSent && APP_DEBUG) ? $code : null,
    ]);
}

function handleAuthResend(array $params): void
{
    $db = getDB();
    $email = trim($params['email'] ?? '');

    if (!$email) {
        sendError('Email is required');
    }

    $stmt = $db->prepare('SELECT u.id, u.username, u.is_verified FROM users u WHERE u.email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        sendError('User not found');
    }
    if ($user['is_verified']) {
        sendError('Email is already verified');
    }

    $code = generateVerificationCode();
    $expires = date('Y-m-d H:i:s', time() + 3600);
    $db->prepare('INSERT INTO email_verifications (user_id, code, expires) VALUES (?, ?, ?)
        ON CONFLICT (user_id) DO UPDATE SET code = ?, expires = ?')
        ->execute([$user['id'], $code, $expires, $code, $expires]);

    $emailSent = sendVerificationEmail($email, $code, $user['username']);

    sendResult([
        'message' => $emailSent ? 'Verification code resent' : 'Could not send email',
        'email_sent' => $emailSent,
        'verification_code' => (!$emailSent && APP_DEBUG) ? $code : null,
    ]);
}

function handleAuthVerify(array $params): void
{
    $db = getDB();
    $email = trim($params['email'] ?? '');
    $code = trim($params['code'] ?? '');

    if (!$email || !$code) {
        sendError('Email and verification code are required');
    }

    $stmt = $db->prepare("SELECT u.id, ev.code, ev.expires FROM users u JOIN email_verifications ev ON u.id = ev.user_id WHERE u.email = ?");
    $stmt->execute([$email]);
    $verification = $stmt->fetch();

    if (!$verification) {
        sendError('Invalid email');
    }
    if ($verification['code'] !== $code) {
        sendError('Invalid verification code');
    }
    if (strtotime($verification['expires']) < time()) {
        sendError('Verification code expired');
    }

    // Активируем аккаунт и создаём настройки
    $db->prepare('UPDATE users SET is_verified = TRUE WHERE id = ?')->execute([$verification['id']]);
    $db->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$verification['id']]);
    $db->prepare('INSERT INTO user_settings (user_id) VALUES (?) ON CONFLICT DO NOTHING')->execute([$verification['id']]);

    $user = getUserById($verification['id']);
    $accessToken = generateAccessToken($user);
    $refreshToken = generateRefreshToken($user);

    sendResult([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'user' => $user,
    ]);
}

function handleAuthLogin(array $params): void
{
    $db = getDB();
    $email = trim($params['email'] ?? '');
    $password = $params['password'] ?? '';

    if (!$email || !$password) {
        sendError('Email and password are required');
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !verifyPassword($password, $user['password_hash'])) {
        sendError('Invalid email or password');
    }
    if (!$user['is_verified']) {
        sendError('Please verify your email first');
    }
    if ($user['is_blocked']) {
        sendError('Account is blocked');
    }

    $fullUser = getUserById($user['id']);
    $accessToken = generateAccessToken($fullUser);
    $refreshToken = generateRefreshToken($fullUser);

    sendResult([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'user' => $fullUser,
    ]);
}

function handleAuthRefresh(array $params): void
{
    $refreshToken = $params['refresh_token'] ?? '';

    if (!$refreshToken) {
        sendError('Refresh token is required');
    }

    try {
        $payload = verifyJWT($refreshToken);
        if ($payload->type !== 'refresh') {
            sendError('Invalid token type');
        }
        if (!isRefreshTokenValid($payload->sub, $refreshToken)) {
            sendError('Token has been revoked');
        }

        $user = getUserById($payload->sub);
        if (!$user) {
            sendError('User not found');
        }

        $newAccessToken = generateAccessToken($user);
        sendResult(['access_token' => $newAccessToken]);
    } catch (\Exception $e) {
        sendError('Invalid refresh token');
    }
}

// ============================================================
// USERS HANDLERS
// ============================================================

function handleUsersMe(array $user): void
{
    sendResult(['user' => $user]);
}

function handleUsersList(array $user): void
{
    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, email, avatar_url, portal_role_id FROM users WHERE portal_id = ? AND is_blocked = FALSE ORDER BY username ASC');
    $stmt->execute([$user['portal_id']]);
    sendResult(['users' => $stmt->fetchAll()]);
}

function handleUsersUpdateProfile(array $user, array $params): void
{
    $db = getDB();

    if (isset($params['username'])) {
        $stmt = $db->prepare('UPDATE users SET username = ? WHERE id = ?');
        $stmt->execute([$params['username'], $user['id']]);
    }
    if (isset($params['avatar_url'])) {
        $stmt = $db->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
        $stmt->execute([$params['avatar_url'], $user['id']]);
    }
    if (isset($params['theme'])) {
        $db->prepare("INSERT INTO user_settings (user_id, theme) VALUES (?, ?) ON CONFLICT (user_id) DO UPDATE SET theme = ?")
            ->execute([$user['id'], $params['theme'], $params['theme']]);
    }
    if (isset($params['language'])) {
        $db->prepare("INSERT INTO user_settings (user_id, language) VALUES (?, ?) ON CONFLICT (user_id) DO UPDATE SET language = ?")
            ->execute([$user['id'], $params['language'], $params['language']]);
    }

    sendResult(['user' => getUserById($user['id'])]);
}

// ============================================================
// TASKS HANDLERS
// ============================================================

function handleTasksList(?array $user, array $params): void
{
    $db = getDB();
    $conditions = [];
    $bindings = [];
    $collabFunnelMode = false;

    if ($user) {
        $conditions[] = 't.portal_id = ?';
        $bindings[] = $user['portal_id'];

        if (!empty($params['funnel_id'])) {
            $funnelId = (int)$params['funnel_id'];
            $stmt = $db->prepare('SELECT * FROM funnels WHERE id = ? AND portal_id = ?');
            $stmt->execute([$funnelId, $user['portal_id']]);
            $funnel = $stmt->fetch();
            if (!$funnel) {
                sendError('Funnel not found');
            }

            if (isPgTrue($funnel['is_main'])) {
                $conditions[] = 't.collab_id IS NULL';
            } else {
                $collabId = (int)$funnel['collab_id'];
                if ($user['role'] !== 'admin') {
                    $memberCheck = $db->prepare('SELECT 1 FROM collab_members WHERE collab_id = ? AND user_id = ?');
                    $memberCheck->execute([$collabId, $user['id']]);
                    if (!$memberCheck->fetch()) {
                        sendError('Access denied');
                    }
                }
                $conditions[] = 't.collab_id = ?';
                $bindings[] = $collabId;
                $collabFunnelMode = true;
            }
        }

        if ($user['role'] !== 'admin' && !$collabFunnelMode) {
            $conditions[] = '(t.creator_id = ? OR t.assignee_id = ? OR t.is_public = TRUE)';
            $bindings[] = $user['id'];
            $bindings[] = $user['id'];
        }

        if (!empty($params['column_id'])) {
            $conditions[] = 't.column_id = ?';
            $bindings[] = $params['column_id'];
        }
        if (!empty($params['collab_id'])) {
            $conditions[] = 't.collab_id = ?';
            $bindings[] = $params['collab_id'];
        }
        if (isset($params['is_public'])) {
            $conditions[] = 't.is_public = ?';
            $bindings[] = toPgBool($params['is_public']);
        }
    } else {
        // Гость: только публичные задачи
        $conditions[] = 't.is_public = TRUE';
        $conditions[] = 't.collab_id IS NULL';
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql = "
        SELECT t.*, u.username as creator_name, u2.username as assignee_name
        FROM tasks t
        LEFT JOIN users u ON t.creator_id = u.id
        LEFT JOIN users u2 ON t.assignee_id = u2.id
        {$where}
        ORDER BY t.position ASC, t.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($bindings);
    sendResult(['tasks' => $stmt->fetchAll()]);
}

function handleTasksCreate(array $user, array $params): void
{
    $db = getDB();
    $title = trim($params['title'] ?? '');
    if (!$title) sendError('Title is required');

    $assigneeId = $params['assignee_id'] ?? null;
    if ($assigneeId) {
        $assignee = getUserById((int)$assigneeId);
        if (!$assignee || $assignee['portal_id'] !== $user['portal_id']) {
            sendError('Invalid assignee');
        }
        // Проверка иерархии (если не в коллабе)
        if (empty($params['collab_id']) && !canAssignTo($user, $assignee)) {
            sendError('Cannot assign task to this user due to hierarchy');
        }
    }

    $collabId = $params['collab_id'] ?? null;
    if (!empty($params['funnel_id']) && !$collabId) {
        $stmt = $db->prepare('SELECT collab_id, is_main FROM funnels WHERE id = ? AND portal_id = ?');
        $stmt->execute([(int)$params['funnel_id'], $user['portal_id']]);
        $funnel = $stmt->fetch();
        if ($funnel && !isPgTrue($funnel['is_main']) && $funnel['collab_id']) {
            $collabId = (int)$funnel['collab_id'];
            if ($user['role'] !== 'admin') {
                $memberCheck = $db->prepare('SELECT 1 FROM collab_members WHERE collab_id = ? AND user_id = ?');
                $memberCheck->execute([$collabId, $user['id']]);
                if (!$memberCheck->fetch()) {
                    sendError('Access denied');
                }
            }
        }
    }

    $stmt = $db->prepare('INSERT INTO tasks (title, description, priority, creator_id, assignee_id, collab_id, column_id, portal_id, position, is_public, is_active, deadline)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
    $stmt->execute([
        $title,
        $params['description'] ?? '',
        $params['priority'] ?? 'medium',
        $user['id'],
        $assigneeId,
        $collabId,
        $params['column_id'] ?? null,
        $user['portal_id'],
        $params['position'] ?? 0,
        !empty($params['is_public']) ? 'true' : 'false',
        isset($params['is_active']) ? ($params['is_active'] ? 'true' : 'false') : 'true',
        $params['deadline'] ?? null,
    ]);

    $taskId = $stmt->fetchColumn();
    $task = $db->prepare('SELECT t.*, u.username AS creator_name, u2.username AS assignee_name FROM tasks t LEFT JOIN users u ON t.creator_id = u.id LEFT JOIN users u2 ON t.assignee_id = u2.id WHERE t.id = ?');
    $task->execute([$taskId]);

    // Инвалидируем кеш канбана
    try { getRedis()->del("kanban:{$user['portal_id']}"); } catch (\Exception $e) {}

    sendResult(['task' => $task->fetch()]);
}

function handleTasksUpdate(array $user, array $params): void
{
    $db = getDB();
    $taskId = (int)($params['id'] ?? 0);
    if (!$taskId) sendError('Task ID is required');

    $stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) sendError('Task not found');
    if ($task['portal_id'] !== $user['portal_id']) sendError('Access denied');

    $isAdmin = $user['role'] === 'admin';
    $isCreator = (int)$task['creator_id'] === (int)$user['id'];
    $isAssignee = (int)$task['assignee_id'] === (int)$user['id'];
    $canFullEdit = $isAdmin || $isCreator;
    $canAssigneeEdit = $isAssignee && !$canFullEdit;

    if (!$canFullEdit && !$canAssigneeEdit) {
        sendError('Access denied');
    }

    $updates = [];
    $bindings = [];

    $fullEditFields = ['title', 'description', 'priority', 'column_id', 'position', 'is_public', 'is_active', 'deadline'];
    $assigneeFields = ['column_id', 'is_active'];

    foreach ($fullEditFields as $field) {
        if (!array_key_exists($field, $params)) continue;
        if ($canAssigneeEdit && !in_array($field, $assigneeFields, true)) continue;

        $updates[] = "{$field} = ?";
        if (in_array($field, ['is_public', 'is_active'], true)) {
            $bindings[] = toPgBool($params[$field]);
        } elseif ($field === 'deadline') {
            $bindings[] = $params[$field] ?: null;
        } elseif ($field === 'column_id') {
            $bindings[] = $params[$field] ? (int)$params[$field] : null;
        } else {
            $bindings[] = $params[$field];
        }
    }

    // Смена исполнителя с проверкой иерархии (только создатель или админ)
    if ($canFullEdit && array_key_exists('assignee_id', $params)) {
        $newAssigneeId = $params['assignee_id'];
        if ($newAssigneeId === '' || $newAssigneeId === null) {
            if ($task['assignee_id'] !== null && $task['assignee_id'] !== '') {
                $updates[] = 'assignee_id = NULL';
            }
        } elseif ((int)$newAssigneeId !== (int)$task['assignee_id']) {
            $assignee = getUserById((int)$newAssigneeId);
            if (!$assignee || $assignee['portal_id'] !== $user['portal_id']) {
                sendError('Invalid assignee');
            }
            if (empty($task['collab_id']) && !canAssignTo($user, $assignee)) {
                sendError('Cannot assign task due to hierarchy');
            }
            $updates[] = 'assignee_id = ?';
            $bindings[] = (int)$newAssigneeId;
        }
    }

    if ($updates) {
        $updates[] = 'updated_at = NOW()';
        $bindings[] = $taskId;
        $sql = 'UPDATE tasks SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $db->prepare($sql)->execute($bindings);
    }

    try { getRedis()->del("kanban:{$user['portal_id']}"); } catch (\Exception $e) {}

    $stmt = $db->prepare('SELECT t.*, u.username AS creator_name, u2.username AS assignee_name FROM tasks t LEFT JOIN users u ON t.creator_id = u.id LEFT JOIN users u2 ON t.assignee_id = u2.id WHERE t.id = ?');
    $stmt->execute([$taskId]);
    sendResult(['task' => $stmt->fetch()]);
}

function handleTasksDelete(array $user, array $params): void
{
    $db = getDB();
    $taskId = (int)($params['id'] ?? 0);

    $stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) sendError('Task not found');
    if ($user['role'] !== 'admin' && $task['creator_id'] !== $user['id']) {
        sendError('Only creator or admin can delete this task');
    }

    $db->prepare('DELETE FROM tasks WHERE id = ?')->execute([$taskId]);
    try { getRedis()->del("kanban:{$user['portal_id']}"); } catch (\Exception $e) {}

    sendResult(['message' => 'Task deleted']);
}

// ============================================================
// BOARD COLUMNS HANDLERS
// ============================================================

function handleBoardColumnsList(array $user, array $params): void
{
    $portalId = $params['portal_id'] ?? $user['portal_id'];
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM board_columns WHERE portal_id = ? ORDER BY position ASC');
    $stmt->execute([$portalId]);
    sendResult(['columns' => $stmt->fetchAll()]);
}

function handleBoardColumnsCreate(array $user, array $params): void
{
    requirePortalAdmin($user);
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO board_columns (portal_id, title, position, color) VALUES (?, ?, ?, ?) RETURNING id');
    $stmt->execute([$user['portal_id'], $params['title'], $params['position'] ?? 0, $params['color'] ?? '#808080']);
    sendResult(['column_id' => $stmt->fetchColumn()]);
}

function handleBoardColumnsUpdate(array $user, array $params): void
{
    requirePortalAdmin($user);
    $db = getDB();
    $id = (int)($params['id'] ?? 0);
    if (!$id) sendError('Column ID is required');

    $stmt = $db->prepare('SELECT * FROM board_columns WHERE id = ? AND portal_id = ?');
    $stmt->execute([$id, $user['portal_id']]);
    $column = $stmt->fetch();
    if (!$column) sendError('Column not found');

    $title = $params['title'] ?? $column['title'];
    $position = $params['position'] ?? $column['position'];
    $color = $params['color'] ?? $column['color'] ?? '#808080';

    $db->prepare('UPDATE board_columns SET title = ?, position = ?, color = ? WHERE id = ? AND portal_id = ?')
        ->execute([$title, $position, $color, $id, $user['portal_id']]);
    sendResult(['message' => 'Column updated']);
}

function handleBoardColumnsDelete(array $user, array $params): void
{
    requirePortalAdmin($user);
    $db = getDB();
    $db->prepare('DELETE FROM board_columns WHERE id = ? AND portal_id = ?')
        ->execute([$params['id'], $user['portal_id']]);
    sendResult(['message' => 'Column deleted']);
}

// ============================================================
// COLLABS HANDLERS
// ============================================================

function handleFunnelsList(array $user): void
{
    $db = getDB();
    if ($user['role'] === 'admin') {
        $stmt = $db->prepare('SELECT * FROM funnels WHERE portal_id = ? ORDER BY is_main DESC, name ASC');
        $stmt->execute([$user['portal_id']]);
    } else {
        $stmt = $db->prepare('
            SELECT f.*
            FROM funnels f
            LEFT JOIN collab_members cm ON f.collab_id = cm.collab_id AND cm.user_id = ?
            WHERE f.portal_id = ? AND (f.is_main = TRUE OR cm.user_id IS NOT NULL)
            ORDER BY f.is_main DESC, f.name ASC
        ');
        $stmt->execute([$user['id'], $user['portal_id']]);
    }
    sendResult(['funnels' => $stmt->fetchAll()]);
}

function handleCollabsList(array $user): void
{
    $db = getDB();
    if ($user['role'] === 'admin') {
        $stmt = $db->prepare('
            SELECT c.*, cm.role AS member_role
            FROM collabs c
            LEFT JOIN collab_members cm ON c.id = cm.collab_id AND cm.user_id = ?
            WHERE c.portal_id = ?
            ORDER BY c.created_at DESC
        ');
        $stmt->execute([$user['id'], $user['portal_id']]);
    } else {
        $stmt = $db->prepare('
            SELECT c.*, cm.role as member_role
            FROM collabs c
            JOIN collab_members cm ON c.id = cm.collab_id AND cm.user_id = ?
            ORDER BY c.created_at DESC
        ');
        $stmt->execute([$user['id']]);
    }
    sendResult(['collabs' => $stmt->fetchAll()]);
}

function handleCollabsGet(array $user, array $params): void
{
    $db = getDB();
    $collabId = (int)($params['id'] ?? 0);
    if (!$collabId) sendError('Collab ID is required');

    $stmt = $db->prepare('SELECT * FROM collabs WHERE id = ? AND portal_id = ?');
    $stmt->execute([$collabId, $user['portal_id']]);
    $collab = $stmt->fetch();
    if (!$collab) sendError('Collab not found');

    if ($user['role'] !== 'admin') {
        $memberCheck = $db->prepare('SELECT role FROM collab_members WHERE collab_id = ? AND user_id = ?');
        $memberCheck->execute([$collabId, $user['id']]);
        $memberRole = $memberCheck->fetchColumn();
        if (!$memberRole) sendError('Access denied');
        $collab['member_role'] = $memberRole;
    } else {
        $memberCheck = $db->prepare('SELECT role FROM collab_members WHERE collab_id = ? AND user_id = ?');
        $memberCheck->execute([$collabId, $user['id']]);
        $collab['member_role'] = $memberCheck->fetchColumn() ?: 'admin';
    }

    sendResult(['collab' => $collab]);
}

function handleCollabsCreate(array $user, array $params): void
{
    $db = getDB();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO collabs (name, description, owner_id, portal_id) VALUES (?, ?, ?, ?) RETURNING id');
        $stmt->execute([$params['name'], $params['description'] ?? '', $user['id'], $user['portal_id']]);
        $collabId = $stmt->fetchColumn();

        $db->prepare("INSERT INTO collab_members (collab_id, user_id, role) VALUES (?, ?, 'admin')")
            ->execute([$collabId, $user['id']]);

        $db->prepare('INSERT INTO funnels (portal_id, name, collab_id, is_main) VALUES (?, ?, ?, FALSE)')
            ->execute([$user['portal_id'], $params['name'], $collabId]);

        $db->commit();
        sendResult(['collab_id' => $collabId]);
    } catch (\Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function handleCollabsMembersList(array $user, array $params): void
{
    $db = getDB();
    $collabId = (int)($params['collab_id'] ?? 0);
    if (!$collabId) sendError('Collab ID is required');

    if ($user['role'] !== 'admin') {
        $stmt = $db->prepare('SELECT 1 FROM collab_members WHERE collab_id = ? AND user_id = ?');
        $stmt->execute([$collabId, $user['id']]);
        if (!$stmt->fetch()) sendError('Not a member of this collab');
    } else {
        $stmt = $db->prepare('SELECT 1 FROM collabs WHERE id = ? AND portal_id = ?');
        $stmt->execute([$collabId, $user['portal_id']]);
        if (!$stmt->fetch()) sendError('Collab not found');
    }

    $stmt = $db->prepare('
        SELECT u.id, u.username, u.email, u.avatar_url, cm.role, cm.joined_at
        FROM collab_members cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.collab_id = ?
        ORDER BY cm.joined_at ASC
    ');
    $stmt->execute([$collabId]);
    sendResult(['members' => $stmt->fetchAll()]);
}

function handleCollabsAddMember(array $user, array $params): void
{
    $db = getDB();
    $collabId = (int)($params['collab_id'] ?? 0);
    $newUserId = (int)($params['user_id'] ?? 0);
    if (!$collabId || !$newUserId) sendError('Collab ID and user ID are required');

    $stmt = $db->prepare("SELECT role FROM collab_members WHERE collab_id = ? AND user_id = ?");
    $stmt->execute([$collabId, $user['id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') sendError('Only collab admin can add members');

    $stmt = $db->prepare('SELECT portal_id FROM users WHERE id = ?');
    $stmt->execute([$newUserId]);
    $newUserPortal = $stmt->fetchColumn();
    if (!$newUserPortal || (int)$newUserPortal !== (int)$user['portal_id']) {
        sendError('User must belong to the same portal');
    }

    $db->prepare("INSERT INTO collab_members (collab_id, user_id, role) VALUES (?, ?, 'executor') ON CONFLICT DO NOTHING")
        ->execute([$collabId, $newUserId]);
    sendResult(['message' => 'Member added']);
}

function handleCollabsRemoveMember(array $user, array $params): void
{
    $db = getDB();
    $stmt = $db->prepare("SELECT role FROM collab_members WHERE collab_id = ? AND user_id = ?");
    $stmt->execute([$params['collab_id'], $user['id']]);
    if ($stmt->fetchColumn() !== 'admin') sendError('Only collab admin can remove members');

    $db->prepare('DELETE FROM collab_members WHERE collab_id = ? AND user_id = ?')
        ->execute([$params['collab_id'], $params['user_id']]);
    sendResult(['message' => 'Member removed']);
}

function handleCollabsGetMessages(array $user, array $params): void
{
    $db = getDB();
    $collabId = (int)($params['collab_id'] ?? 0);
    if (!$collabId) sendError('Collab ID is required');

    if ($user['role'] !== 'admin') {
        $stmt = $db->prepare('SELECT 1 FROM collab_members WHERE collab_id = ? AND user_id = ?');
        $stmt->execute([$collabId, $user['id']]);
        if (!$stmt->fetch()) sendError('Not a member of this collab');
    } else {
        $stmt = $db->prepare('SELECT 1 FROM collabs WHERE id = ? AND portal_id = ?');
        $stmt->execute([$collabId, $user['portal_id']]);
        if (!$stmt->fetch()) sendError('Collab not found');
    }

    $limit = min((int)($params['limit'] ?? 50), 200);
    $stmt = $db->prepare('
        SELECT cm.*, u.username, u.avatar_url
        FROM collab_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.collab_id = ?
        ORDER BY cm.created_at DESC
        LIMIT ?
    ');
    $stmt->execute([$params['collab_id'], $limit]);
    sendResult(['messages' => array_reverse($stmt->fetchAll())]);
}

// ============================================================
// MESSAGES HANDLERS
// ============================================================

function handleMessagesPrivateList(array $user, array $params): void
{
    $db = getDB();
    $otherId = (int)($params['user_id'] ?? 0);
    $limit = min((int)($params['limit'] ?? 50), 200);

    $stmt = $db->prepare('
        SELECT pm.*, u.username, u.username AS sender_name
        FROM private_messages pm
        JOIN users u ON pm.sender_id = u.id
        WHERE (pm.sender_id = ? AND pm.receiver_id = ?) OR (pm.sender_id = ? AND pm.receiver_id = ?)
        ORDER BY pm.created_at DESC
        LIMIT ?
    ');
    $stmt->execute([$user['id'], $otherId, $otherId, $user['id'], $limit]);

    // Помечаем как прочитанные
    $db->prepare('UPDATE private_messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE')
        ->execute([$otherId, $user['id']]);

    sendResult(['messages' => array_reverse($stmt->fetchAll())]);
}

function handleMessagesSend(array $user, array $params): void
{
    $db = getDB();
    $content = trim($params['content'] ?? '');
    if (!$content) sendError('Message content is required');

    if (!empty($params['collab_id'])) {
        // Групповое сообщение
        $stmt = $db->prepare('SELECT 1 FROM collab_members WHERE collab_id = ? AND user_id = ?');
        $stmt->execute([$params['collab_id'], $user['id']]);
        if (!$stmt->fetch()) sendError('Not a member of this collab');

        $db->prepare('INSERT INTO collab_messages (collab_id, sender_id, content) VALUES (?, ?, ?)')
            ->execute([$params['collab_id'], $user['id'], $content]);
    } else {
        // Личное сообщение
        $receiverId = (int)($params['receiver_id'] ?? 0);
        if (!$receiverId) sendError('Receiver ID is required');

        $db->prepare('INSERT INTO private_messages (sender_id, receiver_id, content) VALUES (?, ?, ?)')
            ->execute([$user['id'], $receiverId, $content]);
    }

    sendResult(['message' => 'Message sent']);
}

// ============================================================
// REVIEWS HANDLERS
// ============================================================

function handleReviewsPublic(array $params): void
{
    $db = getDB();
    $stmt = $db->prepare('
        SELECT r.*, u.username
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.is_approved = TRUE
        ORDER BY r.created_at DESC
        LIMIT 12
    ');
    $stmt->execute();
    sendResult(['reviews' => $stmt->fetchAll()]);
}

function handleReviewsList(array $user, array $params): void
{
    $db = getDB();
    $portalId = $params['portal_id'] ?? $user['portal_id'] ?? null;
    $showUnapproved = ($user && $user['role'] === 'admin') ? '' : ' AND r.is_approved = TRUE';

    $stmt = $db->prepare("
        SELECT r.*, u.username, u.avatar_url
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE u.portal_id = ? {$showUnapproved}
        ORDER BY r.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$portalId]);
    sendResult(['reviews' => $stmt->fetchAll()]);
}

function handleReviewsCreate(array $user, array $params): void
{
    $db = getDB();
    $rating = (int)($params['rating'] ?? 0);
    if ($rating < 1 || $rating > 5) sendError('Rating must be between 1 and 5');

    $db->prepare('INSERT INTO reviews (user_id, rating, content) VALUES (?, ?, ?)')
        ->execute([$user['id'], $rating, $params['content'] ?? '']);
    sendResult(['message' => 'Review submitted for moderation']);
}

// ============================================================
// ADMIN HANDLERS
// ============================================================

function handleAdminMethods(array $user, string $method, array $params): void
{
    $db = getDB();

    switch ($method) {
        case 'admin.users.list':
            $stmt = $db->prepare('SELECT u.*, pr.name as portal_role_name FROM users u LEFT JOIN portal_roles pr ON u.portal_role_id = pr.id WHERE u.portal_id = ? ORDER BY u.created_at DESC');
            $stmt->execute([$user['portal_id']]);
            sendResult(['users' => $stmt->fetchAll()]);
            break;

        case 'admin.users.setRole':
            $roleId = !empty($params['portal_role_id']) ? (int)$params['portal_role_id'] : null;
            $db->prepare('UPDATE users SET portal_role_id = ? WHERE id = ? AND portal_id = ?')
                ->execute([$roleId, $params['user_id'], $user['portal_id']]);
            sendResult(['message' => 'Role updated']);
            break;

        case 'admin.users.block':
            $db->prepare('UPDATE users SET is_blocked = ? WHERE id = ? AND portal_id = ?')
                ->execute([$params['block'] ? 'true' : 'false', $params['user_id'], $user['portal_id']]);
            sendResult(['message' => $params['block'] ? 'User blocked' : 'User unblocked']);
            break;

        case 'admin.users.invite':
            $code = substr(bin2hex(random_bytes(10)), 0, 20);
            $db->prepare('INSERT INTO portal_invitations (portal_id, code, created_by, max_uses, expires) VALUES (?, ?, ?, ?, ?)')
                ->execute([$user['portal_id'], $code, $user['id'], $params['max_uses'] ?? 1, $params['expires'] ?? null]);
            sendResult(['invite_code' => $code]);
            break;

        case 'admin.roles.list':
            $stmt = $db->prepare('SELECT * FROM portal_roles WHERE portal_id = ? ORDER BY hierarchy_level ASC');
            $stmt->execute([$user['portal_id']]);
            sendResult(['roles' => $stmt->fetchAll()]);
            break;

        case 'admin.roles.create':
            $stmt = $db->prepare('INSERT INTO portal_roles (portal_id, name, hierarchy_level) VALUES (?, ?, ?) RETURNING id');
            $stmt->execute([$user['portal_id'], $params['name'], $params['hierarchy_level']]);
            sendResult(['role_id' => $stmt->fetchColumn()]);
            break;

        case 'admin.roles.update':
            $db->prepare('UPDATE portal_roles SET name = ?, hierarchy_level = ? WHERE id = ? AND portal_id = ?')
                ->execute([$params['name'], $params['hierarchy_level'], $params['id'], $user['portal_id']]);
            sendResult(['message' => 'Role updated']);
            break;

        case 'admin.roles.delete':
            $db->prepare('DELETE FROM portal_roles WHERE id = ? AND portal_id = ?')
                ->execute([$params['id'], $user['portal_id']]);
            sendResult(['message' => 'Role deleted']);
            break;

        case 'admin.collabs.list':
            $stmt = $db->prepare('SELECT c.*, u.username as owner_name FROM collabs c JOIN users u ON c.owner_id = u.id WHERE c.portal_id = ?');
            $stmt->execute([$user['portal_id']]);
            sendResult(['collabs' => $stmt->fetchAll()]);
            break;

        case 'admin.collabs.delete':
            $db->prepare('DELETE FROM collabs WHERE id = ? AND portal_id = ?')
                ->execute([$params['id'], $user['portal_id']]);
            sendResult(['message' => 'Collab deleted']);
            break;

        case 'admin.tasks.list':
            $stmt = $db->prepare('SELECT t.*, u1.username as creator_name, u2.username as assignee_name FROM tasks t LEFT JOIN users u1 ON t.creator_id = u1.id LEFT JOIN users u2 ON t.assignee_id = u2.id WHERE t.portal_id = ? ORDER BY t.created_at DESC');
            $stmt->execute([$user['portal_id']]);
            sendResult(['tasks' => $stmt->fetchAll()]);
            break;

        case 'admin.tasks.update':
            handleTasksUpdate($user, $params);
            break;

        case 'admin.tasks.delete':
            handleTasksDelete($user, $params);
            break;

        case 'admin.board.columns.list':
            handleBoardColumnsList($user, ['portal_id' => $user['portal_id']]);
            break;

        case 'admin.board.columns.create':
            handleBoardColumnsCreate($user, $params);
            break;

        case 'admin.board.columns.update':
            handleBoardColumnsUpdate($user, $params);
            break;

        case 'admin.board.columns.delete':
            handleBoardColumnsDelete($user, $params);
            break;

        case 'admin.reviews.list':
            $stmt = $db->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE u.portal_id = ? ORDER BY r.created_at DESC");
            $stmt->execute([$user['portal_id']]);
            sendResult(['reviews' => $stmt->fetchAll()]);
            break;

        case 'admin.reviews.approve':
            $db->prepare('UPDATE reviews SET is_approved = TRUE WHERE id = ?')->execute([$params['id']]);
            sendResult(['message' => 'Review approved']);
            break;

        case 'admin.reviews.delete':
            $db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$params['id']]);
            sendResult(['message' => 'Review deleted']);
            break;

        default:
            sendError("Unknown admin method: {$method}");
    }
}
