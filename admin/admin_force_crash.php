<?php
// session_start();
// include "db.php";

// /* ADMIN AUTH (enable later) */
// // if (!isset($_SESSION['admin_id'])) exit("ADMIN ONLY");

// if (!isset($_POST['round_id'])) exit;

// $round_id = (int)$_POST['round_id'];

// $stmt = $conn->prepare("
//     INSERT INTO admin_controls_tbl (round_id, action)
//     VALUES (?, 'force_crash')
// ");
// $stmt->bind_param("i", $round_id);
// $stmt->execute();

// header("Location: admin_spectator.php");
// exit;
?>

<?php
// include "db.php";

// $round_id = (int)$_POST['round_id'];

// $conn->query("
//     INSERT INTO admin_controls_tbl (round_id, action)
//     VALUES ($round_id, 'force_crash')
// ");

// header("Location: admin_global_spectator.php");
// exit;
?>

<?php
session_start();
include "db.php";

// if (!isset($_SESSION['admin_id'])) exit("ADMIN ONLY");

$round_id = (int)$_POST['round_id'];
$crash_at = (float)$_POST['crash_at'];

$stmt = $conn->prepare("
    UPDATE game_rounds_tbl
    SET force_crash_multiplier = ?, status='running'
    WHERE round_id = ?
");
$stmt->bind_param("di", $crash_at, $round_id);
$stmt->execute();

header("Location: admin_spectator.php");
exit;
?>