<?php
// add_income.php - Add income and return updated dashboard data
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$response = ['success' => false, 'message' => ''];

try {
    // Get data from POST
    $user_id = $_POST['user_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $source = $_POST['source'] ?? '';
    $description = $_POST['description'] ?? '';
    $date_added = $_POST['date_added'] ?? date('Y-m-d');
    
    // STRICT user validation
    if (empty($user_id) || !is_numeric($user_id)) {
        $response['message'] = 'Authentication required. Please log in again.';
        echo json_encode($response);
        exit;
    }
    $user_id = (int)$user_id;

    // Validate input
    if (empty($amount) || empty($source)) {
        throw new Exception('Amount and source are required');
    }
    
    if (!is_numeric($amount) || $amount <= 0) {
        throw new Exception('Amount must be a positive number');
    }
    
    // Connect to database
    $pdo = new PDO('mysql:host=localhost;dbname=smart_budget', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verify user exists
    $userCheck = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $userCheck->execute([$user_id]);
    if (!$userCheck->fetch()) {
        throw new Exception('User account not found. Please log in again.');
    }
    
    // Insert income
    $stmt = $pdo->prepare("INSERT INTO incomes (user_id, amount, source, description, date_added) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$user_id, $amount, $source, $description, $date_added])) {
        $response['success'] = true;
        $response['message'] = 'Income added successfully!';
        $response['income_id'] = $pdo->lastInsertId();
        
        // Get updated dashboard data to return
        $updatedData = getUpdatedDashboardData($pdo, $user_id);
        $response['dashboard_data'] = $updatedData;
        
        error_log("Income added for user_id: $user_id - Source: $source, Amount: $amount");
    } else {
        throw new Exception('Failed to add income. Please try again.');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Income creation error for user_id {$user_id}: " . $e->getMessage());
}

echo json_encode($response);

// Function to get updated dashboard data (same as in add_expense.php)
function getUpdatedDashboardData($pdo, $user_id) {
    $data = [];
    
    // Get updated financial summary
    $summaryStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as total_income,
            (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?) as total_expenses,
            COALESCE(SUM(amount), 0) - (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?) as net_balance
        FROM incomes 
        WHERE user_id = ?
    ");
    $summaryStmt->execute([$user_id, $user_id, $user_id]);
    $data['summary'] = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent expenses (last 10)
    $expensesStmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date_spent DESC LIMIT 10");
    $expensesStmt->execute([$user_id]);
    $data['expenses'] = $expensesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent income (last 5)
    $incomeStmt = $pdo->prepare("SELECT * FROM incomes WHERE user_id = ? ORDER BY date_added DESC LIMIT 5");
    $incomeStmt->execute([$user_id]);
    $data['income'] = $incomeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get goals
    $goalsStmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ?");
    $goalsStmt->execute([$user_id]);
    $data['goals'] = $goalsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $data;
}
?>