<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;
use App\WebSocket\GameServer; // Import GameServer for type hinting

/**
 * Interface for WebSocket message handlers.
 * Each specific message type will have a class implementing this interface.
 */
interface MessageHandlerInterface
{
    /**
     * Handles a specific type of WebSocket message.
     *
     * @param ConnectionInterface $from The connection that sent the message.
     * @param array $data The decoded message data (JSON object).
     * @param GameServer $server The main GameServer instance (for accessing its methods/properties).
     */
    public function handle(ConnectionInterface $from, array $data, GameServer $server): void;
}
