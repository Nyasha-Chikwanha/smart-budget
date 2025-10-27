<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    $user_id = $input['user_id'] ?? null;
    $categories = $input['categories'] ?? [];
    
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
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Delete existing categories for this user
    $stmt = $pdo->prepare("DELETE FROM budget_categories WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Insert new categories
    $insertStmt = $pdo->prepare("
        INSERT INTO budget_categories (user_id, name, budget_amount, spent_amount, created_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $savedCount = 0;
    foreach ($categories as $category) {
        // Validate required fields
        if (!isset($category['name'], $category['budget_amount'])) {
            continue; // Skip invalid categories
        }
        
        $insertStmt->execute([
            $user_id,
            $category['name'],
            $category['budget_amount'],
            $category['spent_amount'] ?? 0,
            $category['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        $savedCount++;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully saved $savedCount budget categories",
        'saved_count' => $savedCount
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>