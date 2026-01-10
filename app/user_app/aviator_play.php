<?php
// ================= INTERNAL CASHOUT API =================
if (isset($_GET['action']) && $_GET['action'] === 'cashout') {

    header('Content-Type: application/json');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require "db.php";

    $user_id = $_SESSION['user_id'] ?? 0;
    $bet_id = isset($_GET['bet_id']) ? (int) $_GET['bet_id'] : 0;
    $multiplier = isset($_GET['multiplier']) ? (float) $_GET['multiplier'] : 0;

    // 🔍 HARD VALIDATION
    if ($user_id <= 0 || $bet_id <= 0 || $multiplier <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid input',
            'debug' => compact('user_id', 'bet_id', 'multiplier')
        ]);
        exit;
    }

    // 🔍 VERIFY BET
    $q = $conn->query("
        SELECT bet_amount 
        FROM bets_tbl 
        WHERE bet_id = $bet_id 
          AND user_id = $user_id 
          AND result = 'pending'
        LIMIT 1
    ");

    if (!$q || $q->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Bet not found or already cashed'
        ]);
        exit;
    }

    $bet = $q->fetch_assoc();
    $bet_amount = (float) $bet['bet_amount'];
    $win_amount = round($bet_amount * $multiplier, 2);

    // 🔍 CURRENT BALANCE
    $balQ = $conn->query("
        SELECT IFNULL(SUM(
            CASE 
                WHEN type IN ('deposit','win') THEN amount
                WHEN type IN ('bet','withdraw','refund') THEN -amount
                ELSE 0
            END
        ),0) AS bal
        FROM wallet_transactions_tbl
        WHERE user_id = $user_id
    ");
    $balance = (float) $balQ->fetch_assoc()['bal'];
    $new_balance = $balance + $win_amount;

    // 🔒 TRANSACTION
    $conn->begin_transaction();
    try {

        $conn->query("
            UPDATE bets_tbl
            SET result='won', win_amount=$win_amount
            WHERE bet_id=$bet_id
        ");

        $ref = 'WIN-' . $bet_id;
        $stmt = $conn->prepare("
            INSERT INTO wallet_transactions_tbl
            (user_id,type,amount,balance_after,reference)
            VALUES (?, 'win', ?, ?, ?)
        ");
        $stmt->bind_param("idds", $user_id, $win_amount, $new_balance, $ref);
        $stmt->execute();

        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'new_balance' => $new_balance,
            'win_amount' => $win_amount
        ]);

    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'DB error',
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
?>

<?php
// Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "db.php";

// 1. Session Check
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$user_id = $_SESSION['user_id'];

// 2. Get Current Round ID
$round_id = 0;
$result = $conn->query("SELECT round_id FROM game_rounds_tbl ORDER BY round_id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $round_id = $row['round_id'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aviator.1000X Pro</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-app: #121212;
            --bg-panel: #2c2d30;
            --primary-red: #F12C4C;
            --primary-green: #28a909;
            --primary-yellow: #dca009;
            --text-color: #ffffff;
        }

        body {
            background-color: var(--bg-app);
            color: var(--text-color);
            font-family: 'Roboto', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            user-select: none;
        }

        /* HEADER */
        .app-header {
            background-color: var(--bg-app);
            padding: 8px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #222;
            z-index: 100;
        }

        .logo {
            color: var(--primary-red);
            font-weight: 800;
            font-size: 1.3rem;
            font-style: italic;
            text-decoration: none;
            letter-spacing: 1px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .balance-area {
            color: var(--primary-green);
            font-weight: 700;
            font-size: 0.95rem;
        }

        /* MUSIC & EXIT BTN */
        .btn-icon {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #444;
            color: #fff;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            cursor: pointer;
        }

        .btn-icon:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-icon.active {
            background: var(--primary-red);
            border-color: var(--primary-red);
            box-shadow: 0 0 10px var(--primary-red);
        }

        /* HISTORY */
        .history-bar {
            background-color: #1a1a1a;
            padding: 0 10px;
            display: flex;
            align-items: center;
            height: 35px;
            border-bottom: 1px solid #222;
        }

        .history-list {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            flex-grow: 1;
            scrollbar-width: none;
        }

        .hist-pill {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .h-pink {
            color: #ec4899;
            background: rgba(236, 72, 153, 0.1);
        }

        .h-purple {
            color: #c026d3;
            background: rgba(192, 38, 211, 0.1);
        }

        .h-blue {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }

        /* STAGE */
        .stage-area {
            position: relative;
            flex-grow: 1;
            background: #000;
            background-image: linear-gradient(#1a1a1a 1px, transparent 1px), linear-gradient(90deg, #1a1a1a 1px, transparent 1px);
            background-size: 40px 40px;
            overflow: hidden;
            border-bottom: 2px solid #222;
            min-height: 200px;
        }

        canvas {
            display: block;
            width: 100%;
            height: 100%;
            position: relative;
            z-index: 1;
        }

        .stage-stats {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 10;
            pointer-events: none;
        }

        .big-multiplier {
            font-size: 4.5rem;
            font-weight: 900;
            color: white;
            text-shadow: 0 0 20px rgba(0, 0, 0, 0.8);
            transition: color 0.2s;
        }

        .big-multiplier.crashed {
            color: var(--primary-red);
        }

        .flew-away-msg {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-red);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
            display: none;
            text-shadow: 0 0 10px rgba(241, 44, 76, 0.5);
        }

        /* PLANE */
        .plane-container {
            position: absolute;
            width: 120px;
            height: 60px;
            z-index: 5;
            display: none;
            transition: top 0.1s linear, left 0.1s linear;
        }

        .plane-svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0px 0px 10px rgba(241, 44, 76, 0.6));
            /* Smooth Up/Down effect you requested */
            animation: hovering 1.5s infinite ease-in-out alternate;
        }

        @keyframes hovering {
            0% {
                transform: translateY(0px);
            }

            100% {
                transform: translateY(-8px);
            }
        }

        .plane-body {
            fill: var(--primary-red);
        }

        .plane-window {
            fill: #000;
        }

        .plane-detail {
            fill: #9e0b25;
        }

        .plane-propeller {
            transform-origin: 125px 25px;
            animation: spin-prop 0.1s linear infinite;
            fill: #666;
        }

        @keyframes spin-prop {
            from {
                transform: rotateX(0deg);
            }

            to {
                transform: rotateX(180deg);
            }
        }

        .fly-away-anim {
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
            opacity: 0;
            transform: translate(200px, -200px) scale(0.6) rotate(-20deg) !important;
        }

        .loader-overlay {
            position: absolute;
            top: 65%;
            left: 50%;
            transform: translateX(-50%);
            width: 180px;
            text-align: center;
            display: none;
        }

        .loader-text {
            color: #888;
            font-size: 0.8rem;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .loader-track {
            height: 4px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
        }

        .loader-fill {
            height: 100%;
            background: var(--primary-red);
            width: 0%;
        }

        /* CONTROLS */
        .scroll-container {
            height: 60%;
            overflow-y: auto;
            background: var(--bg-app);
        }

        .bet-panel {
            background-color: var(--bg-panel);
            margin: 8px 10px;
            border-radius: 12px;
            padding: 8px 10px;
            border: 1px solid #3a3a3a;
            position: relative;
        }

        .panel-feedback {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-red);
            font-weight: 900;
            font-size: 1.2rem;
            z-index: 20;
            display: none;
        }

        .panel-tabs {
            display: flex;
            justify-content: center;
            background: #141414;
            border-radius: 20px;
            padding: 2px;
            width: fit-content;
            margin: 0 auto 8px auto;
        }

        .tab-btn {
            background: transparent;
            border: none;
            color: #888;
            padding: 3px 15px;
            border-radius: 18px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .tab-btn.active {
            background: #333;
            color: white;
        }

        .bet-interface {
            display: flex;
            gap: 8px;
            height: 60px;
            margin-bottom: 8px;
        }

        .bet-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .amount-row {
            display: flex;
            align-items: center;
            background: #101010;
            border-radius: 6px;
            border: 1px solid #444;
            padding: 0;
            height: 32px;
        }

        .btn-stepper {
            width: 28px;
            height: 100%;
            background: transparent;
            border: none;
            color: #888;
            font-size: 1.1rem;
        }

        .input-amount {
            flex: 1;
            background: transparent;
            border: none;
            color: white;
            text-align: center;
            font-weight: 700;
            font-size: 0.9rem;
            width: 40px;
        }

        .input-amount:focus {
            outline: none;
        }

        .quick-row {
            display: flex;
            justify-content: space-between;
            margin-top: 4px;
        }

        .btn-quick {
            background: #1b1b1b;
            border: none;
            color: #888;
            font-size: 0.65rem;
            border-radius: 4px;
            width: 23%;
            padding: 3px 0;
        }

        .btn-main {
            flex: 1;
            border-radius: 8px;
            border: none;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transition: transform 0.1s;
        }

        .btn-main:active {
            transform: scale(0.96);
        }

        .btn-main.green {
            background: linear-gradient(180deg, #3bc117 0%, #28a909 100%);
            border-bottom: 3px solid #1e7e06;
        }

        .btn-main.red {
            background: linear-gradient(180deg, #ff4d6a 0%, #d0021b 100%);
            border-bottom: 3px solid #9e0215;
        }

        .btn-main.yellow {
            background: var(--primary-yellow);
            border-bottom: 3px solid #b38307;
            color: #4a3403;
        }

        .btn-main .btn-label {
            font-size: 1rem;
            font-weight: 600;
        }

        .btn-main .btn-val {
            font-size: 0.8rem;
            font-weight: 400;
        }

        .btn-main .btn-sub {
            font-size: 0.6rem;
            text-transform: none;
            opacity: 0.9;
        }

        .auto-options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 5px;
            border-top: 1px solid #3a3a3a;
            font-size: 0.75rem;
            color: #aaa;
        }

        .auto-grp {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-switch .form-check-input {
            width: 2em;
            height: 1em;
            background-color: #444;
            border-color: #444;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
        }

        .form-switch .form-check-input:checked {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .small-inp {
            background: #101010;
            border: 1px solid #444;
            color: white;
            width: 50px;
            border-radius: 12px;
            text-align: center;
            font-size: 0.75rem;
            padding: 1px 0;
        }

        .small-inp:disabled {
            color: #555;
            border-color: #333;
        }

        .stats-footer {
            background: #141414;
            padding: 0;
            margin-top: 10px;
        }

        .footer-tabs {
            display: flex;
            padding: 10px 15px;
            gap: 15px;
            border-bottom: 1px solid #222;
        }

        .ft-pill {
            background: transparent;
            border: none;
            color: #777;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 5px 15px;
            border-radius: 20px;
        }

        .ft-pill.active {
            background: #2c2d30;
            color: white;
        }

        .total-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 15px;
            border-bottom: 1px solid #222;
        }

        .avatars-stack {
            display: flex;
            align-items: center;
        }

        .av-img {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid #141414;
            margin-left: -8px;
        }

        .av-img:first-child {
            margin-left: 0;
        }

        .bets-count {
            margin-left: 8px;
            font-size: 0.8rem;
            color: #888;
        }

        .total-win-info {
            text-align: right;
        }

        .total-win-lbl {
            font-size: 0.6rem;
            color: #888;
        }

        .total-win-val {
            font-size: 0.9rem;
            font-weight: 700;
            color: white;
        }

        .list-header {
            display: flex;
            padding: 5px 15px;
            font-size: 0.7rem;
            color: #555;
            background: #101010;
        }

        .col-user {
            width: 30%;
        }

        .col-bet {
            width: 25%;
            text-align: right;
        }

        .col-x {
            width: 20%;
            text-align: right;
        }

        .col-win {
            width: 25%;
            text-align: right;
        }

        .bets-list {
            padding-bottom: 20px;
        }

        .bet-row {
            display: flex;
            padding: 6px 15px;
            font-size: 0.8rem;
            color: #ccc;
            border-bottom: 1px solid #1a1a1a;
            align-items: center;
            background: #121212;
        }

        .bet-row:nth-child(even) {
            background: #151515;
        }

        .bet-row.won {
            background: rgba(40, 169, 9, 0.05);
            border: 1px solid rgba(40, 169, 9, 0.1);
        }

        .u-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .u-name {
            color: #999;
            font-size: 0.8rem;
        }

        .val-green {
            color: var(--primary-green);
            font-weight: 700;
        }

        .val-mult {
            color: #3b82f6;
            font-weight: 700;
        }

        .toast-custom {
            position: fixed;
            top: 60px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(220, 53, 69, 0.95);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            z-index: 200;
            display: none;
        }

        /* --- AVIATOR SYSTEM LOADER --- */
        #loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #0f0f0f;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .loader-content {
            text-align: center;
        }

        .loading-plane {
            font-size: 5rem;
            color: var(--primary-red);
            display: inline-block;
            animation: planeFly 1.5s infinite ease-in-out;
            filter: drop-shadow(0 0 15px rgba(241, 44, 76, 0.5));
        }

        .loading-bar-container {
            width: 220px;
            height: 4px;
            background: #222;
            border-radius: 10px;
            margin-top: 30px;
            overflow: hidden;
            position: relative;
        }

        .loading-bar-fill {
            position: absolute;
            width: 0%;
            height: 100%;
            background: var(--primary-red);
            box-shadow: 0 0 10px var(--primary-red);
            animation: barFill 2s forwards;
        }

        .loading-text {
            margin-top: 15px;
            font-weight: 900;
            font-size: 0.75rem;
            letter-spacing: 3px;
            color: #555;
            text-transform: uppercase;
        }

        @keyframes planeFly {

            0%,
            100% {
                transform: translateY(0) rotate(-10deg);
            }

            50% {
                transform: translateY(-25px) rotate(10deg);
            }
        }

        @keyframes barFill {
            0% {
                width: 0%;
            }

            100% {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- SYSTEM LOADER -->
    <div id="loader-wrapper">
        <div class="loader-content">
            <i class="bi bi-airplane-engines-fill loading-plane"></i>
            <div class="loading-bar-container">
                <div class="loading-bar-fill"></div>
            </div>
            <div class="loading-text">Connecting to Server...</div>
        </div>
    </div>

    <!-- Sounds -->
    <audio id="bgm" loop
        src="https://cdn.pixabay.com/download/audio/2022/01/18/audio_d0a13f69d2.mp3?filename=space-atmosphere-15632.mp3"></audio>
    <audio id="sfx-bet" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3"></audio>
    <audio id="sfx-win" src="https://assets.mixkit.co/active_storage/sfx/2019/2019-preview.mp3"></audio>
    <audio id="sfx-flyaway"
        src="https://cdn.pixabay.com/download/audio/2022/03/10/audio_c8c8a73467.mp3?filename=whoosh-6316.mp3"></audio>

    <div id="toast" class="toast-custom">Notification</div>

    <!-- Header -->
    <header class="app-header">
        <a href="#" class="logo">Aviator<span
                style="font-size:1rem; color:white; font-style:normal; font-weight:400;">.1000X</span></a>

        <div class="header-right">
            <div class="balance-area"><span id="balance">...</span> INR</div>

            <!-- Music Toggle (Default ON) -->
            <button class="btn-icon active" id="musicBtn" onclick="toggleMusic()">
                <i class="bi bi-volume-up-fill" id="musicIcon"></i>
            </button>

            <!-- Exit Button -->
            <button class="btn-icon" onclick="confirmExit()">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </header>

    <!-- History -->
    <div class="history-bar">
        <div class="history-list" id="historyList"></div>
        <!-- <button class="btn btn-sm btn-dark border-secondary text-secondary ms-2" style="padding: 0 4px;">
            <i class="bi bi-clock-history" style="font-size:0.7rem"></i>
        </button> -->
        <button class="btn btn-sm btn-dark border-secondary text-secondary ms-2" style="padding: 0 4px;"
            onclick="toggleHistory()">
            <i class="bi bi-clock-history" style="font-size:0.7rem"></i>
        </button>
    </div>

    <!-- Canvas -->
    <div class="stage-area" id="stage">
        <canvas id="gameCanvas"></canvas>

        <!-- PROPELLER PLANE -->
        <div class="plane-container" id="plane">
            <svg class="plane-svg" viewBox="0 0 140 70" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="125" cy="25" rx="3" ry="25" class="plane-propeller" />
                <path class="plane-body" d="M10,40 Q30,10 80,15 L120,25 L120,35 L80,45 Q30,50 10,40 Z" fill="#F12C4C" />
                <path d="M10,40 L0,15 L15,20 Z" fill="#9e0b25" />
                <path d="M50,30 L30,55 L70,35 Z" fill="#9e0b25" />
                <path d="M70,18 Q85,15 95,22 L80,25 Z" fill="#000" />
            </svg>
        </div>

        <div class="stage-stats">
            <div class="big-multiplier" id="multiplier">1.00x</div>
            <div class="flew-away-msg" id="flewMsg">FLEW AWAY!</div>

            <div class="loader-overlay" id="loader">
                <div class="loader-text">WAITING FOR NEXT ROUND</div>
                <div class="loader-track">
                    <div class="loader-fill" id="loadBar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Betting Panels -->
    <div class="scroll-container">

        <!-- Panel 1 -->
        <div class="bet-panel">
            <div class="panel-feedback" id="feedback1">YOU LOST</div>
            <div class="bet-interface">
                <div class="bet-left">
                    <div class="amount-row">
                        <button class="btn-stepper" onclick="adjBet(1, -10)">-</button>
                        <input type="number" class="input-amount" id="betInput1" value="10">
                        <button class="btn-stepper" onclick="adjBet(1, 10)">+</button>
                    </div>
                    <div class="quick-row">
                        <button class="btn-quick" onclick="setBet(1,100)">100</button>
                        <button class="btn-quick" onclick="setBet(1,200)">200</button>
                        <button class="btn-quick" onclick="setBet(1,500)">500</button>
                        <button class="btn-quick" onclick="setBet(1,1000)">1k</button>
                    </div>
                </div>
                <button class="btn-main green" id="btnPanel1" onclick="action(1)">
                    <span class="btn-label" id="lblPanel1">Bet</span>
                    <span class="btn-val" id="valPanel1">10.00 INR</span>
                    <span class="btn-sub" id="subPanel1" style="display:none;">Waiting...</span>
                </button>
            </div>
            <div class="auto-options-row">
                <div class="auto-grp">
                    <div class="form-check form-switch mb-0 min-h-0"><input class="form-check-input" type="checkbox"
                            id="autoBet1" onchange="toggleAuto(1)"></div><span>Auto Bet</span>
                </div>
                <div class="auto-grp">
                    <span>Auto Cash Out</span>
                    <div class="form-check form-switch mb-0 min-h-0"><input class="form-check-input" type="checkbox"
                            id="autoCash1" onchange="toggleAuto(1)"></div>
                    <input type="number" id="autoCashVal1" class="small-inp" value="1.10" disabled><span>x</span>
                </div>
            </div>
        </div>

        <!-- Panel 2 -->
        <div class="bet-panel">
            <div class="panel-feedback" id="feedback2">YOU LOST</div>
            <div class="bet-interface">
                <div class="bet-left">
                    <div class="amount-row">
                        <button class="btn-stepper" onclick="adjBet(2, -10)">-</button>
                        <input type="number" class="input-amount" id="betInput2" value="10">
                        <button class="btn-stepper" onclick="adjBet(2, 10)">+</button>
                    </div>
                    <div class="quick-row">
                        <button class="btn-quick" onclick="setBet(2,100)">100</button>
                        <button class="btn-quick" onclick="setBet(2,200)">200</button>
                        <button class="btn-quick" onclick="setBet(2,500)">500</button>
                        <button class="btn-quick" onclick="setBet(2,1000)">1k</button>
                    </div>
                </div>
                <button class="btn-main green" id="btnPanel2" onclick="action(2)">
                    <span class="btn-label" id="lblPanel2">Bet</span>
                    <span class="btn-val" id="valPanel2">10.00 INR</span>
                    <span class="btn-sub" id="subPanel2" style="display:none;">Waiting...</span>
                </button>
            </div>
            <div class="auto-options-row">
                <div class="auto-grp">
                    <div class="form-check form-switch mb-0 min-h-0"><input class="form-check-input" type="checkbox"
                            id="autoBet2" onchange="toggleAuto(2)"></div><span>Auto Bet</span>
                </div>
                <div class="auto-grp">
                    <span>Auto Cash Out</span>
                    <div class="form-check form-switch mb-0 min-h-0"><input class="form-check-input" type="checkbox"
                            id="autoCash2" onchange="toggleAuto(2)"></div>
                    <input type="number" id="autoCashVal2" class="small-inp" value="2.00" disabled><span>x</span>
                </div>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="stats-footer">
            <div class="footer-tabs">
                <button class="ft-pill active">All Bets</button>
                <button class="ft-pill">Previous</button>
                <button class="ft-pill">Top</button>
            </div>
            <div class="total-bar">
                <div class="d-flex flex-column">
                    <div class="avatars-stack">
                        <img src="https://ui-avatars.com/api/?name=A&background=random" class="av-img">
                        <img src="https://ui-avatars.com/api/?name=B&background=random" class="av-img">
                        <span class="bets-count">142/500 Bets</span>
                    </div>
                    <div style="width: 100%; height: 3px; background: #333; margin-top: 5px; border-radius: 2px;">
                        <div style="width: 60%; height: 100%; background: #444; border-radius: 2px;"></div>
                    </div>
                </div>
                <div class="total-win-info">
                    <div class="total-win-lbl">Total win INR</div>
                    <div class="total-win-val">0.00</div>
                </div>
            </div>
            <div class="list-header">
                <div class="col-user">Player</div>
                <div class="col-bet">Bet INR</div>
                <div class="col-x">X</div>
                <div class="col-win">Win INR</div>
            </div>
            <div class="bets-list" id="betsList"></div>
        </div>

    </div>

    <!-- LOGIC -->
    <script>
        // CONFIG
        let balance = 0;
        let round_id = <?= $round_id ?>;
        let crashPoint = null;

        let roundStartTime = null;
        let synced = false;

        let serverOffset = 0;

        let currentRoundId = round_id;
        // let currentRoundId = null;

        let loaderInterval = null;

        let flightRAF = null;
        let hasStartedFlying = false;

        let historyVisible = true;

        /* ============================
           LOAD LAST 20 HISTORY
        ============================ */
        function loadHistory() {
            fetch('history_api.php?_=' + Date.now())
                .then(r => r.json())
                .then(list => {
                    const box = document.getElementById('historyList');
                    box.innerHTML = '';

                    list.forEach(val => {
                        const pill = document.createElement('span');
                        pill.innerText = val.toFixed(2) + 'x';

                        if (val >= 10) pill.className = 'hist-pill h-pink';
                        else if (val >= 2) pill.className = 'hist-pill h-purple';
                        else pill.className = 'hist-pill h-blue';

                        box.appendChild(pill);
                    });
                })
                .catch(err => console.error('History load error', err));
        }

        /* ============================
           TOGGLE HISTORY VISIBILITY
        ============================ */
        function toggleHistory() {
            const list = document.getElementById('historyList');
            historyVisible = !historyVisible;
            list.style.display = historyVisible ? 'flex' : 'none';
        }

        async function syncServerTime() {
            const r = await fetch('server_time.php', { cache: 'no-store' });
            const d = await r.json();
            serverOffset = d.server_time - Date.now();
        }

        let roundStartServerTime = null;

        function loadWallet() {
            fetch('wallet_api.php')
                .then(r => r.json())
                .then(d => {
                    balance = parseFloat(d.balance);
                    document.getElementById('balance').innerText = balance.toFixed(2);
                });
        }
        loadWallet();

        function fetchCrashPoint() {
            return fetch('game_state_api.php')
                .then(r => r.json())
                .then(d => {
                    crashPoint = d.crash_point !== null
                        ? parseFloat(d.crash_point)
                        : null;
                });
            // fetch('game_state_api.php')
            //     .then(r => r.json())
            //     .then(d => {
            //         if (d.crash_point !== null) {
            //             crashPoint = parseFloat(d.crash_point);
            //         }
            //     });
            // return fetch('game_state_api.php')
            //     .then(r => r.json())
            //     .then(d => {
            //         crashPoint = d.crash_point !== null ? parseFloat(d.crash_point) : null;
            //         roundStartTime = d.started_at ? new Date(d.started_at).getTime() : null;
            //         synced = true;
            //     });
        }

        let gameState = 'WAITING';
        let multiplier = 1.00;

        let bets = [
            null,
            { betId: 0, placed: false, active: false, amount: 10, autoBet: false, autoCash: false, cashVal: 1.10 },
            { betId: 0, placed: false, active: false, amount: 10, autoBet: false, autoCash: false, cashVal: 2.00 }
        ];

        // ELEMENTS
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const planeContainer = document.getElementById('plane');
        const elMult = document.getElementById('multiplier');
        const elLoader = document.getElementById('loader');
        const elBar = document.getElementById('loadBar');
        const flewMsg = document.getElementById('flewMsg');
        const toast = document.getElementById('toast');
        const sfxBet = document.getElementById('sfx-bet');
        const sfxWin = document.getElementById('sfx-win');
        const sfxFlyAway = document.getElementById('sfx-flyaway');
        const bgm = document.getElementById('bgm');
        bgm.volume = 0.3;

        // RESIZE
        function resize() {
            canvas.width = canvas.parentElement.offsetWidth;
            canvas.height = canvas.parentElement.offsetHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        // INIT
        populateDummyBets();
        [1.20, 2.30, 1.05, 10.50].forEach(addHistory);
        startGame();

        // --- MUSIC TOGGLE ---
        let musicOn = true;
        function toggleMusic() {
            musicOn = !musicOn;
            const btn = document.getElementById('musicBtn');
            const icon = document.getElementById('musicIcon');
            if (musicOn) {
                btn.classList.add('active');
                icon.className = 'bi bi-volume-up-fill';
                if (gameState === 'FLYING') bgm.play().catch(() => { });
            } else {
                btn.classList.remove('active');
                icon.className = 'bi bi-volume-mute-fill';
                bgm.pause();
            }
        }

        // --- EXIT ---
        function confirmExit() {
            if (confirm("Are you sure you want to exit the game?")) {
                window.location.href = "user_dash.php"; // Redirect
            }
        }


        // UI -- Rest

        function resetRoundUI() {

            // Clear crash visuals
            flewMsg.style.display = 'none';
            elMult.classList.remove('crashed');
            elMult.innerText = '1.00x';

            // Hide loss overlays
            document.getElementById('feedback1').style.display = 'none';
            document.getElementById('feedback2').style.display = 'none';

            // Reset plane
            planeContainer.classList.remove('fly-away-anim');
            planeContainer.style.display = 'none';

            // Reset bets (frontend only)
            for (let i = 1; i <= 2; i++) {
                bets[i].active = false;
                bets[i].placed = false;
            }
        }




        async function startGame() {
            await syncServerTime();

            const r = await fetch('live_round.php', { cache: 'no-store' });
            const round = await r.json();

            currentRoundId = round.round_id;
            crashPoint = round.crash_point ? parseFloat(round.crash_point) : null;

            if (round.started_at) {
                roundStartedAtServer = new Date(round.started_at).getTime();
            } else {
                roundStartedAtServer = null;
            }

            if (round.status === 'running') {
                fly(); // 🔥 SAFE JOIN
            } else {
                showWaiting();
            }
        }






        function syncRound() {
            fetch('live_round.php', { cache: 'no-store' })
                .then(r => r.json())
                .then(round => {

                    // 🔄 New round
                    if (round.round_id !== currentRoundId) {
                        currentRoundId = round.round_id;
                        hasStartedFlying = false;
                        crashPoint = round.crash_point;
                        showWaiting();
                        return;
                    }

                    // ⏳ Waiting
                    if (round.status === 'waiting') {
                        if (gameState !== 'WAITING') showWaiting();
                        return;
                    }

                    // ✈️ Running — START ONLY ONCE
                    if (round.status === 'running') {
                        crashPoint = parseFloat(round.crash_point);
                        if (!hasStartedFlying) {
                            hasStartedFlying = true;
                            startFlying(round.elapsed);
                        }
                        return;
                    }

                    // 💥 Crashed
                    if (round.status === 'crashed' && gameState === 'FLYING') {
                        crash();
                    }
                });
        }




        function checkAutoBets() {
            for (let i = 1; i <= 2; i++) {
                if (bets[i].autoBet && !bets[i].placed) {
                    let val = bets[i].amount;
                    if (balance >= val) {
                        document.getElementById('betInput' + i).value = val;
                        let fd = new FormData();
                        fd.append('round_id', round_id);
                        fd.append('amount', val);
                        fetch('place_bet.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
                            if (res.status === 'success') {
                                bets[i].placed = true;
                                bets[i].betId = res.bet_id;
                                balance = parseFloat(res.new_balance);
                                document.getElementById('balance').innerText = balance.toFixed(2);
                                playSfx(sfxBet);
                                updateBtns();
                            } else {
                                bets[i].autoBet = false;
                                document.getElementById('autoBet' + i).checked = false;
                                showToast(res.message);
                            }
                        });
                    }
                }
            }
        }




        function showWaiting() {

            // 🔥 STOP OLD LOADER
            if (loaderInterval) {
                clearInterval(loaderInterval);
                loaderInterval = null;
            }

            gameState = 'WAITING';
            multiplier = 1.00;

            resetRoundUI(); // ✅ clears YOU LOST + FLEW AWAY

            elLoader.style.display = 'block';
            elMult.style.display = 'none';

            // Restart progress bar smoothly
            elBar.style.width = '0%';
            let p = 0;

            loaderInterval = setInterval(() => {
                p += 2;
                elBar.style.width = p + '%';

                if (p >= 100) {
                    clearInterval(loaderInterval);
                    loaderInterval = null;
                }
            }, 100);

            updateBtns(); // bets enabled
        }


        function startFlying(serverElapsed) {
            if (gameState === 'FLYING') return;

            gameState = 'FLYING';

            elLoader.style.display = 'none';
            elMult.style.display = 'block';
            elMult.classList.remove('crashed');
            flewMsg.style.display = 'none';

            planeContainer.style.display = 'block';
            planeContainer.classList.remove('fly-away-anim');

            if (musicOn) bgm.play().catch(() => { });

            // Activate bets
            for (let i = 1; i <= 2; i++) {
                if (bets[i].placed) {
                    bets[i].active = true;
                    bets[i].placed = false;
                }
            }
            updateBtns();

            const startLocal = performance.now();
            const startElapsed = serverElapsed;

            function loop(now) {
                if (gameState !== 'FLYING') return;

                const t = startElapsed + (now - startLocal) / 1000;

                multiplier = 1 + (t * 0.06) + (t * t * 0.06);
                elMult.innerText = multiplier.toFixed(2) + 'x';

                drawGraph(t);
                updateBtns();
                checkAutoCashOut();

                // 💥 CRASH AT EXACT POINT
                if (crashPoint !== null && multiplier >= crashPoint) {
                    crash();
                    return;
                }

                flightRAF = requestAnimationFrame(loop);
            }

            flightRAF = requestAnimationFrame(loop);
        }




        function checkAutoCashOut() {
            for (let i = 1; i <= 2; i++) {
                if (bets[i].active && bets[i].autoCash) {
                    if (multiplier >= bets[i].cashVal) {
                        doCashOut(i);
                    }
                }
            }
        }

        function doCashOut(i) {
            bets[i].active = false;

            const url =
                'aviator_play.php?action=cashout' +
                '&bet_id=' + bets[i].betId +
                '&multiplier=' + multiplier +
                '&_=' + Date.now();

            fetch(url, {
                method: 'GET',
                credentials: 'include'
            })
                .then(r => r.text())
                .then(txt => {
                    console.log('CASHOUT RAW:', txt);

                    let res;
                    try { res = JSON.parse(txt); }
                    catch {
                        alert('Invalid server response');
                        return;
                    }

                    if (res.status === 'success') {
                        balance = parseFloat(res.new_balance);
                        document.getElementById('balance').innerText = balance.toFixed(2);
                        updateBtns();
                    } else {
                        alert(res.message || 'Cashout rejected');
                        console.log(res);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Cashout failed');
                });
        }


        function crash() {
            if (gameState === 'CRASHED') return;

            // ⛔ STOP FLIGHT LOOP
            if (flightRAF) {
                cancelAnimationFrame(flightRAF);
                flightRAF = null;
            }

            gameState = 'CRASHED';

            elMult.innerText = crashPoint.toFixed(2) + 'x';
            elMult.classList.add('crashed');

            playSfx(sfxFlyAway);
            flewMsg.style.display = 'block';

            // ✈️ Plane flies away
            planeContainer.classList.add('fly-away-anim');

            setTimeout(() => {
                planeContainer.style.display = 'none';
            }, 800);

            // addHistory(crashPoint);
            loadHistory();

            // ❌ Lose active bets
            for (let i = 1; i <= 2; i++) {
                if (bets[i].active) {
                    bets[i].active = false;
                    document.getElementById('feedback' + i).style.display = 'flex';
                }
            }

            updateBtns();
        }



        function action(id) {
            if (bgm.paused && musicOn) bgm.play().catch(() => { });
            let b = bets[id];

            if (gameState === 'FLYING' && b.active) {
                doCashOut(id);
            }
            else if (gameState === 'WAITING' && b.placed) {
                cancelBet(id);
            }
            else if (gameState === 'WAITING') {
                if (!b.placed) {
                    let val = parseFloat(document.getElementById('betInput' + id).value);
                    if (balance < val) { showToast("Low Balance"); return; }
                    let fd = new FormData();
                    fd.append('round_id', round_id);
                    fd.append('amount', val);

                    fetch('place_bet.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
                        if (res.status === 'success') {
                            b.placed = true; b.amount = val; b.betId = res.bet_id;
                            balance = parseFloat(res.new_balance);
                            document.getElementById('balance').innerText = balance.toFixed(2);
                            playSfx(sfxBet);
                            updateBtns();
                        } else { showToast(res.message); }
                    });
                }
            }
        }


        function cancelBet(id) {

            const b = bets[id];
            if (!b.placed || gameState !== 'WAITING') return;

            const fd = new FormData();
            fd.append('bet_id', b.betId);

            fetch('cancel_bet.php', {
                method: 'POST',
                body: fd
            })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {

                        // Reset bet state
                        b.placed = false;
                        b.active = false;
                        b.betId = 0;

                        balance = parseFloat(res.new_balance);
                        document.getElementById('balance').innerText = balance.toFixed(2);

                        updateBtns();
                        showToast("Bet Cancelled");

                    } else {
                        showToast(res.message || 'Cancel failed');
                    }
                })
                .catch(() => showToast('Cancel error'));
        }

        function updateBtns() {
            for (let i = 1; i <= 2; i++) {
                let b = bets[i];
                let btn = document.getElementById('btnPanel' + i);
                let lbl = document.getElementById('lblPanel' + i);
                let val = document.getElementById('valPanel' + i);
                let sub = document.getElementById('subPanel' + i);

                btn.className = 'btn-main';
                sub.style.display = 'none';

                if (gameState === 'FLYING' && b.active) {
                    btn.classList.add('yellow');
                    lbl.innerText = "CASH OUT";
                    val.innerText = (b.amount * multiplier).toFixed(2) + " INR";
                } else if (b.placed) {
                    // btn.classList.add('red');
                    // lbl.innerText = "WAITING";
                    // val.innerText = "Bet Placed";
                    // sub.style.display = 'block';
                    // sub.innerText = "Next Round";
                    btn.classList.add('red');
                    lbl.innerText = "CANCEL";
                    val.innerText = "Cancel Bet";
                    sub.style.display = 'block';
                    sub.innerText = "Waiting Round";
                } else {
                    btn.classList.add('green');
                    lbl.innerText = "BET";
                    val.innerText = parseFloat(document.getElementById('betInput' + i).value).toFixed(2) + " INR";
                }
            }
        }

        // --- UTILS ---
        function drawGraph(t) {
            let w = canvas.width; let h = canvas.height; ctx.clearRect(0, 0, w, h);

            // Grid Lines
            ctx.strokeStyle = '#222';
            ctx.beginPath(); ctx.moveTo(0, h - 50); ctx.lineTo(w, h - 50); ctx.stroke();

            let x = Math.min((multiplier - 1) * 100, w - 60);
            let y = h - Math.min((multiplier - 1) * 100, h - 50);

            // Gradient
            let grad = ctx.createLinearGradient(0, y, 0, h);
            grad.addColorStop(0, "rgba(241, 44, 76, 0.4)");
            grad.addColorStop(1, "rgba(241, 44, 76, 0.0)");

            ctx.beginPath(); ctx.moveTo(0, h); ctx.quadraticCurveTo(x / 2, h, x, y);
            ctx.shadowBlur = 10; ctx.shadowColor = "#F12C4C"; ctx.strokeStyle = "#F12C4C"; ctx.lineWidth = 4;
            ctx.stroke(); ctx.shadowBlur = 0;
            ctx.fillStyle = grad;
            ctx.lineTo(x, h); ctx.lineTo(0, h); ctx.fill();

            planeContainer.style.left = (x - 20) + 'px';
            planeContainer.style.top = (y - 30) + 'px';
            planeContainer.style.transform = `rotate(-15deg)`;
        }

        function toggleAuto(id) {
            bets[id].autoBet = document.getElementById('autoBet' + id).checked;
            bets[id].autoCash = document.getElementById('autoCash' + id).checked;
            document.getElementById('autoCashVal' + id).disabled = !bets[id].autoCash;
            if (bets[id].autoCash) bets[id].cashVal = parseFloat(document.getElementById('autoCashVal' + id).value);
        }
        document.getElementById('autoCashVal1').addEventListener('input', (e) => bets[1].cashVal = parseFloat(e.target.value));
        document.getElementById('autoCashVal2').addEventListener('input', (e) => bets[2].cashVal = parseFloat(e.target.value));

        function setBet(id, v) { document.getElementById('betInput' + id).value = v; bets[id].amount = v; updateBtns(); }
        function adjBet(id, d) {
            let el = document.getElementById('betInput' + id); let v = parseFloat(el.value) + d;
            if (v < 10) v = 10; el.value = v; bets[id].amount = v; updateBtns();
        }
        function addHistory(val) {
            let p = document.createElement('span'); p.innerText = val.toFixed(2) + 'x';
            if (val >= 10) p.className = 'hist-pill h-pink'; else if (val >= 2) p.className = 'hist-pill h-purple'; else p.className = 'hist-pill h-blue';
            document.getElementById('historyList').prepend(p);
        }
        function showToast(m) { toast.innerText = m; toast.style.display = 'block'; setTimeout(() => toast.style.display = 'none', 2000); }
        function playSfx(a) {
            if (musicOn) {
                try { a.currentTime = 0; a.play().catch(() => { }); } catch (e) { }
            }
        }
        function populateDummyBets() { /* Dummy data */ }

        // --- ADMIN POLL ---
        setInterval(() => {
            fetch('game_control_api.php?round_id=<?= $round_id ?>')
                .then(r => r.json())
                .then(data => {
                    data.forEach(c => {
                        if (c.action === 'force_crash' && gameState === 'FLYING') crash();
                        if (c.action === 'force_cashout' && gameState === 'FLYING') {
                            for (let i = 1; i <= 2; i++) { if (bets[i].active) doCashOut(i); }
                        }
                    });
                });
        }, 1000);

        setInterval(syncRound, 1000);

        function loadBetsList() {
            fetch('bets_list_api.php?_=' + Date.now())
                .then(res => res.json())
                .then(rows => {
                    const list = document.getElementById('betsList');
                    list.innerHTML = '';

                    rows.forEach(r => {
                        const div = document.createElement('div');
                        div.className = 'bet-row ' + (r.win > 0 ? 'won' : '');

                        div.innerHTML = `
                    <div class="col-user">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(r.user)}&background=random"
                             class="u-avatar">
                        <span class="u-name">${r.user}</span>
                    </div>
                    <div class="col-bet">${r.bet.toFixed(2)}</div>
                    <div class="col-x val-mult">${r.x > 0 ? r.x + 'x' : '-'}</div>
                    <div class="col-win ${r.win > 0 ? 'val-green' : ''}">
                        ${r.win > 0 ? r.win.toFixed(2) : '-'}
                    </div>
                `;
                        list.appendChild(div);
                    });
                })
                .catch(err => console.error('Leaderboard error:', err));
        }
        setInterval(loadBetsList, 1000);
        loadBetsList();

        setInterval(() => {
            fetch('/Client Project/avietor.1000X/game_engine_api.php', {
                cache: 'no-store'
            });
        }, 1000);

        window.onload = () => {
            // Hide System Loader after 2 seconds
            setTimeout(() => {
                const sysLoader = document.getElementById('loader-wrapper');
                sysLoader.style.transition = 'opacity 0.6s ease';
                sysLoader.style.opacity = '0';
                setTimeout(() => sysLoader.style.display = 'none', 600);
            }, 2000);

            // Your existing New Year or Game Start Logic
            setTimeout(showNY, 1000);
        };
    </script>
</body>

</html>