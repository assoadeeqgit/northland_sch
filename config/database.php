<?php

class Database
{
    private $host = '127.0.0.1';
    private $db_name = 'northland_schools_kano'; // Your database name
    private $username = 'admin';
    private $password = 'A@123456.Aaa'; // Your password
    public $conn;

    public function getConnection(): ?PDO
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            // Log connection errors for debugging, but hide from end-user
            error_log("Connection error: " . $exception->getMessage());
            // You might want to redirect to an error page here in a full application
            echo "<h1>Database Connection Error</h1><p>Please check the connection settings or server logs.</p>";
        }
        return $this->conn;
    }
}
