let startGameButton = null;
let stopGameButton = null;
let onStartGameCallback = null;
let onStopGameCallback = null;

/**
 * Initializes UI element references and attaches event listeners.
 * @param {string} startButtonId - The ID of the start game button.
 * @param {string} stopButtonId - The ID of the stop game button.
 * @param {function} startCallback - Function to call when start button is clicked.
 * @param {function} stopCallback - Function to call when stop button is clicked.
 */
export function initialize(startButtonId, stopButtonId, startCallback, stopCallback) {
    startGameButton = document.getElementById(startButtonId);
    stopGameButton = document.getElementById(stopButtonId);
    onStartGameCallback = startCallback;
    onStopGameCallback = stopCallback;

    if (startGameButton) {
        startGameButton.addEventListener('click', handleStartClick);
        startGameButton.disabled = false;
    }
    if (stopGameButton) {
        stopGameButton.addEventListener('click', handleStopClick);
        stopGameButton.disabled = true;
    }
}

/**
 * Cleans up event listeners and resets UI state.
 */
export function cleanup() {
    if (startGameButton) {
        startGameButton.removeEventListener('click', handleStartClick);
    }
    if (stopGameButton) {
        stopGameButton.removeEventListener('click', handleStopClick);
    }
    startGameButton = null;
    stopGameButton = null;
    onStartGameCallback = null;
    onStopGameCallback = null;
}

/**
 * Handles the start game button click.
 */
function handleStartClick() {
    if (onStartGameCallback) {
        onStartGameCallback();
    }
    setButtonState(true); // Disable start, enable stop
}

/**
 * Handles the stop game button click.
 */
function handleStopClick() {
    if (onStopGameCallback) {
        onStopGameCallback();
    }
    setButtonState(false); // Enable start, disable stop
}

/**
 * Sets the disabled state of the start/stop buttons.
 * @param {boolean} gameStarted - True if the game is considered started, false otherwise.
 */
export function setButtonState(gameStarted) {
    if (startGameButton) {
        startGameButton.disabled = gameStarted;
    }
    if (stopGameButton) {
        stopGameButton.disabled = !gameStarted;
    }
}

