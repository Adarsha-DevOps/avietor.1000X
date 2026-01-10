<?php
header('Content-Type: application/json');
require "db.php";

// Get latest round
$q = $conn->query("
    SELECT round_id 
    FROM game_rounds_tbl 
    ORDER BY round_id DESC 
    LIMIT 1
");

if (!$q || !$q->num_rows) {
    echo json_encode([
        'bets' => 0,
        'total' => 0
    ]);
    exit;
}

$r = $q->fetch_assoc();
$round_id = (int)$r['round_id'];

// Fetch live bet stats
$stats = $conn->query("
    SELECT 
        COUNT(*) AS bet_count,
        IFNULL(SUM(bet_amount), 0) AS bet_total
    FROM bets_tbl
    WHERE round_id = $round_id
")->fetch_assoc();

echo json_encode([
    'bets'  => (int)$stats['bet_count'],
    'total' => (float)$stats['bet_total']
]);
