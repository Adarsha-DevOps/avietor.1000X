<?php
include "db.php";

/* START NEW ROUND */
$conn->query("
    INSERT INTO game_rounds_tbl (status, started_at)
    VALUES ('running', NOW())
");

/* OPTIONAL: clear old admin crash */
$conn->query("DELETE FROM admin_force_cras");

echo "ROUND STARTED";
