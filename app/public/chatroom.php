<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Redirect to index or login page
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Test Client</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 1em;
        }

        #messages {
            border: 1px solid #ccc;
            padding: 1em;
            margin-top: 1em;
            height: 200px;
            overflow-y: scroll;
            background-color: #f9f9f9;
        }

        #messages p {
            margin: 0.2em 0;
        }

        #messageInput {
            width: 80%;
            padding: 0.5em;
            margin-right: 5px;
        }

        button {
            padding: 0.5em;
        }
    </style>
</head>

<body>
    <h2>WebSocket Test</h2>

    <form action="index.php" method="get">
        <button type="submit">Leave Chat</button>
    </form>

    <div id="status">Connecting...</div>
    <div id="messages"></div>
    <div>
        <input type="text" id="messageInput" placeholder="Enter message...">
        <button id="sendButton">Send</button>
    </div>

    <script>
        const messageContainer = document.getElementById('messages');
        const statusDisplay = document.getElementById('status');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');

        // --- Correct WebSocket URL ---
        // Connect to Nginx on the default port (80, which is implicit)
        // Use the path '/ws/' that Nginx is configured to proxy
        const wsUrl = 'ws://localhost/ws/';
        statusDisplay.textContent = `Attempting to connect to: ${wsUrl}`;

        let conn; // Declare conn variable

        function connect() {
            // Close existing connection if any
            if (conn && conn.readyState !== WebSocket.CLOSED) {
                conn.close();
            }

            conn = new WebSocket(wsUrl); // Assign to the outer conn variable

            conn.onopen = function (e) {
                console.log("Connection established!");
                statusDisplay.textContent = "Status: Connected";
                addMessage("System", "Connection established!!!!");
                messageInput.disabled = false;
                sendButton.disabled = false;
            };

            conn.onmessage = function (e) {
                console.log("Message from server:", e.data);
                addMessage("From server", e.data);
            };

            conn.onclose = function (e) {
                console.log("Connection closed.", e);
                statusDisplay.textContent = `Status: Disconnected (Code: ${e.code}, Reason: ${e.reason || 'N/A'}). Attempting to reconnect...`;
                messageInput.disabled = true;
                sendButton.disabled = true;
                addMessage("System", `Connection closed. Code: ${e.code}`);
                // Optional: Attempt to reconnect after a delay
                setTimeout(connect, 5000); // Reconnect after 5 seconds
            };

            conn.onerror = function (e) {
                console.error("WebSocket Error:", e);
                statusDisplay.textContent = "Status: Error connecting";
                addMessage("System", "Connection error.");
                messageInput.disabled = true;
                sendButton.disabled = true;
            };
        }

        function addMessage(sender, message) {
            const p = document.createElement('p');
            p.innerHTML = `<strong>${sender}:</strong> ${message}`; // Use innerHTML to render potential HTML in messages safely if needed, or textContent for plain text
            messageContainer.appendChild(p);
            // Scroll to the bottom
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }

        sendButton.onclick = function () {
            const message = messageInput.value;
            if (message && conn && conn.readyState === WebSocket.OPEN) {
                console.log("Sending message:", message);
                conn.send(message);
                addMessage("You", message); // Display sent message immediately
                messageInput.value = ''; // Clear input field
            } else {
                console.log("Cannot send message. Connection not open or message empty.");
                addMessage("System", "Cannot send message. Connection not open or message empty.");
            }
        };

        // Add event listener for Enter key in the input field
        messageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendButton.click(); // Trigger the send button's click event
            }
        });

        // Initial connection attempt
        messageInput.disabled = true;
        sendButton.disabled = true;
        connect();

    </script>
</body>

</html>