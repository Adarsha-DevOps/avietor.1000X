<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit;
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 1;
$bet_id = $_POST['bet_id'] ?? 0;
$multiplier = floatval($_POST['multiplier'] ?? 1.00);

// 1. Verify Bet
$q = $conn->query("SELECT * FROM bets_tbl WHERE bet_id = $bet_id AND user_id = $user_id AND result = 'pending'");
$bet = $q->fetch_assoc();

if (!$bet) {
    echo json_encode(['status' => 'error', 'message' => 'Bet invalid or already processed']);
    exit;
}

// 2. Calculate Win
$win_amount = round($bet['bet_amount'] * $multiplier, 2);

// 3. Get Current Balance & New Balance
$current_balance = getUserBalance($conn, $user_id);
$new_balance = $current_balance + $win_amount;

// 4. Update DB
$conn->begin_transaction();
try {
    // Update Bet Table
    $conn->query("UPDATE bets_tbl SET result='won', win_amount=$win_amount WHERE bet_id=$bet_id");

    // Add Balance Transaction
    $ref = "WIN-" . $bet_id;
    $stmt = $conn->prepare("INSERT INTO wallet_transactions_tbl (user_id, type, amount, balance_after, reference, created_at) VALUES (?, 'win', ?, ?, ?, NOW())");
    $stmt->bind_param("idds", $user_id, $win_amount, $new_balance, $ref);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'win_amount' => $win_amount,
        'new_balance' => $new_balance
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Error processing win']);
}
?>