<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use App\Service\AuthService;
use App\Repository\ChatMessageRepository;
use SplObjectStorage;
use App\WebSocket\Handlers\MessageHandlerInterface;
use App\WebSocket\Handlers\ChatMessageHandler;
use App\WebSocket\Handlers\GameStartHandler;
use App\WebSocket\Handlers\GameStopHandler;
use App\WebSocket\Handlers\GamePauseHandler;
use App\WebSocket\Handlers\GameResumeHandler;
use App\Game\GameEngine;

/**
 * GameServer - WebSocket server handling both chat and game functionality
 * 
 * This class implements the Ratchet MessageComponentInterface to handle
 * WebSocket connections, chat messages, and real-time game state broadcasting.
 */
class GameServer implements MessageComponentInterface 
{
    // ========================================
    // DEPENDENCIES & CONFIGURATION
    // ========================================
    
    protected SplObjectStorage $clients; // Connected WebSocket clients
    protected LoopInterface $loop; // ReactPHP event loop
    protected AuthService $authService; // User authentication service
    protected ChatMessageRepository $chatMessageRepository; // Chat data persistence
    protected GameEngine $gameEngine; // Game logic and state management

    // Message routing system
    /** @var array<string, MessageHandlerInterface> */
    private array $messageHandlers = [];

    // ========================================
    // CONSTRUCTOR & INITIALIZATION
    // ========================================

    /**
     * Initialize GameServer with all required dependencies
     * 
     * @param AuthService $authService User authentication service
     * @param ChatMessageRepository $chatMessageRepository Chat message persistence
     * @param LoopInterface $loop ReactPHP event loop for timers
     * @param GameEngine $gameEngine Game logic engine instance
     */
    public function __construct(
        AuthService $authService, 
        ChatMessageRepository $chatMessageRepository, 
        LoopInterface $loop, 
        GameEngine $gameEngine
    ) {
        $this->clients = new SplObjectStorage;
        $this->authService = $authService;
        $this->chatMessageRepository = $chatMessageRepository;
        $this->loop = $loop;
        $this->gameEngine = $gameEngine;

        $this->registerMessageHandlers();
    }

    /**
     * Register all message type handlers for routing incoming WebSocket messages
     * Each message type gets its own dedicated handler class
     */
    private function registerMessageHandlers(): void 
    {
        $this->messageHandlers['chat_message'] = new ChatMessageHandler();
        $this->messageHandlers['game_start_request'] = new GameStartHandler();
        $this->messageHandlers['game_stop_request'] = new GameStopHandler();
        $this->messageHandlers['game_pause_request'] = new GamePauseHandler();
        $this->messageHandlers['game_resume_request'] = new GameResumeHandler();
    }

    // ========================================
    // DEPENDENCY ACCESS METHODS
    // ========================================

    /**
     * Get connected clients collection (for message handlers)
     */
    public function getClients(): SplObjectStorage 
    {
        return $this->clients;
    }

    /**
     * Get ReactPHP event loop (for message handlers)
     */
    public function getLoop(): LoopInterface 
    {
        return $this->loop;
    }

    /**
     * Get authentication service (for message handlers)
     */
    public function getAuthService(): AuthService 
    {
        return $this->authService;
    }

    /**
     * Get chat message repository (for message handlers)
     */
    public function getChatMessageRepository(): ChatMessageRepository 
    {
        return $this->chatMessageRepository;
    }

    /**
     * Get game engine instance (for message handlers)
     */
    public function getGameEngine(): GameEngine 
    {
        return $this->gameEngine;
    }

    // ========================================
    // GAME STATE BROADCASTING
    // ========================================

    /**
     * Broadcast game state to all connected clients
     * 
     * This method serves as the callback for GameEngine's state updates.
     * Handles both full game state and legacy ball-only state formats.
     * 
     * @param array $gameState Full game state OR just ball state from GameEngine
     */
    public function broadcastGameState(array $gameState): void 
    {
        try {
            // Debug output for monitoring game state updates
            // echo "Broadcasting game state: " . json_encode($gameState, JSON_PRETTY_PRINT) . "\n";

            // ---- HANDLE DIFFERENT STATE FORMATS ----
            // Support both full game state and legacy ball-only formats
            if (isset($gameState['ball'])) {
                // Full game state format (from GameEngine's broadcastCurrentState)
                $ballData = $gameState['ball'];
                $isRunning = $gameState['isRunning'] ?? false;
                $isPaused = $gameState['isPaused'] ?? false;
            } else {
                // Ball-only format (legacy compatibility)
                $ballData = $gameState;
                $isRunning = false;
                $isPaused = false;
            }

            // ---- VALIDATE BALL DATA STRUCTURE ----
            if (!isset($ballData['x'], $ballData['y'], $ballData['radius'], $ballData['color'])) {
                error_log("Invalid ball data structure: " . json_encode($ballData));
                return;
            }

            // ---- CREATE STANDARDIZED MESSAGE FORMAT ----
            $message = json_encode([
                'type' => 'game_state_update',
                'ball' => [
                    'x' => round((float)$ballData['x'], 2),
                    'y' => round((float)$ballData['y'], 2),
                    'radius' => (int)$ballData['radius'],
                    'color' => (string)$ballData['color']
                ],
                'gameStatus' => [
                    'isRunning' => $isRunning,
                    'isPaused' => $isPaused
                ]
            ]);

            echo "Sending message: " . $message . "\n";

            // ---- BROADCAST TO ALL CLIENTS ----
            foreach ($this->clients as $client) {
                $client->send($message);
            }

        } catch (\Exception $e) {
            error_log("Error broadcasting game state: " . $e->getMessage());
        }
    }

    /**
     * Send current game state to a specific client (used for new connections)
     * 
     * @param ConnectionInterface $client The client to send state to
     */
    private function sendGameStateToClient(ConnectionInterface $client): void
    {
        try {
            // Get complete current game state from GameEngine
            $fullGameState = $this->gameEngine->getGameState();
            
            // Create standardized message format
            $message = json_encode([
                'type' => 'game_state_update', 
                'ball' => [
                    'x' => round((float)$fullGameState['ball']['x'], 2),
                    'y' => round((float)$fullGameState['ball']['y'], 2),
                    'radius' => (int)$fullGameState['ball']['radius'],
                    'color' => (string)$fullGameState['ball']['color']
                ],
                'gameStatus' => [
                    'isRunning' => $fullGameState['isRunning'],
                    'isPaused' => $fullGameState['isPaused']
                ]
            ]);

            $client->send($message);
        } catch (\Exception $e) {
            error_log("Error sending game state to client {$client->resourceId}: " . $e->getMessage());
        }
    }

    // ========================================
    // CHAT MESSAGE BROADCASTING
    // ========================================

    /**
     * Broadcast chat message to all connected clients
     * 
     * @param string $username The user who sent the message
     * @param string $messageText The message content
     * @param ConnectionInterface|null $from Optional sender to exclude from broadcast
     */
    public function broadcastChatMessage(string $username, string $messageText, ?ConnectionInterface $from = null): void 
    {
        $broadcastData = [
            'type' => 'chat_message',
            'user' => htmlspecialchars($username),
            'message' => htmlspecialchars($messageText)
        ];
        $jsonMessage = json_encode($broadcastData);

        // Send to all clients except the sender (if specified)
        foreach ($this->clients as $client) {
            if ($from === null || $client !== $from) {
                $client->send($jsonMessage);
            }
        }
    }

    /**
     * Broadcast system activity messages (user joins/leaves, etc.)
     * 
     * @param string $activityMessage The system message to broadcast
     * @param ConnectionInterface|null $from Optional connection to exclude from broadcast
     */
    public function broadcastChatActivity(string $activityMessage, ?ConnectionInterface $from = null): void 
    {
        $broadcastData = [
            'type' => 'chat_message',
            'user' => 'System',
            'message' => $activityMessage
        ];
        $jsonMessage = json_encode($broadcastData);

        // Send to all clients except the specified connection (if any)
        foreach ($this->clients as $client) {
            if ($from === null || $client !== $from) {
                $client->send($jsonMessage);
            }
        }
    }

    // ========================================
    // WEBSOCKET CONNECTION LIFECYCLE
    // ========================================

    /**
     * Handle new WebSocket connection
     * Authenticates user, sends welcome messages, loads chat history, and syncs game state
     */
    public function onOpen(ConnectionInterface $conn): void 
    {
        // ---- AUTHENTICATION ----
        if (!$this->authService->authenticate($conn)) {
            return; // Authentication failed, connection will be closed
        }
        
        // Add authenticated client to our collection
        $this->clients->attach($conn);
        echo "New connection ({$conn->resourceId}) authenticated for user: {$conn->username} (ID: {$conn->userId})!\n";

        // ---- SEND WELCOME MESSAGE ----
        $conn->send(json_encode([
            'type' => 'chat_message',
            'user' => 'System',
            'message' => 'Welcome to the chatroom! Messages you send may be stored.'
        ]));

        // ---- LOAD AND SEND RECENT CHAT HISTORY ----
        try {
            $recentMessages = $this->chatMessageRepository->getRecentMessages(5);
            if ($recentMessages) {
                foreach ($recentMessages as $message) {
                    $conn->send(json_encode([
                        'type' => 'chat_message',
                        'user' => htmlspecialchars($message['username']),
                        'message' => htmlspecialchars($message['message_text'])
                    ]));
                }
            } else {
                $conn->send(json_encode([
                    'type' => 'chat_message',
                    'user' => 'System',
                    'message' => 'No recent messages found.'
                ]));
            }
        } catch (\PDOException $e) {
            error_log("Error fetching recent messages for connection {$conn->resourceId}: " . $e->getMessage());
            $conn->send(json_encode([
                'type' => 'chat_message',
                'user' => 'System',
                'message' => 'Could not retrieve recent messages.'
            ]));
        }

        // ---- NOTIFY OTHER USERS ----
        $this->broadcastChatActivity("User {$conn->username} connected", $conn);

        // ---- SYNC GAME STATE ----
        // Send current game state to newly connected client
        $this->sendGameStateToClient($conn);
    }

    /**
     * Handle incoming WebSocket messages
     * Routes messages to appropriate handlers based on message type
     */
    public function onMessage(ConnectionInterface $from, $msg): void 
    {
        // ---- PARSE JSON MESSAGE ----
        $data = json_decode($msg, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Invalid JSON received from {$from->resourceId}: " . $msg);
            $from->send(json_encode(['type' => 'error', 'message' => 'Invalid JSON format.']));
            return;
        }

        // ---- VALIDATE MESSAGE TYPE ----
        $messageType = $data['type'] ?? null;

        if ($messageType === null || !isset($this->messageHandlers[$messageType])) {
            error_log("Unknown or missing message type '{$messageType}' from {$from->resourceId}: " . $msg);
            $from->send(json_encode(['type' => 'error', 'message' => 'Unknown message type.']));
            return;
        }

        // ---- DELEGATE TO MESSAGE HANDLER ----
        // Route the message to its specific handler class
        $this->messageHandlers[$messageType]->handle($from, $data, $this);
    }

    /**
     * Handle WebSocket connection closure
     * Removes client from collection and notifies other users
     */
    public function onClose(ConnectionInterface $conn): void 
    {
        // Remove client from our collection
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";

        // ---- NOTIFY OTHER USERS ----
        $username = $conn->username ?? 'Guest';
        $this->broadcastChatActivity("User {$username} disconnected", $conn);

        // ---- OPTIONAL: AUTO-STOP GAME ----
        // Uncomment to automatically stop game when all clients disconnect
        if ($this->clients->count() === 0 && $this->gameEngine->getGameLoopTimer() !== null) {
            // $this->gameEngine->stopServerGameLoop();
        }
    }

    /**
     * Handle WebSocket connection errors
     * Logs error and closes the problematic connection
     */
    public function onError(ConnectionInterface $conn, \Exception $e): void 
    {
        error_log("An error has occurred on connection {$conn->resourceId}: {$e->getMessage()}");
        $conn->close();
    }
}