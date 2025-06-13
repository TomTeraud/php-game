<?php
require_once __DIR__ . '/../bootstrap/app.php';

if (!$user) {
    header("Location: index.php");
    exit;
}

$title = "Chatroom";
require __DIR__ . '/../views/chatroom_view.php';
