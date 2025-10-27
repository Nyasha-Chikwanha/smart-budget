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
    $expenses = $input['expenses'] ?? [];
    
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
    
    // Insert expenses
    $insertStmt = $pdo->prepare("
        INSERT INTO expenses (user_id, category, amount, description, date, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $savedCount = 0;
    foreach ($expenses as $expense) {
        // Validate required fields
        if (!isset($expense['category'], $expense['amount'], $expense['date'])) {
            continue; // Skip invalid expenses
        }
        
        $insertStmt->execute([
            $user_id,
            $expense['category'],
            $expense['amount'],
            $expense['description'] ?? '',
            $expense['date'],
            $expense['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        $savedCount++;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully saved $savedCount expenses",
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