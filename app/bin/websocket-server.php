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

/**
 * WebSocket Server Entry Point
 * 
 * Continuous Game Engine Implementation
 * - Game engine runs automatically when server starts
 * - No user controls for game state (start/stop/pause/resume)
 * - Users connect and see continuous bouncing ball animation
 * - Server admin controls everything via command line/system tools
 */

// ========================================
// SERVER INITIALIZATION
// ========================================

echo "Starting WebSocket server on port 9001...\n";
echo "Implementation: Continuous Game Engine\n";
echo "----------------------------------------\n";

// ---- CORE DEPENDENCIES ----
$loop = Loop::get(); // ReactPHP event loop for timers and WebSocket handling
$pdoConnection = DatabaseConnection::getInstance()->getPdo();
$chatMessageRepository = new ChatMessageRepository($pdoConnection);
$authService = new AuthService();

// ---- RESOLVE CIRCULAR DEPENDENCY ----
// GameEngine needs GameServer's broadcast method, but GameServer needs GameEngine instance
// Solution: Create with placeholder callback, then update after GameServer creation

echo "Initializing game engine...\n";

// Create placeholder callback for initial GameEngine construction
$placeholderCallback = function(array $gameState) {
    error_log("Game state broadcast called before GameServer initialization");
};

// Create GameEngine with placeholder callback
$gameEngine = new GameEngine($loop, $placeholderCallback);

echo "Initializing game server...\n";

// Create GameServer with GameEngine instance
$gameServer = new GameServer($authService, $chatMessageRepository, $loop, $gameEngine);

// Replace placeholder with real broadcast callback
$gameEngine->setBroadcastCallback([$gameServer, 'broadcastGameState']);

// ---- AUTO-START GAME ENGINE (CONTINUOUS MODE) ----
// Game starts immediately and runs continuously
// No user controls - server admin manages via command line only
echo "Auto-starting game engine (continuous mode)...\n";
$gameEngine->startServerGameLoop();

echo "Game engine started at 5.5 FPS\n";

// ========================================
// WEBSOCKET SERVER SETUP
// ========================================

echo "Setting up WebSocket server...\n";

// Create socket server with manual control for graceful shutdown
$webSock = new SocketServer('0.0.0.0:9001', [], $loop);

// Build the Ratchet server stack: IoServer -> HttpServer -> WsServer -> GameServer
$server = new IoServer(
    new HttpServer(
        new WsServer($gameServer)
    ),
    $webSock,
    $loop
);

// ========================================
// GRACEFUL SHUTDOWN HANDLING
// ========================================

/**
 * Graceful shutdown handler for continuous game implementation
 * Ensures proper cleanup of game engine and network resources
 * 
 * @param int $signal The received signal (SIGTERM, SIGINT, SIGQUIT)
 */
$gracefulShutdown = function (int $signal) use ($gameEngine, $loop, $webSock) {
    echo "\n--- Graceful shutdown initiated (signal: $signal) ---\n";

    // Step 1: Stop the continuous game engine
    if ($gameEngine->getGameLoopTimer() !== null) {
        echo "--- Stopping continuous game engine... ---\n";
        $gameEngine->stopServerGameLoop();
        echo "--- Game engine stopped successfully ---\n";
    } else {
        echo "--- Game engine was not running ---\n";
    }

    // Step 2: Close WebSocket server to stop accepting new connections
    if ($webSock instanceof SocketServer) {
        echo "--- Closing WebSocket server... ---\n";
        $webSock->close();
        echo "--- WebSocket server closed ---\n";
    }

    // Step 3: Stop the ReactPHP event loop
    echo "--- Stopping event loop... ---\n";
    $loop->stop();
    echo "--- Server shutdown complete ---\n";
};

// Register signal handlers for different shutdown scenarios
$loop->addSignal(SIGTERM, $gracefulShutdown);  // System termination (systemctl stop)
$loop->addSignal(SIGINT, $gracefulShutdown);   // User interrupt (Ctrl+C)
$loop->addSignal(SIGQUIT, $gracefulShutdown);  // User quit signal

// ========================================
// START SERVER (CONTINUOUS MODE)
// ========================================

echo "----------------------------------------\n";
echo "✅ WebSocket server started successfully!\n";
echo "✅ Game engine running continuously at 5.5 FPS\n";
echo "📡 Listening on: ws://localhost:9001\n";
echo "🎮 Game mode: Continuous bouncing ball\n";
echo "👥 Users can: Connect, chat, watch game\n";
echo "🚫 Users cannot: Control game state\n";
echo "⚡ Admin controls: Command line only\n";
echo "🛑 Stop server: Press Ctrl+C\n";
echo "----------------------------------------\n";

// Start the ReactPHP event loop - this blocks until shutdown
// The loop handles:
// - WebSocket connections (GameServer)
// - Game engine updates (5.5 FPS timer)
// - Signal handling (graceful shutdown)
$loop->run();

// This line only executes after graceful shutdown
echo "WebSocket server stopped.\n";