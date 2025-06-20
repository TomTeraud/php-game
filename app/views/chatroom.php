<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>

<main class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-purple-50 to-indigo-100 p-4 sm:p-6 lg:p-8">
    <div class="bg-white p-6 sm:p-8 lg:p-10 rounded-xl shadow-2xl max-w-xl w-full space-y-6 transform transition-all duration-300 hover:scale-105">
        <h2 class="text-4xl font-extrabold text-gray-900 mb-4 tracking-tight leading-tight text-center">
            WebSocket Chatroom
        </h2>

        <!-- Status display -->
        <div id="status" class="text-center text-sm font-semibold text-gray-600 transition-colors duration-300">
            Connecting...
        </div>

        <!-- Messages display area -->
        <div id="messages" class="border border-gray-200 rounded-lg p-4 h-64 overflow-y-auto bg-gray-50 text-gray-800 text-sm leading-relaxed shadow-inner">
            <!-- Chat messages will be dynamically added here -->
        </div>

        <!-- Message input and send button -->
        <div class="flex items-center space-x-3 mt-4">
            <input type="text" id="messageInput" placeholder="Enter message..."
                   class="flex-grow px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-base transition-colors duration-200">
            <button id="sendButton"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-all duration-200 ease-in-out hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 text-base">
                Send
            </button>
        </div>

        <!-- Leave Chat button -->
        <form action="/" method="get" class="block w-full mt-6 text-center"> <!-- Changed action to '/' -->
            <button type="submit"
                    class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all duration-300 ease-in-out hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 text-base">
                Leave Chat
            </button>
        </form>
    </div>
</main>

<script src="/js/chatroom.js"></script>

<?php include __DIR__ . '/partials/footer.php'; ?>
