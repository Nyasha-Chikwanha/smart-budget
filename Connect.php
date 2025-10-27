<?php
// connect.php - Database connection with better error handling
class Database {
    private $host = 'localhost';
    private $dbname = 'smart_budget';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->dbname, 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // More specific error handling
            if ($e->getCode() == '42S02') {
                throw new Exception("Table doesn't exist. Please run setup_database.php first.");
            } elseif ($e->getCode() == '42S22') {
                throw new Exception("Column not found in table. Database structure mismatch.");
            } else {
                throw new Exception("Connection failed: " . $e->getMessage());
            }
        }
        
        return $this->conn;
    }
}

// Function to safely check and create table structure
function initializeDatabase() {
    $host = 'localhost';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS smart_budget");
        $pdo->exec("USE smart_budget");

        // Drop table if it exists (to ensure clean structure)
        $pdo->exec("DROP TABLE IF EXISTS users");

        // Create users table with exact structure we need
        $pdo->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fullname VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                income DECIMAL(10,2) NULL,
                currency VARCHAR(10) DEFAULT 'USD',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL
            )
        ");

        return true;
    } catch (PDOException $e) {
        throw new Exception("Database initialization failed: " . $e->getMessage());
    }
}
?>