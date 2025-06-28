<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>

<main class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-purple-50 to-indigo-100 p-4 sm:p-6 lg:p-8 space-y-8">
    <h2 class="text-4xl font-extrabold text-gray-900 mb-4 tracking-tight leading-tight text-center">
        Simple Game
    </h2>

    <div class="bg-white p-6 sm:p-8 lg:p-10 rounded-xl shadow-2xl max-w-xl w-full space-y-6 transform transition-all duration-300 hover:scale-105">
        <canvas id="gameCanvas" class="border border-gray-300 rounded-lg shadow-inner block mx-auto" width="600" height="400">
            Your browser does not support the Canvas element.
        </canvas>

        <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4 mt-6">
            <button id="startGameButton"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-green-500 focus:ring-opacity-50 text-xl w-full sm:w-auto">
                Start Game
            </button>
            <button id="stopGameButton"
                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-red-500 focus:ring-opacity-50 text-xl w-full sm:w-auto">
                Stop Game
            </button>
        </div>
    </div>
</main>

<script src="/js/game.js"></script>

<?php include __DIR__ . '/partials/footer.php'; ?>