<?php
// signup.php
// This script securely handles signup requests from your Smart Budget signup page

// -----------------------------
// 1. BASIC SETTINGS
// -----------------------------
header('Content-Type: application/json');

// For local testing only — allow your HTML to call this PHP
// Remove or restrict in production
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit; // Preflight request
}

// -----------------------------
// 2. CONNECT TO DATABASE
// -----------------------------
$host = '127.0.0.1';      // your XAMPP server
$db   = 'smartbudget_db'; // database name
$user = 'root';           // default XAMPP user
$pass = '';               // default password (empty unless you changed it)
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $mysqli->connect_error
    ]);
    exit;
}

$mysqli->set_charset($charset);

// -----------------------------
// 3. RECEIVE AND VALIDATE INPUT
// -----------------------------
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid JSON input"]);
    exit;
}

$fullname = trim($input['fullname'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($fullname) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email address"]);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters"]);
    exit;
}

// -----------------------------
// 4. HASH PASSWORD
// -----------------------------
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// -----------------------------
// 5. INSERT INTO DATABASE
// -----------------------------
$stmt = $mysqli->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database prepare failed"]);
    exit;
}

$stmt->bind_param('sss', $fullname, $email, $hashedPassword);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(["success" => true, "message" => "User registered successfully"]);
} else {
    if ($mysqli->errno === 1062) {
        // Duplicate email (UNIQUE constraint)
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Email already exists"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $mysqli->error]);
    }
}

$stmt->close();
$mysqli->close();
?>
