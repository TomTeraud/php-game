// Ensure MyGame object is globally accessible (or within a specific scope)
// for the HTML buttons to call its methods.
; // Leading semicolon for safety against ASI

const MyGame = {}; // Initialize MyGame as an object to hold game state and functions

(() => {
  // --- Game State Variables ---
  let animationFrameId = null;    // Stores the requestAnimationFrame ID for stopping the loop
  let lastFrameTimeMs = 0;      // Last time the game logic was actually updated (in milliseconds)

  const desiredFPS = 60;          // Target frame rate for game logic and rendering
  const frameIntervalMs = 1000 / desiredFPS; // Milliseconds per frame

  // Canvas and Context
  let canvas = null;
  let ctx = null;

  // Game Object (e.g., a simple bouncing ball)
  const ball = {
    x: 100,
    y: 100,
    radius: 20,
    dx: 50, // pixels per second
    dy: 1600, // pixels per second
    color: '#3498db' // Blue color
  };

  // --- HTML Element References ---
  let startGameButton = null;
  let stopGameButton = null;

  // --- Game Loop Functions ---

  /**
   * Initializes game components and sets up event listeners.
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

    // Get button references
    startGameButton = document.getElementById('startGameButton');
    stopGameButton = document.getElementById('stopGameButton');

    // Add event listeners to buttons
    if (startGameButton) {
      startGameButton.addEventListener('click', MyGame.startGame);
      // Enable start button and disable stop button initially
      startGameButton.disabled = false;
    }
    if (stopGameButton) {
      stopGameButton.addEventListener('click', MyGame.stopGame);
      stopGameButton.disabled = true; // Initially disabled
    }

    console.log("Game initialized. Ready to start.");
    // Initial render to show the ball before starting
    render();
  }

  /**
   * Updates game logic based on the time elapsed since the last frame.
   * @param {number} deltaTime Time in seconds since the last update.
   */
  function update(deltaTime) {
    // Move the ball
    ball.x += ball.dx * deltaTime;
    ball.y += ball.dy * deltaTime;

    // Bounce off walls (x-axis)
    if (ball.x + ball.radius > canvas.width || ball.x - ball.radius < 0) {
      ball.dx *= -1; // Reverse horizontal direction
      // Ensure ball stays within bounds if it overshoots
      if (ball.x + ball.radius > canvas.width) ball.x = canvas.width - ball.radius;
      if (ball.x - ball.radius < 0) ball.x = ball.radius;
    }

    // Bounce off walls (y-axis)
    if (ball.y + ball.radius > canvas.height || ball.y - ball.radius < 0) {
      ball.dy *= -1; // Reverse vertical direction
      // Ensure ball stays within bounds if it overshoots
      if (ball.y + ball.radius > canvas.height) ball.y = canvas.height - ball.radius;
      if (ball.y - ball.radius < 0) ball.y = ball.radius;
    }
  }

  /**
   * Renders the game state to the canvas.
   */
  function render() {
    // Clear the canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw the ball
    ctx.beginPath();
    ctx.arc(ball.x, ball.y, ball.radius, 0, Math.PI * 2);
    ctx.fillStyle = ball.color;
    ctx.fill();
    ctx.closePath();
  }

  /**
   * The main game loop function, called by requestAnimationFrame.
   * It handles frame rate gating and calls update/render.
   * @param {DOMHighResTimeStamp} currentTimeMs The timestamp provided by requestAnimationFrame.
   */
  function gameLoop(currentTimeMs) {
    // Schedule the next frame first. This ensures the loop continues.
    animationFrameId = window.requestAnimationFrame(gameLoop);

    // Calculate time elapsed since last *actual* game logic update
    const elapsedMs = currentTimeMs - lastFrameTimeMs;

    // Only update and render if enough time has passed for the desired FPS
    if (elapsedMs >= frameIntervalMs) {
      // Adjust lastFrameTimeMs to prevent cumulative drift
      lastFrameTimeMs = currentTimeMs - (elapsedMs % frameIntervalMs);

      // Convert elapsed time to seconds for time-based updates
      const deltaTime = elapsedMs / 1000;

      update(deltaTime);
      render();
    }
  }

  // --- Public Methods (attached to MyGame) ---

  /**
   * Starts the game loop.
   */
  MyGame.startGame = () => {
    if (!animationFrameId) { // Prevent multiple starts
      // Disable start button, enable stop button
      if (startGameButton) startGameButton.disabled = true;
      if (stopGameButton) stopGameButton.disabled = false;

      // Initialize lastFrameTimeMs on the first call to gameLoop
      animationFrameId = window.requestAnimationFrame((initialTime) => {
        lastFrameTimeMs = initialTime;
        gameLoop(initialTime); // Start the loop with the initial timestamp
      });
      console.log("Game loop started.");
    }
  };

  /**
   * Stops the game loop.
   */
  MyGame.stopGame = () => {
    if (animationFrameId) {
      window.cancelAnimationFrame(animationFrameId);
      animationFrameId = null; // Clear the ID
      console.log("Game loop stopped.");

      // Enable start button, disable stop button
      if (startGameButton) startGameButton.disabled = false;
      if (stopGameButton) stopGameButton.disabled = true;
    }
  };

  // --- Initial Setup ---
  // Call initializeGame when the DOM is fully loaded
  window.addEventListener('load', initializeGame);

})();
