<?php

namespace App\Game;

use React\EventLoop\LoopInterface;

class GameEngine
{
    private LoopInterface $loop; // ReactPHP event loop
    private $broadcastCallback; // Callable to send game state updates to clients

    // --- Game State Properties ---
    private array $ball;
    private float $lastGameUpdateTime;
    private mixed $gameLoopTimer = null; // Stores the ReactPHP timer object for game updates

    // Game loop parameters
    private float $serverFPS = 0.7; // Server-side update rate for game logic

    // Fixed canvas dimensions (must match frontend for collision logic)
    private int $canvasWidth = 600;
    private int $canvasHeight = 400;

    /**
     * @param LoopInterface $loop The ReactPHP event loop.
     * @param callable $broadcastCallback A callback function to broadcast game state updates.
     * This will typically be a method from GameServer.
     */
    public function __construct(LoopInterface $loop, callable $broadcastCallback)
    {
        $this->loop = $loop;
        $this->broadcastCallback = $broadcastCallback;
        $this->initializeGameState(); // Setup initial ball position/speed for the game
    }

    /**
     * Initializes the ball's starting position and velocity.
     * This is called on server start and when the game is reset.
     */
    private function initializeGameState(): void
    {
        $this->ball = [
            'x' => 100.0,
            'y' => 100.0,
            'radius' => 80,
            'dx' => 50.0, // pixels per second
            'dy' => 70.0, // pixels per second
            'color' => '#3498db'
        ];
        $this->lastGameUpdateTime = microtime(true);
    }

    /**
     * Starts the server-side game loop if it's not already running.
     */
    public function startServerGameLoop(): void
    {
        if ($this->gameLoopTimer === null) {
            echo "Starting GameEngine loop at " . $this->serverFPS . " FPS...\n";
            $this->initializeGameState(); // Reset ball to initial state when game starts
            $this->lastGameUpdateTime = microtime(true); // Reset time for accurate delta calculation

            // Add a periodic timer to the ReactPHP event loop
            // This function will be called every (1 / serverFPS) seconds
            $this->gameLoopTimer = $this->loop->addPeriodicTimer(1 / $this->serverFPS, function() {
                $this->serverGameUpdateAndBroadcast();
            });
        } else {
            echo "GameEngine loop already running.\n";
        }
    }

    /**
     * Stops the server-side game loop if it's running.
     */
    public function stopServerGameLoop(): void
    {
        if ($this->gameLoopTimer !== null) {
            echo "Stopping GameEngine loop.\n";
            $this->loop->cancelTimer($this->gameLoopTimer); // Cancel the periodic timer
            $this->gameLoopTimer = null; // Clear the timer reference
            $this->initializeGameState(); // Reset ball position when stopped
            // Immediately broadcast the reset state so clients see the change
            call_user_func($this->broadcastCallback, $this->ball);
        } else {
            echo "GameEngine loop not running.\n";
        }
    }

    /**
     * Updates the game state (ball position, collisions) and broadcasts it.
     */
    private function serverGameUpdateAndBroadcast(): void
    {
        $currentTime = microtime(true);
        $deltaTime = $currentTime - $this->lastGameUpdateTime; // Time elapsed in seconds
        $this->lastGameUpdateTime = $currentTime;

        // --- Update ball position (Game Logic) ---
        $this->ball['x'] += $this->ball['dx'] * $deltaTime;
        $this->ball['y'] += $this->ball['dy'] * $deltaTime;

        // --- Collision detection and bounce (Game Logic) ---
        // Right wall
        if ($this->ball['x'] + $this->ball['radius'] > $this->canvasWidth) {
            $this->ball['x'] = $this->canvasWidth - $this->ball['radius']; // Clamp to boundary
            $this->ball['dx'] *= -1; // Reverse direction
        }
        // Left wall
        if ($this->ball['x'] - $this->ball['radius'] < 0) {
            $this->ball['x'] = $this->ball['radius']; // Clamp to boundary
            $this->ball['dx'] *= -1; // Reverse direction
        }
        // Bottom wall
        if ($this->ball['y'] + $this->ball['radius'] > $this->canvasHeight) {
            $this->ball['y'] = $this->canvasHeight - $this->ball['radius']; // Clamp to boundary
            $this->ball['dy'] *= -1; // Reverse direction
        }
        // Top wall
        if ($this->ball['y'] - $this->ball['radius'] < 0) {
            $this->ball['y'] = $this->ball['radius']; // Clamp to boundary
            $this->ball['dy'] *= -1; // Reverse direction
        }

        // Call the provided callback to broadcast the new game state
        call_user_func($this->broadcastCallback, $this->ball);
    }

    /**
     * Provides access to the current ball state.
     * @return array
     */
    public function getBallState(): array
    {
        return $this->ball;
    }

    /**
     * Returns the current game loop timer.
     * Useful for checking if the loop is active from outside the class.
     * @return mixed|null
     */
    public function getGameLoopTimer(): mixed
    {
        return $this->gameLoopTimer;
    }
}
