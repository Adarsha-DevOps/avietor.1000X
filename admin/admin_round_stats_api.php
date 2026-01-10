<?php
header('Content-Type: application/json');
include "db.php";

// $q = $conn->query("
//     SELECT round_id, status
//     FROM game_rounds_tbl
//     ORDER BY round_id DESC
//     LIMIT 1
// ");

// if (!$q || !$q->num_rows) {
//     echo json_encode(['status' => 'no_round']);
//     exit;
// }

// $round = $q->fetch_assoc();
// $rid = (int)$round['round_id'];

// $statsQ = $conn->query("
//     SELECT 
//         COUNT(*) AS bet_count,
//         IFNULL(SUM(bet_amount),0) AS bet_total
//     FROM bets_tbl
//     WHERE round_id = $rid
// ");

// $stats = $statsQ->fetch_assoc();

// echo json_encode([
//     'status' => 'ok',
//     'round_id' => $rid,
//     'round_status' => $round['status'],
//     'bet_count' => (int)$stats['bet_count'],
//     'bet_total' => (float)$stats['bet_total']
// ]);

// Get latest round
$rq = $conn->query("
    SELECT round_id 
    FROM game_rounds_tbl 
    ORDER BY round_id DESC 
    LIMIT 1
");

if (!$rq || !$rq->num_rows) {
    echo json_encode(['status' => 'no_round']);
    exit;
}

$round_id = (int)$rq->fetch_assoc()['round_id'];

// Fetch stats
$q = $conn->query("
    SELECT
        COUNT(*) AS bet_count,
        IFNULL(SUM(bet_amount), 0) AS bet_total,
        IFNULL(SUM(CASE WHEN result='won' THEN win_amount ELSE 0 END), 0) AS cashout_total
    FROM bets_tbl
    WHERE round_id = $round_id
");

$row = $q->fetch_assoc();

echo json_encode([
    'status' => 'ok',
    'bet_count' => (int)$row['bet_count'],
    'bet_total' => (float)$row['bet_total'],
    'cashout_total' => (float)$row['cashout_total']
]);
