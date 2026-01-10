<?php
require 'db.php';
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 1;
// $round_id = $_POST['round_id'] ?? 0;

$r = $conn->query("
    SELECT round_id 
    FROM game_rounds_tbl 
    WHERE status='waiting'
    ORDER BY round_id DESC
    LIMIT 1
");
$row = $r->fetch_assoc();
$round_id = $row['round_id'];

$amount = floatval($_POST['amount'] ?? 0);

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
    exit;
}

// 1. Get Current Balance
$current_balance = getUserBalance($conn, $user_id);

if ($current_balance < $amount) {
    echo json_encode(['status' => 'error', 'message' => 'Insufficient Balance']);
    exit;
}

// 2. Calculate New Balance
$new_balance = $current_balance - $amount;

// 3. Start SQL Transaction
$conn->begin_transaction();

try {
    // Insert Bet
    $stmt = $conn->prepare("INSERT INTO bets_tbl (round_id, user_id, bet_amount, result, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("iid", $round_id, $user_id, $amount);
    $stmt->execute();
    $bet_id = $stmt->insert_id;

    // Deduct Balance (Record Transaction)
    $ref = "BET-" . $bet_id;
    $stmt2 = $conn->prepare("INSERT INTO wallet_transactions_tbl (user_id, type, amount, balance_after, reference, created_at) VALUES (?, 'bet', ?, ?, ?, NOW())");
    $stmt2->bind_param("idds", $user_id, $amount, $new_balance, $ref);
    $stmt2->execute();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'bet_id' => $bet_id,
        'new_balance' => $new_balance
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Database Error']);
}
?>