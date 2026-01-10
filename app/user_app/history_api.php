<?php
require "db.php";
header('Content-Type: application/json');

$q = $conn->query("
    SELECT crash_point 
    FROM game_rounds_tbl
    WHERE status = 'crashed'
      AND crash_point IS NOT NULL
    ORDER BY round_id DESC
    LIMIT 20
");

$history = [];
while ($r = $q->fetch_assoc()) {
    $history[] = (float)$r['crash_point'];
}

echo json_encode($history);
