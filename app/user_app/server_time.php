<?php
header("Content-Type: application/json");
echo json_encode([
    "server_time" => round(microtime(true) * 1000)
]);