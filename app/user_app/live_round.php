<?php
// include "db.php";

// $q = $conn->query("
//     SELECT * FROM game_rounds_tbl
//     ORDER BY round_id DESC
//     LIMIT 1
// ");
// $round = $q->fetch_assoc();

// /* IF RUNNING AND NO CRASH POINT → SET RANDOM ONCE */
// if ($round['status'] === 'running' && $round['crash_point'] === null) {

//     // RANDOM CRASH BETWEEN 1.2x - 10x
//     $randomCrash = round(mt_rand(120, 1000) / 100, 2);

//     $stmt = $conn->prepare("
//         UPDATE game_rounds_tbl
//         SET crash_point = ?
//         WHERE round_id = ?
//     ");
//     $stmt->bind_param("di", $randomCrash, $round['round_id']);
//     $stmt->execute();

//     $round['crash_point'] = $randomCrash;
// }

// echo json_encode([
//     'round_id'    => $round['round_id'],
//     'status'      => $round['status'],
//     'started_at'  => $round['started_at'],
//     'crash_point' => $round['crash_point']
// ]);

require "db.php";

$q = $conn->query("
    SELECT 
        round_id,
        status,
        crash_point,
        started_at,
        IF(
            status='running',
            TIMESTAMPDIFF(MICROSECOND, started_at, NOW()) / 1000000,
            0
        ) AS elapsed
    FROM game_rounds_tbl
    ORDER BY round_id DESC
    LIMIT 1
");

$round = $q->fetch_assoc();

echo json_encode([
    'round_id'     => (int)$round['round_id'],
    'status'       => $round['status'],
    'crash_point'  => $round['crash_point'],
    'elapsed'      => (float)$round['elapsed'] // 🔥 THIS IS THE FIX
]);

// require 'db.php';

// $q = $conn->query("
//     SELECT 
//         round_id,
//         status,
//         crash_point,
//         started_at,
//         TIMESTAMPDIFF(SECOND, started_at, NOW()) AS elapsed
//     FROM game_rounds_tbl
//     ORDER BY round_id DESC
//     LIMIT 1
// ");

// $round = $q->fetch_assoc();
// echo json_encode($round);