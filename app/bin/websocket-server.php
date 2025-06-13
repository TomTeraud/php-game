<?php

require_once dirname(__DIR__) . '/bootstrap/app.php';
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use App\WebSocket\GameServer;
use App\Database\DatabaseConnection;
use App\Service\AuthService;
use App\Repository\ChatMessageRepository;

echo "Starting WebSocket server on port 9001...\n";

$pdoConnection = DatabaseConnection::getInstance()->getPdo();
$chatMessageRepository = new ChatMessageRepository($pdoConnection);
$authService = new AuthService();

// Create your main game server application instance.
$gameServer = new GameServer($authService, $chatMessageRepository);

// Set up the ReactPHP Event Loop and Socket Server manually for graceful shutdown.
$loop = Loop::get();
$webSock = new SocketServer('0.0.0.0:9001', [], $loop);

// Wrap your GameServer with Ratchet's HTTP and WebSocket layers.
$server = new IoServer(
    new HttpServer(
        new WsServer(
            $gameServer
        )
    ),
    $webSock,
    $loop
);

/**
 * Common graceful shutdown function for the WebSocket server.
 * This function will be called when SIGTERM or SIGQUIT signals are received.
 *
 * @param int $signal The signal number (e.g., SIGTERM = 15, SIGQUIT = 3).
 * @param \React\EventLoop\LoopInterface $loop The ReactPHP event loop instance.
 * @param \React\Socket\SocketServer $webSock The ReactPHP socket server instance.
 */
$gracefulShutdown = function (int $signal, \React\EventLoop\LoopInterface $loop, SocketServer $webSock) {
    error_log("--- Caught signal ($signal). Initiating graceful shutdown... ---");

    if ($webSock instanceof SocketServer) {
        $webSock->close(); // Stop accepting new connections and close existing ones.
        error_log("--- WebSocket server closed listening socket. ---");
    }

    $loop->stop(); // Stop the event loop, causing the script to exit gracefully.
    error_log("--- Event loop stopped. WebSocket server shutting down. ---");
};

// Add signal handler for SIGTERM (standard Docker graceful shutdown signal).
$loop->addSignal(SIGTERM, function ($signal) use ($gracefulShutdown, $loop, $webSock) {
    $gracefulShutdown($signal, $loop, $webSock);
});

// Add signal handler for SIGQUIT (observed as the signal currently sent by your Docker Compose setup).
$loop->addSignal(SIGQUIT, function ($signal) use ($gracefulShutdown, $loop, $webSock) {
    $gracefulShutdown($signal, $loop, $webSock);
});

// Run the event loop; this starts the WebSocket server and keeps it alive
// until $loop->stop() is explicitly called by a signal handler.
$loop->run();

// This line will only be reached once the $loop->stop() is called and the server has shut down.
echo "WebSocket server stopped.\n";