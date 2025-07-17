// public/js/websocketClient.js

const websocketUrl = 'ws://localhost/ws/'; // WebSocket server URL
let ws = null;
let messageHandlers = {}; // Stores functions to call based on message type
let reconnectAttempt = 0;
const maxReconnectAttempts = 10;
const initialReconnectDelayMs = 1000; // 1 second

/**
 * Connects to the WebSocket server.
 * @param {function} onOpenCallback - Callback to execute on successful connection.
 * @param {function} onCloseCallback - Callback to execute on connection close.
 * @param {function} onErrorCallback - Callback to execute on connection error.
 */
export function connect(onOpenCallback, onCloseCallback, onErrorCallback) {
    if (ws && (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING)) {
        console.log("WebSocket already open or connecting.");
        return;
    }

    ws = new WebSocket(websocketUrl);

    ws.onopen = (event) => {
        console.log("WebSocket connected.");
        reconnectAttempt = 0; // Reset reconnect attempts on success
        if (onOpenCallback) onOpenCallback(event);
    };

    ws.onmessage = (event) => {
        try {
            const message = JSON.parse(event.data);
            // Call the appropriate handler based on message type
            if (message.type && messageHandlers[message.type]) {
                messageHandlers[message.type](message);
            } else {
                console.warn("Received message with unknown or missing type:", message);
            }
        } catch (e) {
            console.error("Failed to parse WebSocket message:", e, event.data);
        }
    };

    ws.onclose = (event) => {
        console.log("WebSocket disconnected:", event);
        if (onCloseCallback) onCloseCallback(event);
        scheduleReconnect(); // Attempt to reconnect
    };

    ws.onerror = (error) => {
        console.error("WebSocket error:", error);
        if (onErrorCallback) onErrorCallback(error);
        scheduleReconnect(); // Attempt to reconnect
    };
}

/**
 * Schedules a reconnection attempt with exponential backoff.
 */
function scheduleReconnect() {
    if (reconnectAttempt < maxReconnectAttempts) {
        const delay = initialReconnectDelayMs * Math.pow(2, reconnectAttempt);
        console.log(`Attempting to reconnect in ${delay / 1000} seconds (attempt ${reconnectAttempt + 1}/${maxReconnectAttempts})...`);
        setTimeout(() => {
            reconnectAttempt++;
            // Reconnect using the stored callbacks (assuming they are set during initial connect call)
            connect(ws.onopen, ws.onclose, ws.onerror);
        }, delay);
    } else {
        console.error("Max reconnection attempts reached. Please refresh the page.");
        // Optionally inform the user via UI that reconnection failed
    }
}

/**
 * Sends a message (JSON object) to the WebSocket server.
 * @param {object} message - The message object to send.
 */
export function send(message) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify(message));
    } else {
        console.warn("WebSocket not open. Cannot send message:", message);
    }
}

/**
 * Registers a handler function for a specific message type.
 * @param {string} type - The 'type' field of the incoming WebSocket message.
 * @param {function} handler - The function to call when a message of this type is received.
 */
export function on(type, handler) {
    messageHandlers[type] = handler;
}

/**
 * Closes the WebSocket connection.
 */
export function disconnect() {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.close();
    }
    ws = null;
    messageHandlers = {}; // Clear handlers on disconnect
}

/**
 * Returns the current WebSocket ready state.
 * @returns {number} WebSocket.CONNECTING, WebSocket.OPEN, WebSocket.CLOSING, or WebSocket.CLOSED.
 */
export function getReadyState() {
    return ws ? ws.readyState : WebSocket.CLOSED;
}
