<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = 'localhost';
$dbname = 'smart_budget';
$username = 'root';  // Default XAMPP username
$password = '';      // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get user dashboard data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    try {
        // Get user information
        $user_stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        // Get income data
        $income_stmt = $pdo->prepare("SELECT * FROM income WHERE user_id = ? ORDER BY date DESC");
        $income_stmt->execute([$user_id]);
        $income = $income_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get expense data
        $expense_stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC");
        $expense_stmt->execute([$user_id]);
        $expenses = $expense_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get goals data
        $goals_stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? ORDER BY created_at DESC");
        $goals_stmt->execute([$user_id]);
        $goals = $goals_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate statistics
        $total_income = array_sum(array_column($income, 'amount'));
        $total_expenses = array_sum(array_column($expenses, 'amount'));
        $net_savings = $total_income - $total_expenses;
        $active_goals = count($goals);

        // Prepare recent activity (last 5 items)
        $recent_activity = [];
        
        // Add income activities
        foreach (array_slice($income, 0, 5) as $item) {
            $recent_activity[] = [
                'type' => 'income',
                'description' => $item['description'] ?? 'Income',
                'amount' => $item['amount'],
                'date' => $item['date'],
                'category' => 'Income'
            ];
        }
        
        // Add expense activities
        foreach (array_slice($expenses, 0, 5) as $item) {
            $recent_activity[] = [
                'type' => 'expense',
                'description' => $item['description'] ?? $item['category'] ?? 'Expense',
                'amount' => $item['amount'],
                'date' => $item['date'],
                'category' => $item['category']
            ];
        }
        
        // Add goal activities
        foreach (array_slice($goals, 0, 5) as $item) {
            $recent_activity[] = [
                'type' => 'goal',
                'description' => $item['name'] ?? 'Goal',
                'amount' => $item['target_amount'],
                'date' => $item['created_at'],
                'category' => 'Goal'
            ];
        }

        // Sort by date and get top 5
        usort($recent_activity, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        $recent_activity = array_slice($recent_activity, 0, 5);

        echo json_encode([
            'success' => true,
            'user' => $user,
            'stats' => [
                'total_income' => $total_income,
                'total_expenses' => $total_expenses,
                'net_savings' => $net_savings,
                'active_goals' => $active_goals
            ],
            'recent_activity' => $recent_activity,
            'income' => $income,
            'expenses' => $expenses,
            'goals' => $goals
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>