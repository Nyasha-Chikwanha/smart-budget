<?php
require_once 'config.php';

try {
    initializeDatabase();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database initialized successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Initialization failed: ' . $e->getMessage()]);
}
?>