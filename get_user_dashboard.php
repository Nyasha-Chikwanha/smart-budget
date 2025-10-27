<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smart_budget";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$user_id = $_GET['user_id'];

// Get transactions
$transactions_sql = "SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($transactions_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get goals
$goals_sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'active'";
$stmt = $conn->prepare($goals_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate summary
$income_sql = "SELECT COALESCE(SUM(amount), 0) as total_income FROM transactions WHERE user_id = ? AND type = 'income'";
$stmt = $conn->prepare($income_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_income = $stmt->get_result()->fetch_assoc()['total_income'];

$expenses_sql = "SELECT COALESCE(SUM(amount), 0) as total_expenses FROM transactions WHERE user_id = ? AND type = 'expense'";
$stmt = $conn->prepare($expenses_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_expenses = $stmt->get_result()->fetch_assoc()['total_expenses'];

echo json_encode([
    'success' => true,
    'summary' => [
        'total_income' => (float)$total_income,
        'total_expenses' => (float)$total_expenses,
        'net_balance' => (float)($total_income - $total_expenses)
    ],
    'transactions' => $transactions,
    'goals' => $goals
]);

$conn->close();
?>