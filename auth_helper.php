<?php
// auth_helper.php - User authentication helper
function validateUserSession($user_id) {
    if (empty($user_id) || !is_numeric($user_id)) {
        throw new Exception('Session expired. Please log in again.');
    }
    
    // You can add more validation here if needed
    return (int)$user_id;
}

function getUserData($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT user_id, fullname, email FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User account not found.');
    }
    
    return $user;
}
?>