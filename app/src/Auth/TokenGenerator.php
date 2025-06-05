<?php

declare(strict_types=1);

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Exception;

class TokenGenerator
{
    private string $secretKey;
    private string $issuer;
    private string $audience;
    private int $expirationInSeconds;

    public function __construct(
        ?string $issuer = null,
        ?string $audience = null,
        int $expirationInSeconds = 86400 // 24 hours
    ) {
        $this->secretKey = getenv('JWT_SECRET_KEY');
        $this->issuer = $issuer ?? 'yourdomain.com';
        $this->audience = $audience ?? 'yourfrontendapp.com';
        $this->expirationInSeconds = $expirationInSeconds;
    }

    /**
     * Factory method for cleaner usage
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Generates a new JWT from user data
     */
    public function generateToken(array $userData): string
    {
        $issuedAt = time();

        $payload = [
            'iat'  => $issuedAt,
            'exp'  => $issuedAt + $this->expirationInSeconds,
            'iss'  => $this->issuer,
            'aud'  => $this->audience,
            'data' => $userData
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    /**
     * Refreshes a JWT by decoding and reissuing with new expiry
     */
    public function refreshToken(string $oldToken): string
    {
        try {
            $decoded = JWT::decode($oldToken, new Key($this->secretKey, 'HS256'));
            $userData = (array) ($decoded->data ?? []);
        } catch (Exception $e) {
            throw new RuntimeException('Invalid token: ' . $e->getMessage());
        }

        return $this->generateToken($userData);
    }
}
