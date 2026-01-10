<?php
session_start();
include "db.php";

// Optional admin auth
// if (!isset($_SESSION['admin_email'])) {
//     http_response_code(403);
//     exit;
// }

$deposit_id = intval($_POST['deposit_id']);
$status     = $_POST['status'];

if (!in_array($status, ['approved', 'rejected'])) {
    exit("invalid");
}

/* ---- START TRANSACTION ---- */
$conn->begin_transaction();

/* ---- FETCH DEPOSIT DETAILS ---- */
$q = $conn->query("
    SELECT user_id, deposit_amount, status 
    FROM user_deposit_tbl 
    WHERE deposit_id = $deposit_id
");

if ($q->num_rows === 0) {
    $conn->rollback();
    exit("not_found");
}

$d = $q->fetch_assoc();

/* ---- PREVENT DOUBLE APPROVAL ---- */
if ($d['status'] === 'approved') {
    $conn->rollback();
    exit("already_approved");
}

/* ---- UPDATE STATUS ---- */
$stmt = $conn->prepare("
    UPDATE user_deposit_tbl 
    SET status = ? 
    WHERE deposit_id = ?
");
$stmt->bind_param("si", $status, $deposit_id);

if (!$stmt->execute()) {
    $conn->rollback();
    exit("error");
}

/* ---- IF APPROVED → ADD TO WALLET ---- */
if ($status === 'approved') {

    // Get last wallet balance
    $balQ = $conn->query("
        SELECT balance_after 
        FROM wallet_transactions_tbl 
        WHERE user_id = {$d['user_id']}
        ORDER BY id DESC 
        LIMIT 1
    ");

    $last_balance = 0;
    if ($balQ->num_rows > 0) {
        $last_balance = $balQ->fetch_assoc()['balance_after'];
    }

    $new_balance = $last_balance + $d['deposit_amount'];

    // Insert wallet transaction
    $stmt2 = $conn->prepare("
        INSERT INTO wallet_transactions_tbl
        (user_id, type, amount, balance_after, reference)
        VALUES (?, 'deposit', ?, ?, ?)
    ");

    $ref = "deposit_" . $deposit_id;
    $stmt2->bind_param(
        "iddd",
        $d['user_id'],
        $d['deposit_amount'],
        $new_balance,
        $ref
    );

    if (!$stmt2->execute()) {
        $conn->rollback();
        exit("wallet_error");
    }

    $stmt2->close();
}

/* ---- COMMIT ---- */
$conn->commit();

$stmt->close();
$conn->close();

echo "success";
