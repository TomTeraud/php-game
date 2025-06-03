<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\DatabaseConnection;
use Firebase\JWT\JWT;


$jwtSecretKey = $_ENV['JWT_SECRET_KEY'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $pdo = DatabaseConnection::getInstance()->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $issuedAt   = time();
        $expiration = $issuedAt + (3600 * 24); // Token valid for 24 hours (adjust as needed)
        $issuer     = "yourdomain.com"; // Your domain name
        $audience   = "yourfrontendapp.com"; // The client application that will consume the token

        $payload = [
            'iat'  => $issuedAt,                // Issued at: time when the token was generated
            'exp'  => $expiration,              // Expiration time
            'iss'  => $issuer,                  // Issuer
            'aud'  => $audience,                // Audience
            'data' => [                         // Custom data you want to include in the token
                'user_id'  => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email']
            ]
        ];

        // Generate the JWT
        $jwt = JWT::encode($payload, $jwtSecretKey, 'HS256'); // HS256 is a common signing algorithm

        // Instead of redirecting with a session, send the JWT back to the client.
        // This is typically done as a JSON response.
        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'Login successful',
            'token'   => $jwt
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Invalid email or password.']);
        exit;
    }
}