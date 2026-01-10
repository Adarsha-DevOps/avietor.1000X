<?php
include "db.php";

function startNewRound($conn)
{

    $code = 'RND-' . time();

    $conn->query("
        INSERT INTO game_rounds_tbl
        (round_code, status, started_at)
        VALUES ('$code', 'running', NOW())
    ");
}