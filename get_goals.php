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
    
    // Get goals for the user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            name,
            target_amount,
            current_amount,
            deadline,
            status,
            created_at,
            updated_at
        FROM goals 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $goals = $stmt->fetchAll();
    
    // Format the response
    $formattedGoals = array_map(function($goal) {
        return [
            'id' => (int)$goal['id'],
            'user_id' => (int)$goal['user_id'],
            'name' => $goal['name'],
            'target_amount' => (float)$goal['target_amount'],
            'current_amount' => (float)$goal['current_amount'],
            'deadline' => $goal['deadline'],
            'status' => $goal['status'],
            'created_at' => $goal['created_at'],
            'updated_at' => $goal['updated_at']
        ];
    }, $goals);
    
    echo json_encode($formattedGoals);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>