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
    
    // Get transactions for the user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            type,
            category,
            amount,
            description,
            date,
            created_at
        FROM transactions 
        WHERE user_id = ? 
        ORDER BY date DESC, created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll();
    
    // Format the response
    $formattedTransactions = array_map(function($transaction) {
        return [
            'id' => (int)$transaction['id'],
            'user_id' => (int)$transaction['user_id'],
            'type' => $transaction['type'],
            'category' => $transaction['category'],
            'amount' => (float)$transaction['amount'],
            'description' => $transaction['description'],
            'date' => $transaction['date'],
            'createdAt' => $transaction['created_at']
        ];
    }, $transactions);
    
    echo json_encode($formattedTransactions);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>