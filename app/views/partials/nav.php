<nav class="bg-gray-800 p-4 shadow-md flex justify-between items-center rounded-b-xl px-6 sm:px-8 lg:px-10">
    <?php if ($user): ?>
        <span class="text-white text-lg font-medium mr-4">Welcome, <span class="text-indigo-400"><?= htmlspecialchars($user->username) ?></span>!</span>

        <div class="flex items-center space-x-4">
            <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                if ($current_page === 'chatroom.php'):
            ?>
                <form action="index.php" method="get" class="inline-block">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-all duration-200 ease-in-out hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-opacity-75 text-base">
                        Leave Chat
                    </button>
                </form>
            <?php else: ?>
                <a href="/chatroom" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-base font-medium transition-colors duration-200">Chatroom</a>
                <a href="/logout" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-all duration-200 ease-in-out hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-opacity-75 text-base">Logout</a>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="flex-grow"></div> <!-- Pushes links to the right -->
        <div class="flex items-center space-x-4">
            <a href="/login" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-base font-medium transition-colors duration-200">Login</a>
            <a href="/register" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-all duration-200 ease-in-out hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-opacity-75 text-base">Register</a>
        </div>
    <?php endif; ?>
</nav>
