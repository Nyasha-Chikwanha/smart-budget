<?php
// check_users.php - Check all users in database
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Checking Registered Users</h1>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=smart_budget', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all users
    $stmt = $pdo->query("SELECT user_id, fullname, email, created_at FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Users in database:</h2>";
    
    if (empty($users)) {
        echo "<p style='color: red;'>❌ No users found in database!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>User ID</th><th>Full Name</th><th>Email</th><th>Created At</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['fullname']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✅ Found " . count($users) . " user(s) in database</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>