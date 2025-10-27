<?php
// check_data_separation.php - Check if data is properly separated
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Data Separation Check</h1>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=smart_budget', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check all users and their data
    $usersStmt = $pdo->query("SELECT user_id, fullname, email FROM users");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "<h2>User: {$user['fullname']} (ID: {$user['user_id']})</h2>";
        
        // Check expenses for this user
        $expensesStmt = $pdo->prepare("SELECT COUNT(*) as count FROM expenses WHERE user_id = ?");
        $expensesStmt->execute([$user['user_id']]);
        $expenseCount = $expensesStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check goals for this user
        $goalsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM goals WHERE user_id = ?");
        $goalsStmt->execute([$user['user_id']]);
        $goalCount = $goalsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Expenses: {$expenseCount['count']} | Goals: {$goalCount['count']}</p>";
        
        // Show sample data
        if ($expenseCount['count'] > 0) {
            $sampleExpenses = $pdo->prepare("SELECT category, amount FROM expenses WHERE user_id = ? LIMIT 3");
            $sampleExpenses->execute([$user['user_id']]);
            $expenses = $sampleExpenses->fetchAll(PDO::FETCH_ASSOC);
            echo "Sample expenses: " . implode(', ', array_map(function($e) {
                return "{$e['category']}: \${$e['amount']}";
            }, $expenses)) . "<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>