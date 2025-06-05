<?php

setcookie('token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
header('Location: /index.php');
exit;
