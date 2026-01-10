<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// require "db.php";

// // fetch latest round
// $rq = $conn->query("
//     SELECT round_id 
//     FROM game_rounds_tbl 
//     ORDER BY round_id DESC 
//     LIMIT 1
// ");

// if (!$rq || $rq->num_rows === 0) {
//     echo json_encode([]);
//     exit;
// }

// $round_id = (int)$rq->fetch_assoc()['round_id'];

// // fetch bets for this round
// $q = $conn->query("
//     SELECT 
//         b.bet_amount,
//         b.win_amount,
//         b.result,
//         u.username
//     FROM bets_tbl b
//     JOIN users_tbl u ON u.user_id = b.user_id
//     WHERE b.round_id = $round_id
//     ORDER BY b.bet_id DESC
//     LIMIT 50
// ");

// if (!$q) {
//     http_response_code(500);
//     echo json_encode([
//         'error' => 'Query failed',
//         'mysql_error' => $conn->error
//     ]);
//     exit;
// }

// $data = [];

// while ($row = $q->fetch_assoc()) {
//     $data[] = [
//         'user' => $row['username'],
//         'bet'  => (float)$row['bet_amount'],
//         'x'    => $row['result'] === 'won'
//                     ? round($row['win_amount'] / $row['bet_amount'], 2)
//                     : 0,
//         'win'  => $row['result'] === 'won'
//                     ? (float)$row['win_amount']
//                     : 0
//     ];
// }

// header('Content-Type: application/json');
// echo json_encode($data);


// header('Content-Type: application/json');
// error_reporting(0); // VERY IMPORTANT (no HTML errors in JSON)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// require "db.php";

// /* ===============================
//    GET CURRENT ROUND
// ================================ */
// $q = $conn->query("
//     SELECT round_id
//     FROM game_rounds_tbl
//     WHERE status IN ('waiting','running','crashed')
//     ORDER BY round_id DESC
//     LIMIT 1
// ");

// if (!$q || $q->num_rows === 0) {
//     echo json_encode([]);
//     exit;
// }

// $round_id = (int)$q->fetch_assoc()['round_id'];

// /* ===============================
//    FETCH BETS FOR ROUND
// ================================ */
// $sql = "
//     SELECT 
//         u.username AS user,
//         b.bet_amount AS bet,
//         CASE 
//             WHEN b.result='won' THEN b.win_amount / b.bet_amount
//             ELSE 0
//         END AS x,
//         CASE 
//             WHEN b.result='won' THEN b.win_amount
//             ELSE 0
//         END AS win
//     FROM bets_tbl b
//     JOIN users_tbl u ON u.user_id = b.user_id
//     WHERE b.round_id = $round_id
//     ORDER BY b.bet_id DESC
//     LIMIT 50
// ";

// $res = $conn->query($sql);

// if (!$res) {
//     echo json_encode([]);
//     exit;
// }

// $out = [];
// while ($r = $res->fetch_assoc()) {
//     $out[] = [
//         'user' => $r['user'],
//         'bet'  => (float)$r['bet'],
//         'x'    => round((float)$r['x'], 2),
//         'win'  => (float)$r['win']
//     ];
// }

// echo json_encode($out);
// exit;

// header('Content-Type: application/json');
// require "db.php";

// error_reporting(0); // VERY IMPORTANT (no HTML errors in JSON)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


// /* 🔐 Get current round */
// $roundQ = $conn->query("
//     SELECT round_id 
//     FROM game_rounds_tbl 
//     ORDER BY round_id DESC 
//     LIMIT 1
// ");

// if (!$roundQ || !$roundQ->num_rows) {
//     echo json_encode([]);
//     exit;
// }

// $round_id = (int)$roundQ->fetch_assoc()['round_id'];

// /* 🔥 Fetch bets with users */
// $q = $conn->query("
//     SELECT 
//         b.bet_amount,
//         b.win_amount,
//         b.result,
//         u.name
//     FROM bets_tbl b
//     INNER JOIN users_tbl u ON u.id = b.user_id
//     WHERE b.round_id = $round_id
//     ORDER BY b.bet_id DESC
//     LIMIT 50
// ");

// $data = [];

// while ($row = $q->fetch_assoc()) {

//     $bet = (float)$row['bet_amount'];
//     $win = (float)$row['win_amount'];

//     $multiplier = ($win > 0 && $bet > 0)
//         ? round($win / $bet, 2)
//         : 0;

//     $data[] = [
//         'user' => htmlspecialchars($row['name']),
//         'bet'  => $bet,
//         'x'    => $multiplier,
//         'win'  => $win
//     ];
// }

// echo json_encode($data);

// header('Content-Type: application/json');
// include "db.php";

// /**
//  * Fetch latest running round
//  */
// $roundQ = $conn->query("
//     SELECT round_id 
//     FROM game_rounds_tbl 
//     WHERE status IN ('waiting','running','crashed')
//     ORDER BY round_id DESC 
//     LIMIT 1
// ");

// if (!$roundQ || !$roundQ->num_rows) {
//     echo json_encode([]);
//     exit;
// }

// $round_id = (int)$roundQ->fetch_assoc()['round_id'];

// /**
//  * Fetch bets with user data
//  */
// $q = $conn->query("
//     SELECT 
//         b.bet_amount,
//         b.win_amount,
//         b.result,
//         u.name AS username
//     FROM bets_tbl b
//     JOIN users_tbl u ON u.id = b.user_id
//     WHERE b.round_id = $round_id
//     ORDER BY b.bet_id DESC
//     LIMIT 50
// ");

// $data = [];

// while ($row = $q->fetch_assoc()) {
//     $data[] = [
//         'user' => htmlspecialchars($row['username']),
//         'bet'  => (float)$row['bet_amount'],
//         'x'    => $row['result'] === 'won' && $row['bet_amount'] > 0
//                     ? round($row['win_amount'] / $row['bet_amount'], 2)
//                     : 0,
//         'win'  => $row['result'] === 'won'
//                     ? (float)$row['win_amount']
//                     : 0
//     ];
// }

// echo json_encode($data);

// header('Content-Type: application/json');
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// require "db.php";

// /* 🔥 GET CURRENT ROUND */
// $roundQ = $conn->query("
//     SELECT round_id 
//     FROM game_rounds_tbl 
//     ORDER BY round_id DESC 
//     LIMIT 1
// ");

// if (!$roundQ || $roundQ->num_rows === 0) {
//     echo json_encode([]);
//     exit;
// }

// $round_id = (int)$roundQ->fetch_assoc()['round_id'];

// /* 🔥 FETCH BETS WITH USER NAMES */
// $sql = "
//     SELECT 
//         b.bet_id,
//         b.bet_amount,
//         b.result,
//         b.win_amount,
//         u.name AS user_name
//     FROM bets_tbl b
//     INNER JOIN users_tbl u ON u.id = b.user_id
//     WHERE b.round_id = $round_id
//     ORDER BY b.bet_id DESC
//     LIMIT 100
// ";

// $q = $conn->query($sql);

// if (!$q) {
//     http_response_code(500);
//     echo json_encode([
//         'error' => 'DB error',
//         'msg' => $conn->error
//     ]);
//     exit;
// }

// $data = [];
// while ($r = $q->fetch_assoc()) {
//     $data[] = [
//         'user' => $r['user_name'],
//         'bet'  => (float)$r['bet_amount'],
//         'x'    => $r['result'] === 'won' ? round($r['win_amount'] / $r['bet_amount'], 2) : 0,
//         'win'  => (float)$r['win_amount']
//     ];
// }

// echo json_encode($data);

require 'db.php';

$q = $conn->query("
    SELECT 
        u.name AS user,
        b.bet_amount AS bet,
        IF(b.result='won', b.win_amount / b.bet_amount, 0) AS x,
        b.win_amount AS win
    FROM bets_tbl b
    JOIN users_tbl u ON u.id = b.user_id
    ORDER BY b.bet_id DESC
    LIMIT 50
");

$data = [];
while ($row = $q->fetch_assoc()) {
    $data[] = [
        'user' => $row['user'],
        'bet'  => (float)$row['bet'],
        'x'    => round((float)$row['x'], 2),
        'win'  => (float)$row['win']
    ];
}

echo json_encode($data);