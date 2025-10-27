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
    $income_data = $input['income'] ?? [];
    
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
    
    // Insert income
    $insertStmt = $pdo->prepare("
        INSERT INTO income (user_id, source, amount, description, date, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $savedCount = 0;
    foreach ($income_data as $income) {
        // Validate required fields
        if (!isset($income['source'], $income['amount'], $income['date'])) {
            continue; // Skip invalid income records
        }
        
        $insertStmt->execute([
            $user_id,
            $income['source'],
            $income['amount'],
            $income['description'] ?? '',
            $income['date'],
            $income['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        $savedCount++;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully saved $savedCount income records",
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