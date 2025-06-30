<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;
use App\WebSocket\GameServer; // Import GameServer for type hinting
use PDOException; // Use global namespace for PDOException

class ChatMessageHandler implements MessageHandlerInterface
{
    /**
     * Handles an incoming chat message.
     * Saves the message to the database and broadcasts it to other clients.
     *
     * @param ConnectionInterface $from The connection that sent the message.
     * @param array $data The decoded message data (expected to contain 'message').
     * @param GameServer $server The main GameServer instance (for accessing services and broadcasting).
     */
    public function handle(ConnectionInterface $from, array $data, GameServer $server): void
    {
        $username = $from->username ?? 'Guest'; // Fallback username from connection property
        $userId = $from->userId ?? 0; // Fallback user ID from connection property
        $chatText = $data['message'] ?? ''; // Extract message text from the data payload

        // Validate that the chat message is not empty
        if (empty($chatText)) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Chat message cannot be empty.']));
            return; // Stop processing if message is empty
        }

        try {
            // Use the injected ChatMessageRepository from the GameServer instance to save the message
            $server->getChatMessageRepository()->saveMessage($userId, $username, $chatText);
            echo "Chat message from {$username} stored in database.\n";
        } catch (PDOException $e) { // Catch PDOException for database errors
            error_log("Error storing chat message in MySQL for user {$username}: " . $e->getMessage());
            $from->send(json_encode(['type' => 'error', 'message' => 'Error: Message not stored in database.']));
            return; // Don't broadcast if database storage failed
        }

        // Broadcast the chat message to all relevant clients using GameServer's method
        $server->broadcastChatMessage($username, $chatText, $from);
    }
}
