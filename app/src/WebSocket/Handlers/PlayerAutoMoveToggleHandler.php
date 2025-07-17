<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;
use App\WebSocket\GameServer;
use App\WebSocket\Handlers\MessageHandlerInterface;

class PlayerAutoMoveToggleHandler implements MessageHandlerInterface
{
    /**
     * Handles the 'toggle_automove' message.
     * Toggles the auto-move state for the specified player in the GameEngine.
     *
     * @param ConnectionInterface $from The connection that sent the message.
     * @param array $data The decoded message data (expected to contain 'player' and optionally 'direction').
     * @param GameServer $server The main GameServer instance.
     */
    public function handle(ConnectionInterface $from, array $data, GameServer $server): void
    {
        $playerId = $data['player'] ?? null; // e.g., 'player1'
        $direction = $data['direction'] ?? null; // Optional: direction to set if activating

        // Basic validation
        if (!in_array($playerId, ['player1'])) { // Only 'player1' for now
            error_log("Invalid player ID for auto-move toggle from {$from->resourceId}: " . json_encode($data));
            $from->send(json_encode(['type' => 'error', 'message' => 'Invalid player ID for auto-move.']));
            return;
        }
        // Validate direction if provided
        if ($direction !== null && !in_array($direction, ['up', 'down', 'left', 'right'])) {
             error_log("Invalid auto-move direction from {$from->resourceId}: " . json_encode($data));
             $from->send(json_encode(['type' => 'error', 'message' => 'Invalid auto-move direction.']));
             return;
        }

        // Delegate the toggle to the GameEngine
        $server->getGameEngine()->toggleAutoMove($playerId, $direction);
    }
}