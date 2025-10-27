<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = 'localhost';
$dbname = 'smart_budget';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get action from query string or input
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';

switch ($action) {
    case 'add_transaction':
        addTransaction($pdo, $input);
        break;
    case 'get_transactions':
        getTransactions($pdo, $input);
        break;
    case 'add_goal':
        addGoal($pdo, $input);
        break;
    case 'get_goals':
        getGoals($pdo, $input);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function addTransaction($pdo, $data) {
    $user_id = $data['user_id'] ?? null;
    $type = $data['type'] ?? '';
    $category = $data['category'] ?? '';
    $amount = $data['amount'] ?? 0;
    $description = $data['description'] ?? '';
    
    if (!$user_id || !$type || !$category || !$amount) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    try {
        $table = $type === 'income' ? 'income' : 'expenses';
        $stmt = $pdo->prepare("INSERT INTO $table (user_id, amount, category, description, date) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->execute([$user_id, $amount, $category, $description]);
        
        echo json_encode(['success' => true, 'message' => 'Transaction added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getTransactions($pdo, $data) {
    $user_id = $data['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    try {
        // Get income
        $income_stmt = $pdo->prepare("SELECT * FROM income WHERE user_id = ? ORDER BY date DESC");
        $income_stmt->execute([$user_id]);
        $income = $income_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get expenses
        $expense_stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC");
        $expense_stmt->execute([$user_id]);
        $expenses = $expense_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'income' => $income, 'expenses' => $expenses]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function addGoal($pdo, $data) {
    $user_id = $data['user_id'] ?? null;
    $name = $data['name'] ?? '';
    $target_amount = $data['target_amount'] ?? 0;
    $deadline = $data['deadline'] ?? null;
    
    if (!$user_id || !$name || !$target_amount) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO goals (user_id, name, target_amount, deadline) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $target_amount, $deadline]);
        
        echo json_encode(['success' => true, 'message' => 'Goal added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getGoals($pdo, $data) {
    $user_id = $data['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'goals' => $goals]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>