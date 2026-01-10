<?php
require "db.php";

/*
|--------------------------------------------------------------------------
| AUTO ROUND ENGINE (NO CRON)
|--------------------------------------------------------------------------
| This file is SAFE to be called every second
| It will:
| 1. Detect crashed round
| 2. Auto-create waiting round
| 3. Auto-start round after waiting time
*/

// SETTINGS
$WAIT_SECONDS = 5;

// Get latest round
$q = $conn->query("
    SELECT * FROM game_rounds_tbl
    ORDER BY round_id DESC
    LIMIT 1
");

if (!$q || !$q->num_rows) {
    // No round exists → create first one
    $cfg = $conn->query("SELECT current_crash_point FROM game_config_tbl WHERE id=1")->fetch_assoc();
    $crash = (float)$cfg['current_crash_point'];

    $code = 'RND-' . date('Ymd-His');
    $stmt = $conn->prepare("
        INSERT INTO game_rounds_tbl (round_code, status, crash_point)
        VALUES (?, 'waiting', ?)
    ");
    $stmt->bind_param("sd", $code, $crash);
    $stmt->execute();
    exit;
}

$round = $q->fetch_assoc();
$now = time();

/* =====================================================
   CASE 1: ROUND CRASHED → CREATE WAITING ROUND
===================================================== */
if ($round['status'] === 'crashed') {

    // Check if a waiting round already exists
    $chk = $conn->query("
        SELECT round_id FROM game_rounds_tbl
        WHERE status='waiting'
        LIMIT 1
    ");

    if ($chk->num_rows === 0) {

        $cfg = $conn->query("
            SELECT current_crash_point 
            FROM game_config_tbl 
            WHERE id=1
        ")->fetch_assoc();

        $crash = (float)$cfg['current_crash_point'];
        $code = 'RND-' . date('Ymd-His');

        $stmt = $conn->prepare("
            INSERT INTO game_rounds_tbl (round_code, status, crash_point)
            VALUES (?, 'waiting', ?)
        ");
        $stmt->bind_param("sd", $code, $crash);
        $stmt->execute();
    }

    exit;
}

/* =====================================================
   CASE 2: WAITING → AUTO START AFTER TIMER
===================================================== */
if ($round['status'] === 'waiting') {

    $created = strtotime($round['created_at']);
    if (($now - $created) >= $WAIT_SECONDS) {

        $conn->query("
            UPDATE game_rounds_tbl
            SET status='running', started_at=NOW()
            WHERE round_id={$round['round_id']}
        ");
    }

    exit;
}

/* =====================================================
   CASE 3: RUNNING → NOTHING (CLIENT HANDLES CRASH)
===================================================== */
exit;
