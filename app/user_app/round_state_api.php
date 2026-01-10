<?php
include "db.php";
header('Content-Type: application/json');

$q = $conn->query("
    SELECT 
        round_id,
        status,
        UNIX_TIMESTAMP(started_at) * 1000 AS started_at_ms
    FROM game_rounds_tbl
    ORDER BY round_id DESC
    LIMIT 1
");

$row = $q->fetch_assoc();

echo json_encode([
    'round_id'   => (int)$row['round_id'],
    'status'     => $row['status'],
    'started_at' => $row['started_at_ms']
]);
