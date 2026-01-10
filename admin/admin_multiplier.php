<?php
include "db.php";

if($_SERVER['REQUEST_METHOD']==='POST'){
    $crash = floatval($_POST['crash_point']);

    $conn->query("
        UPDATE game_control 
        SET crash_point=$crash, multiplier=1.00, status='FLYING'
        WHERE id=1
    ");
    exit("OK");
}

$row = $conn->query("SELECT * FROM game_control WHERE id=1")->fetch_assoc();
?>

<form method="post">
  Crash Point (X):
  <input type="number" step="0.01" name="crash_point" value="<?= $row['crash_point'] ?>"><br><br>
  <button>START ROUND</button>
</form>
