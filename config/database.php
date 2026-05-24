<?php

declare(strict_types=1);

/**
 * DatabaseConfig – Singleton PDO connection manager (no namespace).
 *
 * Usage:
 *   $pdo = DatabaseConfig::getInstance();
 */
class DatabaseConfig
{
    private static ?PDO $instance = null;

    /**
     * Private constructor prevents direct instantiation.
     */
    private function __construct() {}

    /**
     * Prevent cloning of the singleton.
     */
    private function __clone() {}

    /**
     * Return the shared PDO instance, creating it on first call.
     *
     * @throws \RuntimeException when the connection cannot be established.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    /**
     * Build and return a new PDO connection.
     */
    private static function createConnection(): PDO
    {
        $driver   = $_ENV['DB_DRIVER']   ?? 'mysql';
        $host     = $_ENV['DB_HOST']     ?? 'localhost';
        $port     = $_ENV['DB_PORT']     ?? ($driver === 'pgsql' ? '5432' : '3306');
        $database = $_ENV['DB_DATABASE'] ?? 'research_management';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        if ($driver === 'pgsql') {
            $sslmode = $_ENV['DB_SSLMODE'] ?? 'require';
            $dsn     = "pgsql:host={$host};port={$port};dbname={$database};sslmode={$sslmode}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];
        }

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
            return $pdo;
        } catch (PDOException $e) {
            // In production, avoid leaking connection details.
            $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($isDebug) {
                $message = 'Database connection failed: ' . $e->getMessage();
            } else {
                $message = 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ';
                // Log the real error server-side
                error_log('[DatabaseConfig] PDOException: ' . $e->getMessage());
            }

            throw new \RuntimeException($message, (int) $e->getCode(), $e);
        }
    }

    /**
     * Close the connection (useful in long-running scripts or tests).
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}
