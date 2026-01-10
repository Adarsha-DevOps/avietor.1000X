<?php
include "db.php";
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Deposit Balance
$stmt = $conn->prepare("SELECT COALESCE(SUM(deposit_amount), 0) FROM user_deposit_tbl WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$dep_bal = $stmt->get_result()->fetch_row()[0];
$stmt->close();

// 2. Fetch Recent Transactions (Bets, Wins, Deposits)
$txns = [];
$q = $conn->query("
    (SELECT 'Deposit' as type, deposit_amount as amount, status, created_at, 'credit' as flow FROM user_deposit_tbl WHERE user_id=$user_id)
    UNION
    (SELECT type, amount, 'completed' as status, created_at, IF(type='win','credit','debit') as flow FROM wallet_transactions_tbl WHERE user_id=$user_id)
    ORDER BY created_at DESC LIMIT 15
");
while($r = $q->fetch_assoc()){ $txns[] = $r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>FlyWing.1000X - Dashboard</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #09090b; --card-bg: #18181b; --border: #27272a;
            --primary: #E81C4F; --green: #22c55e; --text: #f4f4f5; --text-muted: #a1a1aa;
        }
        body { background-color: var(--bg-dark); color: var(--text); font-family: 'Inter', sans-serif; padding-bottom: 80px; }
        
        /* HEADER */
        .top-nav {
            background: rgba(24, 24, 27, 0.95); backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border); padding: 15px 20px;
            position: sticky; top: 0; z-index: 100;
        }
        .brand { font-family: 'Rajdhani', sans-serif; font-weight: 800; font-size: 1.6rem; letter-spacing: 1px; color: #fff; text-decoration: none; }
        .brand span { color: var(--primary); }

        /* WALLET CARD */
        .wallet-card {
            background: linear-gradient(135deg, #1f1f23, #0f0f10);
            border: 1px solid var(--border); border-radius: 20px;
            padding: 25px; margin-top: 25px; position: relative; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .wallet-card::after {
            content:''; position: absolute; top: -60px; right: -60px;
            width: 180px; height: 180px; background: var(--primary);
            filter: blur(90px); opacity: 0.15; border-radius: 50%;
        }
        .bal-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); font-weight: 700; }
        .bal-value { font-family: 'Rajdhani'; font-size: 2.8rem; font-weight: 700; color: #fff; line-height: 1.1; margin-top: 5px; }
        .dep-value { font-size: 0.95rem; color: var(--green); font-weight: 600; margin-bottom: 20px; display: block;}
        
        .action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }
        .btn-action {
            background: #27272a; border: 1px solid #3f3f46; color: white;
            padding: 14px; border-radius: 12px; font-weight: 700; text-align: center;
            text-decoration: none; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-action:hover { background: #3f3f46; color: white; transform: translateY(-2px); }
        .btn-action.deposit { background: var(--primary); border-color: var(--primary); box-shadow: 0 4px 15px rgba(232, 28, 79, 0.3); }

        /* AVIATOR CARD (Featured) */
        .game-banner {
            margin-top: 30px;
            background: radial-gradient(circle at center, #2a0b12, #000);
            border-radius: 20px; border: 1px solid #3f121d;
            overflow: hidden; position: relative;
            box-shadow: 0 10px 40px rgba(232, 28, 79, 0.1);
        }
        .game-content { padding: 25px; position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; }
        .game-title h2 { font-family: 'Rajdhani'; font-weight: 800; font-size: 2rem; margin: 0; color: #fff; }
        .game-title p { color: var(--text-muted); margin: 0; font-size: 0.9rem; }
        .live-dot { width: 8px; height: 8px; background: var(--green); border-radius: 50%; display: inline-block; margin-right: 5px; box-shadow: 0 0 5px var(--green); }
        
        .play-btn {
            background: #fff; color: #000; font-weight: 800;
            padding: 10px 25px; border-radius: 30px; text-decoration: none;
            transition: 0.2s; display: inline-block;
        }
        .play-btn:hover { transform: scale(1.05); background: var(--primary); color: #fff; }
        
        /* PLANE DECORATION */
        .banner-plane {
            position: absolute; right: 20px; bottom: 10px; font-size: 6rem;
            color: var(--primary); opacity: 0.2; transform: rotate(-10deg);
        }

        /* TABLE */
        .table-section { margin-top: 35px; }
        .tbl-card { background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; }
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { 
            text-align: left; padding: 15px 20px; color: var(--text-muted); 
            font-size: 0.75rem; text-transform: uppercase; font-weight: 700;
            background: #202024; border-bottom: 1px solid var(--border);
        }
        .custom-table td { 
            padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.03); 
            color: #e4e4e7; font-size: 0.9rem; vertical-align: middle;
        }
        .custom-table tr:last-child td { border-bottom: none; }

        .txn-icon {
            width: 35px; height: 35px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; margin-right: 12px; flex-shrink: 0;
        }
        .icon-dep { background: rgba(34, 197, 94, 0.1); color: var(--green); }
        .icon-bet { background: rgba(239, 68, 68, 0.1); color: var(--primary); }
        .icon-win { background: rgba(250, 204, 21, 0.1); color: #facc15; }

        .amt-plus { color: var(--green); font-weight: 700; }
        .amt-minus { color: var(--text); opacity: 0.8; }

        /* BOTTOM NAV */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%;
            background: rgba(24, 24, 27, 0.95); backdrop-filter: blur(10px);
            border-top: 1px solid var(--border);
            display: flex; justify-content: space-around; padding: 12px 0;
            z-index: 1000;
        }
        .nav-item { text-align: center; color: var(--text-muted); text-decoration: none; font-size: 0.7rem; transition: 0.2s; }
        .nav-item i { font-size: 1.4rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: var(--primary); }

        @media(min-width: 768px) { .bottom-nav { display: none; } }

        /* New year 2026 */

    #newYearOverlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(8px);
        z-index: 9999; display: flex; align-items: center; justify-content: center;
        opacity: 0; visibility: hidden; transition: all 0.5s ease;
    }
    #newYearOverlay.active { opacity: 1; visibility: visible; }

    .ny-card {
        width: 90%; max-width: 450px; background: rgba(24, 24, 27, 0.8);
        border: 2px solid var(--primary); border-radius: 30px;
        padding: 40px 30px; text-align: center; position: relative;
        box-shadow: 0 0 50px rgba(232, 28, 79, 0.4);
        overflow: hidden; transform: scale(0.8); transition: transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    #newYearOverlay.active .ny-card { transform: scale(1); }

    .ny-year { 
        font-family: 'Rajdhani', sans-serif; font-size: 5rem; font-weight: 900; 
        line-height: 1; color: #fff; text-shadow: 0 0 20px var(--primary);
        margin-bottom: 10px; animation: glowPulse 2s infinite;
    }
    .ny-greet { 
        font-family: 'Rajdhani', sans-serif; font-size: 1.5rem; font-weight: 700; 
        color: var(--primary); text-transform: uppercase; letter-spacing: 2px;
    }
    .ny-quote {
        margin: 25px 0; font-style: italic; color: #d4d4d8; font-size: 1.1rem;
        line-height: 1.5; min-height: 60px;
    }
    
    .ny-btn {
        background: var(--primary); border: none; color: white; padding: 15px 40px;
        border-radius: 50px; font-weight: 800; font-size: 1rem;
        box-shadow: 0 10px 20px rgba(232, 28, 79, 0.3); transition: 0.3s;
    }
    .ny-btn:hover { transform: translateY(-3px); background: #fff; color: #000; }

    /* Canvas for fireworks */
    #nyCanvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: -1; }

    @keyframes glowPulse {
        0%, 100% { opacity: 1; filter: drop-shadow(0 0 10px var(--primary)); }
        50% { opacity: 0.8; filter: drop-shadow(0 0 30px var(--primary)); }
    }

    .close-x {
        position: absolute; top: 20px; right: 25px; color: #52525b;
        font-size: 1.5rem; cursor: pointer; transition: 0.3s;
    }
    .close-x:hover { color: #fff; }

    /* --- AVIATOR SYSTEM LOADER --- */
#loader-wrapper {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: #0f0f0f; z-index: 99999;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.loader-content { text-align: center; }
.loading-plane { 
    font-size: 5rem; color: var(--primary-red); 
    display: inline-block;
    animation: planeFly 1.5s infinite ease-in-out; 
    filter: drop-shadow(0 0 15px rgba(241, 44, 76, 0.5));
}
.loading-bar-container { 
    width: 220px; height: 4px; background: #222; 
    border-radius: 10px; margin-top: 30px; overflow: hidden; position: relative; 
}
.loading-bar-fill { 
    position: absolute; width: 0%; height: 100%; 
    background: var(--primary-red); box-shadow: 0 0 10px var(--primary-red);
    animation: barFill 2s forwards; 
}
.loading-text { 
    margin-top: 15px; font-weight: 900; font-size: 0.75rem; 
    letter-spacing: 3px; color: #555; text-transform: uppercase; 
}

@keyframes planeFly {
    0%, 100% { transform: translateY(0) rotate(-10deg); }
    50% { transform: translateY(-25px) rotate(10deg); }
}
@keyframes barFill {
    0% { width: 0%; }
    100% { width: 100%; }
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

    <div id="newYearOverlay">
        <div class="ny-card">
            <span class="close-x" onclick="closeNY()"><i class="bi bi-x-lg"></i></span>
            <canvas id="nyCanvas"></canvas>
            
            <div class="ny-greet">Happy New Year</div>
            <div class="ny-year">2026</div>
            
            <div class="ny-quote" id="quoteText">
                "Your 1000X takeoff is waiting in the clouds of 2026!"
            </div>

            <button class="ny-btn" onclick="closeNY()">
                START TAKEOFF <i class="bi bi-airplane-fill ms-2"></i>
            </button>
        </div>
    </div>

    <!-- TOP BAR -->
    <!-- <nav class="top-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="#" class="brand">FlyWing<span>.1000X</span></a>
            <div class="d-none d-md-block text-white fw-bold small">
                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
            </div>
            <a href="logout.php" class="text-danger d-md-none"><i class="bi bi-box-arrow-right fs-4"></i></a>
        </div>
    </nav> -->
    <nav class="top-nav">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="#" class="brand">FlyWing<span>.1000X</span></a>

        <!-- DESKTOP USER + LOGOUT -->
        <div class="d-none d-md-flex align-items-center gap-3">
            <span class="text-white fw-bold small">
                <i class="bi bi-person-circle me-1"></i>
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
            </span>

            <a href="index.php"
               class="btn btn-sm btn-outline-danger fw-bold px-3 rounded-pill">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>

        <!-- MOBILE LOGOUT -->
        <a href="logout.php" class="text-danger d-md-none">
            <i class="bi bi-box-arrow-right fs-4"></i>
        </a>
    </div>
</nav>

    <div class="container">

        <!-- WALLET CARD -->
        <div class="row">
            <div class="col-md-6 offset-md-3 col-lg-4 offset-lg-4">
                <div class="wallet-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="bal-label">Real Wallet Balance</div>
                            <div class="bal-main">₹ <span id="liveWallet">...</span></div>
                        </div>
                        <div class="text-end">
                            <div class="bal-label">Deposits</div>
                            <span class="dep-value">₹ <?php echo number_format($dep_bal, 2); ?></span>
                        </div>
                    </div>

                    <div class="action-grid">
                        <a href="deposit.php" class="btn-action deposit"><i class="bi bi-plus-lg"></i> DEPOSIT</a>
                        <a href="withdraw.php" class="btn-action"><i class="bi bi-arrow-down"></i> WITHDRAW</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- AVIATOR GAME CARD -->
        <div class="game-banner">
            <div class="game-content">
                <div class="game-title">
                    <h2>AVIATOR</h2>
                    <p><span class="live-dot"></span> 2,450 Players Online</p>
                </div>
                <div>
                    <a href="aviator_play.php" class="play-btn">PLAY NOW <i class="bi bi-caret-right-fill"></i></a>
                </div>
            </div>
            <!-- Decorative Icon -->
            <i class="bi bi-airplane-engines-fill banner-plane"></i>
        </div>

        <!-- TRANSACTION HISTORY TABLE -->
        <div class="table-section mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                <h5 class="fw-bold m-0 text-white">History</h5>
                <a href="#" class="text-muted small text-decoration-none">View All</a>
            </div>
            
            <div class="tbl-card">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($txns)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">No recent activity found.</td></tr>
                            <?php else: ?>
                                <?php foreach($txns as $t): 
                                    // Logic for Icons & Colors
                                    $icon = 'bi-arrow-left-right'; 
                                    $bg = 'icon-dep';
                                    $sign = '';
                                    $amtClass = 'amt-minus';
                                    $label = $t['type'];

                                    if($t['type'] == 'Deposit') { $icon='bi-wallet2'; $bg='icon-dep'; $sign='+'; $amtClass='amt-plus'; }
                                    if($t['type'] == 'bet')     { $icon='bi-joystick'; $bg='icon-bet'; $sign='-'; $label='Game Bet'; }
                                    if($t['type'] == 'win')     { $icon='bi-trophy-fill'; $bg='icon-win'; $sign='+'; $amtClass='amt-plus'; $label='Game Win'; }
                                    
                                    // Status Logic
                                    $stColor = 'text-warning';
                                    $stText = 'Pending';
                                    if($t['status'] == 'approved' || $t['status'] == 'completed') { $stColor = 'text-success'; $stText = 'Success'; }
                                    if($t['status'] == 'rejected') { $stColor = 'text-danger'; $stText = 'Failed'; }
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="txn-icon <?php echo $bg; ?>"><i class="bi <?php echo $icon; ?>"></i></div>
                                            <div>
                                                <div class="fw-bold text-white text-capitalize"><?php echo $label; ?></div>
                                                <div class="text-muted small" style="font-size:0.75rem"><?php echo date('d M, h:i A', strtotime($t['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small fw-bold <?php echo $stColor; ?>">
                                            <i class="bi bi-circle-fill" style="font-size:0.4rem; vertical-align:middle;"></i> <?php echo $stText; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="<?php echo $amtClass; ?>" style="font-size:1rem;">
                                            <?php echo $sign; ?>₹<?php echo number_format($t['amount'], 2); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- MOBILE BOTTOM NAV -->
    <div class="bottom-nav">
        <a href="#" class="nav-item active"><i class="bi bi-grid-fill"></i> Home</a>
        <a href="deposit.php" class="nav-item"><i class="bi bi-wallet-fill"></i> Wallet</a>
        <a href="#" class="nav-item"><i class="bi bi-clock-history"></i> History</a>
        <a href="logout.php" class="nav-item text-danger"><i class="bi bi-person-fill"></i> Logout</a>
    </div>

    <!-- LIVE BALANCE SCRIPT -->
    <!-- <script>
    function updateWallet() {
        fetch('wallet_api.php') // Ensure this file exists from previous steps
            .then(r => r.json())
            .then(d => {
                let bal = parseFloat(d.balance);
                document.getElementById('liveWallet').innerText = bal.toFixed(2);
            }).catch(()=>{});
    }
    updateWallet();
    setInterval(updateWallet, 3000);

    // New Year 2026
    const quotes = [
        "New Year, New Altitude. Make 2026 your most profitable flight!",
        "Fortune favors the brave. 2026 is the year of 1000X wins!",
        "Start your 2026 journey with a perfect cash-out!",
        "The sky isn't the limit in 2026, it's your playground. Fly high!",
        "New Year resolution: Catch the 100x multiplier every single day!"
    ];

    function showNY() {
        // Only show once per session so users don't get annoyed
        if(!sessionStorage.getItem('nyShown')) {
            document.getElementById('quoteText').innerText = `"${quotes[Math.floor(Math.random() * quotes.length)]}"`;
            document.getElementById('newYearOverlay').classList.add('active');
            initFireworks();
        }
    }

    function closeNY() {
        document.getElementById('newYearOverlay').classList.remove('active');
        sessionStorage.setItem('nyShown', 'true');
    }

    // Small Fireworks Effect
    function initFireworks() {
        const canvas = document.getElementById('nyCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;

        let particles = [];
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = canvas.height + 10;
                this.speed = Math.random() * 3 + 2;
                this.radius = Math.random() * 2;
                this.color = `hsl(${Math.random() * 360}, 70%, 60%)`;
            }
            update() {
                this.y -= this.speed;
                if(this.y < -10) this.y = canvas.height + 10;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
            }
        }

        for(let i=0; i<30; i++) particles.push(new Particle());

        function animate() {
            ctx.clearRect(0,0, canvas.width, canvas.height);
            particles.forEach(p => { p.update(); p.draw(); });
            if(document.getElementById('newYearOverlay').classList.contains('active')) {
                requestAnimationFrame(animate);
            }
        }
        animate();
    }

    // Trigger on load
    window.onload = () => {
        setTimeout(showNY, 1000);
    };
    </script> -->

    <script>
/* ================= LIVE WALLET ================= */
function updateWallet() {
    fetch('wallet_api.php')
        .then(r => r.json())
        .then(d => {
            let bal = parseFloat(d.balance);
            document.getElementById('liveWallet').innerText = bal.toFixed(2);
        }).catch(()=>{});
}
updateWallet();
setInterval(updateWallet, 3000);

/* ================= NEW YEAR 2026 ================= */

// Quotes
const quotes = [
    "New Year, New Altitude. Make 2026 your most profitable flight!",
    "Fortune favors the brave. 2026 is the year of 1000X wins!",
    "Start your 2026 journey with a perfect cash-out!",
    "The sky isn't the limit in 2026, it's your playground. Fly high!",
    "New Year resolution: Catch the 1000X multiplier every single day!"
];

// Date guard (only Jan 1–7, 2026)
function isNewYear2026() {
    const now = new Date();
    return now.getFullYear() === 2026 && now.getMonth() === 0 && now.getDate() <= 7;
}

function showNY() {
    if (!sessionStorage.getItem('nyShown') && isNewYear2026()) {
        document.getElementById('quoteText').innerText =
            `"${quotes[Math.floor(Math.random() * quotes.length)]}"`;
        document.getElementById('newYearOverlay').classList.add('active');
        initFireworks();
    }
}

function closeNY() {
    document.getElementById('newYearOverlay').classList.remove('active');
    sessionStorage.setItem('nyShown', 'true');
}

/* ================= FIREWORKS ================= */
function initFireworks() {
    const canvas = document.getElementById('nyCanvas');
    const ctx = canvas.getContext('2d');

    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

    let particles = [];

    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = canvas.height + 10;
            this.speed = Math.random() * 3 + 2;
            this.radius = Math.random() * 2;
            this.color = `hsl(${Math.random() * 360},70%,60%)`;
        }
        update() {
            this.y -= this.speed;
            if (this.y < -10) this.y = canvas.height + 10;
        }
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.fill();
        }
    }

    for (let i = 0; i < 30; i++) particles.push(new Particle());

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => { p.update(); p.draw(); });

        if (document.getElementById('newYearOverlay').classList.contains('active')) {
            requestAnimationFrame(animate);
        }
    }
    animate();
}

// Trigger popup after load
window.onload = () => {
    setTimeout(showNY, 1000);
};


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