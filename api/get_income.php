<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$user_id = $_GET['user_id'] ?? '';

if ($user_id) {
    // In production, query your database
    // For demo, return empty array or mock data
    echo json_encode([]);
} else {
    echo json_encode([]);
}
?>