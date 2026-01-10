<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

/* ---- AUTH CHECK ---- */
if (!isset($_SESSION['user_email']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ---- DB CONNECTION ---- */
include "db.php";

/* ---- HANDLE DEPOSIT SUBMIT ---- */
if (isset($_POST['submit_deposit'])) {

    $deposit_amount = floatval($_POST['deposit_amount']);
    $uti            = trim($_POST['uti_number']);

    if ($deposit_amount <= 119) {
        echo "<script>alert('❌ Minimum deposit amount is ₹120');</script>";
    } 
    elseif (!isset($_FILES['transaction_screenshot'])) {
        echo "<script>alert('❌ No file uploaded');</script>";
    } 
    else {
        $file = $_FILES['transaction_screenshot'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo "<script>alert('❌ Upload error code: {$file['error']}');</script>";
        } 
        else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];

            if (!in_array($ext, $allowed)) {
                echo "<script>alert('❌ Only JPG, JPEG, PNG files allowed');</script>";
            } 
            else {
                if (!is_dir("uploads/transactions")) {
                    mkdir("uploads/transactions", 0755, true);
                }
                $newName = "TXN_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                $path    = "uploads/transactions/" . $newName;

                if (move_uploaded_file($file['tmp_name'], $path)) {
                    $stmt = $conn->prepare(
                        "INSERT INTO user_deposit_tbl (user_id, deposit_amount, uti_number, transaction_screenshot) VALUES (?, ?, ?, ?)"
                    );
                    $stmt->bind_param("idss", $user_id, $deposit_amount, $uti, $path);
                    if ($stmt->execute()) {
                        echo "<script>
                            alert('✅ Deposit submitted successfully. Waiting for admin approval.');
                            window.location.href='user_dash.php';
                        </script>";
                        exit;
                    } else {
                        echo "<script>alert('❌ UTI already exists');</script>";
                    }
                    $stmt->close();
                } else {
                    echo "<script>alert('❌ Failed to move uploaded file');</script>";
                }
            }
        }
    }
}

// Fetch latest payment screenshot
$paymentImg = null;

$q = $conn->query("
    SELECT payment_screenshot
    FROM admin_payment_tbl
    ORDER BY admin_payment_id DESC
    LIMIT 1
");

if ($q && $q->num_rows > 0) {
    $row = $q->fetch_assoc();
    $paymentImg = $row['payment_screenshot'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit | FlyWing.1000X</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #0d1117;
            --card-bg: #161b22;
            --accent: #ff0055;
            --text-main: #ffffff;
            --text-muted: #8b949e;
            --input-bg: #0d1117;
            --border: #30363d;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
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

        /* Deposit Card */
        .main-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-top: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }

        .qr-section {
            background: #fff;
            padding: 12px;
            border-radius: 12px;
            display: inline-block;
            margin: 15px 0;
        }

        .alert-custom {
            background: rgba(255, 0, 85, 0.1);
            border: 1px solid var(--accent);
            color: #fff;
            border-radius: 10px;
            font-size: 0.85rem;
            padding: 10px;
        }

        /* Form Controls */
        .form-label {
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
            margin-bottom: 8px;
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
            box-shadow: 0 0 0 0.25rem rgba(255, 0, 85, 0.25) !important;
        }

        .form-control::placeholder { color: #555; }

        .btn-deposit {
            background: var(--accent);
            border: none;
            padding: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .btn-deposit:hover {
            background: #e6004c;
            transform: translateY(-2px);
        }

        /* Support Section */
        .support-card {
            background: #1c2128;
            border: 1px dashed #444;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }

        .support-btn {
            background: #25d366; /* WhatsApp Green */
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            font-weight: bold;
            margin-top: 10px;
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

        .nav-link-item i {
            display: block;
            font-size: 1.3rem;
            margin-bottom: 3px;
        }

        .nav-link-item.active {
            color: var(--accent);
        }

        .nav-link-item.active i { color: var(--accent); }

    </style>
</head>
<body>

    <!-- TOP NAVIGATION -->
    <nav class="top-nav">
        <a href="user_dash.php" class="brand">FLYWING.1000x</a>
        <div class="user-badge">
            <span class="name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <span class="uid">ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></span>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">

                <div class="card main-card">
                    <div class="card-body p-4">
                        
                        <h4 class="text-white fw-bold mb-1">Add Cash</h4>
                        <p class="text-muted small mb-4">Transfer money to the QR below and upload proof.</p>

                        <!-- QR & PAYMENT INFO -->
                        <div class="text-center">
                            <div class="qr-section text-center">
                                <?php if ($paymentImg): ?>
                                    <img 
                                        src="<?= htmlspecialchars($paymentImg) ?>" 
                                        alt="Payment QR"
                                        style="width:200px; height:200px; object-fit:contain;"
                                    >
                                <?php else: ?>
                                    <p class="text-warning">Payment QR not available</p>
                                <?php endif; ?>
                            </div>
                                                        
                            <div class="alert-custom mb-4">
                                <i class="fas fa-shield-alt me-2"></i> Only Pay via official UPI apps (PhonePe, GPay, Paytm)
                            </div>
                        </div>

                        <!-- DEPOSIT FORM -->
                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-3">
                                <label class="form-label">Deposit Amount (₹)</label>
                                <input type="number" name="deposit_amount" 
                                       class="form-control" 
                                       placeholder="Enter amount (Min ₹120)" 
                                       min="120" step="0.01" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">UTI / Transaction Number (12 Digits)</label>
                                <input type="text" name="uti_number" 
                                       class="form-control" 
                                       placeholder="Ex: 4125XXXXXXXX" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Payment Screenshot</label>
                                <input type="file" name="transaction_screenshot" 
                                       class="form-control" 
                                       accept="image/*" required>
                            </div>

                            <button type="submit" name="submit_deposit" class="btn btn-primary w-100 btn-deposit">
                                <i class="fas fa-lock me-2"></i> Submit Deposit Request
                            </button>

                        </form>
                    </div>
                </div>

                <!-- SUPPORT SECTION -->
                <div class="support-card">
                    <h6 class="text-white mb-1">Facing Issues?</h6>
                    <p class="text-muted small">Our support team is available 24/7 to help you with your deposit.</p>
                    <a href="https://wa.me/YOUR_NUMBER" class="support-btn">
                        <i class="fab fa-whatsapp me-2"></i> Contact Support
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
        <a href="deposit.php" class="nav-link-item active">
            <i class="fas fa-wallet"></i>
            Deposit
        </a>
        <a href="withdraw.php" class="nav-link-item">
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