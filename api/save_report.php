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
    
    if (isset($input['user_id']) && isset($input['month'])) {
        $user_id = $input['user_id'];
        $month = $input['month'];
        $total_income = $input['total_income'] ?? 0;
        $total_expense = $input['total_expense'] ?? 0;
        $net_balance = $input['net_balance'] ?? 0;
        
        // Debug logging
        error_log("=== REPORT SAVE DEBUG ===");
        error_log("User ID from request: " . $user_id);
        error_log("Month: " . $month);
        
        try {
            // First, let's check what columns actually exist in the users table
            $columnsStmt = $pdo->prepare("SHOW COLUMNS FROM users");
            $columnsStmt->execute();
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Users table columns: " . implode(', ', $columns));
            
            // Check if user exists - try different possible column names
            $userExists = false;
            $actualUserIdColumn = '';
            
            // Try different possible user ID column names
            $possibleIdColumns = ['User_id', 'user_id', 'id'];
            foreach ($possibleIdColumns as $column) {
                if (in_array($column, $columns)) {
                    error_log("Checking user existence with column: " . $column);
                    $userCheckStmt = $pdo->prepare("SELECT $column FROM users WHERE $column = ?");
                    $userCheckStmt->execute([$user_id]);
                    if ($userCheckStmt->fetch()) {
                        $userExists = true;
                        $actualUserIdColumn = $column;
                        error_log("User found with column: " . $column);
                        break;
                    }
                }
            }
            
            if (!$userExists) {
                error_log("User does not exist, attempting to create...");
                
                // Check what columns we have to work with
                $hasFullname = in_array('fullname', $columns);
                $hasEmail = in_array('email', $columns);
                $hasPassword = in_array('password', $columns);
                
                error_log("Available columns - fullname: $hasFullname, email: $hasEmail, password: $hasPassword");
                
                // Build dynamic INSERT based on available columns
                $insertColumns = [];
                $insertValues = [];
                $insertPlaceholders = [];
                
                // Use the correct ID column name (first one that exists)
                $idColumn = $possibleIdColumns[0];
                foreach ($possibleIdColumns as $col) {
                    if (in_array($col, $columns)) {
                        $idColumn = $col;
                        break;
                    }
                }
                
                $insertColumns[] = $idColumn;
                $insertValues[] = $user_id;
                $insertPlaceholders[] = '?';
                
                if ($hasFullname) {
                    $insertColumns[] = 'fullname';
                    $insertValues[] = $input['user_name'] ?? 'Report User';
                    $insertPlaceholders[] = '?';
                }
                
                if ($hasEmail) {
                    $insertColumns[] = 'email';
                    $insertValues[] = $user_id . '_' . uniqid() . '@smartbudget.com';
                    $insertPlaceholders[] = '?';
                }
                
                if ($hasPassword) {
                    $insertColumns[] = 'password';
                    $insertValues[] = password_hash('temp_password_123', PASSWORD_DEFAULT);
                    $insertPlaceholders[] = '?';
                }
                
                if (in_array('created_at', $columns)) {
                    $insertColumns[] = 'created_at';
                    $insertPlaceholders[] = 'NOW()';
                }
                
                $columnList = implode(', ', $insertColumns);
                $placeholderList = implode(', ', $insertPlaceholders);
                
                error_log("Attempting to create user with: $columnList");
                
                $createUserStmt = $pdo->prepare("INSERT INTO users ($columnList) VALUES ($placeholderList)");
                
                // Only bind the values that have placeholders
                $paramIndex = 1;
                foreach ($insertValues as $value) {
                    $createUserStmt->bindValue($paramIndex, $value);
                    $paramIndex++;
                }
                
                $createUserStmt->execute();
                error_log("User created successfully");
            }
            
            // Now save the report
            error_log("Attempting to save report...");
            $checkStmt = $pdo->prepare("SELECT report_id FROM reports WHERE user_id = ? AND month = ?");
            $checkStmt->execute([$user_id, $month]);
            $existingReport = $checkStmt->fetch();
            
            if ($existingReport) {
                $stmt = $pdo->prepare("UPDATE reports SET total_income = ?, total_expense = ?, net_balance = ?, created_at = NOW() WHERE report_id = ?");
                $stmt->execute([$total_income, $total_expense, $net_balance, $existingReport['report_id']]);
                $message = 'Report updated successfully';
                error_log("Report updated");
            } else {
                $stmt = $pdo->prepare("INSERT INTO reports (user_id, month, total_income, total_expense, net_balance, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id, $month, $total_income, $total_expense, $net_balance]);
                $message = 'Report saved successfully';
                error_log("New report created");
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'report_id' => $existingReport ? $existingReport['report_id'] : $pdo->lastInsertId()
            ]);
            
        } catch (PDOException $e) {
            error_log("DATABASE ERROR: " . $e->getMessage());
            error_log("Error code: " . $e->getCode());
            
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage(),
                'debug' => 'Check server error logs for details'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: user_id and month are required'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Only POST method allowed'
    ]);
}
?>