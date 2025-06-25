<?php

require_once dirname(__DIR__) . '/bootstrap/app.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($requestUri) {
    case '/':
        $title = "Welcome";
        include __DIR__ . '/../views/partials/header.php';
        include __DIR__ . '/../views/partials/nav.php';
?>
        <main class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4 sm:p-6 lg:p-8 space-y-8">
            <?php if ($user): ?>
                <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight leading-tight text-center">
                    Welcome, <span class="text-indigo-700"><?= htmlspecialchars($user->username) ?></span>!
                </h2>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6 w-full max-w-2xl">
                    <div class="bg-white p-6 sm:p-8 lg:p-10 rounded-xl shadow-2xl max-w-md w-full text-center space-y-6 transform transition-all duration-300 hover:scale-105">
                        <h3 class="text-3xl font-extrabold text-gray-900 mb-4 tracking-tight leading-tight">
                            Chatroom
                        </h3>
                        <a href="/chatroom" class="block w-full">
                            <button type="button" class="mt-6 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 text-xl">
                                Enter Chat
                            </button>
                        </a>
                    </div>
                    <div class="bg-white p-6 sm:p-8 lg:p-10 rounded-xl shadow-2xl max-w-md w-full text-center space-y-6 transform transition-all duration-300 hover:scale-105">
                        <h3 class="text-3xl font-extrabold text-gray-900 mb-4 tracking-tight leading-tight">
                            Game
                        </h3>
                        <a href="/game" class="block w-full">
                            <button type="button" class="mt-6 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 text-xl">
                                Play Game
                            </button>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white p-6 sm:p-8 lg:p-10 rounded-xl shadow-2xl max-w-md w-full text-center space-y-6 transform transition-all duration-300 hover:scale-105">
                    <h2 class="text-4xl font-extrabold text-gray-900 mb-4 tracking-tight leading-tight">
                        Welcome to the App
                    </h2>
                    <p class="text-lg text-gray-700 leading-relaxed">
                        Please <a href="/login" class="font-semibold text-indigo-600 hover:text-indigo-800 underline transition-colors duration-200">log in</a> or <a href="/register" class="font-semibold text-green-600 hover:text-green-800 underline transition-colors duration-200">register</a> to continue.
                    </p>
                </div>
            <?php endif; ?>
        </main>
<?php
        include __DIR__ . '/../views/partials/footer.php';
        break;

    case '/login':
        $title = "Login";
        include __DIR__ . '/../views/login.php';
        break;

    case '/login_submit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include __DIR__ . '/../actions/login_action.php';
            exit;
        }

    case '/register':
        $title = "Register";
        include __DIR__ . '/../views/register.php';
        break;

    case '/register_submit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include __DIR__ . '/../actions/register_action.php';
            exit;
        }

    case '/chatroom':
        if (!$user) { // $user should be set by bootstrap/auth.php
            header("Location: /login");
            exit;
        }
        $title = "Chatroom";
        include __DIR__ . '/../views/chatroom.php';
        break;

    case '/logout': // Handle logout action
        // This is where your logout logic (from logout.php) would go.
        // For simple apps, including the script here is fine.
        include __DIR__ . '/../actions/logout.php';
        exit; // Important to exit after a redirect/action

    case (strpos($requestUri, '/actions/') === 0 && $_SERVER['REQUEST_METHOD'] === 'POST'):
        // This is a very basic way to handle POST actions.
        // For a more robust app, you'd define specific POST routes like /login_submit.
        $actionFile = dirname(__DIR__) . $requestUri; // e.g., /var/www/html/actions/login_action.php
        if (file_exists($actionFile)) {
            include $actionFile;
        } else {
            http_response_code(404);
            echo "Action not found.";
        }
        exit; // Exit after processing an action
        break;

    default:
        // Handle 404 Not Found for any other unhandled routes
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>The page you requested could not be found.</p>";
        break;
}
?>