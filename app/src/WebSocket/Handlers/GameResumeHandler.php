<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;
use App\WebSocket\GameServer; // Import GameServer for type hinting
use App\WebSocket\Handlers\MessageHandlerInterface; // Import MessageHandlerInterface explicitly

class GameResumeHandler implements MessageHandlerInterface
{
    /**
     * Handles the 'game_pause_request' message.
     * Delegates the command to stop the game loop to the GameEngine.
     *
     * @param ConnectionInterface $from The connection that sent the message.
     * @param array $data The decoded message data.
     * @param GameServer $server The main GameServer instance.
     */
    public function handle(ConnectionInterface $from, array $data, GameServer $server): void
    {
        // Delegate the call to pause the game loop to the GameEngine instance
        $server->getGameEngine()->resumeServerGameLoop();

        // Optionally send a status message back to all connected clients
        // The GameServer provides a getter for its clients collection.
        foreach ($server->getClients() as $client) {
            $client->send(json_encode(['type' => 'game_status', 'message' => 'Game resumed.']));
        }
    }
}
