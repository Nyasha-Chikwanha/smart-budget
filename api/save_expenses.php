<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = 'localhost';
$dbname = 'smart_budget';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['user_id']) && isset($input['expenses'])) {
        $user_id = $input['user_id'];
        $expenses_data = $input['expenses'];
        
        try {
            // Verify user exists in users table
            $userCheckStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $userCheckStmt->execute([$user_id]);
            $userExists = $userCheckStmt->fetch();
            
            if (!$userExists) {
                echo json_encode([
                    'success' => false,
                    'error' => 'User account not found. Please log in with a valid account.'
                ]);
                exit;
            }
            
            $saved_count = 0;
            
            // Save each expense record
            foreach ($expenses_data as $expense) {
                $stmt = $pdo->prepare("
                    INSERT INTO expenses (user_id, category, description, amount, date_spent) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $expense['category'] ?? 'Uncategorized',
                    $expense['description'] ?? '',
                    $expense['amount'] ?? 0,
                    $expense['date'] ?? date('Y-m-d'),
                ]);
                $saved_count++;
            }
            
            error_log("Expenses data saved for user: " . $user_id . " - " . $saved_count . " records");
            
            echo json_encode([
                'success' => true,
                'saved_count' => $saved_count,
                'message' => 'Expenses data saved successfully'
            ]);
            
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid data format: user_id and expenses are required'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Only POST method allowed'
    ]);
}
?>