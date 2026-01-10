<?php
include "db.php";

while (true) {

    $row = $conn->query("
        SELECT multiplier, crash_point, status 
        FROM game_control 
        WHERE id=1
    ")->fetch_assoc();

    if ($row['status'] !== 'FLYING') {
        usleep(100000);
        continue;
    }

    $multiplier = $row['multiplier'] + 0.01;

    if ($multiplier >= $row['crash_point']) {
        $conn->query("
            UPDATE game_control 
            SET status='CRASHED'
            WHERE id=1
        ");
    } else {
        $conn->query("
            UPDATE game_control 
            SET multiplier=$multiplier
            WHERE id=1
        ");
    }

    usleep(100000); // 100ms = smooth animation
}
