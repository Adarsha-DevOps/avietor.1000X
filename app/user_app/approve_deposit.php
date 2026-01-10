$conn->begin_transaction();

$deposit_id = $_POST['deposit_id'];

$q = $conn->prepare("
    SELECT user_id, deposit_amount 
    FROM user_deposit_tbl 
    WHERE deposit_id = ? AND status = 'pending'
");
$q->bind_param("i", $deposit_id);
$q->execute();
$res = $q->get_result();
$row = $res->fetch_assoc();

$user_id = $row['user_id'];
$amount  = $row['deposit_amount'];

// Update wallet
$conn->query("
    UPDATE users_tbl
    SET wallet_balance = wallet_balance + $amount
    WHERE id = $user_id
");

// Log transaction
$conn->query("
    INSERT INTO wallet_transactions_tbl
    (user_id, type, amount, balance_after, reference)
    VALUES (
        $user_id,
        'deposit',
        $amount,
        (SELECT wallet_balance FROM users_tbl WHERE id=$user_id),
        'DEPOSIT#$deposit_id'
    )
");

// Mark deposit approved
$conn->query("
    UPDATE user_deposit_tbl
    SET status='approved'
    WHERE deposit_id=$deposit_id
");

$conn->commit();
