<nav>
    <?php if (isset($_SESSION['user_id'])): ?>
        <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>

        <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page === 'chatroom.php'):
        ?>
            <form action="index.php" method="get" style="display:inline;">
                <button type="submit">Leave Chat</button>
            </form>
        <?php else: ?>
            <a href="chatroom.php">Chatroom</a>
            <a href="logout.php">Logout</a>
        <?php endif; ?>

    <?php else: ?>
        <a href="/views/login.php">Login</a>
        <a href="/views/register.php">Register</a>
    <?php endif; ?>
</nav>
