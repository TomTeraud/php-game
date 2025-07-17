let canvas = null;
let ctx = null;

/**
 * Initializes the canvas and its 2D rendering context.
 * @param {HTMLCanvasElement} gameCanvas - The canvas element.
 * @returns {boolean} True if successful, false otherwise.
 */
export function initialize(gameCanvas) {
    canvas = gameCanvas;
    ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error("Failed to get 2D rendering context for canvas.");
        return false;
    }
    return true;
}

/**
 * Renders the current game state to the canvas.
 * @param {object} heroState - The hero's current interpolated state ({x, y, size, color}).
 * @param {object} worldDimensions - The dimensions of the game world ({width, height}).
 * @param {number} gridSize - The size of the grid cells.
 * @param {number} canvasWidth - The width of the canvas.
 * @param {number} canvasHeight - The height of the canvas.
 */
export function render(heroState, worldDimensions, gridSize, canvasWidth, canvasHeight) {
    if (!ctx || !canvas) {
        console.warn("Canvas context not available for rendering.");
        return;
    }

    ctx.clearRect(0, 0, canvasWidth, canvasHeight); // Clear the entire canvas

    // Calculate camera offset to center the hero on the screen
    let cameraX = heroState.x - canvasWidth / 2;
    let cameraY = heroState.y - canvasHeight / 2;

    // Clamp camera to world boundaries
    cameraX = Math.max(0, Math.min(cameraX, worldDimensions.width - canvasWidth));
    cameraY = Math.max(0, Math.min(cameraY, worldDimensions.height - canvasHeight));

    // Draw Grid
    ctx.strokeStyle = '#cccccc'; // Light gray for grid lines
    ctx.lineWidth = 0.5;
    for (let x = 0; x < worldDimensions.width; x += gridSize) {
        ctx.beginPath();
        ctx.moveTo(x - cameraX, 0);
        ctx.lineTo(x - cameraX, worldDimensions.height);
        ctx.stroke();
    }
    for (let y = 0; y < worldDimensions.height; y += gridSize) {
        ctx.beginPath();
        ctx.moveTo(0, y - cameraY);
        ctx.lineTo(worldDimensions.width, y - cameraY);
        ctx.stroke();
    }

    // Draw Hero
    ctx.beginPath();
    ctx.fillStyle = heroState.color;
    ctx.fillRect(
        heroState.x - heroState.size / 2 - cameraX,
        heroState.y - heroState.size / 2 - cameraY,
        heroState.size,
        heroState.size
    );
    ctx.closePath();
}
