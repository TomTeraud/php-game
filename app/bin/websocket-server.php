<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

use App\WebSocket\GameServer;
use App\Database\DatabaseConnection;
use App\Service\AuthService;
use App\Repository\ChatMessageRepository;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    // Load environment variables from the container's root directory.
    $dotenv = Dotenv::createImmutable('/var/www/html/');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    error_log("Warning: .env.local file not found or could not be loaded: " . $e->getMessage());
}

echo "Starting WebSocket server on port 9001...\n";

$pdoConnection = DatabaseConnection::getInstance()->getPdo();
$chatMessageRepository = new ChatMessageRepository($pdoConnection);
$authService = new AuthService();

$gameServer = new GameServer($authService, $chatMessageRepository);

// Set up the ReactPHP Event Loop and Socket Server manually for graceful shutdown.
$loop = Loop::get();
$webSock = new SocketServer('0.0.0.0:9001', [], $loop);

$server = new IoServer(
    new HttpServer(
        new WsServer(
            $gameServer
        )
    ),
    $webSock,
    $loop
);

// Add a signal handler for SIGTERM to ensure a clean shutdown.
$loop->addSignal(SIGTERM, function ($signal) use ($loop, $webSock) {
    echo "Caught SIGTERM ($signal). Initiating graceful shutdown...\n";
    if ($webSock instanceof SocketServer) {
        $webSock->close(); // Stop accepting new connections
    }
    $loop->stop(); // Stop the event loop
    echo "Event loop stopped. WebSocket server shutting down.\n";
});

$loop->run();

echo "WebSocket server stopped.\n";