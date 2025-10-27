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
    
    // Get budget categories for the user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            name,
            budget_amount,
            spent_amount,
            created_at,
            updated_at
        FROM budget_categories 
        WHERE user_id = ? 
        ORDER BY name ASC
    ");
    
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll();
    
    // Format the response
    $formattedCategories = array_map(function($category) {
        return [
            'id' => (int)$category['id'],
            'user_id' => (int)$category['user_id'],
            'name' => $category['name'],
            'budget_amount' => (float)$category['budget_amount'],
            'spent_amount' => (float)$category['spent_amount'],
            'created_at' => $category['created_at'],
            'updated_at' => $category['updated_at']
        ];
    }, $categories);
    
    echo json_encode($formattedCategories);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>