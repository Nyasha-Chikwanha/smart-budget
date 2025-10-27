<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'smart_budget';
$port = 3306;

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

$user_id = $conn->real_escape_string($data['user_id']);
$budget_categories = $data['budget_categories'] ?? [];
$last_sync = $conn->real_escape_string($data['last_sync'] ?? date('Y-m-d H:i:s'));

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Update user last sync
    $user_sql = "INSERT INTO users (user_id, last_sync, updated_at) 
                 VALUES (?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE last_sync = VALUES(last_sync), updated_at = NOW()";
    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("ss", $user_id, $last_sync);
    $stmt->execute();
    $stmt->close();
    
    // Save budget categories if provided
    $categories_count = 0;
    if (!empty($budget_categories)) {
        // Delete existing categories for this user
        $delete_sql = "DELETE FROM budget_categories WHERE user_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Insert new categories
        $insert_sql = "INSERT INTO budget_categories (user_id, category_id, name, budget, spent, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        
        foreach ($budget_categories as $category) {
            $category_id = $conn->real_escape_string($category['id']);
            $name = $conn->real_escape_string($category['name']);
            $budget = floatval($category['budget']);
            $spent = floatval($category['spent']);
            $created_at = $conn->real_escape_string($category['createdAt'] ?? date('Y-m-d H:i:s'));
            
            $stmt->bind_param("sssddss", $user_id, $category_id, $name, $budget, $spent, $created_at);
            
            if ($stmt->execute()) {
                $categories_count++;
            }
        }
        
        $stmt->close();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "User data saved successfully",
        'categories_saved' => $categories_count,
        'last_sync' => $last_sync
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>