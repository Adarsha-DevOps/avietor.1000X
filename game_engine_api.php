<?php
// game_engine_api.php
// AUTO ROUND ENGINE (NO OUTPUT)

require_once "db.php";

/* ==========================
   CONFIG
========================== */
define('WAIT_AFTER_CRASH', 3); // seconds

/* ==========================
   HELPER
========================== */
function generateRoundCode() {
    return 'RND-' . date('Ymd-His') . '-' . random_int(100,999);
}

/* ==========================
   GET LATEST ROUND
========================== */
$q = $conn->query("
    SELECT *
    FROM game_rounds_tbl
    ORDER BY round_id DESC
    LIMIT 1
");

$round = $q && $q->num_rows ? $q->fetch_assoc() : null;

/* ==========================
   GET GLOBAL CRASH POINT
========================== */
$cfg = $conn->query("
    SELECT current_crash_point
    FROM game_config_tbl
    WHERE id = 1
    LIMIT 1
")->fetch_assoc();

$globalCrash = isset($cfg['current_crash_point'])
    ? (float)$cfg['current_crash_point']
    : null;

/* ==========================
   NO ROUND EXISTS → CREATE
========================== */
if (!$round) {

    if ($globalCrash !== null) {
        $stmt = $conn->prepare("
            INSERT INTO game_rounds_tbl (round_code, status, crash_point)
            VALUES (?, 'waiting', ?)
        ");
        $code = generateRoundCode();
        $stmt->bind_param("sd", $code, $globalCrash);
        $stmt->execute();
    }

    http_response_code(204);
    exit;
}

/* ==========================
   LAST ROUND CRASHED → AUTO CREATE
========================== */
if ($round['status'] === 'crashed') {

    $endedAt = strtotime($round['ended_at'] ?? 'now');
    $now     = time();

    // wait few seconds before new round
    if (($now - $endedAt) >= WAIT_AFTER_CRASH) {

        // check no newer round created
        $chk = $conn->query("
            SELECT COUNT(*) AS c
            FROM game_rounds_tbl
            WHERE round_id > {$round['round_id']}
        ")->fetch_assoc();

        if ($chk['c'] == 0 && $globalCrash !== null) {

            $stmt = $conn->prepare("
                INSERT INTO game_rounds_tbl (round_code, status, crash_point)
                VALUES (?, 'waiting', ?)
            ");
            $code = generateRoundCode();
            $stmt->bind_param("sd", $code, $globalCrash);
            $stmt->execute();
        }
    }
}

http_response_code(204);
exit;
