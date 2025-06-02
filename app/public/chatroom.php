<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$title = "Chatroom";
require __DIR__ . '/../views/chatroom_view.php';
