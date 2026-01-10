<?php
session_start();
include "db.php";

/* ADMIN AUTH (enable later) */
// if (!isset($_SESSION['admin_id'])) exit("ADMIN ONLY");

$round_id = 0;
$q = $conn->query("
    SELECT round_id 
    FROM game_rounds_tbl 
    WHERE status='running'
    ORDER BY round_id DESC LIMIT 1
");
if ($q && $r = $q->fetch_assoc()) {
    $round_id = (int)$r['round_id'];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Spectator – Aviator</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{margin:0;background:#0b0f19;color:#fff}
.admin-bar{
    height:55px;background:#111827;border-bottom:1px solid #1f2937;
    display:flex;align-items:center;justify-content:space-between;padding:0 20px;
}
iframe{border:none;width:100%;height:calc(100vh - 55px)}
.lock{
    position:absolute;inset:0;background:rgba(0,0,0,.55);
    display:flex;align-items:center;justify-content:center;
    font-size:1.4rem;font-weight:700;pointer-events:none;
}
</style>
</head>
<body>

<div class="admin-bar">
    <div>
        🎮 <b>ADMIN SPECTATOR</b>
        <span class="badge bg-success ms-2">LIVE</span>
        <span class="badge bg-info ms-2">Round #<?= $round_id ?></span>
    </div>

    <div class="d-flex gap-2">
        <form method="post" action="admin_force_crash.php">
            <input type="hidden" name="round_id" value="<?= $round_id ?>">
            <input type="hidden" name="crash_at" value="1.00">
            <button class="btn btn-warning fw-bold">CRASH @ 1.00x</button>
        </form>

        <form method="post" action="admin_force_crash.php">
            <input type="hidden" name="round_id" value="<?= $round_id ?>">
            <input type="hidden" name="crash_at" value="2.00">
            <button class="btn btn-danger fw-bold">CRASH @ 2.00x</button>
        </form>
    </div>
</div>

<div style="position:relative">
    <iframe src="../app/user_app/aviator_play.php?spectator=1"></iframe>
    <div class="lock">👁 ADMIN SPECTATOR MODE</div>
</div>

</body>
</html>
