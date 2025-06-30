// public/js/chatroom.js

const messageContainer = document.getElementById('messages');
const statusDisplay = document.getElementById('status');
const messageInput = document.getElementById('messageInput');
const sendButton = document.getElementById('sendButton');

// --- WebSocket connection ---
const wsUrl = 'ws://localhost/ws/';
statusDisplay.textContent = `Attempting to connect to: ${wsUrl}`;

let conn;

function connect() {
    if (conn && (conn.readyState === WebSocket.OPEN || conn.readyState === WebSocket.CONNECTING)) {
        console.log("WebSocket already open or connecting for chat.");
        return;
    }

    conn = new WebSocket(wsUrl);

    conn.onopen = () => {
        statusDisplay.textContent = "Status: Connected";
        addMessage("System", "Connection established!");
        messageInput.disabled = false;
        sendButton.disabled = false;
    };

    conn.onmessage = (e) => {
        try {
            const data = JSON.parse(e.data); // Attempt to parse incoming JSON message
            
            // Check the message type
            if (data.type === 'chat_message') {
                // Assuming chat messages have 'user' and 'message' properties
                const sender = data.user || "Server"; // Default sender if not provided
                const message = data.message || "No message content.";
                addMessage(sender, message);
            } else if (data.type === 'game_state_update') {
                // If the same WebSocket is used for game, ignore game updates here
                // console.log("Received game state update in chatroom, ignoring:", data);
            } else {
                // Handle other unexpected message types or log them
                addMessage("Server (Unknown Type)", JSON.stringify(data));
                console.warn("Received unknown message type in chatroom:", data);
            }
        } catch (jsonError) {
            // If the message is not valid JSON (e.g., plain text), display it as raw server message
            addMessage("Server (Raw)", e.data);
            console.warn("Received non-JSON message in chatroom:", e.data);
        }
    };

    conn.onclose = (e) => {
        statusDisplay.textContent = `Status: Disconnected. Reconnecting...`;
        messageInput.disabled = true;
        sendButton.disabled = true;
        addMessage("System", `Connection closed. Code: ${e.code}. Reason: ${e.reason || 'No reason'}`);
    };

    conn.onerror = (error) => {
        statusDisplay.textContent = "Status: Error connecting";
        addMessage("System", "Connection error. Check console for details.");
        messageInput.disabled = true;
        sendButton.disabled = true;
        console.error("Chatroom WebSocket error:", error);
    };
}

function addMessage(sender, message) {
    const p = document.createElement('p');
    p.classList.add('py-1', 'px-2', 'rounded-md', 'mb-1', 'break-words'); // Basic styling for messages

    if (sender === "You") {
        p.classList.add('bg-blue-100', 'text-blue-800', 'self-end'); // Blue for your messages
        p.style.textAlign = 'right'; // Align your messages to the right
        p.innerHTML = `<strong>${sender}:</strong> ${message}`;
    } else if (sender === "System") {
        p.classList.add('bg-gray-200', 'text-gray-700', 'font-medium', 'text-center'); // Gray for system messages
        p.innerHTML = `<em>${message}</em>`;
    } else {
        p.classList.add('bg-white', 'text-gray-900', 'border', 'border-gray-100'); // White for others' messages
        p.innerHTML = `<strong>${sender}:</strong> ${message}`;
    }

    messageContainer.appendChild(p);
    messageContainer.scrollTop = messageContainer.scrollHeight; // Auto-scroll to bottom
}

// Event handler for sending messages
sendButton.onclick = () => {
    const message = messageInput.value.trim(); // Trim whitespace
    if (message && conn && conn.readyState === WebSocket.OPEN) {
        // Send a JSON object to the server with a 'type'
        const chatMessage = {
            type: 'chat_message', // This type aligns with GameServer's expected message types
            message: message
            // You might add 'user' or 'token' here later for authentication on server
        };
        conn.send(JSON.stringify(chatMessage));
        addMessage("You", message); // Display your message immediately
        messageInput.value = ''; // Clear input field
        messageInput.focus(); // Keep focus on input
    } else {
        addMessage("System", "Cannot send message. Connection not open or message is empty.");
        console.warn("Attempted to send message when WS not open or message empty.");
    }
};

// Listen for Enter key press in the input field to send message
messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendButton.click();
    }
});

// Initial state and connection attempt
messageInput.disabled = true;
sendButton.disabled = true;
connect(); // Start the WebSocket connection when the script loads
