<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "flywing-1000X_DB"; // Your DB Name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'DB Connection Failed']));
}

// Helper function to get current balance based on your transaction table
function getUserBalance($conn, $user_id) {
    $q = $conn->query("SELECT balance_after FROM wallet_transactions_tbl WHERE user_id = $user_id ORDER BY id DESC LIMIT 1");
    if ($q && $row = $q->fetch_assoc()) {
        return floatval($row['balance_after']);
    }
    return 0.00; // Default if no transactions
}
?>