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

$user_id = (int)$_SESSION['user_id'];

/* --- FETCH USER DETAILS --- */
$userQ = $conn->prepare("
    SELECT name, email, created_at 
    FROM users_tbl 
    WHERE id = ?
    LIMIT 1
");
$userQ->bind_param("i", $user_id);   // ✅ FIXED
$userQ->execute();
$user = $userQ->get_result()->fetch_assoc();

if (!$user) {
    die("User not found"); // 🔒 safety
}

/* --- FETCH WALLET BALANCE --- */
$balQ = $conn->prepare("
    SELECT balance_after 
    FROM wallet_transactions_tbl 
    WHERE user_id = ? 
    ORDER BY id DESC 
    LIMIT 1
");
$balQ->bind_param("i", $user_id);
$balQ->execute();
$balRow = $balQ->get_result()->fetch_assoc();
$balance = $balRow['balance_after'] ?? 0.00;

/* --- FETCH STATS --- */
$statsQ = $conn->query("
    SELECT 
        COUNT(*) as total_bets,
        SUM(CASE WHEN result = 'won' THEN 1 ELSE 0 END) as wins,
        IFNULL(SUM(win_amount), 0) as total_profit
    FROM bets_tbl 
    WHERE user_id = $user_id
");
$stats = $statsQ->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pilot Profile | FlyWing.1000X</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #09090b; --card-bg: #18181b; --border: #27272a;
            --primary: #E81C4F; --green: #22c55e; --text: #f4f4f5; --text-muted: #a1a1aa;
        }

        body { background-color: var(--bg-dark); color: var(--text); font-family: 'Inter', sans-serif; padding-bottom: 90px; }

        /* --- AVIATOR LOADER --- */
        #loader-wrapper {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-dark); z-index: 10000;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .loader-plane { font-size: 4rem; color: var(--primary); animation: fly 1.5s infinite ease-in-out; }
        .loader-bar { width: 180px; height: 3px; background: #27272a; border-radius: 10px; margin-top: 20px; overflow: hidden; position: relative; }
        .loader-progress { position: absolute; width: 0%; height: 100%; background: var(--primary); animation: load 1.8s forwards; }
        @keyframes fly { 0%, 100% { transform: translateY(0) rotate(-5deg); } 50% { transform: translateY(-15px) rotate(5deg); } }
        @keyframes load { 0% { width: 0%; } 100% { width: 100%; } }

        /* --- NAVIGATION --- */
        .top-nav { background: var(--card-bg); border-bottom: 1px solid var(--border); padding: 15px 20px; position: sticky; top: 0; z-index: 100; }
        .brand { font-family: 'Rajdhani', sans-serif; font-weight: 800; font-size: 1.6rem; color: #fff; text-decoration: none; }
        .brand span { color: var(--primary); }
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #121214; border-top: 1px solid var(--border); display: flex; justify-content: space-around; padding: 12px 0; z-index: 1000; }
        .nav-item { text-align: center; color: var(--text-muted); text-decoration: none; font-size: 0.7rem; }
        .nav-item i { font-size: 1.4rem; display: block; }
        .nav-item.active { color: var(--primary); }

        /* --- PROFILE UI --- */
        .profile-hero {
            background: linear-gradient(180deg, #1e1e22 0%, #09090b 100%);
            padding: 40px 20px; text-align: center; border-bottom: 1px solid var(--border);
        }
        .avatar-circle {
            width: 90px; height: 90px; background: var(--primary); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; font-weight: 800; margin: 0 auto 15px;
            box-shadow: 0 0 25px rgba(232, 28, 79, 0.4); border: 4px solid #18181b;
        }
        .pilot-name { font-family: 'Rajdhani'; font-size: 1.8rem; font-weight: 700; margin-bottom: 2px; }
        .pilot-id { color: var(--primary); font-weight: 700; font-size: 0.85rem; letter-spacing: 1px; }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 25px; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border); padding: 15px 10px; border-radius: 12px; }
        .stat-val { display: block; font-family: 'Rajdhani'; font-size: 1.2rem; font-weight: 700; color: white; }
        .stat-lbl { display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-top: 2px; }

        .menu-section { margin-top: 20px; padding: 0 15px; }
        .menu-item {
            background: var(--card-bg); border: 1px solid var(--border); 
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px; border-radius: 12px; margin-bottom: 10px;
            text-decoration: none; color: white; transition: 0.3s;
        }
        .menu-item:active { transform: scale(0.98); background: #202024; }
        .menu-info { display: flex; align-items: center; }
        .menu-icon { 
            width: 35px; height: 35px; background: rgba(255,255,255,0.05); 
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            margin-right: 15px; color: var(--primary);
        }
    </style>
</head>
<body>

    <!-- AVIATOR LOADER -->
    <div id="loader-wrapper">
        <i class="bi bi-airplane-engines-fill loader-plane"></i>
        <div class="loader-bar"><div class="loader-progress"></div></div>
        <p class="mt-3 small fw-bold text-muted text-uppercase" style="letter-spacing: 2px;">Syncing Profile...</p>
    </div>

    <!-- TOP BAR -->
    <nav class="top-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="user_dash.php" class="brand">FlyWing<span>.1000X</span></a>
            <div class="text-white small fw-bold"><i class="bi bi-shield-check text-success me-1"></i> Verified</div>
        </div>
    </nav>

    <!-- PROFILE HERO -->
    <div class="profile-hero">
<div class="avatar-circle">
    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
</div>

<div class="pilot-name">
    <?php echo htmlspecialchars($user['name']); ?>
</div>
        <!-- <div class="pilot-name"><?php echo htmlspecialchars($user['name']); ?></div> -->
        <div class="pilot-id">PILOT ID: #FW<?php echo str_pad($user_id, 5, '0', STR_PAD_LEFT); ?></div>

        <div class="stats-grid container">
            <div class="stat-card">
                <span class="stat-val"><?php echo $stats['total_bets']; ?></span>
                <span class="stat-lbl">Total Flights</span>
            </div>
            <div class="stat-card">
                <span class="stat-val text-success"><?php echo $stats['wins']; ?></span>
                <span class="stat-lbl">Successful</span>
            </div>
            <div class="stat-card">
                <span class="stat-val text-warning">₹<?php echo number_format($balance, 0); ?></span>
                <span class="stat-lbl">Wallet</span>
            </div>
        </div>
    </div>

    <!-- MENU SECTION -->
    <div class="menu-section mb-5">
        
        <a href="edit_profile.php" class="menu-item">
            <div class="menu-info">
                <div class="menu-icon"><i class="bi bi-person-gear"></i></div>
                <div>
                    <div class="fw-bold">Account Settings</div>
                    <div class="text-muted small">Update your personal details</div>
                </div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>

        <a href="history.php" class="menu-item">
            <div class="menu-info">
                <div class="menu-icon"><i class="bi bi-journals"></i></div>
                <div>
                    <div class="fw-bold">Game Records</div>
                    <div class="text-muted small">View your betting history</div>
                </div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>

        <a href="https://wa.me/YOUR_NUMBER" class="menu-item">
            <div class="menu-info">
                <div class="menu-icon"><i class="bi bi-headset"></i></div>
                <div>
                    <div class="fw-bold">24/7 Support</div>
                    <div class="text-muted small">Contact flight control office</div>
                </div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>

        <a href="logout.php" class="menu-item" style="border-color: rgba(232, 28, 79, 0.2);">
            <div class="menu-info">
                <div class="menu-icon" style="background: rgba(232, 28, 79, 0.1);"><i class="bi bi-box-arrow-right"></i></div>
                <div>
                    <div class="fw-bold text-danger">Logout Session</div>
                    <div class="text-muted small">Securely exit FlyWing</div>
                </div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>

        <p class="text-center text-muted mt-4" style="font-size: 0.7rem; letter-spacing: 1px;">
            MEMBER SINCE: <?php echo date('M Y', strtotime($user['created_at'])); ?><br>
            APP VERSION 2.0.26
        </p>
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
        <a href="profile.php" class="nav-item active">
            <i class="bi bi-person-circle"></i>
            Profile
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // HIDE LOADER AFTER 1.5 SECONDS
        window.addEventListener('load', function() {
            setTimeout(function() {
                const loader = document.getElementById('loader-wrapper');
                loader.style.opacity = '0';
                loader.style.transition = 'opacity 0.5s ease';
                setTimeout(() => loader.style.display = 'none', 500);
            }, 1500);
        });
    </script>
</body>
</html>