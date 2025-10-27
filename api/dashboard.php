<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get dashboard data
    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    try {
        // Calculate totals from transactions
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expenses,
                COUNT(*) as transaction_count
            FROM transactions 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $transactionData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Count goals
        $stmt = $pdo->prepare("SELECT COUNT(*) as goals_count FROM goals WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $goalsData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $dashboardData = [
            'totalIncome' => floatval($transactionData['total_income']),
            'totalExpenses' => floatval($transactionData['total_expenses']),
            'balance' => floatval($transactionData['total_income']) - floatval($transactionData['total_expenses']),
            'transactionCount' => intval($transactionData['transaction_count']),
            'goalsCount' => intval($goalsData['goals_count']),
            'lastUpdated' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($dashboardData);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save dashboard data (if you want to store calculated summaries)
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id'])) {
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    try {
        // You can create a dashboard_summary table if you want to store this data
        // For now, we'll just return success
        echo json_encode(['success' => true, 'message' => 'Dashboard data processed']);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>