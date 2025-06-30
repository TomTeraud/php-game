<?php

require_once dirname(__DIR__) . '/bootstrap/app.php';
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use App\WebSocket\GameServer;
use App\Game\GameEngine;
use App\Database\DatabaseConnection;
use App\Service\AuthService;
use App\Repository\ChatMessageRepository;

echo "Starting WebSocket server on port 9001...\n";

$loop = Loop::get(); // Get the global ReactPHP event loop

$pdoConnection = DatabaseConnection::getInstance()->getPdo();
$chatMessageRepository = new ChatMessageRepository($pdoConnection);
$authService = new AuthService();

// Instantiate GameEngine BEFORE GameServer
// Pass the loop and a callable (the GameServer's broadcastGameState method)
$gameEngine = new GameEngine($loop, function(array $ballState) use ($chatMessageRepository, $authService, $loop, &$gameServer) {
    // This callback runs in GameEngine. It needs to call GameServer's method.
    // The $gameServer variable is passed by reference (&) to ensure the callback has the *actual* GameServer instance
    // once it's created below. This is a common pattern for circular dependencies or late binding.
    // Ensure $gameServer is defined before this callback is executed.
    if (isset($gameServer)) {
        $gameServer->broadcastGameState($ballState);
    } else {
        error_log("Attempted to broadcast game state before GameServer was fully initialized.");
    }
});


// Create your main game server application instance.
// CRITICAL LINE: Ensure all 4 arguments are passed here.
// App\WebSocket\GameServer::__construct(AuthService $authService, ChatMessageRepository $chatMessageRepository, LoopInterface $loop, GameEngine $gameEngine)
$gameServer = new GameServer($authService, $chatMessageRepository, $loop, $gameEngine);


// Set up the ReactPHP Event Loop and Socket Server manually for graceful shutdown.
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
        $webSock->close();
        error_log("--- WebSocket server closed listening socket. ---");
    }

    $loop->stop();
    error_log("--- Event loop stopped. WebSocket server shutting down. ---");
};

$loop->addSignal(SIGTERM, function ($signal) use ($gracefulShutdown, $loop, $webSock) {
    $gracefulShutdown($signal, $loop, $webSock);
});

$loop->addSignal(SIGQUIT, function ($signal) use ($gracefulShutdown, $loop, $webSock) {
    $gracefulShutdown($signal, $loop, $webSock);
});

$loop->run();

echo "WebSocket server stopped.\n";