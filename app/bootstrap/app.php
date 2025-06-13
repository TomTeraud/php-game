<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

try {
    // Load environment variables from the project root (mounted as /var/www/html/ in Docker)
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // Log the warning. For critical apps, you might want to exit(1) here.
    error_log("Warning: .env file(s) not found or could not be loaded: " . $e->getMessage());
}

// You can add other global setup here:
// - Error handling configuration
// - Session starting (if not done elsewhere)
// - Global constants or configurations
// - Potentially database connection setup (though your current getInstance() is fine)

// You might also put your auth bootstrapping here if it's truly global:
require_once dirname(__DIR__) . '/bootstrap/auth.php';
?>