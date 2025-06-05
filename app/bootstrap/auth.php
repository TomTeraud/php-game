<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Auth\TokenAuthenticator;

$user = TokenAuthenticator::authenticate();
