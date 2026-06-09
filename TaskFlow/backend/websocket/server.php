<?php
/**
 * TaskFlow — WebSocket-сервер на Ratchet
 * Обрабатывает личные и групповые чаты в реальном времени.
 * Запуск: php server.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ChatServer implements MessageComponentInterface
{
    /** @var array<int, ConnectionInterface[]> — user_id => [connections] */
    protected array $clients = [];

    /** @var array<int, array> — connection resourceId => user */
    protected array $userMap = [];

    public function onOpen(ConnectionInterface $conn): void
    {
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $params);
        $token = $params['token'] ?? '';

        if (!$token) {
            $conn->close();
            echo "Connection rejected: no token\n";
            return;
        }

        try {
            $payload = verifyJWT($token);
            $user = getUserById($payload->sub);

            if (!$user) {
                $conn->close();
                return;
            }

            $this->userMap[$conn->resourceId] = $user;
            $this->clients[$user['id']][] = $conn;

            echo "User {$user['username']} (#{$user['id']}) connected\n";
        } catch (\Exception $e) {
            $conn->close();
            echo "Connection rejected: invalid token\n";
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $user = $this->userMap[$from->resourceId] ?? null;
        if (!$user) return;

        $data = json_decode($msg, true);
        if (!$data || empty($data['type'])) return;

        $type = $data['type'];
        $targetId = (int)($data['target_id'] ?? 0);
        $content = trim($data['content'] ?? '');

        if (!$content) return;

        $db = getDB();
        $message = [
            'type' => $type,
            'sender_id' => $user['id'],
            'sender_name' => $user['username'],
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($type === 'private') {
            // Сохраняем в БД
            $db->prepare('INSERT INTO private_messages (sender_id, receiver_id, content) VALUES (?, ?, ?)')
                ->execute([$user['id'], $targetId, $content]);

            $message['receiver_id'] = $targetId;

            // Отправляем отправителю (эхо)
            $from->send(json_encode($message));

            // Отправляем получателю (если онлайн)
            if (isset($this->clients[$targetId])) {
                foreach ($this->clients[$targetId] as $client) {
                    $client->send(json_encode($message));
                }
            }
        } elseif ($type === 'collab') {
            // Сохраняем в БД
            $db->prepare('INSERT INTO collab_messages (collab_id, sender_id, content) VALUES (?, ?, ?)')
                ->execute([$targetId, $user['id'], $content]);

            $message['collab_id'] = $targetId;

            // Получаем всех участников коллаба
            $stmt = $db->prepare('SELECT user_id FROM collab_members WHERE collab_id = ?');
            $stmt->execute([$targetId]);
            $members = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            // Рассылаем всем онлайн-участникам
            foreach ($members as $memberId) {
                if (isset($this->clients[$memberId])) {
                    foreach ($this->clients[$memberId] as $client) {
                        $client->send(json_encode($message));
                    }
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $user = $this->userMap[$conn->resourceId] ?? null;
        if ($user) {
            // Удаляем соединение из списка
            if (isset($this->clients[$user['id']])) {
                $this->clients[$user['id']] = array_filter(
                    $this->clients[$user['id']],
                    fn($c) => $c !== $conn
                );
                if (empty($this->clients[$user['id']])) {
                    unset($this->clients[$user['id']]);
                }
            }
            unset($this->userMap[$conn->resourceId]);
            echo "User {$user['username']} (#{$user['id']}) disconnected\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Запуск сервера
$port = (int)($_ENV['WS_PORT'] ?? 8080);
echo "WebSocket server starting on port {$port}...\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    $port
);

$server->run();
