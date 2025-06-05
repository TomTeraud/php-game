<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth\TokenAuthenticator;

$title = "Welcome";

$user = \App\Auth\TokenAuthenticator::authenticate();

?>

<?php include __DIR__ . '/../views/partials/header.php'; ?>
<?php include __DIR__ . '/../views/partials/nav.php'; ?>

<h2>Welcome to the Chat App</h2>

<?php if ($user): ?>
    <p>You are logged in as <?= htmlspecialchars($user->username) ?>.</p>
    <a href="chatroom.php">
        <button type="button">Enter Chat</button>
    </a>
<?php else: ?>
    <p>Please <a href="/views/login.php">log in</a> to enter the chat.</p>
<?php endif; ?>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
