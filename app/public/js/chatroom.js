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
    if (conn && conn.readyState !== WebSocket.CLOSED) {
        conn.close();
    }

    conn = new WebSocket(wsUrl);

    conn.onopen = () => {
        statusDisplay.textContent = "Status: Connected";
        addMessage("System", "Connection established!");
        messageInput.disabled = false;
        sendButton.disabled = false;
    };

    conn.onmessage = (e) => {
        addMessage("From server", e.data);
    };

    conn.onclose = (e) => {
        statusDisplay.textContent = `Status: Disconnected. Reconnecting...`;
        messageInput.disabled = true;
        sendButton.disabled = true;
        addMessage("System", `Connection closed. Code: ${e.code}`);
        setTimeout(connect, 5000);
    };

    conn.onerror = () => {
        statusDisplay.textContent = "Status: Error connecting";
        addMessage("System", "Connection error.");
        messageInput.disabled = true;
        sendButton.disabled = true;
    };
}

function addMessage(sender, message) {
    const p = document.createElement('p');
    p.innerHTML = `<strong>${sender}:</strong> ${message}`;
    messageContainer.appendChild(p);
    messageContainer.scrollTop = messageContainer.scrollHeight;
}

sendButton.onclick = () => {
    const message = messageInput.value;
    if (message && conn && conn.readyState === WebSocket.OPEN) {
        conn.send(message);
        addMessage("You", message);
        messageInput.value = '';
    } else {
        addMessage("System", "Cannot send message.");
    }
};

messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendButton.click();
    }
});

messageInput.disabled = true;
sendButton.disabled = true;
connect();
