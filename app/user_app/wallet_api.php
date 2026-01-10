<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit;
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 1; // Default to 1 for testing

$balance = getUserBalance($conn, $user_id);

echo json_encode(['status' => 'success', 'balance' => $balance]);
?>