<?php
include "db.php";

$q = $conn->query("
    SELECT 
        round_id,
        status,
        force_crash_multiplier,
        TIMESTAMPDIFF(MICROSECOND, started_at, NOW()) / 1000000 AS t
    FROM game_rounds_tbl
    WHERE status IN ('running','crashed')
    ORDER BY round_id DESC
    LIMIT 1
");

$row = $q->fetch_assoc();

$multiplier = 1.00;

if ($row['status'] === 'running') {

    $t = max(0, (float)$row['t']);
    $multiplier = 1 + ($t * 0.6) + ($t * $t * 0.15);

    if ($row['force_crash_multiplier'] !== null &&
        $multiplier >= $row['force_crash_multiplier']) {

        $conn->query("
            UPDATE game_rounds_tbl
            SET status='crashed', ended_at=NOW()
            WHERE round_id={$row['round_id']}
        ");

        echo json_encode([
            'status' => 'crashed',
            'multiplier' => (float)$row['force_crash_multiplier']
        ]);
        exit;
    }
}

echo json_encode([
    'status' => $row['status'],
    'multiplier' => round($multiplier, 2)
]);
