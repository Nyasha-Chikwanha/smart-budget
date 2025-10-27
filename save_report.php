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
    $report_type = $input['report_type'] ?? null;
    $report_data = $input['report_data'] ?? null;
    
    if (!$user_id || !$report_type || !$report_data) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID, report type, and report data are required']);
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
    
    // Insert report
    $insertStmt = $pdo->prepare("
        INSERT INTO reports (user_id, report_type, report_data, period_start, period_end)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $insertStmt->execute([
        $user_id,
        $report_type,
        json_encode($report_data),
        $input['period_start'] ?? null,
        $input['period_end'] ?? null
    ]);
    
    $report_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Report saved successfully',
        'report_id' => (int)$report_id
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>