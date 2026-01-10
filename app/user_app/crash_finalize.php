<?php
include "db.php";
include "round_manager.php";

$round_id = (int)$_POST['round_id'];

$conn->begin_transaction();

$conn->query("
    UPDATE bets_tbl
    SET result='lost'
    WHERE round_id=$round_id AND result='pending'
");

$conn->query("
    UPDATE game_rounds_tbl
    SET status='crashed', ended_at=NOW()
    WHERE round_id=$round_id
");

startNewRound($conn);

$conn->commit();

echo json_encode(['ok'=>true]);
