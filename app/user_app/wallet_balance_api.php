<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT balance_after
    FROM wallet_transactions_tbl
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$balance = $res ? (float)$res['balance_after'] : 0;

header("Content-Type: application/json");
echo json_encode([
    "balance" => number_format($balance, 2, ".", "")
]);
