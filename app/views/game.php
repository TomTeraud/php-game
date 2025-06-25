<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>

<main class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-purple-50 to-indigo-100 p-4 sm:p-6 lg:p-8 space-y-8">
    <h2 class="text-4xl font-extrabold text-gray-900 mb-4 tracking-tight leading-tight text-center">
        Game
    </h2>

    <div class="bg-white p-6 sm:p-8 lg:p-10 rounded-xl shadow-2xl max-w-xl w-full space-y-6 transform transition-all duration-300 hover:scale-105">
    <canvas id="canvas" width="150" height="150"></canvas>
</div>
</main>

<script src="/js/game.js"></script>

<?php include __DIR__ . '/partials/footer.php'; ?>