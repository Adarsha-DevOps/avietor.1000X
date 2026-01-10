<?php
include "db.php";

$round_id = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;

$data = [];

$stmt = $conn->prepare("
    SELECT 
        u.name,
        b.bet_amount,
        b.cashout_multiplier,
        b.win_amount,
        b.result
    FROM bets_tbl b
    JOIN users_tbl u ON u.id = b.user_id
    WHERE b.round_id = ?
    ORDER BY b.bet_id DESC
    LIMIT 50
");
$stmt->bind_param("i", $round_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

header("Content-Type: application/json");
echo json_encode($data);
