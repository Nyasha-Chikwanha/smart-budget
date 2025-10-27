<?php
// reset_database.php - Completely reset the database
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';

echo "<h1>Complete Database Reset</h1>";

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop database completely
    $pdo->exec("DROP DATABASE IF EXISTS smart_budget");
    echo "<p style='color: green'>✅ Dropped old database</p>";
    
    // Create fresh database
    $pdo->exec("CREATE DATABASE smart_budget");
    echo "<p style='color: green'>✅ Created new database</p>";
    
    $pdo->exec("USE smart_budget");
    
    // Create table with EXACT structure
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
    echo "<p style='color: green'>✅ Created users table with correct structure</p>";
    
    // Test insertion
    $hashedPassword = password_hash('test123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    $stmt->execute(['Test User', 'test@example.com', $hashedPassword]);
    
    echo "<p style='color: green'>✅ Test insertion successful</p>";
    echo "<p style='color: green'><strong>🎉 Database completely reset and ready!</strong></p>";
    echo "<p><a href='signup.html'>Test Signup Now</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red'><strong>Error: " . $e->getMessage() . "</strong></p>";
}
?>