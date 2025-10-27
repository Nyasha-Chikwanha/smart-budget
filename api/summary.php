<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['error' => 'User ID required']);
    exit;
}

try {
    // Total income
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_income FROM transactions WHERE user_id = ? AND type = 'income'");
    $stmt->execute([$user_id]);
    $total_income = $stmt->fetchColumn();
    
    // Total expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_expenses FROM transactions WHERE user_id = ? AND type = 'expense'");
    $stmt->execute([$user_id]);
    $total_expenses = $stmt->fetchColumn();
    
    // Total transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_transactions FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_transactions = $stmt->fetchColumn();
    
    echo json_encode([
        'summary' => [
            'total_income' => $total_income,
            'total_expenses' => $total_expenses,
            'total_transactions' => $total_transactions
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>