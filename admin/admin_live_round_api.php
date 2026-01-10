<?php
include "db.php";
header('Content-Type: application/json');

// ENABLE TEMP DEBUG (remove later)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$round_id = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;

$response = [
    'cashout_count'   => 0,
    'cashout_amount' => "0.00",
    'bets'            => []
];

if ($round_id <= 0) {
    echo json_encode($response);
    exit;
}

/* ===== CASHOUT COUNT ===== */
$q = $conn->prepare("
    SELECT COUNT(*) 
    FROM bets_tbl 
    WHERE round_id = ? AND result = 'won'
");
$q->bind_param("i", $round_id);
$q->execute();
$q->bind_result($cnt);
$q->fetch();
$q->close();
$response['cashout_count'] = (int)$cnt;

/* ===== TOTAL CASHOUT AMOUNT ===== */
$q2 = $conn->prepare("
    SELECT IFNULL(SUM(win_amount),0)
    FROM bets_tbl
    WHERE round_id = ? AND result = 'won'
");
$q2->bind_param("i", $round_id);
$q2->execute();
$q2->bind_result($amt);
$q2->fetch();
$q2->close();
$response['cashout_amount'] = number_format($amt, 2, '.', '');

/* ===== LIVE BET LIST (NO get_result) ===== */
$q3 = $conn->prepare("
    SELECT 
        b.bet_amount,
        b.result,
        IFNULL(b.win_amount,0),
        u.name
    FROM bets_tbl b
    JOIN users_tbl u ON u.id = b.user_id
    WHERE b.round_id = ?
    ORDER BY b.bet_id DESC
");

if (!$q3) {
    echo json_encode($response);
    exit;
}

$q3->bind_param("i", $round_id);
$q3->execute();
$q3->bind_result($bet_amount, $result, $win_amount, $name);

while ($q3->fetch()) {

    if ($result === 'pending') {
        $uiResult = 'flying';
    } elseif ($result === 'won') {
        $uiResult = 'won';
    } else {
        $uiResult = 'lost';
    }

    $response['bets'][] = [
        'name'       => $name,
        'bet_amount' => (float)$bet_amount,
        'result'     => $uiResult,
        'win_amount' => (float)$win_amount
    ];
}

$q3->close();

echo json_encode($response);
