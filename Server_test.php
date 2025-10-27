<?php
// server_test.php - Test if PHP is working
echo "<h1>Server Test</h1>";
echo "<p>✅ PHP is working correctly</p>";
echo "<p>✅ Server time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>✅ File is being executed</p>";

// Test database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=smart_budget', 'root', '');
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
?>