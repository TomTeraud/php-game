<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\DatabaseConnection;
use App\Auth\TokenGenerator;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $pdo = DatabaseConnection::getInstance()->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $token = TokenGenerator::create()->generateToken([
            'user_id'  => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email']
        ]);

        setcookie('token', $token, [
            'expires'  => time() + 86400,
            'path'     => '/',
            'httponly' => true,
            'secure'   => isset($_SERVER['HTTPS']),
            'samesite' => 'Lax' // Use 'Strict' or 'Lax' based on frontend
        ]);

        header('Location: /index.php');

        exit;
    } else {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['message' => 'Invalid email or password.']);
        exit;
    }
}