<?php
include "db.php";

/* GET ACTIVE ROUND */
$q = $conn->query("
    SELECT round_id, started_at, crash_point
    FROM game_rounds_tbl
    WHERE status='running'
    LIMIT 1
");
if(!$q || !$r = $q->fetch_assoc()) exit;

$round_id = (int)$r['round_id'];
$start    = strtotime($r['started_at']);
$crash    = (float)$r['crash_point'];

$elapsed = microtime(true) - $start;
$multiplier = 1 + ($elapsed * $elapsed * 0.06);

/* FORCE CRASH CHECK */
$fc = $conn->query("
    SELECT 1 FROM admin_controls_tbl
    WHERE round_id=$round_id
      AND action='force_crash'
      AND status='pending'
    LIMIT 1
");

if ($multiplier >= $crash || $fc->num_rows > 0) {

    $conn->query("
        UPDATE game_rounds_tbl
        SET status='crashed', ended_at=NOW()
        WHERE round_id=$round_id
    ");

    $conn->query("
        UPDATE admin_controls_tbl
        SET status='applied'
        WHERE round_id=$round_id AND action='force_crash'
    ");
}
