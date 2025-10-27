<?php
// debug_database.php - Let's see exactly what's in your database
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';

echo "<h1>Database Debug Information</h1>";

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'smart_budget'");
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        echo "<p style='color: red'><strong>❌ Database 'smart_budget' does NOT exist!</strong></p>";
        exit;
    }
    
    echo "<p style='color: green'><strong>✅ Database 'smart_budget' exists</strong></p>";
    
    $pdo->exec("USE smart_budget");
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p style='color: red'><strong>❌ No tables found in database!</strong></p>";
        exit;
    }
    
    echo "<h2>Tables found:</h2>";
    foreach ($tables as $table) {
        echo "<h3>Table: <strong>$table</strong></h3>";
        
        // Show exact table structure
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show some sample data
        echo "<h4>Sample Data (first 5 rows):</h4>";
        $stmt = $pdo->query("SELECT * FROM $table LIMIT 5");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($data)) {
            echo "<p>No data in table</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'>";
            foreach (array_keys($data[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red'><strong>Error: " . $e->getMessage() . "</strong></p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
}
?>