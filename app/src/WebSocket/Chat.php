<?php
// app/src/WebSocket/Chat.php
namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Database\DatabaseConnection;
use PDOException;


class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $db; // PDO database connection object

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->db = DatabaseConnection::getInstance()->getPdo();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $broadcastMsg = sprintf("User %s connected", $conn->resourceId);
        $conn->send("Welcome! Messages you send may be stored in MySQL. Recent messages:");
        $this->broadcastToOtherClients($broadcastMsg, $conn);

        // Send last 5 messages (MySQL example)
        if ($this->db) {
            try {
                $stmt = $this->db->query("SELECT client_resource_id, message_text, received_at FROM chat_messages ORDER BY received_at DESC LIMIT 5");
                $recentMessages = $stmt->fetchAll();
                if ($recentMessages) {
                    foreach (array_reverse($recentMessages) as $message) { // Reverse to show oldest of the 5 first
                        $conn->send(sprintf("[%s] User %s: %s", $message['received_at'], $message['client_resource_id'], htmlspecialchars($message['message_text'])));
                    }
                } else {
                    $conn->send("System: No recent messages found.");
                }
            } catch (PDOException $e) {
                echo "Error fetching recent messages: " . $e->getMessage() . "\n";
                $conn->send("System: Could not retrieve recent messages.");
            }
        } else {
            $conn->send("System: Database (MySQL) connection not available for recent messages.");
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $senderId = $from->resourceId;
        if ($this->db) {
            try {
                $stmt = $this->db->prepare(
                    "INSERT INTO chat_messages (client_resource_id, message_text) VALUES (:id, :msg)"
                );
                $stmt->execute([':id' => $senderId, ':msg' => $msg]);
                echo "Message from {$from->resourceId} stored in database.\n";
            } catch (PDOException $e) {
                echo "Error storing message in MySQL: " . $e->getMessage() . "\n";
                $from->send("System: Error: Message not stored in MySQL.");
            }
        } else {
            $from->send("System: Error: MySQL not connected. Message not stored.");
        }

        // Broadcast original message to all clients
        $broadcastMsg = sprintf("User %s: %s", $senderId, htmlspecialchars($msg));
        $this->broadcastToOtherClients($broadcastMsg, $from);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $broadcastMsg = sprintf("User %s disconnected", $conn->resourceId);
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        // Notify other clients about the disconnection
        $this->broadcastToOtherClients($broadcastMsg, $conn->resourceId);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred on connection {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function broadcastToOtherClients($message, $from)
    {
        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send($message);
            }
        }
    }
}
