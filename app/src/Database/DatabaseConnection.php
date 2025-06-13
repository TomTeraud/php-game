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
        $host = getenv('MYSQL_HOST') ?? 'localhost';
        $db   = getenv('MYSQL_DATABASE') ?? '';
        $user = getenv('MYSQL_USER') ?? '';
        $pass = getenv('MYSQL_PASSWORD') ?? '';
        $port = getenv('MYSQL_PORT') ?? '3306';

        if (!$db || !$user) {
            throw new PDOException("Missing DB name or user in environment variables.");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$this->charset}";

        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $attempt++;
                $this->pdo = new PDO($dsn, $user, $pass, self::OPTIONS);
                error_log("DB connection attempt {$attempt}/{$maxAttempts} succeeded: ");
                break; // success
            } catch (PDOException $e) {
                if ($attempt === $maxAttempts) {
                    throw new PDOException("Connection failed after {$maxAttempts} attempts: " . $e->getMessage(), (int)$e->getCode(), $e);
                }
                error_log("DB connection attempt {$attempt}/{$maxAttempts} failed: " . $e->getMessage());
                sleep(2); // wait before retrying
            }
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
