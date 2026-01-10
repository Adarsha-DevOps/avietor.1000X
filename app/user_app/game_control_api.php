<?php
require 'db.php'; // Adjust path if necessary
header('Content-Type: application/json');

// Get latest round
$q = $conn->query("SELECT * FROM game_rounds_tbl ORDER BY round_id DESC LIMIT 1");
$round = $q->fetch_assoc();

$current_time = time();

if (!$round || $round['status'] == 'crashed') {
    // Logic to start new round could go here (e.g. via cron or simple check)
    // For this demo, we just read the DB. 
    // You should have a separate script inserting new rounds.
}

// Simple Multiplier Logic based on time elapsed
$multiplier = 1.00;
if($round['status'] == 'running') {
    $start = strtotime($round['started_at']);
    $diff = microtime(true) - $start; // Use microtime in DB insertion for smoother anim
    // Fallback if DB uses simple datetime
    $diff = time() - $start; 
    
    // Formula
    $multiplier = 1.00 + ($diff * $diff * 0.05); 
}

echo json_encode([
    'round_id' => $round['round_id'],
    'status' => $round['status'] == 'running' ? 'flying' : $round['status'], 
    'crash_point' => floatval($round['crash_point']),
    'multiplier' => $multiplier
]);
?>