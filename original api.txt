<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

// Connect to MySQL (XAMPP default settings)
$servername = "localhost";
$username = "root"; // default XAMPP username
$password = "";     // default XAMPP password is empty
$dbname = "smartbudget_db"; // make sure you create this DB in phpMyAdmin

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "Database connection failed."]);
  exit();
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$fullname = $conn->real_escape_string($data['fullname']);
$email = $conn->real_escape_string($data['email']);
$pass = password_hash($data['password'], PASSWORD_BCRYPT);

// Check if email already exists
$check = $conn->query("SELECT * FROM users WHERE email='$email'");
if ($check->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "Email already registered."]);
  exit();
}

// Insert into users table
$sql = "INSERT INTO users (fullname, email, password) VALUES ('$fullname', '$email', '$pass')";

if ($conn->query($sql) === TRUE) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Error saving data."]);
}

$conn->close();
?>
