<?php

namespace App\Game;

use React\EventLoop\LoopInterface;

/**
 * GameEngine - Handles server-side game logic and state management
 * 
 * This class manages a bouncing ball simulation with real-time updates
 * sent to connected clients via WebSocket broadcasting.
 */
class GameEngine
{
    // ========================================
    // DEPENDENCIES & CONFIGURATION
    // ========================================
    
    private LoopInterface $loop; // ReactPHP event loop for timers
    private $broadcastCallback; // Callable to send game state updates to clients
    
    // Game world constants
    private const CANVAS_WIDTH = 600;
    private const CANVAS_HEIGHT = 400;
    private const SERVER_FPS = 1.5; // Server-side update rate for game logic
    private const MAX_DELTA_TIME = 0.1; // Cap delta time to prevent large jumps (100ms)

    // ========================================
    // GAME STATE PROPERTIES
    // ========================================
    
    private array $ball; // Ball position, velocity, and properties
    private float $lastGameUpdateTime; // Timestamp for delta time calculation
    private ?object $gameLoopTimer = null; // ReactPHP timer object for game updates

    // Game lifecycle flags
    private bool $isRunning = false;
    private bool $isPaused = false;

    // ========================================
    // CONSTRUCTOR & INITIALIZATION
    // ========================================

    /**
     * Initialize the GameEngine with ReactPHP loop and broadcast callback
     * 
     * @param LoopInterface $loop The ReactPHP event loop for managing timers
     * @param callable $broadcastCallback Function to broadcast game state updates to clients
     */
    public function __construct(LoopInterface $loop, callable $broadcastCallback)
    {
        $this->loop = $loop;
        $this->broadcastCallback = $broadcastCallback;
        $this->initializeGameState();
    }

    /**
     * Reset ball position, velocity, and game state to initial values
     * Called on server start, game reset, and full stop
     */
    private function initializeGameState(): void
    {
        // Initialize ball with starting position and velocity
        $this->ball = [
            'x' => 100.0,
            'y' => 100.0,
            'radius' => 80,
            'dx' => 50.0, // pixels per second horizontal velocity
            'dy' => 70.0, // pixels per second vertical velocity
            'color' => '#3498db'
        ];
        
        $this->lastGameUpdateTime = microtime(true);
        
        // Note: Don't set isRunning/isPaused here to avoid race conditions
        // Let the calling methods handle these flags appropriately
    }

    // ========================================
    // GAME LOOP CONTROL METHODS
    // ========================================

    /**
     * Start the server-side game loop
     * Initializes game state and begins periodic updates at configured FPS
     */
    public function startServerGameLoop(): void
    {
        if ($this->isRunning) {
            echo "GameEngine loop is already running.\n";
            return;
        }

        // Clean up any existing timer to prevent conflicts
        $this->cleanupTimer();

        echo "Starting GameEngine loop at " . self::SERVER_FPS . " FPS...\n";
        
        // Reset game state for fresh start
        $this->initializeGameState();
        $this->lastGameUpdateTime = microtime(true);

        // Set state flags AFTER initialization to avoid race conditions
        $this->isRunning = true;
        $this->isPaused = false;

        // Create periodic timer for game updates
        $this->gameLoopTimer = $this->loop->addPeriodicTimer(
            1 / self::SERVER_FPS,
            fn() => $this->serverGameUpdateAndBroadcast()
        );

        // Send initial state to all clients
        $this->broadcastCurrentState();
    }

    /**
     * Stop the server-side game loop completely
     * Resets game state and notifies clients
     */
    public function stopServerGameLoop(): void
    {
        if (!$this->isRunning && !$this->isPaused) {
            echo "GameEngine loop is not running or paused, nothing to stop.\n";
            return;
        }

        echo "Stopping GameEngine loop.\n";
        
        // Clean up timer
        $this->cleanupTimer();

        // Reset game state and flags
        $this->initializeGameState();
        $this->isRunning = false;
        $this->isPaused = false;

        // Send reset state to clients
        $this->broadcastCurrentState();
    }

    /**
     * Pause the server-side game loop
     * Maintains current game state but stops updates
     */
    public function pauseServerGameLoop(): void
    {
        if (!$this->isRunning) {
            echo "Game is not running, cannot pause.\n";
            return;
        }
        if ($this->isPaused) {
            echo "Game is already paused.\n";
            return;
        }

        echo "Pausing GameEngine loop.\n";
        
        // Stop timer but maintain game state
        $this->cleanupTimer();

        // Update state flags
        $this->isRunning = false;
        $this->isPaused = true;

        // Notify clients of paused state
        $this->broadcastCurrentState();
    }

    /**
     * Resume the server-side game loop from paused state
     * Continues from current game state without reset
     */
    public function resumeServerGameLoop(): void
    {
        if (!$this->isPaused) {
            echo "Game is not paused, cannot resume.\n";
            return;
        }
        if ($this->isRunning) {
            echo "Game is already running.\n";
            return;
        }

        echo "Resuming GameEngine loop.\n";
        
        // Reset timing for smooth continuation
        $this->lastGameUpdateTime = microtime(true);

        // Update state flags
        $this->isRunning = true;
        $this->isPaused = false;

        // Restart periodic timer
        $this->gameLoopTimer = $this->loop->addPeriodicTimer(
            1 / self::SERVER_FPS,
            fn() => $this->serverGameUpdateAndBroadcast()
        );

        // Notify clients that game has resumed
        $this->broadcastCurrentState();
    }

    // ========================================
    // GAME LOGIC & UPDATE METHODS
    // ========================================

    /**
     * Core game update method - handles physics and broadcasts state
     * Called periodically by the ReactPHP timer
     */
    private function serverGameUpdateAndBroadcast(): void
    {
        // Calculate time elapsed since last update
        $currentTime = microtime(true);
        $deltaTime = $currentTime - $this->lastGameUpdateTime;
        
        // Cap delta time to prevent large jumps during lag
        $deltaTime = min($deltaTime, self::MAX_DELTA_TIME);
        
        $this->lastGameUpdateTime = $currentTime;

        // ---- PHYSICS UPDATE ----
        // Update ball position based on velocity and elapsed time
        $this->ball['x'] += $this->ball['dx'] * $deltaTime;
        $this->ball['y'] += $this->ball['dy'] * $deltaTime;

        // ---- COLLISION DETECTION ----
        // Handle wall collisions with proper boundary clamping
        
        // Right wall collision
        if ($this->ball['x'] + $this->ball['radius'] > self::CANVAS_WIDTH) {
            $this->ball['x'] = self::CANVAS_WIDTH - $this->ball['radius'];
            $this->ball['dx'] *= -1; // Reverse horizontal direction
        }
        
        // Left wall collision
        if ($this->ball['x'] - $this->ball['radius'] < 0) {
            $this->ball['x'] = $this->ball['radius'];
            $this->ball['dx'] *= -1; // Reverse horizontal direction
        }
        
        // Bottom wall collision
        if ($this->ball['y'] + $this->ball['radius'] > self::CANVAS_HEIGHT) {
            $this->ball['y'] = self::CANVAS_HEIGHT - $this->ball['radius'];
            $this->ball['dy'] *= -1; // Reverse vertical direction
        }
        
        // Top wall collision
        if ($this->ball['y'] - $this->ball['radius'] < 0) {
            $this->ball['y'] = $this->ball['radius'];
            $this->ball['dy'] *= -1; // Reverse vertical direction
        }

        // ---- BROADCAST UPDATE ----
        // Send consistent state format to all clients
        $this->broadcastCurrentState();
    }

    // ========================================
    // BROADCASTING & STATE ACCESS
    // ========================================

    /**
     * Send current complete game state to all connected clients
     * Uses consistent message format for all broadcasts
     */
    private function broadcastCurrentState(): void
    {
        $gameState = [
            'ball' => $this->ball,
            'isRunning' => $this->isRunning,
            'isPaused' => $this->isPaused
        ];

        ($this->broadcastCallback)($gameState);
    }

    /**
     * Get current ball state (public accessor)
     * 
     * @return array Current ball position, velocity, and properties
     */
    public function getBallState(): array
    {
        return $this->ball;
    }

    /**
     * Get complete current game state (public accessor)
     * 
     * @return array Full game state including ball data and lifecycle flags
     */
    public function getGameState(): array
    {
        return [
            'ball' => $this->ball,
            'isRunning' => $this->isRunning,
            'isPaused' => $this->isPaused
        ];
    }

    /**
     * Get current game loop timer object (for debugging/monitoring)
     * 
     * @return object|null ReactPHP timer object or null if not running
     */
    public function getGameLoopTimer(): ?object
    {
        return $this->gameLoopTimer;
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    /**
     * Clean up the current timer if it exists
     * Centralized timer cleanup to prevent memory leaks
     */
    private function cleanupTimer(): void
    {
        if ($this->gameLoopTimer !== null) {
            $this->loop->cancelTimer($this->gameLoopTimer);
            $this->gameLoopTimer = null;
        }
    }
}