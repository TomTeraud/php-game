; // Leading semicolon for safety against Automatic Semicolon Insertion (ASI)

const MyGame = {}; // Initialize MyGame as an object to hold game state and functions

(() => {
  // --- Game State Variables (Client-side representation) ---
  let canvas = null;
  let ctx = null;

  // The ball state will now be updated by messages from the server.
  // Initialize with some sensible defaults.
  let ball = {
    x: 0,
    y: 0,
    radius: 20, // This will be provided by the server, but a default prevents errors
    color: '#3498db' // Default color, will be updated by server
  };

  // WebSocket Connection details
  let ws = null;
  const websocketUrl = 'ws://localhost/ws/';

  // --- HTML Element References ---
  let startGameButton = null;
  let stopGameButton = null;

  // --- WebSocket Functions ---

  /**
   * Establishes the WebSocket connection to the game server.
   */
  function connectWebSocket() {
    // Prevent multiple connections if already open or connecting
    if (ws && (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING)) {
      console.log("WebSocket already open or connecting.");
      return;
    }

    ws = new WebSocket(websocketUrl);

    // Event handler for successful WebSocket connection
    ws.onopen = (event) => {
      console.log("WebSocket connected to game server.");
      // You might want to enable/disable UI elements here
    };

    // Event handler for messages received from the WebSocket server
    ws.onmessage = (event) => {
      const message = JSON.parse(event.data);
      console.log("Received message from server:", message); // Uncomment for debugging

      // Check the message type to differentiate between different server updates
      if (message.type === 'game_state_update') {
        // Update client-side ball state with authoritative data from the server
        ball.x = message.ball.x;
        ball.y = message.ball.y;
        ball.radius = message.ball.radius;
        ball.color = message.ball.color;

        render(); // Re-render the canvas with the new server-provided state
      } else if (message.type === 'game_status') {
        // Example: Server sending a text status update
        console.log("Game status from server:", message.message);
        // You could update a UI element (e.g., a status div) with this message
      }
      // Add more message types as your game develops (e.g., player joined, score update)
    };

    // Event handler for WebSocket connection closure
    ws.onclose = (event) => {
      console.log("WebSocket disconnected from game server:", event);
      MyGame.stopGame(); // Ensure client-side buttons reflect the stopped state
      // Implement re-connection logic here if your app needs it
    };

    // Event handler for WebSocket errors
    ws.onerror = (error) => {
      console.error("WebSocket error:", error);
      MyGame.stopGame(); // Ensure client-side buttons reflect the stopped state
      // Display an error message to the user if needed
    };
  }

  /**
   * Sends a message (JSON object) to the WebSocket server.
   * @param {object} message The message object to send.
   */
  function sendWebSocketMessage(message) {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(message));
    } else {
      console.warn("WebSocket not open. Cannot send message:", message);
      // Optionally display a user-friendly message about connection issue
    }
  }

  // --- Client-Side Rendering Function ---

  function render() {
    if (!ctx || !canvas) { // Ensure canvas and context are available
      console.warn("Canvas context not available for rendering.");
      return;
    }

    ctx.clearRect(0, 0, canvas.width, canvas.height); // Clear the entire canvas

    // Draw the ball based on the state received from the server
    ctx.beginPath();
    // Ensure ball.x and ball.y are within canvas bounds for drawing,
    // though server should primarily manage this.
    const drawX = Math.max(ball.radius, Math.min(ball.x, canvas.width - ball.radius));
    const drawY = Math.max(ball.radius, Math.min(ball.y, canvas.height - ball.radius));

    ctx.arc(drawX, drawY, ball.radius, 0, Math.PI * 2);
    ctx.fillStyle = ball.color;
    ctx.fill();
    ctx.closePath();
  }

  // --- Initialization and Event Handling ---

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
    ctx = canvas.getContext('2d');
    if (!ctx) {
      console.error("Failed to get 2D rendering context for canvas.");
      return;
    }

    // Get references to the Start and Stop buttons
    startGameButton = document.getElementById('startGameButton');
    stopGameButton = document.getElementById('stopGameButton');

    // Add click event listeners to the buttons
    if (startGameButton) {
      startGameButton.addEventListener('click', MyGame.startGame);
      startGameButton.disabled = false; // Start button is enabled initially
    }
    if (stopGameButton) {
      stopGameButton.addEventListener('click', MyGame.stopGame);
      stopGameButton.disabled = true; // Stop button is disabled initially
    }

    console.log("Game initialized. Attempting WebSocket connection...");
    connectWebSocket(); // Establish WebSocket connection when game initializes
    render(); // Initial render to show the ball at its default position
  }

  // --- Public Methods (attached to MyGame object) ---

  /**
   * Sends a request to the server to start the game simulation.
   * Updates button states.
   */
  MyGame.startGame = () => {
    if (ws && ws.readyState === WebSocket.OPEN) {
      sendWebSocketMessage({ type: 'game_start_request' }); // Send the start command
      console.log("Sent game_start_request to server.");
      // Update button states immediately for better UX
      if (startGameButton) startGameButton.disabled = true;
      if (stopGameButton) stopGameButton.disabled = false;
    } else {
      console.warn("WebSocket not open. Cannot send start game request. Attempting to reconnect.");
      connectWebSocket(); // Try to reconnect if not open
    }
  };

  /**
   * Sends a request to the server to stop the game simulation.
   * Updates button states.
   */
  MyGame.stopGame = () => {
    if (ws && ws.readyState === WebSocket.OPEN) {
      sendWebSocketMessage({ type: 'game_stop_request' }); // Send the stop command
      console.log("Sent game_stop_request to server.");
      // Update button states immediately for better UX
      if (startGameButton) startGameButton.disabled = false;
      if (stopGameButton) stopGameButton.disabled = true;
      // Also, clear canvas or reset state visually if server doesn't send a final reset
      render(); // Render once more to clear or show default state
    } else {
      console.warn("WebSocket not open. Cannot send stop game request.");
      // If WS not open, just update button states
      if (startGameButton) startGameButton.disabled = false;
      if (stopGameButton) stopGameButton.disabled = true;
    }
  };

  // --- Initial Setup Trigger ---
  // Call initializeGame when the entire DOM is loaded (including external scripts like this one).
  window.addEventListener('load', initializeGame);

})();
