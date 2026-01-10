<?php
header('Content-Type: application/json');
session_start();
require "db.php";

$user_id = $_SESSION['user_id'] ?? 0;
$bet_id  = isset($_POST['bet_id']) ? (int)$_POST['bet_id'] : 0;

if ($user_id <= 0 || $bet_id <= 0) {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

// 🔍 Verify bet (ONLY pending & waiting)
$q = $conn->query("
    SELECT bet_amount 
    FROM bets_tbl 
    WHERE bet_id = $bet_id 
      AND user_id = $user_id 
      AND result = 'pending'
    LIMIT 1
");

if (!$q || $q->num_rows === 0) {
    echo json_encode(['status'=>'error','message'=>'Bet cannot be cancelled']);
    exit;
}

$bet = $q->fetch_assoc();
$amount = (float)$bet['bet_amount'];

// 🔒 TRANSACTION
$conn->begin_transaction();
try {

    // ❌ Delete bet
    $conn->query("DELETE FROM bets_tbl WHERE bet_id = $bet_id");

    // 💰 Refund wallet
    $balQ = $conn->query("
        SELECT IFNULL(SUM(
            CASE 
                WHEN type IN ('deposit','win','refund') THEN amount
                WHEN type IN ('bet','withdraw') THEN -amount
                ELSE 0
            END
        ),0) AS bal
        FROM wallet_transactions_tbl
        WHERE user_id = $user_id
    ");
    $balance = (float)$balQ->fetch_assoc()['bal'];
    $new_balance = $balance + $amount;

    $stmt = $conn->prepare("
        INSERT INTO wallet_transactions_tbl
        (user_id,type,amount,balance_after,reference)
        VALUES (?, 'refund', ?, ?, ?)
    ");
    $ref = 'CANCEL-'.$bet_id;
    $stmt->bind_param("idds",$user_id,$amount,$new_balance,$ref);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'status'=>'success',
        'new_balance'=>$new_balance
    ]);

} catch(Throwable $e) {
    $conn->rollback();
    echo json_encode(['status'=>'error','message'=>'Cancel failed']);
}
