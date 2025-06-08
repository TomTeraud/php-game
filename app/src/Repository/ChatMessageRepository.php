<?php
namespace App\Repository;

use PDO;
use PDOException;

class ChatMessageRepository
{
    private PDO $db;

    public function __construct(PDO $pdoConnection)
    {
        $this->db = $pdoConnection;
    }

    /**
     * Stores a chat message in the database.
     *
     * @param int    $userId The ID of the user sending the message.
     * @param string $username The username of the sender.
     * @param string $messageText The content of the message.
     * @throws PDOException If the message cannot be stored.
     */
    public function saveMessage(int $userId, string $username, string $messageText): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO chat_messages (username, message_text) VALUES (:username, :msg)"
        );
        $stmt->execute([':username' => $username, ':msg' => $messageText]);
    }

    /**
     * Retrieves the most recent chat messages from the database.
     *
     * @param int $limit The maximum number of messages to retrieve.
     * @return array An array of message data.
     * @throws PDOException If messages cannot be retrieved.
     */
    public function getRecentMessages(int $limit = 5): array
    {
        $stmt = $this->db->query(
            "SELECT username, message_text, received_at FROM chat_messages ORDER BY received_at DESC LIMIT " . (int)$limit
        );
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC)); // Reverse to show oldest of the N first
    }
}