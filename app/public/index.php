<?php
session_start();
$title = "Welcome";
?>

<?php include __DIR__ . '/../views/partials/header.php'; ?>
<?php include __DIR__ . '/../views/partials/nav.php'; ?>

<h2>Welcome to the Chat App</h2>

<?php if (isset($_SESSION['user_id'])): ?>
    <p>You are logged in as <?= htmlspecialchars($_SESSION['username']) ?>.</p>
    <a href="chatroom.php">
        <button type="button">Enter Chat</button>
    </a>
<?php else: ?>
    <p>Please <a href="/views/login.php">log in</a> to enter the chat.</p>
<?php endif; ?>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>