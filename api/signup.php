<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smartbudget_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "Database connection failed."]);
  exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$fullname = $conn->real_escape_string($data['fullname']);
$email = $conn->real_escape_string($data['email']);
$pass = password_hash($data['password'], PASSWORD_BCRYPT);

$check = $conn->query("SELECT * FROM users WHERE email='$email'");
if ($check->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "Email already registered."]);
  exit();
}

$sql = "INSERT INTO users (fullname, email, password) VALUES ('$fullname', '$email', '$pass')";
if ($conn->query($sql) === TRUE) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Error saving data."]);
}

$conn->close();
?>
