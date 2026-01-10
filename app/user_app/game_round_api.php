<?php
header('Content-Type: application/json');

/*
 THIS FILE CREATES A GLOBAL ROUND CLOCK
 - SAME FOR ALL USERS
 - SURVIVES REFRESH
*/

$ROUND_DURATION = 5; // waiting time before fly (seconds)

// server epoch
$now = time();

// every round starts on exact 5-second boundary
$round_start = $now - ($now % $ROUND_DURATION);

// flying starts after wait
$fly_start = $round_start + $ROUND_DURATION;

echo json_encode([
    'server_time' => $now * 1000,
    'fly_start'   => $fly_start * 1000
]);
