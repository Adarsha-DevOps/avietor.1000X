<?php
include "db.php";
header('Content-Type: application/json');

/* GET LATEST CRASH POINT */
$crash_point = null;

$q = $conn->query("
    SELECT force_crash 
    FROM admin_force_cras 
    ORDER BY id DESC 
    LIMIT 1
");

if ($q && $row = $q->fetch_assoc()) {
    $crash_point = (float)$row['force_crash'];
}

/* SEND TO CLIENT */
echo json_encode([
    'crash_point' => $crash_point
]);

?>