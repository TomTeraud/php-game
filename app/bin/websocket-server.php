<?php
// bin/websocket-server.php

// Use statements for Ratchet classes and your application's WebSocket component
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\Chat; // Ensure this namespace and class exist and implement MessageComponentInterface

// Require the Composer autoloader to load dependencies
require dirname(__DIR__) . '/vendor/autoload.php'; // Assumes vendor is one level up from bin/

echo "Starting WebSocket server on port 9001...\n";

// Create the server instance
$server = IoServer::factory(
    // Wrap your core application logic (Chat class) within the WebSocket and HTTP server protocols
    new HttpServer(
        new WsServer(
            // Instantiate your application's WebSocket handler class
            // This class (e.g., MyApp\Chat) must implement Ratchet\MessageComponentInterface
            new Chat()
        )
    ),
    // *** Port Change: Listen on port 9001 ***
    // This should match the port specified in the Nginx proxy_pass directive (e.g., http://websocket:9001)
    9001,
    // Bind to '0.0.0.0' to accept connections on all available network interfaces within the container
    '0.0.0.0'
);

// Start the server event loop
$server->run();

echo "WebSocket server stopped.\n"; // This line might not be reached if run() blocks indefinitely
