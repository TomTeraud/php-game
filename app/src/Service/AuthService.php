<?php
namespace App\Service;

use Ratchet\ConnectionInterface;
use App\Auth\TokenAuthenticator;

class AuthService
{
    /**
     * Authenticates a WebSocket connection using a token from cookies.
     * If authentication fails, the connection is closed.
     *
     * @param ConnectionInterface $conn The connection to authenticate.
     * @return bool True if authentication was successful, false otherwise.
     */
    public function authenticate(ConnectionInterface $conn): bool
    {
        $request = $conn->httpRequest;
        $cookieHeader = $request->getHeaderLine('Cookie');

        parse_str(str_replace('; ', '&', $cookieHeader), $cookies);
        $token = $cookies['token'] ?? null;
       
        if ($token) {
            $decodedUserData = TokenAuthenticator::decode($token);

            if ($decodedUserData) {
                // Token is valid! Store user data directly on the connection object for later use
                $conn->userId = $decodedUserData->userId;
                $conn->username = $decodedUserData->username;

                error_log("WebSocket connection authenticated for user: " . $decodedUserData->username . " (ID: " . $decodedUserData->userId . ")");
                return true;
            } else {
                // Token is invalid or decode failed.
                error_log("WebSocket connection rejected: Invalid or expired token.");
                $conn->send(json_encode(['status' => 'error', 'message' => 'Authentication failed: Invalid token.']));
                $conn->close();
                return false;
            }
        } else {
            // No token provided.
            error_log("WebSocket connection rejected: No token provided.");
            $conn->send(json_encode(['status' => 'error', 'message' => 'Authentication failed: No token provided.']));
            $conn->close();
            return false;
        }
    }
}