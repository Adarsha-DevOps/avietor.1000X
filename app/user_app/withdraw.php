<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$msg = "";

/* =========================
   GET USER BALANCE
========================= */
$balQ = $conn->prepare("
    SELECT balance_after 
    FROM wallet_transactions_tbl 
    WHERE user_id=? 
    ORDER BY id DESC LIMIT 1
");
$balQ->bind_param("i", $user_id);
$balQ->execute();
$balR = $balQ->get_result()->fetch_assoc();
$balance = $balR ? (float)$balR['balance_after'] : 0;

/* =========================
   HANDLE WITHDRAW REQUEST
========================= */
if (isset($_POST['withdraw'])) {

    $bank = trim($_POST['bank_name']);
    $ifsc = trim($_POST['bank_ifsc']);
    $acc  = trim($_POST['account_number']);
    $amt  = (float)$_POST['amount'];

    if ($amt < 100 || $amt > 30000) {
        $msg = "❌ Withdrawal must be between ₹100 and ₹30,000";
    }
    elseif ($amt > $balance) {
        $msg = "❌ Insufficient balance";
    }
    else {
        $limitQ = $conn->prepare("
            SELECT IFNULL(SUM(withdraw_amount),0) AS total
            FROM user_withdrawal_tbl
            WHERE user_id=? AND DATE(created_at)=CURDATE()
            AND status!='rejected'
        ");
        $limitQ->bind_param("i", $user_id);
        $limitQ->execute();
        $totalToday = $limitQ->get_result()->fetch_assoc()['total'];

        if (($totalToday + $amt) > 30000) {
            $msg = "❌ Daily withdrawal limit exceeded (₹30,000)";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO user_withdrawal_tbl
                (user_id, bank_name, bank_ifsc, account_number, withdraw_amount)
                VALUES (?,?,?,?,?)
            ");
            $stmt->bind_param("isssd", $user_id, $bank, $ifsc, $acc, $amt);
            $stmt->execute();

            $newBal = $balance - $amt;
            $wstmt = $conn->prepare("
                INSERT INTO wallet_transactions_tbl
                (user_id, type, amount, balance_after, reference)
                VALUES (?,?,?,?,?)
            ");
            $ref = "withdraw_request";
            $type = "refund"; 
            $wstmt->bind_param("isdss", $user_id, $type, $amt, $newBal, $ref);
            $wstmt->execute();

            $msg = "✅ Withdrawal request submitted successfully";
            $balance = $newBal;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Withdraw | FlyWing.1000X</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #0d1117;
            --card-bg: #161b22;
            --accent: #ff0055;
            --success-green: #00c853;
            --text-main: #ffffff;
            --text-muted: #8b949e;
            --input-bg: #0d1117;
            --border: #30363d;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-main);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding-bottom: 90px;
        }

        /* Top Navigation */
        .top-nav {
            background: var(--card-bg);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand {
            font-size: 1.4rem;
            font-weight: 900;
            color: var(--accent);
            text-decoration: none;
            letter-spacing: 1px;
        }

        .user-badge {
            text-align: right;
            line-height: 1.2;
        }

        .user-badge .name { font-weight: bold; font-size: 0.95rem; color: #fff; display: block; }
        .user-badge .uid { font-size: 0.75rem; color: var(--accent); font-weight: 600; }

        /* Balance Card */
        .balance-hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .balance-label { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        .balance-amount { font-size: 2.2rem; font-weight: 800; color: var(--success-green); margin-top: 5px; }

        /* Main Form Card */
        .withdraw-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .input-group-text {
            background-color: var(--input-bg);
            border: 1px solid var(--border);
            color: var(--text-muted);
        }

        .form-control {
            background: var(--input-bg) !important;
            border: 1px solid var(--border) !important;
            color: #fff !important;
            padding: 12px;
            border-radius: 8px;
        }

        .form-control:focus {
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 0.25rem rgba(255, 0, 85, 0.2) !important;
        }

        .btn-withdraw {
            background: var(--accent);
            border: none;
            padding: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 12px;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-withdraw:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        /* Rules/Info Box */
        .rules-box {
            background: rgba(59, 130, 246, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
        }
        .rule-item {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
        }
        .rule-item i { color: #3b82f6; margin-right: 10px; font-size: 0.9rem; }

        /* Support Section */
        .support-card {
            background: #1c2128;
            border: 1px dashed #444;
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            text-align: center;
        }
        .support-btn {
            background: #25d366;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            font-weight: bold;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: var(--card-bg);
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            border-top: 1px solid var(--border);
            z-index: 1000;
        }
        .nav-link-item {
            color: var(--text-muted);
            text-decoration: none;
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .nav-link-item i { display: block; font-size: 1.3rem; margin-bottom: 3px; }
        .nav-link-item.active { color: var(--accent); }

        .alert-custom {
            border-radius: 10px;
            font-size: 0.9rem;
            padding: 12px;
            border: 1px solid transparent;
        }
        .alert-success { background: rgba(0, 200, 83, 0.1); color: #00c853; border-color: #00c853; }
        .alert-danger { background: rgba(255, 0, 85, 0.1); color: #ff0055; border-color: #ff0055; }
    </style>
</head>
<body>

    <!-- TOP NAVIGATION -->
    <nav class="top-nav">
        <a href="user_dash.php" class="brand">FLYWING.1000x</a>
        <div class="user-badge">
            <span class="name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
            <span class="uid">ID: <?php echo htmlspecialchars($user_id); ?></span>
        </div>
    </nav>

    <div class="container mt-3 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <!-- WALLET HERO -->
                <div class="balance-hero">
                    <div class="balance-label">Withdrawable Balance</div>
                    <div class="balance-amount">₹<?= number_format($balance, 2) ?></div>
                </div>

                <!-- MESSAGES -->
                <?php if($msg): ?>
                    <div class="alert alert-custom mb-4 <?php echo strpos($msg, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
                        <i class="fas <?php echo strpos($msg, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                        <?= $msg ?>
                    </div>
                <?php endif; ?>

                <!-- WITHDRAWAL FORM -->
                <div class="withdraw-card">
                    <form method="post">
                        <input type="hidden" name="withdraw" value="1">

                        <div class="mb-3">
                            <label class="form-label">Select Bank</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-university"></i></span>
                                <input type="text" name="bank_name" class="form-control" placeholder="Bank Name (e.g. HDFC, SBI)" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">IFSC Code</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                <input type="text" name="bank_ifsc" class="form-control text-uppercase" placeholder="11 Digit IFSC Code" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-list-ol"></i></span>
                                <input type="number" name="account_number" class="form-control" placeholder="Enter Full Account Number" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Withdrawal Amount (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-white">₹</span>
                                <input type="number" name="amount" class="form-control fw-bold" placeholder="Min 100 - Max 30,000" min="100" max="30000" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-withdraw">
                            Proceed Withdrawal <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </form>

                    <!-- GUIDELINES -->
                    <div class="rules-box">
                        <div class="rule-item"><i class="fas fa-info-circle"></i> Minimum withdrawal amount is ₹100.</div>
                        <div class="rule-item"><i class="fas fa-clock"></i> Payouts are processed within 24-48 working hours.</div>
                        <div class="rule-item"><i class="fas fa-shield-alt"></i> Daily withdrawal limit is ₹30,000.</div>
                    </div>
                </div>

                <!-- SUPPORT -->
                <div class="support-card">
                    <h6 class="text-white mb-1">Need help with Payouts?</h6>
                    <p class="text-muted small">Contact our billing team for instant support.</p>
                    <a href="https://wa.me/YOUR_NUMBER" class="support-btn">
                        <i class="fab fa-whatsapp me-2"></i> WhatsApp Support
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- MOBILE BOTTOM NAVIGATION -->
    <div class="bottom-nav">
        <a href="user_dash.php" class="nav-link-item">
            <i class="fas fa-th-large"></i>
            Lobby
        </a>
        <a href="deposit.php" class="nav-link-item">
            <i class="fas fa-wallet"></i>
            Deposit
        </a>
        <a href="withdraw.php" class="nav-link-item active">
            <i class="fas fa-hand-holding-usd"></i>
            Withdraw
        </a>
        <a href="history.php" class="nav-link-item">
            <i class="fas fa-history"></i>
            History
        </a>
        <a href="profile.php" class="nav-link-item">
            <i class="fas fa-user-circle"></i>
            Account
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>