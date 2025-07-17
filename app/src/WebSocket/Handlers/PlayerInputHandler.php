<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;
use App\WebSocket\GameServer;

class PlayerInputHandler implements MessageHandlerInterface
{
    /**
     * Handles player input messages for hero movement.
     * Delegates the active input state to the GameEngine.
     *
     * @param ConnectionInterface $clientConnection The connection that sent the message.
     * @param array $messageData The decoded message data (expected to contain 'player', 'inputState', 'sequence').
     * @param GameServer $gameServerInstance The main GameServer instance.
     */
    public function handle(ConnectionInterface $clientConnection, array $messageData, GameServer $gameServerInstance): void
    {
        $playerId = $messageData['player'] ?? null; // In a real game, this would be derived from $clientConnection->userId
        $inputState = $messageData['inputState'] ?? []; // Expected to be an associative array like ['up' => true, 'left' => false]
        $sequence = $messageData['sequence'] ?? null; // Get sequence number

        // Ensure $inputState is an array and $sequence is an integer (or null if the GameEngine allows)
        // Given the client sends a sequence, we'll make it mandatory here.
        if ($playerId === null || !is_array($inputState) || $sequence === null) {
            error_log("Invalid player input message from {$clientConnection->resourceId}: Missing player, inputState, or sequence.");
            $clientConnection->send(json_encode(['type' => 'error', 'message' => 'Invalid input.']));
            return;
        }

        // Delegate the input state (the entire activeInputs object) and sequence to the GameEngine
        $gameServerInstance->getGameEngine()->setPlayerInput($playerId, $inputState, (int)$sequence);
    }
}
