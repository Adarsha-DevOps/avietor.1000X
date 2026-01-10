<?php
include "db.php";
header('Content-Type: application/json');

$q = $conn->query("
    SELECT round_id, status, started_at 
    FROM game_rounds_tbl 
    ORDER BY round_id DESC 
    LIMIT 1
");

$data = [
    'round_id' => null,
    'status' => 'waiting',
    'started_at' => null,
    'server_time' => time() * 1000
];

if ($q && $r = $q->fetch_assoc()) {
    $data['round_id'] = (int)$r['round_id'];
    $data['status'] = $r['status'];
    $data['started_at'] = $r['started_at']
        ? strtotime($r['started_at']) * 1000
        : null;
}

echo json_encode($data);
