<?php
session_start();
include "db.php";

/* ================================
   HELPER: GENERATE ROUND CODE
================================ */
function generateRoundCode() {
    return 'RND-' . date('Ymd') . '-' . random_int(100000, 999999);
}

/* ================================
   FETCH RUNNING ROUND ONLY
================================ */
function getRunningRound($conn) {
    $q = $conn->query("
        SELECT * FROM game_rounds_tbl
        WHERE status = 'running'
        ORDER BY round_id DESC
        LIMIT 1
    ");
    return ($q && $q->num_rows) ? $q->fetch_assoc() : null;
}

/* ================================
   FETCH LATEST ROUND (ANY STATE)
================================ */
function getLatestRound($conn) {
    $q = $conn->query("
        SELECT * FROM game_rounds_tbl
        ORDER BY round_id DESC
        LIMIT 1
    ");
    return ($q && $q->num_rows) ? $q->fetch_assoc() : null;
}

/* ================================
   CREATE NEW WAITING ROUND
================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'new_round') {
//     $conn->query("UPDATE game_rounds_tbl SET status='crashed', ended_at=NOW() WHERE status='running'");
//     $round_code = generateRoundCode();
//     $stmt = $conn->prepare("INSERT INTO game_rounds_tbl (round_code, status) VALUES (?, 'waiting')");
//     $stmt->bind_param("s", $round_code);
//     $stmt->execute();
//     header("Location: admin_bet_control.php?new=ok");
//     exit;
// }

/* ================================
   START ROUND (WAITING → RUNNING)
================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'start_round') {
//     $conn->query("UPDATE game_rounds_tbl SET status='running', started_at=NOW(), crash_point=NULL WHERE status='waiting' ORDER BY round_id ASC LIMIT 1");
//     header("Location: admin_bet_control.php?start=ok");
//     exit;
// }

/* ================================
   START ROUND (WAITING → RUNNING)
   ⚠️ DO NOT TOUCH crash_point
================================ */
if (isset($_POST['action']) && $_POST['action'] === 'start_round') {

    $conn->query("
        UPDATE game_rounds_tbl
        SET 
            status = 'running',
            started_at = NOW()
        WHERE status = 'waiting'
        ORDER BY round_id ASC
        LIMIT 1
    ");

    header("Location: admin_bet_control.php?start=ok");
    exit;
}


/* ================================
   START ROUND (WAITING → RUNNING)
================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'start_round') {

//     // Pick oldest waiting round WITH crash point
//     $q = $conn->query("
//         SELECT round_id 
//         FROM game_rounds_tbl
//         WHERE status='waiting'
//         ORDER BY round_id ASC
//         LIMIT 1
//     ");

//     if ($q && $q->num_rows) {
//         $r = $q->fetch_assoc();
//         $rid = (int)$r['round_id'];

//         $conn->query("
//             UPDATE game_rounds_tbl
//             SET status='running', started_at=NOW()
//             WHERE round_id=$rid
//         ");
//     }

//     header("Location: admin_bet_control.php?start=ok");
//     exit;
// }

/* ================================
   CREATE NEW WAITING ROUND
================================ */
if (isset($_POST['action']) && $_POST['action'] === 'new_round') {

    // End running round safely
    $conn->query("
        UPDATE game_rounds_tbl
        SET status='crashed', ended_at=NOW()
        WHERE status='running'
    ");

    // 🔥 Get global crash point
    $cfg = $conn->query("
        SELECT current_crash_point
        FROM game_config_tbl
        WHERE id = 1
        LIMIT 1
    ")->fetch_assoc();

    $crash_point = (float)$cfg['current_crash_point'];
    $round_code  = generateRoundCode();

    // 🔒 Copy crash into round
    $stmt = $conn->prepare("
        INSERT INTO game_rounds_tbl (round_code, status, crash_point)
        VALUES (?, 'waiting', ?)
    ");
    $stmt->bind_param("sd", $round_code, $crash_point);
    $stmt->execute();

    header("Location: admin_bet_control.php?new=ok");
    exit;
}

/* ================================
   FORCE CRASH ROUND
================================ */
if (isset($_POST['action']) && $_POST['action'] === 'force_crash') {
    $round = getRunningRound($conn);
    if ($round) {
        $rid = (int)$round['round_id'];
        $conn->query("UPDATE game_rounds_tbl SET status='crashed', ended_at=NOW() WHERE round_id=$rid");
        $conn->query("UPDATE bets_tbl SET result='lost' WHERE round_id=$rid AND result='pending'");
        $round_code = generateRoundCode();
        $stmt = $conn->prepare("INSERT INTO game_rounds_tbl (round_code, status) VALUES (?, 'waiting')");
        $stmt->bind_param("s", $round_code);
        $stmt->execute();
    }
    header("Location: admin_bet_control.php?crash=ok");
    exit;
}

// /* ================================
//    FORCE CRASH AT LIVE MULTIPLIER
//    (NO INSTANT ROUND END)
// ================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'force_crash') {

//     $round = getRunningRound($conn);

//     if ($round && $round['started_at']) {

//         // ⏱ Calculate elapsed time since round started
//         $started = strtotime($round['started_at']);
//         $elapsed = microtime(true) - $started;

//         // 🎯 SAME FORMULA AS FRONTEND
//         $crash_point = round(
//             1 + ($elapsed * 0.06) + ($elapsed * $elapsed * 0.06),
//             2
//         );

//         // 🔒 ONLY SET CRASH POINT
//         $stmt = $conn->prepare("
//             UPDATE game_rounds_tbl
//             SET crash_point = ?
//             WHERE round_id = ?
//         ");
//         $stmt->bind_param("di", $crash_point, $round['round_id']);
//         $stmt->execute();
//     }

//     // ❗ DO NOT change status
//     // ❗ DO NOT insert new round
//     // ❗ DO NOT mark bets lost

//     exit;
// }

// /* ================================
//    FORCE CRASH AT LIVE MULTIPLIER
//    SAFE VERSION (NO 500 ERROR)
// ================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'force_crash') {

//     $round = getRunningRound($conn);

//     if (!$round) {
//         http_response_code(200);
//         exit;
//     }

//     if (empty($round['started_at'])) {
//         http_response_code(200);
//         exit;
//     }

//     // ⏱ SAFE elapsed calculation
//     $started_ts = strtotime($round['started_at']);
//     if ($started_ts === false) {
//         http_response_code(200);
//         exit;
//     }

//     $elapsed = microtime(true) - $started_ts;
//     if ($elapsed < 0) $elapsed = 0;

//     // 🎯 SAME FORMULA AS FRONTEND
//     $crash_point = round(
//         1 + ($elapsed * 0.06) + ($elapsed * $elapsed * 0.06),
//         2
//     );

//     // 🛑 MINIMUM SAFETY
//     if ($crash_point < 1) {
//         $crash_point = 1.00;
//     }

//     // 🔒 UPDATE ONLY CRASH POINT
//     $stmt = $conn->prepare("
//         UPDATE game_rounds_tbl
//         SET crash_point = ?
//         WHERE round_id = ?
//         LIMIT 1
//     ");
//     $stmt->bind_param("di", $crash_point, $round['round_id']);
//     $stmt->execute();

//     http_response_code(200);
//     exit;
// }

// /* ================================
//    FORCE CRASH AT LIVE MULTIPLIER
//    PRECISION VERSION
// ================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'force_crash') {

//     $round = getRunningRound($conn);

//     if ($round && !empty($round['started_at'])) {
        
//         // ⏱️ Get precise server time
//         $started_ts = strtotime($round['started_at']);
//         $now = microtime(true); 
//         $elapsed = $now - $started_ts;

//         if ($elapsed < 0) $elapsed = 0;

//         // 🎯 EXACT formula used in your frontend
//         // multiplier = 1 + (t * 0.06) + (t * t * 0.06)
//         $current_multiplier = 1 + ($elapsed * 0.06) + ($elapsed * $elapsed * 0.06);
//         $crash_point = round($current_multiplier, 2);

//         // 🛑 Safety: Minimum crash is 1.00
//         if ($crash_point < 1) $crash_point = 1.00;

//         // 🔒 UPDATE CRASH POINT
//         // This will cause the game engine/frontend to trigger the crash 
//         // because the 'multiplier' will suddenly be >= 'crash_point'
//         $stmt = $conn->prepare("
//             UPDATE game_rounds_tbl
//             SET crash_point = ?
//             WHERE round_id = ?
//         ");
//         $stmt->bind_param("di", $crash_point, $round['round_id']);
//         $stmt->execute();
        
//         echo json_encode(['status' => 'success', 'crashed_at' => $crash_point]);
//     } else {
//         echo json_encode(['status' => 'no_running_round']);
//     }
//     exit; // Stop execution for AJAX
// }

// /* ================================
//    FORCE CRASH AT LIVE MULTIPLIER
//    KILL-SWITCH VERSION (STOPS ALL BETS)
// ================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'force_crash') {

//     $round = getRunningRound($conn);

//     if ($round && !empty($round['started_at'])) {
//         $rid = (int)$round['round_id'];
        
//         // ⏱️ Precise Server Multiplier calculation
//         // microtime(true) ensures we get milliseconds for a smooth stop
//         $started_ts = strtotime($round['started_at']);
//         $now = microtime(true); 
//         $elapsed = $now - $started_ts;
//         if ($elapsed < 0) $elapsed = 0;

//         // 🎯 The EXACT formula used in your game frontend
//         $current_multiplier = 1 + ($elapsed * 0.06) + ($elapsed * $elapsed * 0.06);
//         $final_crash = round($current_multiplier, 2);

//         // 🛑 Minimum safety
//         if ($final_crash < 1) $final_crash = 1.00;

//         // 🔒 ATOMIC UPDATE: 
//         // We set status to 'crashed' immediately so users cannot cash out 
//         // while the frontend is still playing the "fly away" animation.
//         $stmt = $conn->prepare("
//             UPDATE game_rounds_tbl 
//             SET status = 'crashed', 
//                 crash_point = ?, 
//                 ended_at = NOW() 
//             WHERE round_id = ?
//         ");
//         $stmt->bind_param("di", $final_crash, $rid);
//         $stmt->execute();

//         // ❌ Settle all pending bets as LOST immediately in the database
//         $conn->query("
//             UPDATE bets_tbl 
//             SET result = 'lost' 
//             WHERE round_id = $rid AND result = 'pending'
//         ");
        
//         echo json_encode(['status' => 'success', 'crashed_at' => $final_crash]);
//     } else {
//         echo json_encode(['status' => 'no_running_round']);
//     }
//     exit; // Stop execution here for the AJAX call
// }

// /* ================================
//    FORCE CRASH AT LIVE MULTIPLIER
//    (NATURAL CRASH – PRODUCTION SAFE)
// ================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'force_crash') {

//     header('Content-Type: application/json');

//     $round = getRunningRound($conn);
//     if (!$round || empty($round['started_at'])) {
//         echo json_encode(['status' => 'no_running_round']);
//         exit;
//     }

//     $rid = (int)$round['round_id'];

//     // ⏱ Calculate elapsed time precisely
//     $started_ts = strtotime($round['started_at']);
//     if ($started_ts === false) {
//         echo json_encode(['status' => 'invalid_start_time']);
//         exit;
//     }

//     $elapsed = microtime(true) - $started_ts;
//     if ($elapsed < 0) $elapsed = 0;

//     // 🎯 EXACT SAME FORMULA AS FRONTEND
//     // multiplier = 1 + (t * 0.06) + (t² * 0.06)
//     $current_multiplier = 1 + ($elapsed * 0.06) + ($elapsed * $elapsed * 0.06);
//     $crash_point = round($current_multiplier, 2);

//     if ($crash_point < 1) $crash_point = 1.00;

//     // 🔒 ONLY SET CRASH POINT — DO NOT END ROUND
//     $stmt = $conn->prepare("
//         UPDATE game_rounds_tbl
//         SET crash_point = ?
//         WHERE round_id = ?
//         LIMIT 1
//     ");
//     $stmt->bind_param("di", $crash_point, $rid);
//     $stmt->execute();

//     echo json_encode([
//         'status' => 'success',
//         'crashed_at' => $crash_point
//     ]);
//     exit;
// }

// if (isset($_POST['action']) && $_POST['action'] === 'force_crash') {

//     header('Content-Type: application/json');

//     $round = getRunningRound($conn);
//     if (!$round) {
//         echo json_encode(['status'=>'no_running_round']);
//         exit;
//     }

//     $crash_point = isset($_POST['crash_point']) ? (float)$_POST['crash_point'] : 0;
//     if ($crash_point < 1) $crash_point = 1.00;

//     $stmt = $conn->prepare("
//         UPDATE game_rounds_tbl
//         SET crash_point = ?
//         WHERE round_id = ?
//         LIMIT 1
//     ");
//     $stmt->bind_param("di", $crash_point, $round['round_id']);
//     $stmt->execute();

//     echo json_encode([
//         'status' => 'success',
//         'crashed_at' => $crash_point
//     ]);
//     exit;
// }

// /* ================================
//    FORCE CRASH (NATURAL CRASH)
// ================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'force_crash') {

//     $round = getRunningRound($conn);

//     if ($round) {
//         $rid = (int)$round['round_id'];

//         // 🔥 Get server-side elapsed time
//         $started_at = strtotime($round['started_at']);
//         $elapsed = max(0, time() - $started_at);

//         // 🔢 Same formula used on frontend
//         $crash_point = round(
//             1 + ($elapsed * 0.06) + ($elapsed * $elapsed * 0.06),
//             2
//         );

//         // 🔒 Set crash point ONLY (do not end round yet)
//         $stmt = $conn->prepare("
//             UPDATE game_rounds_tbl 
//             SET crash_point = ?
//             WHERE round_id = ?
//         ");
//         $stmt->bind_param("di", $crash_point, $rid);
//         $stmt->execute();
//     }

//     header("Location: admin_bet_control.php?crash=ok");
//     exit;
// }

// /* ================================
//    FORCE CRASH (EXACT POINT)
// ================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'force_crash') {

//     $round = getRunningRound($conn);

//     if ($round && isset($_POST['crash_point'])) {
//         $rid = (int)$round['round_id'];
//         $crash = (float)$_POST['crash_point'];

//         if ($crash >= 1) {
//             $stmt = $conn->prepare("
//                 UPDATE game_rounds_tbl
//                 SET crash_point = ?
//                 WHERE round_id = ?
//             ");
//             $stmt->bind_param("di", $crash, $rid);
//             $stmt->execute();
//         }
//     }

//     exit;
// }

// /* ================================
//    FORCE CRASH AT LIVE MULTIPLIER
// ================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'force_crash_now') {

//     $round = getRunningRound($conn);

//     if ($round && $round['started_at']) {

//         // ⏱ Calculate elapsed time
//         $started = strtotime($round['started_at']);
//         $elapsed = microtime(true) - $started;

//         // 🎯 SAME FORMULA AS FRONTEND
//         $multiplier = 1 + ($elapsed * 0.06) + ($elapsed * $elapsed * 0.06);
//         $multiplier = round($multiplier, 2);

//         // 💥 Set crash point EXACTLY NOW
//         $stmt = $conn->prepare("
//             UPDATE game_rounds_tbl
//             SET crash_point = ?
//             WHERE round_id = ?
//         ");
//         $stmt->bind_param("di", $multiplier, $round['round_id']);
//         $stmt->execute();
//     }

//     exit;
// }

// /* ================================
//    FORCE CRASH AT LIVE MULTIPLIER (FINAL FIX)
// ================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'force_crash_now') {

//     $round = getRunningRound($conn);

//     if ($round && $round['started_at']) {

//         // ⏱ Calculate elapsed time precisely
//         $started = strtotime($round['started_at']);
//         $elapsed = microtime(true) - $started;

//         // 🎯 SAME multiplier formula (frontend + backend MUST match)
//         $multiplier = 1 + ($elapsed * 0.06) + ($elapsed * $elapsed * 0.06);
//         $multiplier = round($multiplier, 2);

//         // 💥 FORCE IMMEDIATE CRASH
//         $stmt = $conn->prepare("
//             UPDATE game_rounds_tbl
//             SET 
//                 crash_point = ?,
//                 status = 'crashed',
//                 ended_at = NOW()
//             WHERE round_id = ?
//         ");
//         $stmt->bind_param("di", $multiplier, $round['round_id']);
//         $stmt->execute();

//         // ❌ Mark remaining bets as lost
//         $conn->query("
//             UPDATE bets_tbl
//             SET result='lost'
//             WHERE round_id={$round['round_id']}
//             AND result='pending'
//         ");
//     }

//     exit;
// }


/* ================================
   SET CRASH POINT (FIXED)
================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'set_crash') {
//     $crash = (float)$_POST['crash_point'];
//     $round = getRunningRound($conn);
//     if ($round) {
//         $stmt = $conn->prepare("UPDATE game_rounds_tbl SET crash_point = ? WHERE round_id = ?");
//         $stmt->bind_param("di", $crash, $round['round_id']);
//         $stmt->execute();
//     }
//     header("Location: admin_bet_control.php?cp=ok");
//     exit;
// }

/* ================================
   SET CRASH POINT (WAITING ONLY)
================================ */
// if (isset($_POST['action']) && $_POST['action'] === 'set_crash') {

//     $crash = (float)$_POST['crash_point'];

//     // 🔥 ONLY WAITING ROUND
//     $q = $conn->query("
//         SELECT round_id 
//         FROM game_rounds_tbl 
//         WHERE status = 'waiting'
//         ORDER BY round_id ASC
//         LIMIT 1
//     ");

//     if ($q && $q->num_rows) {
//         $r = $q->fetch_assoc();
//         $round_id = (int)$r['round_id'];

//         $stmt = $conn->prepare("
//             UPDATE game_rounds_tbl 
//             SET crash_point = ? 
//             WHERE round_id = ?
//         ");
//         $stmt->bind_param("di", $crash, $round_id);
//         $stmt->execute();
//     }

//     header("Location: admin_bet_control.php?cp=ok");
//     exit;
// }

/* ================================
   FETCH CURRENT ROUND
================================ */
$currentRound = getLatestRound($conn);

/* ================================
   FETCH BET STATS FOR ROUND
=============================== */
$betStats = ['count' => 0, 'total' => 0];
if ($currentRound) {
    $rid = (int)$currentRound['round_id'];
    $q = $conn->query("SELECT COUNT(*) AS bet_count, IFNULL(SUM(bet_amount),0) AS bet_total FROM bets_tbl WHERE round_id = $rid");
    if ($q && $row = $q->fetch_assoc()) {
        $betStats['count'] = $row['bet_count'];
        $betStats['total'] = $row['bet_total'];
    }
}

/* ================================
   SET GLOBAL CRASH POINT
================================ */
if (isset($_POST['action']) && $_POST['action'] === 'set_crash') {

    $crash = (float)$_POST['crash_point'];

    if ($crash >= 1) {
        $stmt = $conn->prepare("
            UPDATE game_config_tbl
            SET current_crash_point = ?
            WHERE id = 1
        ");
        $stmt->bind_param("d", $crash);
        $stmt->execute();
    }

    header("Location: admin_bet_control.php?cp=ok");
    exit;
}

/* ================================
   AUTO ROUND ENGINE (SAFE)
   AJAX ONLY
================================ */
if (isset($_GET['auto_engine'])) {
    header('Content-Type: application/json');

    $cfg = $conn->query("SELECT auto_round FROM game_config_tbl WHERE id=1")->fetch_assoc();

    // Auto round OFF → do nothing
    if (!$cfg || (int)$cfg['auto_round'] !== 1) {
        echo json_encode(['status' => 'off']);
        exit;
    }

    // Any running round?
    $running = $conn->query("
        SELECT round_id FROM game_rounds_tbl
        WHERE status='running'
        LIMIT 1
    ");

    if ($running->num_rows) {
        echo json_encode(['status' => 'running']);
        exit;
    }

    // Any waiting round?
    $waiting = $conn->query("
        SELECT round_id, created_at FROM game_rounds_tbl
        WHERE status='waiting'
        ORDER BY round_id DESC
        LIMIT 1
    ");

    if (!$waiting->num_rows) {
        // 🔥 CREATE WAITING ROUND
        $cfg = $conn->query("
            SELECT current_crash_point
            FROM game_config_tbl
            WHERE id=1
        ")->fetch_assoc();

        $round_code = 'RND-' . date('Ymd-His') . '-' . rand(100,999);
        $cp = (float)$cfg['current_crash_point'];

        $stmt = $conn->prepare("
            INSERT INTO game_rounds_tbl (round_code, status, crash_point, created_at)
            VALUES (?, 'waiting', ?, NOW())
        ");
        $stmt->bind_param("sd", $round_code, $cp);
        $stmt->execute();

        echo json_encode(['status' => 'created']);
        exit;
    }

    // Waiting round exists → check time
    $row = $waiting->fetch_assoc();
    $created = strtotime($row['created_at']);

    if (time() - $created >= 5) {
        // 🚀 START ROUND
        $conn->query("
            UPDATE game_rounds_tbl
            SET status='running', started_at=NOW()
            WHERE round_id={$row['round_id']}
        ");

        echo json_encode(['status' => 'started']);
        exit;
    }

    echo json_encode(['status' => 'waiting']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'toggle_auto_round') {
    $conn->query("
        UPDATE game_config_tbl
        SET auto_round = IF(auto_round=1,0,1)
        WHERE id=1
    ");
    header("Location: admin_bet_control.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviator Admin | Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-body: #0b0e14;
            --bg-card: #161b22;
            --accent-color: #e91e63;
            --text-muted: #8b949e;
            --border-color: #30363d;
        }

        body { 
            background-color: var(--bg-body); 
            color: #f0f6fc; 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        /* Sidebar-like layout wrapper */
        .admin-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }

        .dashboard-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .card-header {
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .card-header i { margin-right: 10px; color: var(--accent-color); }

        .stat-box {
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .stat-label { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { font-size: 1.5rem; font-weight: 700; margin-top: 5px; }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .status-running { background: #238636; color: #fff; box-shadow: 0 0 10px rgba(35, 134, 54, 0.4); animation: pulse 2s infinite; }
        .status-waiting { background: #d29922; color: #fff; }
        .status-crashed { background: #da3633; color: #fff; }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .form-control {
            background: #0d1117;
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 12px;
        }

        .form-control:focus {
            background: #0d1117;
            border-color: var(--accent-color);
            color: #fff;
            box-shadow: none;
        }

        .btn { border-radius: 8px; padding: 10px 20px; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: #2f81f7; border: none; }
        .btn-success { background: #238636; border: none; }
        .btn-danger { background: #da3633; border: none; }
        .btn-warning { background: #d29922; border: none; color: #fff; }
        .btn:hover { filter: brightness(1.2); transform: translateY(-1px); }

        .round-info-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .round-info-item:last-child { border-bottom: none; }
        .round-info-label { color: var(--text-muted); }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-plane-departure me-2 text-danger"></i> Aviator Control</h2>
        <div class="text-muted small">System Time: <?= date('H:i:s') ?></div>
    </div>

    <div class="row">
        <!-- Live Status Column -->
        <div class="col-lg-5">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-satellite-dish"></i> Live Round Monitor
                </div>
                <div class="p-4">
                    <?php if ($currentRound): ?>
                        <div class="text-center mb-4">
                            <span class="status-badge status-<?= $currentRound['status'] ?>">
                                <?= $currentRound['status'] ?>
                            </span>
                        </div>

                        <div class="round-info-item">
                            <span class="round-info-label">Round ID</span>
                            <span>#<?= $currentRound['round_id'] ?></span>
                        </div>
                        <div class="round-info-item">
                            <span class="round-info-label">Round Code</span>
                            <span class="font-monospace text-info"><?= $currentRound['round_code'] ?></span>
                        </div>
                        <div class="round-info-item">
                            <span class="round-info-label">Crash Point</span>
                            <span class="fw-bold text-warning"><?= $currentRound['crash_point'] ? $currentRound['crash_point'].'x' : '---' ?></span>
                        </div>

                        <!--<div class="row mt-4 g-2">
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-label">Bets</div>
                                    <div class="stat-value text-primary"><?= $betStats['count'] ?></div> -->
<!--                                     <div class="stat-value text-primary" id="liveBets">
                                        <?= $betStats['count'] ?>
                                    </div>
                                    <div class="stat-value text-primary" id="liveBetCount">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-label">Total Volume</div>
                                    <div class="stat-value text-success">₹<?= number_format($betStats['total'], 0) ?></div> 
                                   <div class="stat-value text-success" id="liveTotal">
                                        ₹<?= number_format($betStats['total'], 0) ?>
                                    </div>
                                    <div class="stat-value text-success" id="liveBetTotal">₹0</div>
                                </div>
                            </div>
                        </div>-->
                        <div class="row mt-4 g-2">
    <div class="col-4">
        <div class="stat-box">
            <div class="stat-label">Bets</div>
            <div class="stat-value text-primary" id="liveBetCount">0</div>
        </div>
    </div>

    <div class="col-4">
        <div class="stat-box">
            <div class="stat-label">Total Bet</div>
            <div class="stat-value text-success" id="liveBetTotal">₹0</div>
        </div>
    </div>

    <div class="col-4">
        <div class="stat-box">
            <div class="stat-label">Cashed Out</div>
            <div class="stat-value text-warning" id="liveCashoutTotal">₹0</div>
        </div>
    </div>
</div>
                    <?php else: ?>
                        <div class="alert alert-dark border-secondary text-center">
                            No active rounds found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actions Column -->
        <div class="col-lg-7">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <i class="fas fa-gamepad"></i> Game Master Controls
                </div>
                <div class="p-4">
                    
                    <div class="mb-4">
                        <label class="stat-label mb-2 d-block">Initialization</label>
                        <form method="post" class="mb-2">
                            <input type="hidden" name="action" value="new_round">
                            <button class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle me-2"></i> Create New Waiting Round
                            </button>
                        </form>
                        
                        <form method="post">
                            <input type="hidden" name="action" value="start_round">
                            <button class="btn btn-success w-100">
                                <i class="fas fa-play me-2"></i> Take Off (Start Round)
                            </button>
                        </form>
                    </div>

                    <div class="mb-4">
                        <label class="stat-label mb-2 d-block">In-Game Manipulation</label>
                        <form method="post" class="row g-2 align-items-center">
                            <input type="hidden" name="action" value="set_crash">
                            <div class="col-7">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-white">x</span>
                                    <input type="number" step="0.01" min="1"
                                           name="crash_point" class="form-control"
                                           placeholder="Set Crash Point">
                                </div>
                            </div>
                            <div class="col-5">
                                <button class="btn btn-warning w-100">
                                    <i class="fas fa-crosshairs me-2"></i> Fix Crash
                                </button>
                            </div>
                        </form>
                    </div>

<!--                     <div class="mt-4 pt-3 border-top border-secondary">
                        <label class="stat-label mb-2 d-block text-danger">Emergency</label>
                        <form method="post">
                            <input type="hidden" name="action" value="force_crash">
                            <button class="btn btn-danger w-100 py-3">
                                <i class="fas fa-skull-crossbones me-2"></i> FORCE IMMEDIATE CRASH
                            </button>
                        </form>
                    </div> -->
                    <div class="mt-4 pt-3 border-top border-secondary">
    <label class="stat-label mb-2 d-block text-danger">Emergency</label>
    <!-- We use a button instead of a form to prevent page refresh -->
    <button type="button" id="forceCrashBtn" class="btn btn-danger w-100 py-3" onclick="triggerImmediateCrash()">
        <i class="fas fa-skull-crossbones me-2"></i> FORCE IMMEDIATE CRASH
    </button>
    <div id="crashFeedback" class="text-center mt-2 small text-warning" style="display:none;"></div>
</div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// function updateRoundStats() {
//     fetch('round_stats_api.php', { cache: 'no-store' })
//         .then(res => res.json())
//         .then(data => {
//             document.getElementById('liveBets').innerText = data.bets;
//             document.getElementById('liveTotal').innerText =
//                 '₹' + Number(data.total).toLocaleString();
//         })
//         .catch(err => console.error('Stats error:', err));
// }

// // Update every 1 second
// setInterval(updateRoundStats, 1000);

// // Initial load
// updateRoundStats();


//     function fetchLiveStats() {
//     fetch('admin_round_stats_api.php', { cache: 'no-store' })
//         .then(r => r.json())
//         .then(d => {
//             if (d.status !== 'ok') return;

//             document.getElementById('liveBetCount').innerText = d.bet_count;
//             document.getElementById('liveBetTotal').innerText = '₹' + d.bet_total.toFixed(0);
//         })
//         .catch(err => console.error('Live stats error:', err));
// }

// // 🔁 Refresh every 1 second
// setInterval(fetchLiveStats, 1000);

// // 🚀 Load instantly
// fetchLiveStats();

    function fetchLiveStats() {
    fetch('admin_round_stats_api.php', { cache: 'no-store' })
        .then(r => r.json())
        .then(d => {
            if (d.status !== 'ok') return;

            document.getElementById('liveBetCount').innerText = d.bet_count;
            document.getElementById('liveBetTotal').innerText =
                '₹' + d.bet_total.toFixed(0);

            document.getElementById('liveCashoutTotal').innerText =
                '₹' + d.cashout_total.toFixed(0);
        })
        .catch(err => console.error('Live stats error:', err));
}

// Refresh every second
setInterval(fetchLiveStats, 1000);
fetchLiveStats();

setInterval(() => {
    fetch('?auto_engine=1', { cache: 'no-store' })
        .then(r => r.json())
        .then(d => {
            // console.log('AUTO:', d.status);
        })
        .catch(()=>{});
}, 2000);



    /* ===============================
       FORCE CRASH AT LIVE MULTIPLIER
    =============================== */
    // function forceCrashNow() {

    //     const multText = document.querySelector('.big-multiplier');
    //     if (!multText) {
    //         alert('Plane UI not found');
    //         return;
    //     }

    //     const currentMultiplier = parseFloat(
    //         multText.innerText.replace('x','')
    //     );

    //     if (!currentMultiplier || currentMultiplier < 1) {
    //         alert('Plane not flying');
    //         return;
    //     }

    //     fetch('admin_bet_control.php', {
    //         method: 'POST',
    //         headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    //         body: new URLSearchParams({
    //             action: 'force_crash',
    //             crash_point: currentMultiplier
    //         })
    //     });

    //     console.log('🔥 Forced crash at', currentMultiplier);
    // }

// function forceCrashNow() {
//     fetch('admin_bet_control.php', {
//         method: 'POST',
//         headers: {'Content-Type': 'application/x-www-form-urlencoded'},
//         body: new URLSearchParams({
//             action: 'force_crash_now'
//         })
//     }).then(() => {
//         console.log('🔥 Force crash requested');
//     });
// }


// function triggerImmediateCrash() {
//     const btn = document.getElementById('forceCrashBtn');
//     const feedback = document.getElementById('crashFeedback');
    
//     // Visual feedback that the button was pressed
//     btn.disabled = true;
//     btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> CRASHING...';

//     // Send the request via AJAX (Fetch)
//     fetch('admin_bet_control.php', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/x-www-form-urlencoded',
//         },
//         body: 'action=force_crash'
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.status === 'success') {
//             feedback.style.display = 'block';
//             feedback.innerText = '💥 Plane stopped at ' + data.crashed_at + 'x';
            
//             // Re-enable after a short delay
//             setTimeout(() => {
//                 btn.disabled = false;
//                 btn.innerHTML = '<i class="fas fa-skull-crossbones me-2"></i> FORCE IMMEDIATE CRASH';
//                 feedback.style.display = 'none';
//             }, 2000);
//         } else {
//             alert('No running round found to crash.');
//             btn.disabled = false;
//             btn.innerHTML = '<i class="fas fa-skull-crossbones me-2"></i> FORCE IMMEDIATE CRASH';
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         btn.disabled = false;
//         btn.innerHTML = '<i class="fas fa-skull-crossbones me-2"></i> FORCE IMMEDIATE CRASH';
//     });
// }

// function triggerImmediateCrash() {
//     const btn = document.getElementById('forceCrashBtn');
//     const feedback = document.getElementById('crashFeedback');
    
//     // Prevent double clicking
//     btn.disabled = true;
//     btn.innerHTML = '<i class="fas fa-biohazard fa-spin me-2"></i> KILLING ROUND...';

//     fetch('admin_bet_control.php', {
//         method: 'POST',
//         headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//         body: 'action=force_crash'
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.status === 'success') {
//             feedback.style.display = 'block';
//             feedback.className = "text-center mt-2 small text-danger fw-bold";
//             feedback.innerHTML = '💥 EMERGENCY STOP: Plane crashed at ' + data.crashed_at + 'x';
            
//             // Refresh the live stats immediately to show the losses/settlement
//             fetchLiveStats();

//             setTimeout(() => {
//                 btn.disabled = false;
//                 btn.innerHTML = '<i class="fas fa-skull-crossbones me-2"></i> FORCE IMMEDIATE CRASH';
//                 feedback.style.display = 'none';
//                 // Optional: window.location.reload(); // If you want to refresh the whole UI
//             }, 3000);
//         } else {
//             alert('Error: No running round detected.');
//             btn.disabled = false;
//             btn.innerHTML = '<i class="fas fa-skull-crossbones me-2"></i> FORCE IMMEDIATE CRASH';
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         btn.disabled = false;
//     });
// }

function triggerImmediateCrash() {
    const btn = document.getElementById('forceCrashBtn');
    const feedback = document.getElementById('crashFeedback');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-biohazard fa-spin me-2"></i> FORCING CRASH...';

    fetch('admin_bet_control.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=force_crash'
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            feedback.style.display = 'block';
            feedback.innerHTML = `💥 Crash locked at ${data.crashed_at}x`;
        } else {
            alert('No running round');
        }
    })
    .catch(console.error)
    .finally(() => {
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-skull-crossbones me-2"></i> FORCE IMMEDIATE CRASH';
            feedback.style.display = 'none';
        }, 2000);
    });
}

// function triggerImmediateCrash() {
//     const mult = parseFloat(document.getElementById('multiplier').innerText.replace('x',''));

//     if (!mult || mult < 1) {
//         alert('Plane not flying');
//         return;
//     }

//     fetch('admin_bet_control.php', {
//         method: 'POST',
//         headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//         body: new URLSearchParams({
//             action: 'force_crash',
//             crash_point: mult
//         })
//     })
//     .then(r => r.json())
//     .then(d => console.log('Crash at', d.crashed_at));
// }

</script>


</body>
</html>