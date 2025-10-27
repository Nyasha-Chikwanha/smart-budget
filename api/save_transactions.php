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
    $transactions = $input['transactions'] ?? [];
    
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
    
    // Delete existing transactions for this user (simplified sync approach)
    // In a real app, you might want more sophisticated conflict resolution
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Insert new transactions
    $insertStmt = $pdo->prepare("
        INSERT INTO transactions (user_id, type, category, amount, description, date, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $savedCount = 0;
    foreach ($transactions as $transaction) {
        // Validate required fields
        if (!isset($transaction['type'], $transaction['category'], $transaction['amount'], $transaction['date'])) {
            continue; // Skip invalid transactions
        }
        
        $insertStmt->execute([
            $user_id,
            $transaction['type'],
            $transaction['category'],
            $transaction['amount'],
            $transaction['description'] ?? '',
            $transaction['date'],
            $transaction['createdAt'] ?? date('Y-m-d H:i:s')
        ]);
        
        $savedCount++;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully saved $savedCount transactions",
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