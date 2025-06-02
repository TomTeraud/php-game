<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\DatabaseConnection;


session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $pdo = DatabaseConnection::getInstance()->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: /../index.php");
        exit;
    } else {
        echo "Invalid email or password.";
    }
}