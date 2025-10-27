<?php
// signup.php - Handles both JSON and form data
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$response = ['success' => false, 'message' => ''];

try {
    // Debug: Log the request
    error_log("Signup request received. Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
    
    // Get raw input for debugging
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    // Determine how to get the data
    $fullname = '';
    $email = '';
    $password = '';
    
    if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        // JSON data
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data: ' . json_last_error_msg());
        }
        $fullname = $input['fullname'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
    } else {
        // Form data (application/x-www-form-urlencoded or multipart/form-data)
        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
    }
    
    error_log("Extracted data - Name: $fullname, Email: $email, Password: " . (empty($password) ? 'empty' : 'provided'));
    
    // Validate input
    if (empty($fullname) || empty($email) || empty($password)) {
        throw new Exception('All fields are required. Received - Name: ' . (empty($fullname) ? 'empty' : 'ok') . ', Email: ' . (empty($email) ? 'empty' : 'ok') . ', Password: ' . (empty($password) ? 'empty' : 'ok'));
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    // Connect to database
    $pdo = new PDO('mysql:host=localhost;dbname=smart_budget', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user already exists
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Email already registered. Please use a different email or log in.');
    }
    
    // Insert new user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $insertStmt = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    
    if ($insertStmt->execute([$fullname, $email, $hashedPassword])) {
        $user_id = $pdo->lastInsertId();
        
        // Verify the user was actually inserted
        $verifyStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $verifyStmt->execute([$user_id]);
        
        if ($verifyStmt->fetch()) {
            $response['success'] = true;
            $response['message'] = 'Registration successful!';
            $response['user_id'] = $user_id;  // ← THIS LINE HAS BEEN ADDED
            error_log("User registered successfully: " . $email . " (ID: " . $user_id . ")");
        } else {
            throw new Exception('Registration failed - user not found after insertion');
        }
    } else {
        throw new Exception('Failed to create user account');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Registration error: " . $e->getMessage());
}

echo json_encode($response);
?>