<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>

<main class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4 sm:p-6 lg:p-8">
    <div class="bg-white p-6 sm:p-8 lg:p-10 rounded-xl shadow-2xl max-w-sm w-full text-center space-y-6 transform transition-all duration-300 hover:scale-105">
        <h2 class="text-4xl font-extrabold text-gray-900 mb-6 tracking-tight leading-tight">Login</h2>

        <form method="POST" action="/login_submit" class="space-y-4">
            <div class="text-left">
                <label for="email" class="block text-lg font-medium text-gray-700 mb-1">Email:</label>
                <input type="email" id="email" name="email" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-base transition-colors duration-200"
                       placeholder="your@example.com">
            </div>

            <div class="text-left">
                <label for="password" class="block text-lg font-medium text-gray-700 mb-1">Password:</label>
                <input type="password" id="password" name="password" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-base transition-colors duration-200"
                       placeholder="••••••••">
            </div>

            <button type="submit"
                    class="mt-6 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 text-xl">
                Login
            </button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
