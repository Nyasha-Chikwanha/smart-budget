<?php
// test_connection.php - Test if data is being received
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log everything
error_log("=== TEST CONNECTION ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
error_log("POST data: " . print_r($_POST, true));
error_log("Raw input: " . file_get_contents('php://input'));

$response = [
    'success' => true,
    'message' => 'Connection test successful',
    'received_method' => $_SERVER['REQUEST_METHOD'],
    'received_content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set',
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input')
];

echo json_encode($response);
?>