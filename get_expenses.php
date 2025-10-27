<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Get user_id from query parameters
    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        exit;
    }
    
    // Validate user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Get expenses for the user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            category,
            amount,
            description,
            date,
            created_at
        FROM expenses 
        WHERE user_id = ? 
        ORDER BY date DESC, created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $expenses = $stmt->fetchAll();
    
    // Format the response
    $formattedExpenses = array_map(function($expense) {
        return [
            'id' => (int)$expense['id'],
            'user_id' => (int)$expense['user_id'],
            'category' => $expense['category'],
            'amount' => (float)$expense['amount'],
            'description' => $expense['description'],
            'date' => $expense['date'],
            'created_at' => $expense['created_at'],
            'type' => 'expense' // Add type for compatibility
        ];
    }, $expenses);
    
    echo json_encode($formattedExpenses);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>