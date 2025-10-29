<?php
namespace App\Helper;

use PDO;
use PDOException;

class DatabaseHelper
{
    private static ?PDO $pdo = null;

    /**
     * Get a shared PDO connection
     */
    public static function getConnection(): PDO
    {
        if (!isset(self::$pdo)) {
            $dsn  = $_ENV['DB_DSN']  ?? '';
            $user = $_ENV['DB_USER'] ?? '';
            $pass = $_ENV['DB_PASS'] ?? '';

            if (empty($dsn) || empty($user)) {
                throw new \RuntimeException("Database configuration is incomplete.");
            }

            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (\PDOException $e) {
                throw new \RuntimeException("Database connection failed: " . $e->getMessage(), 500, $e);
            }
        }

        return self::$pdo;
    }

    /**
     * Start a transaction
     */
    public static function beginTransaction(): void
    {
        $pdo = self::getConnection();
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }
    }

    /**
     * Commit the current transaction
     */
    public static function commit(): void
    {
        $pdo = self::getConnection();
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    }

    /**
     * Rollback the current transaction
     */
    public static function rollBack(): void
    {
        $pdo = self::getConnection();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    /**
     * Manually disconnect the database (optional)
     */
    public static function disconnect(): void
    {
        self::$pdo = null;
    }
}
