<?php 
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php'; 
?>

<h2>WebSocket Chatroom</h2>


<div id="status">Connecting...</div>
<div id="messages"></div>
<div>
    <input type="text" id="messageInput" placeholder="Enter message...">
    <button id="sendButton">Send</button>
</div>
<form action="index.php" method="get">
<button type="submit">Leave Chat</button>
</form>

<script src="/js/chatroom.js"></script>


<?php include __DIR__ . '/partials/footer.php'; ?>
