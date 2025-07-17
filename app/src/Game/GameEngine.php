<?php

namespace App\Game;

use React\EventLoop\LoopInterface;

class GameEngine
{
    private LoopInterface $loop;
    private $broadcastCallback;

    // --- Game State Properties ---
    private array $hero; // Our hero object
    private int $worldWidth = 2000; // Larger game world width
    private int $worldHeight = 1500; // Larger game world height

    private float $lastServerTickTimestamp; // Timestamp of the last server game loop tick (in microseconds)
    private mixed $serverGameLoopHandle = null; // Handle to the ReactPHP periodic timer for the server game loop

    // Game loop parameters
    private float $serverFPS = 10.0; // Server-side update rate (10 updates/sec) - Increased for smoother client interpolation

    // Fixed canvas dimensions (must match frontend viewport)
    private int $canvasWidth = 600;
    private int $canvasHeight = 400;

    // Hero properties
    private int $heroSize = 40; // Size of the hero (e.g., square side length)
    private int $gridSize = 50; // Size of each grid square in the world

    // Speed control properties
    private float $heroBaseTileMoveDurationMs = 1000.0; // Base time in milliseconds for one grid move at 1x speed (e.g., 1000ms = 1 tile/sec)
    private float $heroSpeedMultiplier = 1.0; // Multiplier for hero's movement speed (1.0 is normal, 1.05 is 5% faster, 0.95 is 5% slower)
    private float $heroCurrentTileMoveProgressMs = 0.0; // Accumulates elapsed time to determine when the hero should move to the next grid tile

    // Player input buffer: stores the *active state* of movement directions
    // Map connection ID to an associative array of active input actions (e.g., ['up' => true, 'left' => false])
    private array $playerInputs = [];

    // Auto-move state
    // Map connection ID to ['active' => bool, 'direction' => string|null]
    private array $autoMoveState = [];

    // Store the last processed input sequence number for each player
    private array $lastProcessedInputSequence = [];

    // Pre-computed map of allowed next positions for all grid cells
    private array $allowedNextPositionsMap = [];


    public function __construct(LoopInterface $loop, callable $broadcastCallback)
    {
        $this->loop = $loop;
        $this->broadcastCallback = $broadcastCallback;
        $this->initializeGameState(); // Initialize state, but not time for the loop yet
    }

    private function initializeGameState(): void
    {
        // Ensure hero starts snapped to the grid center
        // Hero's (x,y) represents the CENTER of its grid cell
        $initialX = round($this->worldWidth / 2 / $this->gridSize) * $this->gridSize + ($this->gridSize / 2);
        $initialY = round($this->worldHeight / 2 / $this->gridSize) * $this->gridSize + ($this->gridSize / 2);

        $this->hero = [
            'x' => $initialX,
            'y' => $initialY,
            'size' => $this->heroSize,
            'color' => '#3498db' // Blue color for hero
        ];

        $this->playerInputs = []; // Clear inputs on game state initialization
        $this->autoMoveState = ['player1' => ['active' => false, 'direction' => 'right']]; // Default auto-move off, but direction set
        $this->heroCurrentTileMoveProgressMs = 0.0; // Reset time accumulator
        $this->heroSpeedMultiplier = 1.0; // Reset speed to default
        $this->lastProcessedInputSequence = ['player1' => 0]; // Initialize sequence number for player1

        $this->allowedNextPositionsMap = $this->generateAllowedNextPositionsMap(); // Generate the map
    }

    /**
     * Generates a map of allowed next grid positions for all possible cells.
     * This is used for client-side prediction validation.
     * @return array
     */
    private function generateAllowedNextPositionsMap(): array
    {
        $map = [];
        $halfGridSize = $this->gridSize / 2;

        // Iterate through all possible grid cell centers in the world
        for ($y = $halfGridSize; $y < $this->worldHeight; $y += $this->gridSize) {
            for ($x = $halfGridSize; $x < $this->worldWidth; $x += $this->gridSize) {
                $currentKey = "{$x}_{$y}";
                $map[$currentKey] = [];

                // Define potential moves and clamp them
                $potentialMoves = [
                    'up' => ['x' => $x, 'y' => $y - $this->gridSize],
                    'down' => ['x' => $x, 'y' => $y + $this->gridSize],
                    'left' => ['x' => $x - $this->gridSize, 'y' => $y],
                    'right' => ['x' => $x + $this->gridSize, 'y' => $y],
                ];

                foreach ($potentialMoves as $direction => $pos) {
                    $clampedX = max($halfGridSize, min($pos['x'], $this->worldWidth - $halfGridSize));
                    $clampedY = max($halfGridSize, min($pos['y'], $this->worldHeight - $halfGridSize));

                    // Only add to map if the move is actually valid (i.e., not clamped to the same position)
                    if ($clampedX !== $x || $clampedY !== $y) {
                        $map[$currentKey][$direction] = ['x' => $clampedX, 'y' => $clampedY];
                    }
                }
            }
        }
        return $map;
    }


    public function startServerGameLoop(): void
    {
        if ($this->serverGameLoopHandle === null) {
            echo "Starting GameEngine loop at " . $this->serverFPS . " FPS...\n";
            $this->initializeGameState(); // Reset game state on start
            $this->lastServerTickTimestamp = microtime(true); // Set the time precisely when the loop starts

            // Calculate the loop interval
            $interval = 1 / $this->serverFPS;

            $this->serverGameLoopHandle = $this->loop->addPeriodicTimer($interval, function() {
                $this->processGameTick(); // Renamed method call
            });
        } else {
            echo "GameEngine loop already running.\n";
        }
    }

    public function stopServerGameLoop(): void
    {
        if ($this->serverGameLoopHandle !== null) {
            echo "Stopping GameEngine loop.\n";
            $this->loop->cancelTimer($this->serverGameLoopHandle);
            $this->serverGameLoopHandle = null;
            $this->initializeGameState(); // Reset game state on stop
            $this->broadcastCurrentState(); // Send one last update to reset clients
        } else {
            echo "GameEngine loop not running.\n";
        }
    }

    /**
     * Calculates the effective time duration (in milliseconds) it takes for the hero to move one tile,
     * considering the base duration and the current speed multiplier.
     * @return float
     */
    private function getCalculatedTileMoveDuration(): float
    {
        return $this->heroBaseTileMoveDurationMs / $this->heroSpeedMultiplier;
    }

    /**
     * Updates the last server tick timestamp and accumulates time for hero movement.
     * @return float The accumulated heroCurrentTileMoveProgressMs in milliseconds.
     */
    private function updateTimeAndAccumulateProgress(): float
    {
        $currentTime = microtime(true);
        $deltaTime = $currentTime - $this->lastServerTickTimestamp;
        $this->lastServerTickTimestamp = $currentTime;

        // Convert deltaTime to milliseconds for accumulation
        $this->heroCurrentTileMoveProgressMs += $deltaTime * 1000;
        return $this->heroCurrentTileMoveProgressMs; // Return the accumulated progress
    }

    /**
     * Processes hero movement based on accumulated time and effective tile move duration.
     * This method assumes the time condition has already been met by the caller.
     * It will apply one tile movement and reduce the accumulator.
     * @param float $effectiveTileMoveDurationMs The calculated time needed for one tile move.
     */
    private function processHeroMovement(float $effectiveTileMoveDurationMs): void
    {
        // Subtract the interval. If more than one interval passed, this handles it.
        $this->heroCurrentTileMoveProgressMs -= $effectiveTileMoveDurationMs;

        // --- Determine effective movement direction ---
        $effectiveDirection = null;
        $player1Inputs = $this->playerInputs['player1'] ?? [];

        // Check for manual input first (WASD)
        // Manual input takes precedence over auto-move
        if (isset($player1Inputs['up']) && $player1Inputs['up']) {
            $effectiveDirection = 'up';
        } elseif (isset($player1Inputs['down']) && $player1Inputs['down']) {
            $effectiveDirection = 'down';
        } elseif (isset($player1Inputs['left']) && $player1Inputs['left']) {
            $effectiveDirection = 'left';
        } elseif (isset($player1Inputs['right']) && $player1Inputs['right']) {
            $effectiveDirection = 'right';
        }

        // If no manual input, and auto-move is active, use auto-move direction
        if ($effectiveDirection === null && ($this->autoMoveState['player1']['active'] ?? false)) {
            $effectiveDirection = $this->autoMoveState['player1']['direction'];
        }

        // Apply movement if a direction is determined
        if ($effectiveDirection !== null) {
            $this->applyMoveCommandToHero($this->hero, $effectiveDirection);
        }
        // After applying a move, we consider the last received input sequence for this player as processed.
        // This assumes that any move processed by the server corresponds to the latest input it received
        // for that player's movement command.
        if (isset($player1Inputs['last_sequence'])) {
            $this->lastProcessedInputSequence['player1'] = max($this->lastProcessedInputSequence['player1'], $player1Inputs['last_sequence']);
        }
    }

    /**
     * Updates the game state (hero position) and broadcasts it.
     */
    private function processGameTick(): void // Renamed method
    {
        // Get the updated progress directly from the function call
        $currentProgress = $this->updateTimeAndAccumulateProgress();

        // Calculate the effective time needed for one tile move (call once)
        $effectiveTileMoveDurationMs = $this->getCalculatedTileMoveDuration();

        // Only process movement if enough time has passed
        if ($currentProgress >= $effectiveTileMoveDurationMs) {
            // Process hero movement based on accumulated time
            $this->processHeroMovement($effectiveTileMoveDurationMs);
        }

        // Broadcast the new game state to all connected clients (regardless of whether hero moved this tick)
        $this->broadcastCurrentState($effectiveTileMoveDurationMs); // Pass the calculated value to broadcast
    }

    /**
     * Applies a single move command to the hero, snapping to the grid.
     * @param array $hero The hero array (passed by reference).
     * @param string $command The move command ('up', 'down', 'left', 'right').
     */
    private function applyMoveCommandToHero(array &$hero, string $command): void
    {
        $newX = $hero['x'];
        $newY = $hero['y'];

        // Movement is always by one gridSize
        $moveAmount = $this->gridSize;

        switch ($command) {
            case 'up':
                $newY -= $moveAmount;
                break;
            case 'down':
                $newY += $moveAmount;
                break;
            case 'left':
                $newX -= $moveAmount;
                break;
            case 'right':
                $newX += $moveAmount;
                break;
        }

        // Clamp hero position to world boundaries, ensuring it stays grid-aligned
        $halfGridSize = $this->gridSize / 2;

        $newX = max($halfGridSize, min($newX, $this->worldWidth - $halfGridSize));
        $newY = max($halfGridSize, min($newY, $this->worldHeight - $halfGridSize));

        $hero['x'] = $newX;
        $hero['y'] = $newY;
    }


    /**
     * Sends the current full game state to all connected clients via the callback.
     * @param float|null $effectiveTileMoveDurationMs The pre-calculated effective tile move duration.
     * If not provided, it will be calculated internally (e.g., for initial state broadcast).
     */
    private function broadcastCurrentState(?float $effectiveTileMoveDurationMs = null): void
    {
        // Calculate if not already provided (e.g., for initial getGameState() call)
        if ($effectiveTileMoveDurationMs === null) {
            $effectiveTileMoveDurationMs = $this->getCalculatedTileMoveDuration();
        }

        call_user_func($this->broadcastCallback, [
            'hero' => $this->hero,
            'worldWidth' => $this->worldWidth,
            'worldHeight' => $this->worldHeight,
            'canvasWidth' => $this->canvasWidth,
            'canvasHeight' => $this->canvasHeight,
            'gridSize' => $this->gridSize,
            'serverFps' => $this->serverFPS,
            'effectiveTileMoveDurationMs' => $effectiveTileMoveDurationMs, // Send calculated value
            'autoMoveActive' => $this->autoMoveState['player1']['active'] ?? false,
            'autoMoveDirection' => $this->autoMoveState['player1']['direction'] ?? null,
            'lastProcessedInputSequence' => $this->lastProcessedInputSequence['player1'] ?? 0,
            'allowedNextPositionsMap' => $this->allowedNextPositionsMap
        ]);
    }

    /**
     * Provides access to the current full game state.
     * @return array
     */
    public function getGameState(): array
    {
        return [
            'hero' => $this->hero,
            'worldWidth' => $this->worldWidth,
            'worldHeight' => $this->worldHeight,
            'canvasWidth' => $this->canvasWidth,
            'canvasHeight' => $this->canvasHeight,
            'gridSize' => $this->gridSize,
            'serverFps' => $this->serverFPS,
            'effectiveTileMoveDurationMs' => $this->getCalculatedTileMoveDuration(), // Send calculated value
            'autoMoveActive' => $this->autoMoveState['player1']['active'] ?? false,
            'autoMoveDirection' => $this->autoMoveState['player1']['direction'] ?? null,
            'lastProcessedInputSequence' => $this->lastProcessedInputSequence['player1'] ?? 0,
            'allowedNextPositionsMap' => $this->allowedNextPositionsMap
        ];
    }

    /**
     * Returns the current game loop timer.
     * @return mixed|null
     */
    public function getGameLoopTimer(): mixed
    {
        return $this->serverGameLoopHandle;
    }

    /**
     * Sets the input state for a specific player (currently 'player1' for the hero).
     * This method will be called by the PlayerInputHandler.
     * @param string $playerId 'player1' (or a connection ID in multiplayer)
     * @param string $action 'up', 'down', 'left', 'right'
     * @param bool $isActive true if key pressed, false if key released
     * @param int|null $sequenceNumber The client's input sequence number for this action.
     */
    public function setPlayerInput(string $playerId, string $action, bool $isActive, ?int $sequenceNumber = null): void
    {
        if (!isset($this->playerInputs[$playerId])) {
            // Initialize with default state, including a placeholder for the last sequence
            $this->playerInputs[$playerId] = [
                'up' => false,
                'down' => false,
                'left' => false,
                'right' => false,
                'last_sequence' => 0 // Simplified: store only one last sequence for any active input
            ];
        }
        $this->playerInputs[$playerId][$action] = $isActive;

        // If an input is active (key down) and has a sequence number, update the last_sequence
        if ($isActive && $sequenceNumber !== null) {
            $this->playerInputs[$playerId]['last_sequence'] = max($this->playerInputs[$playerId]['last_sequence'], $sequenceNumber);
        }

        // If a manual direction key is pressed AND auto-move is active,
        // update the auto-move direction to the current manual direction.
        if ($isActive && ($this->autoMoveState[$playerId]['active'] ?? false)) {
            if (in_array($action, ['up', 'down', 'left', 'right'])) {
                $this->autoMoveState[$playerId]['direction'] = $action;
            }
        }
    }

    /**
     * Toggles the auto-move state for a player.
     * @param string $playerId 'player1'
     * @param string|null $direction The direction to set for auto-move if activating, or null to keep current.
     */
    public function toggleAutoMove(string $playerId, ?string $direction = null): void
    {
        if (!isset($this->autoMoveState[$playerId])) {
            $this->autoMoveState[$playerId] = ['active' => false, 'direction' => 'right']; // Default direction
        }

        // Toggle active state
        $this->autoMoveState[$playerId]['active'] = !$this->autoMoveState[$playerId]['active'];

        // If a specific direction is provided when activating, set it
        // OR if auto-move is now active and no direction was provided, try to use current manual input
        if ($this->autoMoveState[$playerId]['active']) {
            if ($direction !== null && in_array($direction, ['up', 'down', 'left', 'right'])) {
                $this->autoMoveState[$playerId]['direction'] = $direction;
            } elseif ($direction === null) {
                // If toggling ON without a specific direction, try to infer from active manual input
                $playerInputs = $this->playerInputs[$playerId] ?? [];
                if (isset($playerInputs['up']) && $playerInputs['up']) {
                    $this->autoMoveState[$playerId]['direction'] = 'up';
                } elseif (isset($playerInputs['down']) && $playerInputs['down']) {
                    $this->autoMoveState[$playerId]['direction'] = 'down';
                } elseif (isset($playerInputs['left']) && $playerInputs['left']) {
                    $this->autoMoveState[$playerId]['direction'] = 'left';
                } elseif (isset($playerInputs['right']) && $playerInputs['right']) {
                    $this->autoMoveState[$playerId]['direction'] = 'right';
                }
                // If no manual input is active, it will retain its previous direction or default to 'right'
            }
        }

        echo "Player {$playerId} auto-move toggled to " . ($this->autoMoveState[$playerId]['active'] ? 'ON' : 'OFF') . "\n";
        if ($this->autoMoveState[$playerId]['active']) {
            echo "Auto-move direction: {$this->autoMoveState[$playerId]['direction']}\n";
        }
    }

    /**
     * Sets the hero's movement speed multiplier.
     * @param string $playerId 'player1'
     * @param float $multiplier The new speed multiplier (e.g., 1.0, 1.05, 0.95).
     */
    public function setHeroSpeedMultiplier(string $playerId, float $multiplier): void
    {
        // For now, only affecting 'player1' (the hero)
        $this->heroSpeedMultiplier = max(0.1, min($multiplier, 5.0)); // Clamp between 0.1x and 5x speed
        echo "Hero speed multiplier set to: {$this->heroSpeedMultiplier}\n";
    }
}
