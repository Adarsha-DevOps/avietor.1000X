<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (!$conn) {
    die("Database connection failed");
}

$user_id = (int)$_SESSION['user_id'];

/* --- FETCH DEPOSITS --- */
$deposits = [];
$dep_q = $conn->query("
    SELECT * 
    FROM user_deposit_tbl 
    WHERE user_id = $user_id 
    ORDER BY deposit_id DESC
");
if ($dep_q) {
    while ($row = $dep_q->fetch_assoc()) {
        $deposits[] = $row;
    }
}

/* --- FETCH WITHDRAWALS --- */
$withdrawals = [];
$wit_q = $conn->query("
    SELECT * 
    FROM user_withdrawal_tbl 
    WHERE user_id = $user_id 
    ORDER BY withdraw_id DESC
");
if ($wit_q) {
    while ($row = $wit_q->fetch_assoc()) {
        $withdrawals[] = $row;
    }
}

/* --- FETCH BETS --- */
$bets = [];
$bet_q = $conn->query("
    SELECT 
        b.bet_id,
        b.round_id,
        b.bet_amount,
        b.win_amount,
        b.result,
        r.round_code
    FROM bets_tbl b
    LEFT JOIN game_rounds_tbl r ON b.round_id = r.round_id
    WHERE b.user_id = $user_id
    ORDER BY b.bet_id DESC
    LIMIT 50
");
if ($bet_q) {
    while ($row = $bet_q->fetch_assoc()) {
        $bets[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Transaction History | FlyWing.1000X</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #09090b; --card-bg: #18181b; --border: #27272a;
            --primary: #E81C4F; --green: #22c55e; --text: #f4f4f5; --text-muted: #a1a1aa;
        }

        body { background-color: var(--bg-dark); color: var(--text); font-family: 'Inter', sans-serif; padding-bottom: 90px; overflow-x: hidden; }

        /* --- AVIATOR LOADER --- */
        #loader-wrapper {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-dark); z-index: 10000;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .loader-plane { font-size: 4rem; color: var(--primary); animation: fly 1.5s infinite ease-in-out; }
        .loader-bar { width: 200px; height: 4px; background: #27272a; border-radius: 10px; margin-top: 20px; overflow: hidden; position: relative; }
        .loader-progress { position: absolute; width: 0%; height: 100%; background: var(--primary); animation: load 2s forwards; }
        @keyframes fly { 0%, 100% { transform: translateY(0) rotate(-5deg); } 50% { transform: translateY(-20px) rotate(5deg); } }
        @keyframes load { 0% { width: 0%; } 100% { width: 100%; } }

        /* --- NAVIGATION --- */
        .top-nav { background: var(--card-bg); border-bottom: 1px solid var(--border); padding: 15px 20px; position: sticky; top: 0; z-index: 100; }
        .brand { font-family: 'Rajdhani', sans-serif; font-weight: 800; font-size: 1.6rem; color: #fff; text-decoration: none; }
        .brand span { color: var(--primary); }
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #121214; border-top: 1px solid var(--border); display: flex; justify-content: space-around; padding: 12px 0; z-index: 1000; }
        .nav-item { text-align: center; color: var(--text-muted); text-decoration: none; font-size: 0.7rem; }
        .nav-item i { font-size: 1.4rem; display: block; }
        .nav-item.active { color: var(--primary); }

        /* --- TABS & TABLES --- */
        .nav-pills .nav-link { color: var(--text-muted); background: var(--card-bg); border: 1px solid var(--border); margin-right: 8px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; padding: 10px 20px; }
        .nav-pills .nav-link.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        .history-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; margin-top: 20px; }
        .table { margin-bottom: 0; color: var(--text); border-color: var(--border); }
        .table thead { background: rgba(255,255,255,0.03); }
        .table th { border: none; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; padding: 15px; }
        .table td { border-color: var(--border); padding: 15px; vertical-align: middle; font-size: 0.85rem; }

        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .bg-pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .bg-success-custom { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
        .bg-danger-custom { background: rgba(232, 28, 79, 0.1); color: var(--primary); }
    </style>
</head>
<body>

    <!-- AVIATOR LOADER -->
    <div id="loader-wrapper">
        <i class="bi bi-airplane-engines-fill loader-plane"></i>
        <div class="loader-bar"><div class="loader-progress"></div></div>
        <p class="mt-3 small fw-bold text-muted">PREPARING HISTORY...</p>
    </div>

    <!-- TOP BAR -->
    <nav class="top-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="user_dash.php" class="brand">FlyWing<span>.1000X</span></a>
            <a href="logout.php" class="text-danger fw-bold text-decoration-none small"><i class="bi bi-power"></i> LOGOUT</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h4 class="fw-bold mb-4">Transaction Logs</h4>

        <!-- TABS TRIGGER -->
        <ul class="nav nav-pills mb-3 flex-nowrap overflow-auto pb-2" id="pills-tab" role="tablist">
            <li class="nav-item-tab">
                <button class="nav-link active" id="pills-bets-tab" data-bs-toggle="pill" data-bs-target="#pills-bets" type="button">Game Bets</button>
            </li>
            <li class="nav-item-tab">
                <button class="nav-link" id="pills-dep-tab" data-bs-toggle="pill" data-bs-target="#pills-dep" type="button">Deposits</button>
            </li>
            <li class="nav-item-tab">
                <button class="nav-link" id="pills-wit-tab" data-bs-toggle="pill" data-bs-target="#pills-wit" type="button">Withdrawals</button>
            </li>
        </ul>

        <!-- TABS CONTENT -->
        <div class="tab-content" id="pills-tabContent">
            
            <!-- BETS HISTORY -->
            <div class="tab-pane fade show active" id="pills-bets">
                <div class="history-card">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Round</th>
                                    <th>Bet</th>
                                    <th>Multiplier</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($bets)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No game history found.</td></tr>
                                <?php else: foreach($bets as $b): ?>
                                    <tr>
                                        <td><span class="text-muted small">#<?php echo $b['round_id']; ?></span></td>
                                        <td class="fw-bold">₹<?php echo number_format($b['bet_amount'], 2); ?></td>
                                        <td class="<?php echo $b['result'] == 'won' ? 'text-success' : 'text-danger'; ?>">
                                            <?php
if ($b['result'] === 'won' && $b['bet_amount'] > 0) {
    echo number_format($b['win_amount'] / $b['bet_amount'], 2) . 'x';
} else {
    echo '---';
}
?>
                                        </td>
                                        <td>
                                            <?php if($b['result'] == 'won'): ?>
                                                <span class="text-success fw-bold">+₹<?php echo number_format($b['win_amount'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-danger small">LOSS</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- DEPOSIT HISTORY -->
            <div class="tab-pane fade" id="pills-dep">
                <div class="history-card">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>UTI</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($deposits)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No deposits found.</td></tr>
                                <?php else: foreach($deposits as $d): ?>
                                    <tr>
                                        <td><div class="small text-muted"><?php echo date('d M, Y', strtotime($d['created_at'])); ?></div></td>
                                        <td class="fw-bold">₹<?php echo number_format($d['deposit_amount'], 2); ?></td>
                                        <td><span class="small font-monospace"><?php echo $d['uti_number']; ?></span></td>
                                        <td>
                                            <?php 
                                            $stClass = ($d['status'] == 'approved') ? 'bg-success-custom' : (($d['status'] == 'rejected') ? 'bg-danger-custom' : 'bg-pending');
                                            ?>
                                            <span class="status-badge <?php echo $stClass; ?>"><?php echo $d['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- WITHDRAWAL HISTORY -->
            <div class="tab-pane fade" id="pills-wit">
                <div class="history-card">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($withdrawals)): ?>
                                    <tr><td colspan="3" class="text-center py-5 text-muted">No withdrawals found.</td></tr>
                                <?php else: foreach($withdrawals as $w): ?>
                                    <tr>
                                        <td><div class="small text-muted"><?php echo date('d M, Y', strtotime($w['created_at'])); ?></div></td>
                                        <td class="fw-bold">₹<?php echo number_format($w['withdraw_amount'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $stClass = ($w['status'] == 'approved') ? 'bg-success-custom' : (($w['status'] == 'rejected') ? 'bg-danger-custom' : 'bg-pending');
                                            ?>
                                            <span class="status-badge <?php echo $stClass; ?>"><?php echo $w['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- BOTTOM MOBILE NAV -->
    <div class="bottom-nav">
        <a href="user_dash.php" class="nav-item">
            <i class="bi bi-house-door-fill"></i>
            Home
        </a>
        <a href="deposit.php" class="nav-item">
            <i class="bi bi-plus-square-fill"></i>
            Deposit
        </a>
        <a href="withdraw.php" class="nav-item">
            <i class="bi bi-cash-stack"></i>
            Withdraw
        </a>
        <a href="history.php" class="nav-item active">
            <i class="bi bi-clock-history"></i>
            History
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // HIDE LOADER AFTER 2 SECONDS (OR WINDOW LOAD)
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loader-wrapper').style.opacity = '0';
                setTimeout(function() {
                    document.getElementById('loader-wrapper').style.display = 'none';
                }, 500);
            }, 1500);
        });
    </script>
</body>
</html>