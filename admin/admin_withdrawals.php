<?php
session_start();
include "db.php";

/* ===============================
   ADMIN AUTH (ENABLE IN PROD)
================================ */
// if (!isset($_SESSION['admin_id'])) {
//     http_response_code(403);
//     exit("ADMIN ONLY");
// }

/* ===============================
   HANDLE APPROVE / REJECT
================================ */
if (isset($_POST['action'])) {

    $wid = (int)$_POST['withdraw_id'];
    $action = $_POST['action'];
    $remark = trim($_POST['admin_remark'] ?? '');

    // Fetch withdrawal
    $q = $conn->prepare("
        SELECT user_id, withdraw_amount, status
        FROM user_withdrawal_tbl
        WHERE withdraw_id=?
    ");
    $q->bind_param("i", $wid);
    $q->execute();
    $wd = $q->get_result()->fetch_assoc();

    if ($wd && $wd['status'] === 'pending') {

        // APPROVE
        if ($action === 'approve') {

            $stmt = $conn->prepare("
                UPDATE user_withdrawal_tbl
                SET status='approved', admin_remark=?
                WHERE withdraw_id=?
            ");
            $stmt->bind_param("si", $remark, $wid);
            $stmt->execute();
        }

        // REJECT (REFUND)
        if ($action === 'reject') {

            // Get last balance
            $bq = $conn->prepare("
                SELECT balance_after
                FROM wallet_transactions_tbl
                WHERE user_id=?
                ORDER BY id DESC LIMIT 1
            ");
            $bq->bind_param("i", $wd['user_id']);
            $bq->execute();
            $bal = $bq->get_result()->fetch_assoc();
            $currentBal = $bal ? $bal['balance_after'] : 0;

            $newBal = $currentBal + $wd['withdraw_amount'];

            // Refund wallet
            $wstmt = $conn->prepare("
                INSERT INTO wallet_transactions_tbl
                (user_id, type, amount, balance_after, reference)
                VALUES (?,?,?,?,?)
            ");
            $type = 'refund';
            $ref = 'withdraw_rejected';
            $wstmt->bind_param(
                "isdss",
                $wd['user_id'],
                $type,
                $wd['withdraw_amount'],
                $newBal,
                $ref
            );
            $wstmt->execute();

            // Update withdrawal
            $stmt = $conn->prepare("
                UPDATE user_withdrawal_tbl
                SET status='rejected', admin_remark=?
                WHERE withdraw_id=?
            ");
            $stmt->bind_param("si", $remark, $wid);
            $stmt->execute();
        }
    }

    header("Location: admin_withdrawals.php");
    exit;
}

/* ===============================
   FILTER & SEARCH
================================ */
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = "1";
$params = [];
$types = "";

if ($statusFilter) {
    $where .= " AND w.status=?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($search) {
    $where .= " AND (u.name LIKE ? OR w.bank_ifsc LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

/* ===============================
   EXPORT CSV
================================ */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=withdrawals.csv");

    $out = fopen("php://output", "w");
    fputcsv($out, [
        'User','Bank','IFSC','Account',
        'Amount','Status','Remark','Date'
    ]);

    $sql = "
        SELECT u.name,w.bank_name,w.bank_ifsc,
               w.account_number,w.withdraw_amount,
               w.status,w.admin_remark,w.created_at
        FROM user_withdrawal_tbl w
        JOIN users_tbl u ON u.id=w.user_id
        WHERE $where
        ORDER BY w.withdraw_id DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

/* ===============================
   FETCH WITHDRAWALS
================================ */
$list = [];
$sql = "
    SELECT w.withdraw_id,u.name,w.bank_name,w.bank_ifsc,
           w.account_number,w.withdraw_amount,
           w.status,w.admin_remark,w.created_at
    FROM user_withdrawal_tbl w
    JOIN users_tbl u ON u.id=w.user_id
    WHERE $where
    ORDER BY w.withdraw_id DESC
";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
    $list[] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Withdrawals</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#121212; color:#f1f1f1; }
.table td, .table th { vertical-align: middle; }
textarea { resize:none; }
</style>
</head>

<body class="p-4">

<div class="container-fluid">

<h3 class="mb-3">🏦 Withdrawal Requests</h3>

<!-- FILTER BAR -->
<form class="row g-2 mb-3">
<div class="col-md-2">
<select name="status" class="form-select">
<option value="">All</option>
<option value="pending" <?= $statusFilter=='pending'?'selected':'' ?>>Pending</option>
<option value="approved" <?= $statusFilter=='approved'?'selected':'' ?>>Approved</option>
<option value="rejected" <?= $statusFilter=='rejected'?'selected':'' ?>>Rejected</option>
</select>
</div>

<div class="col-md-3">
<input type="text" name="search" class="form-control"
placeholder="Search name / IFSC"
value="<?= htmlspecialchars($search) ?>">
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Filter</button>
</div>

<div class="col-md-2">
<a href="?export=csv" class="btn btn-success w-100">
Export CSV
</a>
</div>
</form>

<!-- TABLE -->
<div class="table-responsive">
<table class="table table-dark table-bordered table-hover">

<thead class="table-secondary text-dark">
<tr>
<th>User</th>
<th>Bank</th>
<th>IFSC</th>
<th>Account</th>
<th>Amount</th>
<th>Status</th>
<th>Admin Remark</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php if (!$list): ?>
<tr><td colspan="9" class="text-center text-muted">No records</td></tr>
<?php endif; ?>

<?php foreach ($list as $w): ?>
<tr>
<td><?= htmlspecialchars($w['name']) ?></td>
<td><?= $w['bank_name'] ?></td>
<td><?= $w['bank_ifsc'] ?></td>
<td><?= $w['account_number'] ?></td>
<td class="fw-bold text-info">₹<?= number_format($w['withdraw_amount'],2) ?></td>

<td>
<span class="badge bg-<?= 
$w['status']=='approved'?'success':
($w['status']=='rejected'?'danger':'warning') ?>">
<?= strtoupper($w['status']) ?>
</span>
</td>

<td><?= htmlspecialchars($w['admin_remark']) ?></td>
<td><?= $w['created_at'] ?></td>

<td>
<?php if ($w['status']=='pending'): ?>
<form method="post">
<input type="hidden" name="withdraw_id" value="<?= $w['withdraw_id'] ?>">
<textarea name="admin_remark" class="form-control mb-1"
placeholder="Remark" required></textarea>

<button name="action" value="approve"
class="btn btn-success btn-sm w-100 mb-1">
Approve
</button>

<button name="action" value="reject"
onclick="return confirm('Reject & refund?')"
class="btn btn-outline-danger btn-sm w-100">
Reject
</button>
</form>
<?php else: ?>
<span class="text-muted">Completed</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>

</div>
</body>
</html>
