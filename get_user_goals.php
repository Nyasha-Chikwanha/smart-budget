<?php
// get_user_goals.php - Get ONLY this user's goals
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$response = ['success' => false, 'message' => ''];

try {
    $user_id = $_GET['user_id'] ?? '';
    
    if (empty($user_id) || !is_numeric($user_id)) {
        throw new Exception('Authentication required.');
    }
    
    $user_id = (int)$user_id;
    
    $pdo = new PDO('mysql:host=localhost;dbname=smart_budget', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ONLY get goals for this specific user
    $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? ORDER BY deadline ASC");
    $stmt->execute([$user_id]);
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['goals'] = $goals;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>