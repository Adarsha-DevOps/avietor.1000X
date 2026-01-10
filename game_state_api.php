<?php
include "db.php";

$q = $conn->query("
    SELECT round_id, status, started_at, force_crash_multiplier
    FROM game_rounds_tbl
    WHERE status IN ('running','crashed')
    ORDER BY round_id DESC
    LIMIT 1
");

$r = $q->fetch_assoc();

$elapsed = time() - strtotime($r['started_at']);
$multiplier = 1 + ($elapsed * 0.6) + ($elapsed * $elapsed * 0.15);

if (
    $r['force_crash_multiplier'] !== null &&
    $multiplier >= $r['force_crash_multiplier'] &&
    $r['status'] === 'running'
) {
    $conn->query("
        UPDATE game_rounds_tbl
        SET status='crashed'
        WHERE round_id={$r['round_id']}
    ");

    echo json_encode([
        "status" => "crashed",
        "multiplier" => round($r['force_crash_multiplier'], 2)
    ]);
    exit;
}

echo json_encode([
    "status" => $r['status'],
    "multiplier" => round($multiplier, 2)
]);
