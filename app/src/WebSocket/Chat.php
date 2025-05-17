<?php
// app/src/WebSocket/Chat.php
namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO; // For MySQL
use PDOException; // For error handling

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $db; // PDO database connection object

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->initializeDbConnection();
    }

    public function onOpen(ConnectionInterface $conn) {
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

    public function onMessage(ConnectionInterface $from, $msg) {
        echo sprintf('Connection %d sending message "%s"' . "\n", $from->resourceId, $msg);

        // Store in MySQL
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("INSERT INTO chat_messages (client_resource_id, message_text) VALUES (?, ?)");
                $stmt->execute([$from->resourceId, $msg]);
                echo "Message from {$from->resourceId} stored in database.\n";
            } catch (PDOException $e) {
                echo "Error storing message in MySQL: " . $e->getMessage() . "\n";
                $from->send("System: Error: Message not stored in MySQL.");
            }
        } else {
            $from->send("System: Error: MySQL not connected. Message not stored.");
        }

        // Broadcast original message to all clients
        $broadcastMsg = sprintf("User %s: %s", $from->resourceId, htmlspecialchars($msg));
        $this->broadcastToOtherClients($broadcastMsg, $from);
    }

    public function onClose(ConnectionInterface $conn) {
        $broadcastMsg = sprintf("User %s disconnected", $conn->resourceId);
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        // Notify other clients about the disconnection
        $this->broadcastToOtherClients($broadcastMsg, $conn->resourceId);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred on connection {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function initializeDbConnection() {
        $dbHost = getenv('MYSQL_HOST') ?: 'db';
        $dbName = getenv('MYSQL_DATABASE') ?: 'your_db_name';
        $dbUser = getenv('MYSQL_USER') ?: 'your_db_user';
        $dbPass = getenv('MYSQL_PASSWORD') ?: 'your_db_password';
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $maxRetries = 5; // Number of times to retry connecting
        $retryDelay = 3; // Seconds to wait between retries

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->db = new PDO($dsn, $dbUser, $dbPass, $options);
                echo "Successfully connected to MySQL database '{$dbName}' on attempt {$attempt}.\n";
                return; // Connection successful, exit the method
            } catch (PDOException $e) {
                echo "Database Connection Error (Attempt {$attempt}/{$maxRetries}): " . $e->getMessage() . "\n";
                if ($attempt < $maxRetries) {
                    echo "Retrying in {$retryDelay} seconds...\n";
                    sleep($retryDelay); // Wait before retrying
                } else {
                    echo "Failed to connect to the database after {$maxRetries} attempts.\n";
                    $this->db = null; // Ensure $this->db is null if connection failed
                }
            }
        }
    }

    protected function broadcastToOtherClients($message, $from) {
        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send($message);
            }
        }
    }
}