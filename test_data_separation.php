<?php
// test_data_separation.php - Test that users only see their own data
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Data Separation</h1>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=smart_budget', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all users
    $users = $pdo->query("SELECT user_id, fullname, email FROM users")->fetchAll();
    
    foreach ($users as $user) {
        echo "<h2>Testing User: {$user['fullname']} (ID: {$user['user_id']})</h2>";
        
        // Test expenses
        $expenses = $pdo->prepare("SELECT COUNT(*) as count FROM expenses WHERE user_id = ?");
        $expenses->execute([$user['user_id']]);
        $expenseCount = $expenses->fetch()['count'];
        
        // Test goals
        $goals = $pdo->prepare("SELECT COUNT(*) as count FROM goals WHERE user_id = ?");
        $goals->execute([$user['user_id']]);
        $goalCount = $goals->fetch()['count'];
        
        echo "<p>This user should see: {$expenseCount} expenses and {$goalCount} goals</p>";
        
        if ($expenseCount > 0) {
            $sample = $pdo->prepare("SELECT category, amount FROM expenses WHERE user_id = ? LIMIT 2");
            $sample->execute([$user['user_id']]);
            $samples = $sample->fetchAll();
            echo "Sample expenses: ";
            foreach ($samples as $s) {
                echo "{$s['category']}: \${$s['amount']} ";
            }
            echo "<br>";
        }
    }
    
    echo "<p style='color: green; font-weight: bold;'>✅ Data separation test completed</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>