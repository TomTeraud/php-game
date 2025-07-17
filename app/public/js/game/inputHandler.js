const activeInputs = {
    'up': false,
    'down': false,
    'left': false,
    'right': false
};

let inputChangeListener = null; // Callback to notify when input state changes

/**
 * Sets a callback function to be invoked when the active input state changes.
 * @param {function} listener - The function to call, which will receive the `activeInputs` object.
 */
export function onInputChange(listener) {
    inputChangeListener = listener;
}

/**
 * Initializes keyboard event listeners.
 */
export function initialize() {
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', handleKeyUp);
}

/**
 * Removes keyboard event listeners.
 */
export function cleanup() {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('keyup', handleKeyUp);
    inputChangeListener = null;
}

/**
 * Handles keydown events, updates activeInputs, and notifies listener if state changes.
 * @param {KeyboardEvent} e - The keyboard event.
 */
function handleKeyDown(e) {
    let inputChanged = false;
    if (e.key === 'w' || e.key === 'W' || e.key === 'ArrowUp') {
        if (!activeInputs.up) {
            activeInputs.up = true;
            inputChanged = true;
        }
    } else if (e.key === 's' || e.key === 'S' || e.key === 'ArrowDown') {
        if (!activeInputs.down) {
            activeInputs.down = true;
            inputChanged = true;
        }
    } else if (e.key === 'a' || e.key === 'A' || e.key === 'ArrowLeft') {
        if (!activeInputs.left) {
            activeInputs.left = true;
            inputChanged = true;
        }
    } else if (e.key === 'd' || e.key === 'D' || e.key === 'ArrowRight') {
        if (!activeInputs.right) {
            activeInputs.right = true;
            inputChanged = true;
        }
    }

    if (inputChanged && inputChangeListener) {
        inputChangeListener({ ...activeInputs }); // Pass a copy to prevent external modification
    }
}

/**
 * Handles keyup events, updates activeInputs, and notifies listener if state changes.
 * @param {KeyboardEvent} e - The keyboard event.
 */
function handleKeyUp(e) {
    let inputChanged = false;
    if (e.key === 'w' || e.key === 'W' || e.key === 'ArrowUp') {
        if (activeInputs.up) {
            activeInputs.up = false;
            inputChanged = true;
        }
    } else if (e.key === 's' || e.key === 'S' || e.key === 'ArrowDown') {
        if (activeInputs.down) {
            activeInputs.down = false;
            inputChanged = true;
        }
    } else if (e.key === 'a' || e.key === 'A' || e.key === 'ArrowLeft') {
        if (activeInputs.left) {
            activeInputs.left = false;
            inputChanged = true;
        }
    } else if (e.key === 'd' || e.key === 'D' || e.key === 'ArrowRight') {
        if (activeInputs.right) {
            activeInputs.right = false;
            inputChanged = true;
        }
    }

    if (inputChanged && inputChangeListener) {
        inputChangeListener({ ...activeInputs }); // Pass a copy
    }
}

/**
 * Returns the current state of active inputs.
 * @returns {object} A copy of the activeInputs object.
 */
export function getActiveInputs() {
    return { ...activeInputs };
}

/**
 * Resets the active inputs state.
 */
export function resetInputs() {
    for (const key in activeInputs) {
        activeInputs[key] = false;
    }
    if (inputChangeListener) {
        inputChangeListener({ ...activeInputs });
    }
}
