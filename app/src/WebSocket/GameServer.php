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
use App\WebSocket\Handlers\PlayerInputHandler; // Ensure this is the corrected one
use App\Game\GameEngine; // This should be the GameEngine for hero movement


class GameServer implements MessageComponentInterface {
    protected SplObjectStorage $clients;
    protected LoopInterface $loop;
    protected AuthService $authService;
    protected ChatMessageRepository $chatMessageRepository;
    protected GameEngine $gameEngine;

    // --- Message Handlers ---
    /** @var array<string, MessageHandlerInterface> */
    private array $messageHandlers = [];

    public function __construct(AuthService $authService, ChatMessageRepository $chatMessageRepository, LoopInterface $loop, GameEngine $gameEngine) {
        $this->clients = new SplObjectStorage;
        $this->authService = $authService;
        $this->chatMessageRepository = $chatMessageRepository;
        $this->loop = $loop;
        $this->gameEngine = $gameEngine;

        $this->registerMessageHandlers();
    }

    private function registerMessageHandlers(): void {
        $this->messageHandlers['chat_message'] = new ChatMessageHandler();
        $this->messageHandlers['game_start_request'] = new GameStartHandler();
        $this->messageHandlers['game_stop_request'] = new GameStopHandler();
        $this->messageHandlers['player_input'] = new PlayerInputHandler();
    }

    // --- Getters for Handlers and GameEngine to access GameServer's properties/methods ---
    public function getClients(): SplObjectStorage {
        return $this->clients;
    }

    public function getLoop(): LoopInterface {
        return $this->loop;
    }

    public function getAuthService(): AuthService {
        return $this->authService;
    }

    public function getChatMessageRepository(): ChatMessageRepository {
        return $this->chatMessageRepository;
    }

    public function getGameEngine(): GameEngine {
        return $this->gameEngine;
    }

    /**
     * Broadcasts the current full game state (hero position, world dimensions) to all connected clients.
     * This method is called by GameEngine.
     * @param array $gameState The current full game state.
     */
    public function broadcastGameState(array $gameState): void {
        // Ensure the data structure matches what the client's game.js expects for hero movement
        $message = json_encode([
            'type' => 'game_state_update',
            'hero' => [
                'x' => round($gameState['hero']['x'], 2),
                'y' => round($gameState['hero']['y'], 2),
                'size' => $gameState['hero']['size'],
                'color' => $gameState['hero']['color']
            ],
            'worldWidth' => $gameState['worldWidth'],
            'worldHeight' => $gameState['worldHeight'],
            'canvasWidth' => $gameState['canvasWidth'],
            'canvasHeight' => $gameState['canvasHeight'],
            'gridSize' => $gameState['gridSize'],
            'serverFps' => $gameState['serverFps'],
            'effectiveTileMoveDurationMs' => $gameState['effectiveTileMoveDurationMs'],
            'autoMoveActive' => $gameState['autoMoveActive'],
            'autoMoveDirection' => $gameState['autoMoveDirection'],
            'lastProcessedInputSequence' => $gameState['lastProcessedInputSequence'],
            'allowedNextPositionsMap' => $gameState['allowedNextPositionsMap']
        ]);
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }

    // --- Ratchet MessageComponentInterface Methods ---

    public function onOpen(ConnectionInterface $conn): void {
        if (!$this->authService->authenticate($conn)) {
            return;
        }
        $this->clients->attach($conn);
        echo "New connection ({$conn->resourceId}) authenticated for user: {$conn->username} (ID: {$conn->userId})!\n";

        $conn->send(json_encode([
            'type' => 'chat_message',
            'user' => 'System',
            'message' => 'Welcome to the chatroom! Messages you send may be stored.'
        ]));

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
        $this->broadcastChatActivity("User {$conn->username} connected", $conn);

        // On new connection, send initial game state from GameEngine
        $this->broadcastGameState($this->gameEngine->getGameState());
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        $data = json_decode($msg, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Invalid JSON received from {$from->resourceId}: " . $msg);
            $from->send(json_encode(['type' => 'error', 'message' => 'Invalid JSON format.']));
            return;
        }

        $messageType = $data['type'] ?? null;

        if ($messageType === null || !isset($this->messageHandlers[$messageType])) {
            error_log("Unknown or missing message type '{$messageType}' from {$from->resourceId}: " . $msg);
            $from->send(json_encode(['type' => 'error', 'message' => 'Unknown message type.']));
            return;
        }

        $this->messageHandlers[$messageType]->handle($from, $data, $this);
    }

    public function onClose(ConnectionInterface $conn): void {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";

        $username = $conn->username ?? 'Guest';
        $this->broadcastChatActivity("User {$username} disconnected", $conn);

        if ($this->clients->count() === 0 && $this->gameEngine->getGameLoopTimer() !== null) {
            // $this->gameEngine->stopServerGameLoop();
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void {
        error_log("An error has occurred on connection {$conn->resourceId}: {$e->getMessage()}");
        $conn->close();
    }

    public function broadcastChatMessage(string $username, string $messageText, ?ConnectionInterface $from = null): void {
        $broadcastData = [
            'type' => 'chat_message',
            'user' => htmlspecialchars($username),
            'message' => htmlspecialchars($messageText)
        ];
        $jsonMessage = json_encode($broadcastData);

        foreach ($this->clients as $client) {
            if ($from === null || $client !== $from) {
                $client->send($jsonMessage);
            }
        }
    }

    public function broadcastChatActivity(string $activityMessage, ?ConnectionInterface $from = null): void {
        $broadcastData = [
            'type' => 'chat_message',
            'user' => 'System',
            'message' => $activityMessage
        ];
        $jsonMessage = json_encode($broadcastData);

        foreach ($this->clients as $client) {
            if ($from === null || $client !== $from) {
                $client->send($jsonMessage);
            }
        }
    }
}
