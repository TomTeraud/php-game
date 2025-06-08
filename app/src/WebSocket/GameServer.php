<?php
namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use PDOException;

use App\Service\AuthService;
use App\Repository\ChatMessageRepository;

class GameServer implements MessageComponentInterface
{
    protected SplObjectStorage $clients;
    protected AuthService $authService;
    protected ChatMessageRepository $chatMessageRepository;

    public function __construct(AuthService $authService, ChatMessageRepository $chatMessageRepository)
    {
        $this->clients = new SplObjectStorage;
        $this->authService = $authService;
        $this->chatMessageRepository = $chatMessageRepository;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        if (!$this->authService->authenticate($conn)) {
            // AuthService already handled closing the connection and logging if authentication failed
            return;
        }

        $this->clients->attach($conn);
        echo "New connection ({$conn->resourceId}) authenticated for user: {$conn->username} (ID: {$conn->userId})!\n";

        $conn->send("Welcome! Messages you send may be stored in MySQL. Recent messages:");
        try {
            $recentMessages = $this->chatMessageRepository->getRecentMessages(5);
            if ($recentMessages) {
                foreach ($recentMessages as $message) {
                    $conn->send(sprintf("[%s] User %s: %s", $message['received_at'], $message['username'], htmlspecialchars($message['message_text'])));
                }
            } else {
                $conn->send("System: No recent messages found.");
            }
        } catch (PDOException $e) {
            error_log("Error fetching recent messages: " . $e->getMessage());
            $conn->send("System: Could not retrieve recent messages.");
        }

        $broadcastMsg = sprintf("User %s connected", $conn->username);
        $this->broadcastToOtherClients($broadcastMsg, $conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $username = $from->username ?? 'Guest'; // Fallback for unauthenticated connections if they slip through
        $userId = $from->userId ?? 0;

        try {
            $this->chatMessageRepository->saveMessage($userId, $username, $msg);
            echo "Message from {$username} stored in database.\n";
        } catch (PDOException $e) {
            error_log("Error storing message in MySQL: " . $e->getMessage());
            $from->send("System: Error: Message not stored in MySQL.");
            return; // Don't broadcast if storage failed
        }

        $broadcastMsg = sprintf("User %s: %s", $username, htmlspecialchars($msg));
        $this->broadcastToOtherClients($broadcastMsg, $from);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";

        $username = $conn->username ?? $conn->resourceId;
        $broadcastMsg = sprintf("User %s disconnected", $username);
        $this->broadcastToOtherClients($broadcastMsg, $conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        error_log("An error has occurred on connection {$conn->resourceId}: {$e->getMessage()}");
        $conn->close();
    }

    /**
     * Broadcasts a message to all clients currently connected, excluding the sender.
     * This method could be extracted into a dedicated Broadcaster service later.
     *
     * @param string $message The message to send.
     * @param ConnectionInterface $from The connection that sent the message (will be excluded).
     */
    protected function broadcastToOtherClients($message, ConnectionInterface $from) // Type-hint $from
    {
        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send($message);
            }
        }
    }
}