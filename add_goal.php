<?php
// add_goal.php - Add goal for specific user only with strict validation
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$response = ['success' => false, 'message' => ''];

try {
    // Get data from POST
    $user_id = $_POST['user_id'] ?? '';
    $goal_name = $_POST['goal_name'] ?? '';
    $target_amount = $_POST['target_amount'] ?? '';
    $saved_amount = $_POST['saved_amount'] ?? 0;
    $deadline = $_POST['deadline'] ?? '';
    
    // STRICT user validation
    if (empty($user_id) || !is_numeric($user_id)) {
        throw new Exception('Authentication required. Please log in again.');
    }
    $user_id = (int)$user_id;

    // Validate input
    if (empty($goal_name) || empty($target_amount) || empty($deadline)) {
        throw new Exception('Goal name, target amount, and deadline are required');
    }
    
    if (!is_numeric($target_amount) || $target_amount <= 0) {
        throw new Exception('Target amount must be a positive number');
    }
    
    if (!is_numeric($saved_amount) || $saved_amount < 0) {
        throw new Exception('Saved amount must be a non-negative number');
    }
    
    if ($saved_amount > $target_amount) {
        throw new Exception('Saved amount cannot exceed target amount');
    }
    
    // Validate date
    $deadline_date = DateTime::createFromFormat('Y-m-d', $deadline);
    if (!$deadline_date || $deadline_date->format('Y-m-d') !== $deadline) {
        throw new Exception('Invalid deadline date format. Use YYYY-MM-DD');
    }
    
    // Connect to database
    $pdo = new PDO('mysql:host=localhost;dbname=smart_budget', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verify user exists
    $userCheck = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $userCheck->execute([$user_id]);
    if (!$userCheck->fetch()) {
        throw new Exception('User account not found. Please log in again.');
    }
    
    // Insert goal only for this specific user
    $stmt = $pdo->prepare("INSERT INTO goals (user_id, goal_name, target_amount, saved_amount, deadline, status) VALUES (?, ?, ?, ?, ?, 'active')");
    
    if ($stmt->execute([$user_id, $goal_name, $target_amount, $saved_amount, $deadline])) {
        $response['success'] = true;
        $response['message'] = 'Savings goal created successfully in your account!';
        $response['goal_id'] = $pdo->lastInsertId();
        
        error_log("Goal created for user_id: $user_id - Goal: $goal_name, Target: $target_amount");
    } else {
        throw new Exception('Failed to create savings goal. Please try again.');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Goal creation error for user_id {$user_id}: " . $e->getMessage());
}

echo json_encode($response);
?>