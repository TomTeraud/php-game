<?php

declare(strict_types=1);

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class TokenAuthenticator
{
    /**
     * Authenticates a user from a token (header or cookie).
     * Returns decoded data or null on failure (for UI use).
     *
     * @return object|null
     */
    public static function authenticate(): ?object
    {
        try {
            $jwtSecretKey = self::getJwtSecretKey();
            $token = self::extractToken();

            error_log("[Auth Debug] Token: " . ($token ?? 'null'));


            if (!$token) {
                error_log("[Auth Debug] No token found.");
                return null;
            }

            $decoded = JWT::decode($token, new Key($jwtSecretKey, 'HS256'));
            error_log("[Auth Debug] Decoded: " . print_r($decoded, true));
            return $decoded->data ?? null;
        } catch (Exception $e) {
            error_log("[Auth Debug] Exception during decoding: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Same as `authenticate()`, but exits with JSON error on failure.
     * Use in API endpoints.
     *
     * @return object
     */
    public static function authenticateOrFail(): object
    {
        try {
            $jwtSecretKey = self::getJwtSecretKey();
            $token = self::extractToken();

            if (!$token) {
                self::sendErrorResponse(401, 'No token provided.');
            }

            $decoded = JWT::decode($token, new Key($jwtSecretKey, 'HS256'));
            return $decoded->data ?? (object)[];
        } catch (Exception $e) {
            self::sendErrorResponse(401, 'Invalid or expired token: ' . $e->getMessage());
            exit;
        }
    }

    /**
     * Extracts token from Authorization header or 'token' cookie.
     *
     * @return string|null
     */
    private static function extractToken(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        return $_COOKIE['token'] ?? null;
    }

    /**
     * Gets the JWT secret key from environment.
     *
     * @return string
     * @throws Exception
     */
    private static function getJwtSecretKey(): string
    {
        $key = getenv('JWT_SECRET_KEY') ?? null;

        if (!$key) {
            throw new Exception('JWT_SECRET_KEY environment variable is not set.');
        }

        return $key;
    }

    /**
     * Sends JSON error response and exits.
     *
     * @param int $statusCode
     * @param string $message
     */
    private static function sendErrorResponse(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'code' => $statusCode,
        ]);
        exit;
    }

    public static function decode(string $token): ?object
    {
        try {
            $jwtSecretKey = self::getJwtSecretKey();
            $decoded = JWT::decode($token, new Key($jwtSecretKey, 'HS256'));
            return $decoded->data ?? null;
        } catch (Exception $e) {
            error_log("[Auth Debug] Token decode failed in WebSocket: " . $e->getMessage());
            return null;
        }
    }
}
