<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - FlyWing.1000X</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }

        /* --- Navbar & Sidebar (Shared Styles) --- */
        .navbar-custom { background-color: #0d6efd; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: 700; color: #fff !important; letter-spacing: 1px; }
        .btn-logout { background-color: rgba(255, 255, 255, 0.2); color: #fff; border: 1px solid rgba(255, 255, 255, 0.5); transition: all 0.3s; }
        .btn-logout:hover { background-color: #fff; color: #0d6efd; }
        
        .sidebar { min-height: 100vh; background-color: #fff; box-shadow: 2px 0 5px rgba(0,0,0,0.05); padding-top: 20px; }
        .nav-link { color: #495057; font-weight: 500; padding: 12px 20px; margin-bottom: 5px; border-radius: 5px; display: flex; align-items: center; transition: all 0.2s; }
        .nav-link i { font-size: 1.2rem; margin-right: 15px; width: 25px; text-align: center; }
        .nav-link:hover { background-color: #e9ecef; color: #0d6efd; transform: translateX(5px); }
        .nav-link.active { background-color: #e7f1ff; color: #0d6efd; font-weight: 600; }

        /* --- Main Content --- */
        .main-content { padding: 30px; }
        
        .table-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            background: white;
            overflow: hidden;
        }

        .table-header {
            background-color: #fff;
            border-bottom: 1px solid #edf2f9;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Table Styling */
        .custom-table thead th {
            background-color: #f8f9fa;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            border-bottom: 2px solid #edf2f9;
            padding: 15px;
        }
        .custom-table tbody td {
            vertical-align: middle;
            padding: 15px;
            color: #333;
            border-bottom: 1px solid #edf2f9;
        }

        .user-avatar { width: 35px; height: 35px; border-radius: 50%; margin-right: 10px; }
        
        /* Status Badges */
        .badge-pending { background-color: #fff3cd; color: #ffc107; border: 1px solid #ffecb5; }
        .badge-active { background-color: #d1e7dd; color: #198754; border: 1px solid #badbcc; }
        .badge-reject { background-color: #f8d7da; color: #dc3545; border: 1px solid #f5c2c7; }

        /* Action Buttons */
        .btn-action {
            width: 35px; height: 35px;
            border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            border: none; margin: 0 2px;
            transition: transform 0.2s;
        }
        .btn-action:hover { transform: scale(1.1); }
        .btn-view { background-color: #e7f1ff; color: #0d6efd; }
        .btn-approve { background-color: #d1e7dd; color: #198754; }
        .btn-reject { background-color: #f8d7da; color: #dc3545; }

        /* Screenshot Thumbnail in Table */
        .screenshot-thumb {
            width: 40px; height: 40px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.2s;
        }
        .screenshot-thumb:hover { transform: scale(1.2); border-color: #0d6efd; }

        /* Modal Image */
        #proofImage { width: 100%; border-radius: 8px; border: 1px solid #dee2e6; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand ms-3" href="#"><i class="bi bi-airplane-engines-fill me-2"></i>FlyWing.1000X</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav me-3">
                    <li class="nav-item">
                        <a class="btn btn-sm btn-logout" href="login.html"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading px-3 mt-2 mb-3 text-muted text-uppercase">Menu</h6>
                    <ul class="nav flex-column px-2">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-house-fill"></i> Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                        <li class="nav-item"><a class="nav-link active" href="payment_table.php"><i class="bi bi-table"></i> Payment Table</a></li>
                        <li class="nav-item"><a class="nav-link" href="payment_method_add.php"><i class="bi bi-credit-card-2-front-fill"></i> Payment Add</a></li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold">Payment Transactions</h3>
                        <p class="text-muted">Review proof of payments and activate user wallets.</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary"><i class="bi bi-filter"></i> Filter</button>
                        <button class="btn btn-primary"><i class="bi bi-download"></i> Export</button>
                    </div>
                </div>

                <!-- Payment Table Card -->
                <div class="table-card">
                    <div class="table-header">
                        <h5 class="mb-0">Recent Requests</h5>
                        <div class="input-group" style="width: 250px;">
                            <input type="text" class="form-control form-control-sm" placeholder="Search Transaction ID...">
                            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table custom-table table-hover mb-0" id="paymentTable">
                            <thead>
                                <tr>
                                    <th>Tx ID</th>
                                    <th>User</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Proof</th> <!-- Screenshot Column -->
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>

                            <?php
                            session_start();
                            include "db.php";

                            /* --- ADMIN AUTH CHECK (adjust as needed) --- */
                            // if (!isset($_SESSION['admin_email'])) {
                            //     header("Location: index.php");
                            //     exit;
                            // }

                            /* --- FETCH DEPOSIT DATA --- */
                            $sql = "
                            SELECT 
                                d.deposit_id,
                                d.deposit_amount,
                                d.uti_number,
                                d.transaction_screenshot,
                                d.status,
                                d.created_at,
                                u.name,
                                u.id AS user_id
                            FROM user_deposit_tbl d
                            JOIN users_tbl u ON d.user_id = u.id
                            ORDER BY d.created_at DESC
                            ";

                            $result = $conn->query($sql);
                            ?>


                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr id="row-<?php echo $row['deposit_id']; ?>">

                                    <td><span class="text-muted small">#<?php echo htmlspecialchars($row['uti_number']); ?></span></td>

                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['name']); ?>&background=random"
                                                 class="user-avatar">
                                            <div>
                                                <h6 class="mb-0 text-sm"><?php echo htmlspecialchars($row['name']); ?></h6>
                                                <small class="text-muted">User<?php echo $row['user_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-qr-code"></i> UPI
                                        </span>
                                    </td>

                                    <td class="fw-bold text-success">
                                        ₹<?php echo number_format($row['deposit_amount'], 2); ?>
                                    </td>

                                    <td>
                                        <img src="<?php echo $row['transaction_screenshot']; ?>"
                                             class="screenshot-thumb"
                                             onclick="viewScreenshot('<?php echo $row['transaction_screenshot']; ?>', '#<?php echo $row['uti_number']; ?>')">
                                    </td>

                                    <td class="small">
                                        <?php echo date("M d, Y", strtotime($row['created_at'])); ?><br>
                                        <?php echo date("h:i A", strtotime($row['created_at'])); ?>
                                    </td>

                                    <td>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <span class="badge badge-pending rounded-pill px-3" id="status-<?php echo $row['deposit_id']; ?>">
                                                Pending
                                            </span>
                                        <?php elseif ($row['status'] === 'approved'): ?>
                                            <span class="badge badge-active rounded-pill px-3">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-reject rounded-pill px-3">Rejected</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <button class="btn-action btn-approve"
                                                onclick="updateStatus(<?php echo $row['deposit_id']; ?>,'approved')">
                                                <i class="bi bi-check-lg"></i>
                                            </button>

                                            <button class="btn-action btn-reject"
                                                onclick="updateStatus(<?php echo $row['deposit_id']; ?>,'rejected')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-success small fw-bold">
                                                <i class="bi bi-check-circle-fill"></i> Completed
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No transactions found</td>
                                </tr>
                                <?php endif; ?>
                                </tbody>

                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Proof Screenshot Modal -->
    <div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaction Proof: <span id="modalTxId" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center bg-light">
                    <img src="" id="proofImage" alt="Payment Screenshot" class="img-fluid shadow-sm">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="downloadLink" class="btn btn-primary" download><i class="bi bi-download"></i> Download</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Logic Script -->
    <script>
        // Function to Open Modal and View Screenshot
        function viewScreenshot(imageUrl, txId) {
            // Set image source
            document.getElementById('proofImage').src = imageUrl;
            // Set download link
            document.getElementById('downloadLink').href = imageUrl;
            // Set Title
            document.getElementById('modalTxId').innerText = txId;
            
            // Show Modal
            var myModal = new bootstrap.Modal(document.getElementById('proofModal'));
            myModal.show();
        }

        // Function to Approve or Reject
        // function updateStatus(rowId, action) {
        //     const statusBadge = document.getElementById(`status-${rowId}`);
        //     const btnApprove = document.getElementById(`btn-approve-${rowId}`);
        //     const btnReject = document.getElementById(`btn-reject-${rowId}`);
            
        //     if (action === 'active') {
        //         // Change to Approved/Active
        //         statusBadge.className = "badge badge-active rounded-pill px-3";
        //         statusBadge.innerText = "Active";
                
        //         // Optional: Hide buttons after action to prevent double clicking
        //         btnApprove.style.display = 'none';
        //         btnReject.style.display = 'none';
                
        //         // Show a text indicating completion (Optional)
        //         const parentTd = btnApprove.parentElement;
        //         const completedText = document.createElement('span');
        //         completedText.className = "text-success small fw-bold";
        //         completedText.innerHTML = '<i class="bi bi-check-circle-fill"></i> Approved';
        //         parentTd.appendChild(completedText);
                
        //     } else if (action === 'reject') {
        //         // Change to Rejected
        //         statusBadge.className = "badge badge-reject rounded-pill px-3";
        //         statusBadge.innerText = "Rejected";

        //         // Optional: Hide buttons
        //         btnApprove.style.display = 'none';
        //         btnReject.style.display = 'none';
                
        //         const parentTd = btnApprove.parentElement;
        //         const completedText = document.createElement('span');
        //         completedText.className = "text-danger small fw-bold";
        //         completedText.innerHTML = '<i class="bi bi-x-circle-fill"></i> Rejected';
        //         parentTd.appendChild(completedText);
        //     }
        // }
            function updateStatus(depositId, status) {
                if (!confirm("Are you sure?")) return;

                fetch("update_deposit_status.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `deposit_id=${depositId}&status=${status}`
                })
                .then(res => res.text())
                .then(data => {
                    if (data === "success") {
                        alert("Deposit updated successfully");
                        location.reload();
                    } 
                    else if (data === "already_approved") {
                        alert("Deposit already approved");
                    }
                    else {
                        alert("Action failed: " + data);
                    }
                })
                .catch(err => {
                    alert("Network error");
                    console.error(err);
                });
            }
    </script>
</body>
</html>