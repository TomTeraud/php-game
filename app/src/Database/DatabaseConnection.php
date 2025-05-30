<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use Exception;

class DatabaseConnection
{
    private static ?self $instance = null;
    private PDO $pdo;
    private string $charset = 'utf8mb4';

    private const OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    private function __construct()
    {
        $host = $_ENV['MYSQL_HOST'] ?? 'localhost';
        $db   = $_ENV['MYSQL_DATABASE'] ?? '';
        $user = $_ENV['MYSQL_USER'] ?? '';
        $pass = $_ENV['MYSQL_PASSWORD'] ?? '';
        $port = $_ENV['MYSQL_PORT'] ?? '3306';

        if (!$db || !$user) {
            throw new PDOException("Missing DB name or user in environment variables.");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$this->charset}";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, self::OPTIONS);
        } catch (PDOException $e) {
            throw new PDOException("Connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new Exception("Cannot unserialize a singleton.");
    }
}
