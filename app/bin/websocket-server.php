<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\GameServer;
use App\Database\DatabaseConnection;
use App\Service\AuthService;
use App\Repository\ChatMessageRepository;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    // The .env files are located at /var/www/html/ in the container
    $dotenv = Dotenv::createImmutable('/var/www/html/');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    error_log("Warning: .env.local file not found or could not be loaded: " . $e->getMessage());
}

echo "Starting WebSocket server on port 9001...\n";

// Instantiate your services/repositories
$pdoConnection = DatabaseConnection::getInstance()->getPdo();
$chatMessageRepository = new ChatMessageRepository($pdoConnection);
$authService = new AuthService();

// Pass the services to the GameServer constructor
$gameServer = new GameServer($authService, $chatMessageRepository);

$server = IoServer::factory(
    // Wrap your core application logic (GameServer class) within the WebSocket and HTTP server protocols
    new HttpServer(
        new WsServer(
            $gameServer
        )
    ),
    9001,
    // Bind to '0.0.0.0' to accept connections on all available network interfaces within the container
    '0.0.0.0'
);

$server->run();

// This line will never be reached as $server->run() creates an infinite loop
echo "WebSocket server stopped.\n";