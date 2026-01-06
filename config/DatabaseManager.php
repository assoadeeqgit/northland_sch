<?php

/**
 * Database Connection Manager - Singleton Pattern
 * Ensures only ONE database connection is created per request
 * Reduces overhead from multiple connections
 */
class DatabaseManager
{
    private static $instance = null;
    private $connection = null;
    
    private $host = '127.0.0.1';
    private $db_name = 'northland_schools_kano';
    private $username = 'root';
    private $password = 'A@123456.Aaa';

    /**
     * Private constructor - prevents direct instantiation
     */
    private function __construct()
    {
        try {
            $this->connection = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
                    PDO::ATTR_PERSISTENT => true, // Reuse connections when possible
                ]
            );
        } catch (PDOException $exception) {
            error_log("Database Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed. Please contact administrator.");
        }
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
