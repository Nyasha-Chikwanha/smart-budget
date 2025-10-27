<?php
// setup_database.php - Force recreate the table with correct structure
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Database Structure</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Fixing Database Structure</h1>
";

try {
    echo "<p>Recreating database structure...</p>";
    
    // This will drop and recreate the table
    if (initializeDatabase()) {
        echo "<p class='success'>✓ Database structure recreated successfully</p>";
        
        // Test the connection
        $database = new Database();
        $pdo = $database->getConnection();
        
        echo "<p class='success'>✓ Connection test successful</p>";
        echo "<p class='success'>🎉 Database is now ready with correct structure!</p>";
        echo "<p><a href='signup.html'>Test Signup Now</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>