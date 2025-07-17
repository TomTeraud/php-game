<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;
use App\WebSocket\GameServer;
use App\WebSocket\Handlers\MessageHandlerInterface;

class PlayerMoveCommandHandler implements MessageHandlerInterface
{
    /**
     * Handles the 'player_move_command' message.
     * Adds the move command to the GameEngine's pending commands queue.
     *
     * @param ConnectionInterface $from The connection that sent the message.
     * @param array $data The decoded message data (expected to contain 'player' and 'command').
     * @param GameServer $server The main GameServer instance.
     */
    public function handle(ConnectionInterface $from, array $data, GameServer $server): void
    {
        $playerId = $data['player'] ?? null; // e.g., 'player1'
        $command = $data['command'] ?? null; // e.g., 'up', 'down', 'left', 'right'

        // Basic validation of input data
        if (!in_array($playerId, ['player1']) || !in_array($command, ['up', 'down', 'left', 'right'])) {
            error_log("Invalid player move command received from {$from->resourceId}: " . json_encode($data));
            $from->send(json_encode(['type' => 'error', 'message' => 'Invalid player move command.']));
            return;
        }

        // Delegate adding the move command to the GameEngine
        $server->getGameEngine()->addPlayerMoveCommand($playerId, $command);
    }
}