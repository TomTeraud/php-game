; // Leading semicolon for safety against ASI

import * as websocketClient from '../websocketClient.js';
import * as gameRenderer from './gameRenderer.js';
import * as inputHandler from './inputHandler.js';
import * as gameUI from './gameUI.js';
import {
  initializeHeroState,
  updateInterpolationState, // Renamed from reconcileState
  getRenderedHeroState,
  updateWorldParameters,
  // updateAllowedNextPositionsMap is removed as it's no longer exported
  resetEngine as resetHeroMovementEngine // Rename to avoid conflict with game.js's own reset
} from './heroMovementEngine.js';

const MyGame = {}; // Global object for public game methods

(() => {
  // --- Game State Variables (Client-side representation) ---
  let canvas = null;
  let heroCoordinatesDisplay = null; // NEW: Reference to the coordinate display element

  // Game state properties that will be updated by server messages
  // hero state is now managed internally by heroMovementEngine
  let worldDimensions = {
    width: 0,
    height: 0
  };
  let gridSize = 50;
  let serverFps = 10;
  let effectiveTileMoveDurationMs = 1000; // Will be updated by server

  // Client-side input sequence number (still used for server's tracking)
  let clientInputSequence = 0;

  // --- Client-Side Rendering Loop ---
  let animationFrameId = null;

  /**
   * The main client-side rendering loop.
   * This function continuously draws the game state, using interpolation for smooth movement.
   */
  function gameLoop() {
    // Get interpolated hero state from the heroMovementEngine module
    const currentHeroState = getRenderedHeroState();

    // Render the game using the gameRenderer module
    gameRenderer.render(
      currentHeroState, // heroMovementEngine's getRenderedHeroState already returns full hero object
      worldDimensions,
      gridSize,
      canvas.width,
      canvas.height
    );

    // NEW: Update hero coordinates display
    if (heroCoordinatesDisplay) {
      const gridX = Math.floor(currentHeroState.x / gridSize);
      const gridY = Math.floor(currentHeroState.y / gridSize);
      heroCoordinatesDisplay.textContent = `X: ${gridX}, Y: ${gridY}`;
    }

    animationFrameId = requestAnimationFrame(gameLoop); // Request next frame
  }

  /**
   * Starts the client-side rendering loop.
   */
  function startClientRenderLoop() {
    if (!animationFrameId) {
      animationFrameId = requestAnimationFrame(gameLoop);
    }
  }

  /**
   * Stops the client-side rendering loop.
   */
  function stopClientRenderLoop() {
    if (animationFrameId) {
      cancelAnimationFrame(animationFrameId);
      animationFrameId = null;
    }
  }

  // --- WebSocket Message Handlers ---

  /**
   * Handles 'game_state_update' messages from the server.
   * @param {object} message - The game_state_update message.
   */
  function handleGameStateUpdate(message) {
    // Update general game state properties from the server
    worldDimensions.width = message.worldWidth;
    worldDimensions.height = message.worldHeight;
    gridSize = message.gridSize;
    serverFps = message.serverFps;
    effectiveTileMoveDurationMs = message.effectiveTileMoveDurationMs; // Update effective duration

    // Update parameters in the hero movement engine
    updateWorldParameters(worldDimensions.width, worldDimensions.height, gridSize);
    // updateAllowedNextPositionsMap(message.allowedNextPositionsMap); // REMOVED: No longer exported or needed

    // Update hero interpolation state with the server's authoritative data
    updateInterpolationState(
      message.hero,
      effectiveTileMoveDurationMs // Pass effective duration for speed changes
    );
  }

  /**
   * Handles 'game_status' messages from the server.
   * @param {object} message - The game_status message.
   */
  function handleGameStatus(message) {
    console.log("Game status from server:", message.message);
    // You could update a UI element with this status
  }

  /**
   * Handles 'chat_message' messages from the server.
   * Logs the chat message to the console, as this client doesn't display chat.
   * @param {object} message - The chat_message.
   */
  function handleChatMessage(message) {
    console.log(`Chat Message [${message.user}]: ${message.message}`);
    // This client does not have a UI element to display chat messages,
    // so we just log them to avoid the "unknown or missing type" warning.
  }

  // --- Input Change Listener ---

  /**
   * Callback for inputHandler when active input state changes.
   * Sends the current input state to the server.
   * @param {object} currentActiveInputs - The current state of active inputs.
   */
  function onActiveInputsChange(currentActiveInputs) {
    // Increment sequence number for each input message sent
    clientInputSequence++;
    websocketClient.send({
      type: 'player_input',
      player: 'player1', // Hardcoded for now
      inputState: currentActiveInputs,
      sequence: clientInputSequence // Include the sequence number
    });
  }

  // --- Public Methods (attached to MyGame object) ---

  /**
   * Sends a request to the server to start the game simulation.
   * Updates button states via gameUI.
   */
  MyGame.startGame = () => {
    if (websocketClient.getReadyState() === WebSocket.OPEN) {
      websocketClient.send({ type: 'game_start_request' });
      console.log("Sent game_start_request to server.");
      gameUI.setButtonState(true); // Disable start, enable stop
    } else {
      console.warn("WebSocket not open. Cannot send start game request. Attempting to reconnect.");
      websocketClient.connect(onWebSocketOpen, onWebSocketClose, onWebSocketError); // Try to reconnect
    }
  };

  /**
   * Sends a request to the server to stop the game simulation.
   * Updates button states via gameUI.
   */
  MyGame.stopGame = () => {
    if (websocketClient.getReadyState() === WebSocket.OPEN) {
      websocketClient.send({ type: 'game_stop_request' });
      console.log("Sent game_stop_request to server.");
      gameUI.setButtonState(false); // Enable start, disable stop
    } else {
      console.warn("WebSocket not open. Cannot send stop game request.");
      gameUI.setButtonState(false); // Just update button states
    }
  };

  // --- WebSocket Callbacks for websocketClient.js ---
  function onWebSocketOpen() {
    // Initializing hero movement engine with placeholder values.
    // The first `game_state_update` message from the server will provide authoritative values.
    initializeHeroState(
      { x: 0, y: 0, size: 40, color: '#3498db' }, // Placeholder hero state
      gridSize,
      worldDimensions.width,
      worldDimensions.height,
      effectiveTileMoveDurationMs // Initial effective duration
      // lastProcessedInputSequence and allowedNextPositionsMap are no longer passed
    );
    startClientRenderLoop();
  }

  function onWebSocketClose() {
    stopClientRenderLoop();
    inputHandler.resetInputs(); // Clear active inputs
    resetHeroMovementEngine(); // Reset hero movement engine state
    gameUI.setButtonState(false); // Ensure buttons are in stopped state
    clientInputSequence = 0; // Reset client input sequence on disconnect
    if (heroCoordinatesDisplay) { // NEW: Clear coordinates display on close
        heroCoordinatesDisplay.textContent = 'X: 0, Y: 0';
    }
  }

  function onWebSocketError(error) {
    console.error("WebSocket connection error:", error);
    stopClientRenderLoop();
    inputHandler.resetInputs(); // Clear active inputs
    resetHeroMovementEngine(); // Reset hero movement engine state
    gameUI.setButtonState(false); // Ensure buttons are in stopped state
    clientInputSequence = 0; // Reset client input sequence on error
    if (heroCoordinatesDisplay) { // NEW: Clear coordinates display on error
        heroCoordinatesDisplay.textContent = 'X: 0, Y: 0';
    }
  }

  // --- Initial Setup Trigger ---
  window.addEventListener('load', initializeGame);

  /**
   * Initializes game components, gets element references, and sets up event listeners.
   * Called when the page's DOM is fully loaded.
   */
  function initializeGame() {
    canvas = document.getElementById('gameCanvas');
    if (!canvas) {
      console.error("Canvas element with ID 'gameCanvas' not found.");
      return;
    }
    heroCoordinatesDisplay = document.getElementById('heroCoordinatesDisplay'); // NEW: Get reference to display element

    // Initialize rendering module
    if (!gameRenderer.initialize(canvas)) {
      return; // Stop if canvas context not available
    }

    // Initialize UI module
    gameUI.initialize('startGameButton', 'stopGameButton', MyGame.startGame, MyGame.stopGame);

    // Initialize input handling module and register listener
    inputHandler.initialize();
    inputHandler.onInputChange(onActiveInputsChange);

    console.log("Game initialized. Attempting WebSocket connection...");

    // Register WebSocket message handlers
    websocketClient.on('game_state_update', handleGameStateUpdate);
    websocketClient.on('game_status', handleGameStatus);
    websocketClient.on('chat_message', handleChatMessage); // NEW: Register handler for chat messages

    // Connect WebSocket
    websocketClient.connect(onWebSocketOpen, onWebSocketClose, onWebSocketError);
  }

})();
