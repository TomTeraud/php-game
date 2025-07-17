// public/js/game/heroMovementEngine.js

// Authoritative state (last confirmed by server)
let authoritativeHero = { x: 0, y: 0, size: 0, color: '' };

// Interpolation state (client's current visual representation)
// These variables define the start and end points for the visual interpolation.
let interpolationStartPos = { x: 0, y: 0 };
let interpolationEndPos = { x: 0, y: 0 };
let interpolationStartTime = 0;
let interpolationPeriod = 0; // This will directly be the effective duration from the server

// Game parameters (from server) - used for initial setup and updates
let gridSize = 50;
let worldWidth = 2000;
let worldHeight = 1500;

// The allowedNextPositionsMap is no longer used for client-side prediction,
// as the client will simply interpolate between server-provided authoritative positions.
// Therefore, it's removed from this file.

// Pending inputs and sequence numbers are removed as client-side prediction is removed.

/**
 * Initializes the hero's state and world parameters for interpolation.
 * Called once on game start or initial connection.
 * @param {object} initialHeroState - The initial hero state from the server.
 * @param {number} initialGridSize - The initial grid size.
 * @param {number} initialWorldWidth - The initial world width.
 * @param {number} initialWorldHeight - The initial world height.
 * @param {number} initialEffectiveTileMoveDurationMs - Hero's effective move duration from the server.
 */
export function initializeHeroState(initialHeroState, initialGridSize, initialWorldWidth, initialWorldHeight, initialEffectiveTileMoveDurationMs) {
    authoritativeHero = { ...initialHeroState };

    // Initialize interpolation to match authoritative
    interpolationStartPos = { x: initialHeroState.x, y: initialHeroState.y };
    interpolationEndPos = { x: initialHeroState.x, y: initialHeroState.y };
    interpolationStartTime = performance.now();
    interpolationPeriod = initialEffectiveTileMoveDurationMs; // Use effective duration directly

    gridSize = initialGridSize; // Update internal gridSize
    worldWidth = initialWorldWidth; // Update internal worldWidth
    worldHeight = initialWorldHeight; // Update internal worldHeight
}

/**
 * Updates the interpolation state based on a new authoritative state from the server.
 * This replaces the previous `reconcileState` function.
 * @param {object} serverHeroState - The authoritative hero state from the server.
 * @param {number} newEffectiveTileMoveDurationMs - The new effective tile move duration from the server.
 */
export function updateInterpolationState(serverHeroState, newEffectiveTileMoveDurationMs) {
    const now = performance.now();

    // Get the hero's current visual position before potential correction
    const { x: currentVisualX, y: currentVisualY } = getRenderedHeroState();

    // Update the authoritative hero state
    authoritativeHero = { ...serverHeroState };

    // If the authoritative position has changed OR the effective speed has changed,
    // start a new interpolation segment.
    const heroPositionChanged = (serverHeroState.x !== interpolationEndPos.x || serverHeroState.y !== interpolationEndPos.y);
    const effectiveSpeedChanged = (newEffectiveTileMoveDurationMs !== interpolationPeriod);

    if (heroPositionChanged || effectiveSpeedChanged) {
        interpolationStartPos = { x: currentVisualX, y: currentVisualY }; // Start from where it's currently rendered
        interpolationEndPos = { x: serverHeroState.x, y: serverHeroState.y }; // End at the new authoritative position
        interpolationStartTime = now; // Restart the interpolation timer
        interpolationPeriod = newEffectiveTileMoveDurationMs; // Use the new effective duration
    }
    // If neither position nor speed changed, but a new state update arrived,
    // we still ensure the interpolation starts from the current authoritative if it somehow drifted.
    // This is a safety net for very subtle drifts, though less critical without prediction.
    else if (interpolationStartPos.x !== authoritativeHero.x || interpolationStartPos.y !== authoritativeHero.y) {
        interpolationStartPos = { x: authoritativeHero.x, y: authoritativeHero.y };
        interpolationEndPos = { x: authoritativeHero.x, y: authoritativeHero.y };
        interpolationStartTime = now;
        interpolationPeriod = newEffectiveTileMoveDurationMs;
    }
}

/**
 * Returns the hero position that should be rendered on the client.
 * This is the interpolated position.
 * @returns {{x: number, y: number, size: number, color: string}} The hero's visual state.
 */
export function getRenderedHeroState() {
    const now = performance.now();
    const effectiveInterpolationPeriod = (interpolationPeriod > 0) ? interpolationPeriod : 1; // Fallback to 1ms to prevent division by zero
    let interpolationFactor = (now - interpolationStartTime) / effectiveInterpolationPeriod;
    interpolationFactor = Math.min(Math.max(interpolationFactor, 0), 1); // Clamp between 0 and 1

    const currentHeroX = interpolationStartPos.x + (interpolationEndPos.x - interpolationStartPos.x) * interpolationFactor;
    const currentHeroY = interpolationStartPos.y + (interpolationEndPos.y - interpolationStartPos.y) * interpolationFactor;

    return { x: currentHeroX, y: currentHeroY, size: authoritativeHero.size, color: authoritativeHero.color };
}

/**
 * Updates world and grid size parameters.
 * @param {number} newWorldWidth - The new world width.
 * @param {number} newWorldHeight - The new world height.
 * @param {number} newGridSize - The new grid size.
 */
export function updateWorldParameters(newWorldWidth, newWorldHeight, newGridSize) {
    worldWidth = newWorldWidth;
    worldHeight = newWorldHeight;
    gridSize = newGridSize;
}

// The following functions are removed as they are no longer needed without client-side prediction:
// - applyPredictedMove (no client prediction)
// - updateAllowedNextPositionsMap (map only needed for client prediction)
// - getNextAllowedGridPosition (helper for client prediction)

/**
 * Resets the engine's internal state.
 */
export function resetEngine() {
    authoritativeHero = { x: 0, y: 0, size: 0, color: '' };
    interpolationStartPos = { x: 0, y: 0 };
    interpolationEndPos = { x: 0, y: 0 };
    interpolationStartTime = 0;
    interpolationPeriod = 0;
    gridSize = 50; // Reset to default
    worldWidth = 2000; // Reset to default
    worldHeight = 1500; // Reset to default
}
