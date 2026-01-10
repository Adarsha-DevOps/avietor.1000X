<?php
require 'db.php';
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

// 1. Get Current Precision Time
$current_time = microtime(true);

// 2. Fetch Latest Round
$q = $conn->query("SELECT * FROM game_rounds_tbl ORDER BY round_id DESC LIMIT 1");
$round = $q->fetch_assoc();

// --- FAILSAFE: CREATE ROUND IF EMPTY ---
if (!$round) {
    $conn->query("INSERT INTO game_rounds_tbl (status, crash_point, started_at) VALUES ('waiting', 2.00, NOW())");
    echo json_encode(['status' => 'waiting', 'multiplier' => 1.00, 'time_left_ms' => 5000]);
    exit;
}

$round_id = $round['round_id'];
$status = $round['status'];
$crash_point = (float)$round['crash_point'];

// 3. Time Calculation (The Fix for "Stuck" Games)
// We convert DB time to timestamp. 
$db_start_time = strtotime($round['started_at']); 
$elapsed_seconds = time() - $db_start_time; // Integer seconds

// --- STATE MACHINE ---

// CASE A: WAITING
if ($status == 'waiting') {
    $wait_duration = 5; // Seconds
    $time_left = $wait_duration - $elapsed_seconds;

    // If waiting time is over, SWITCH TO FLYING
    if ($time_left <= 0) {
        // Update DB to 'running' and set NEW start time to NOW using PHP time to ensure sync
        $now_str = date("Y-m-d H:i:s");
        $conn->query("UPDATE game_rounds_tbl SET status='running', started_at='$now_str' WHERE round_id=$round_id");
        
        $status = 'running';
        $elapsed_seconds = 0;
        $current_multiplier = 1.00;
        $time_left = 0;
    } else {
        $current_multiplier = 1.00;
    }
}

// CASE B: FLYING (RUNNING)
if ($status == 'running') {
    // Recalculate precise elapsed time for smooth animation
    // We fetch again to get the EXACT 'started_at' we just updated
    // But for performance, we use the logic:
    
    // Multiplier Formula: Grows exponentially
    // Using microtime diff for smoothness
    // Note: If you just switched to running, elapsed might be near 0
    
    $fly_time = $elapsed_seconds; 
    
    // Use a small offset correction if needed, but raw seconds work for stability
    // Formula: 1 + (t^2 * 0.06)
    $current_multiplier = 1.0 + ($fly_time * $fly_time * 0.06);
    
    // FIX: If multiplier is somehow crazy high due to old timestamp, force crash immediately
    if($fly_time > 1000) {
         $current_multiplier = 999.99; // Cap it to trigger crash
    }

    // CHECK CRASH
    if ($current_multiplier >= $crash_point) {
        $final_crash = $crash_point;
        
        // Update DB
        $conn->query("UPDATE game_rounds_tbl SET status='crashed' WHERE round_id=$round_id");
        
        // Set Bets to Lost
        $conn->query("UPDATE bets_tbl SET result='lost' WHERE round_id=$round_id AND result='pending'");
        
        $status = 'crashed';
        $current_multiplier = $final_crash;
    }
    
    // Visual fix: Never send < 1.00
    if($current_multiplier < 1.00) $current_multiplier = 1.00;
}

// CASE C: CRASHED
if ($status == 'crashed') {
    $current_multiplier = $crash_point;
    
    // Auto-Restart logic
    // If it crashed more than 4 seconds ago, start new round
    // We check 'ended_at' usually, but here we use elapsed since start > flight_time + 4
    // Simple Trigger: just Create New Round immediately if client polls and it's crashed
    
    // Generate new Crash Point
    $new_crash = rand(100, 500) / 100; // 1.00x to 5.00x
    if(rand(1,100) < 20) $new_crash = 1.00;
    
    $conn->query("INSERT INTO game_rounds_tbl (status, crash_point, started_at) VALUES ('waiting', $new_crash, NOW())");
    
    // Return Waiting status for the NEXT round immediately
    $status = 'waiting';
    $time_left = 5;
    $current_multiplier = 1.00;
}

// 4. Output Response
echo json_encode([
    'round_id' => (int)$round_id,
    'status' => ($status == 'running') ? 'flying' : $status,
    'multiplier' => (float)$current_multiplier,
    'crash_point' => (float)$crash_point,
    'time_left_ms' => ($status == 'waiting') ? ($time_left * 1000) : 0,
    'elapsed_ms' => ($status == 'running') ? ($elapsed_seconds * 1000) : 0
]);
?>