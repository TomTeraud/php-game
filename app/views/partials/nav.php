<nav>
    <?php if (isset($_SESSION['user_id'])): ?>
        <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
        <a href="chatroom.php">Chatroom</a>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="/views/login.php">Login</a>
        <a href="/views/register.php">Register</a>
    <?php endif; ?>
</nav>
