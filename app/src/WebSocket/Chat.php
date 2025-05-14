<?php
// app/src/WebSocket/Chat.php
namespace App\WebSocket; // Ensure this matches your composer.json PSR-4 autoloading for "App\\" -> "src/"

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        // SplObjectStorage is used to store all connected clients
        $this->clients = new \SplObjectStorage;
        echo "Simple Echo WebSocket server initialized.\n";
        echo "Any message received from a client will be echoed back to that client.\n";
    }

    /**
     * Called when a new client has connected
     * @param ConnectionInterface $conn The new connection
     */
    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";

        // Send a welcome message to the newly connected client
        $conn->send("Welcome! This is an echo server. Send a message, and I'll send it back to you.");
    }

    /**
     * Called when a message is received from a client
     * @param ConnectionInterface $from The connection that sent the message
     * @param string $msg The message received
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) -1; // Not really relevant for echo, but can be kept for logging
        echo sprintf('Connection %d sending message "%s"' . "\n", $from->resourceId, $msg);

        // --- This is the core of the "Simple Echo" ---
        // Send the received message ($msg) back to the original sender ($from)
        $from->send($msg);
        echo "Echoed message back to Connection {$from->resourceId}.\n";
    }

    /**
     * Called when a client disconnects
     * @param ConnectionInterface $conn The connection that disconnected
     */
    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * Called when an error occurs on a connection
     * @param ConnectionInterface $conn The connection that experienced the error
     * @param \Exception $e The exception that occurred
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred on connection {$conn->resourceId}: {$e->getMessage()}\n";

        // It's good practice to close the connection on error
        $conn->close();
    }
}
