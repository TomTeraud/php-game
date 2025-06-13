<?php

// Set cookie to expire immediately (or in the past)
// Make sure path and domain match how the cookie was set during login
setcookie('token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);

header('Location: /');
exit;
