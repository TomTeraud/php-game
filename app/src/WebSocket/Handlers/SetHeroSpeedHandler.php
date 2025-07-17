<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;
use App\WebSocket\GameServer;
use App\WebSocket\Handlers\MessageHandlerInterface;

class SetHeroSpeedHandler implements MessageHandlerInterface
{
    /**
     * Handles the 'set_hero_speed' message.
     * Sets the hero's movement speed multiplier in the GameEngine.
     *
     * @param ConnectionInterface $from The connection that sent the message.
     * @param array $data The decoded message data (expected to contain 'player' and 'multiplier').
     * @param GameServer $server The main GameServer instance.
     */
    public function handle(ConnectionInterface $from, array $data, GameServer $server): void
    {
        $playerId = $data['player'] ?? null;
        $multiplier = $data['multiplier'] ?? null;

        // Basic validation
        if (!in_array($playerId, ['player1']) || !is_numeric($multiplier)) {
            error_log("Invalid set_hero_speed data from {$from->resourceId}: " . json_encode($data));
            $from->send(json_encode(['type' => 'error', 'message' => 'Invalid speed data.']));
            return;
        }

        // Delegate setting the speed to the GameEngine
        $server->getGameEngine()->setHeroSpeedMultiplier($playerId, (float)$multiplier);
    }
}