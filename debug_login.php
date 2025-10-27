<?php
// debug_login.php - See what data login is receiving
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log everything for debugging
error_log("=== LOGIN DEBUG ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
error_log("POST data: " . print_r($_POST, true));
error_log("Raw input: " . file_get_contents('php://input'));

$response = [
    'success' => true,
    'message' => 'Debug information',
    'received_method' => $_SERVER['REQUEST_METHOD'],
    'received_content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set',
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input')
];

echo json_encode($response);
?>