<?php
include "db.php";

$conn->query("
    INSERT INTO game_rounds_tbl (round_code, status, started_at)
    VALUES (UUID(), 'running', NOW())
");

echo "ROUND STARTED";
