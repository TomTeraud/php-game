<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>

<main class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-purple-50 to-indigo-100 p-4 sm:p-6 lg:p-8 space-y-8">
    <h2 class="text-4xl font-extrabold text-gray-900 mb-4 tracking-tight leading-tight text-center">
        Chatroom
    </h2>

    <div id="status" class="text-center text-sm font-semibold text-gray-600 transition-colors duration-300">
        Connecting...
    </div>

    <div class="bg-white p-6 sm:p-8 lg:p-10 rounded-xl shadow-2xl max-w-xl w-full space-y-6 transform transition-all duration-300 hover:scale-105">
        <div id="messages" class="border border-gray-200 rounded-lg p-4 h-64 overflow-y-auto bg-gray-50 text-gray-800 text-sm leading-relaxed shadow-inner">
            <!-- Chat messages will be dynamically added here -->
        </div>

        <div class="flex items-center space-x-3 mt-4">
            <input type="text" id="messageInput" placeholder="Enter message..."
                class="flex-grow px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-base transition-colors duration-200">
            <button id="sendButton"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-all duration-200 ease-in-out hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 text-base">
                Send
            </button>
        </div>

    </div>
</main>

<script src="/js/chatroom.js"></script>

<?php include __DIR__ . '/partials/footer.php'; ?>