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
    $goals = $input['goals'] ?? [];
    
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
    
    // Delete existing goals for this user
    $stmt = $pdo->prepare("DELETE FROM goals WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Insert new goals
    $insertStmt = $pdo->prepare("
        INSERT INTO goals (user_id, name, target_amount, current_amount, deadline, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $savedCount = 0;
    foreach ($goals as $goal) {
        // Validate required fields
        if (!isset($goal['name'], $goal['target_amount'])) {
            continue; // Skip invalid goals
        }
        
        // Auto-update status based on progress and deadline
        $current_amount = $goal['current_amount'] ?? 0;
        $target_amount = $goal['target_amount'];
        $deadline = $goal['deadline'] ?? null;
        $status = $goal['status'] ?? 'active';
        
        // Auto-complete if target reached
        if ($current_amount >= $target_amount) {
            $status = 'completed';
        }
        // Auto-fail if deadline passed and not completed
        elseif ($deadline && strtotime($deadline) < time() && $status === 'active') {
            $status = 'failed';
        }
        
        $insertStmt->execute([
            $user_id,
            $goal['name'],
            $target_amount,
            $current_amount,
            $deadline,
            $status,
            $goal['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        $savedCount++;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully saved $savedCount goals",
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