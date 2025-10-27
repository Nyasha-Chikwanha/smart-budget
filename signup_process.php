<?php
// Enable error reporting for debugging
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
    // Database configuration - UPDATE THESE FOR YOUR XAMPP SETUP
    $host = 'localhost';
    $dbname = 'smart_budget';
    $username = 'root';
    $password = ''; // Default XAMPP password is empty

    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Get POST data - handle both JSON and form data
    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        $input = json_decode(file_get_contents('php://input'), true);
        $fullname = trim($input['fullname'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
    } else {
        // Form data
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
    }

    // Log received data for debugging
    error_log("Received signup data - Name: $fullname, Email: $email");

    // Validate input
    if (