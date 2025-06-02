<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\DatabaseConnection;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email    = $_POST["email"];
    $password = $_POST["password"];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // var_dump($username, $email, $password);
    // var_dump($hashedPassword);


    try {
        // Use your custom DB Connection class
        $pdo = DatabaseConnection::getInstance()->getPdo();

        // Check if the email is already in use
        $checkEmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        $emailExists = $checkEmail->fetchColumn();

        $checkUsername = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $checkUsername->execute([$username]);
        $usernameExists = $checkUsername->fetchColumn();

        
        
        if ($emailExists || $usernameExists) {
            echo <<<HTML
            <h2>Email or username already in use!</h2>
            <form action="register.php">
                <button type="submit">Back to Register</button>
            </form>
            HTML;
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);


        echo <<<HTML
        <h2>Registration successful!</h2>
        <form action="/../index.php">
            <button type="submit">Go to Home</button>
        </form>
        HTML;

        // Redirect to index.php after successful registration
        // header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
