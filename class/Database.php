<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Class Database
 *
 * Handles the database connection using PDO.
 * It reads the database credentials from the config file.
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $conn;

    /**
     * Establishes a database connection.
     *
     * @return PDO|null The PDO connection object or null on failure.
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // In a real application, this should be logged, not echoed.
            error_log("Database Connection Error: " . $exception->getMessage());
            return null;
        }

        return $this->conn;
    }
}
?>
