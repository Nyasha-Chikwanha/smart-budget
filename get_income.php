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
    
    // Get income for the user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            source,
            amount,
            description,
            date,
            created_at
        FROM income 
        WHERE user_id = ? 
        ORDER BY date DESC, created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $income = $stmt->fetchAll();
    
    // Format the response
    $formattedIncome = array_map(function($item) {
        return [
            'id' => (int)$item['id'],
            'user_id' => (int)$item['user_id'],
            'source' => $item['source'],
            'amount' => (float)$item['amount'],
            'description' => $item['description'],
            'date' => $item['date'],
            'created_at' => $item['created_at'],
            'type' => 'income' // Add type for compatibility
        ];
    }, $income);
    
    echo json_encode($formattedIncome);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>