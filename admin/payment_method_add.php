<?php
session_start();

include "db.php";

/* ---- HANDLE ADMIN PAYMENT INSERT ---- */
if (isset($_POST['submit']) && isset($_FILES['payment_screenshot'])) {

    $file = $_FILES['payment_screenshot'];

    /* ---- UPLOAD ERROR CHECK ---- */
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('❌ Upload error code: {$file['error']}');</script>";
        return;
    }

    /* ---- FILE TYPE VALIDATION ---- */
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo "<script>alert('❌ Only JPG, JPEG, PNG, WEBP allowed');</script>";
        return;
    }

    /* ---- UPLOAD DIRECTORY (INSIDE ADMIN FOLDER) ---- */
    $uploadDir = __DIR__ . "/uploads/payments/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    /* ---- SAFE UNIQUE FILE NAME ---- */
    $newName = "ADMIN_PAY_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

    $fullPath = $uploadDir . $newName;

    /* ---- PATH STORED IN DATABASE (RELATIVE) ---- */
    $dbPath = "http://127.0.0.1/Client%20Project/avietor.1000X/admin/uploads/payments/" . $newName;

    /* ---- MOVE FILE ---- */
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        echo "<script>alert('❌ Failed to move uploaded file');</script>";
        return;
    }

    /* ---- INSERT INTO DATABASE ---- */
    $stmt = $conn->prepare(
        "INSERT INTO admin_payment_tbl (payment_screenshot)
         VALUES (?)"
    );
    $stmt->bind_param("s", $dbPath);

    if ($stmt->execute()) {
        echo "<script>
            alert('✅ Payment record added successfully');
            window.location.href='payment_table.php';
        </script>";
        exit;
    } else {
        echo "<script>alert('❌ Database insert failed');</script>";
    }

    $stmt->close();
}
?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Payment - FlyWing.1000X</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }

        /* --- Shared Sidebar/Navbar --- */
        .navbar-custom { background-color: #0d6efd; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: 700; color: #fff !important; letter-spacing: 1px; }
        .sidebar { min-height: 100vh; background-color: #fff; box-shadow: 2px 0 5px rgba(0,0,0,0.05); padding-top: 20px; }
        .nav-link { color: #495057; font-weight: 500; padding: 12px 20px; margin-bottom: 5px; border-radius: 5px; display: flex; align-items: center; transition: all 0.2s; }
        .nav-link i { font-size: 1.2rem; margin-right: 15px; width: 25px; text-align: center; }
        .nav-link:hover { background-color: #e9ecef; color: #0d6efd; }
        .nav-link.active { background-color: #e7f1ff; color: #0d6efd; font-weight: 600; }

        /* --- Form Specific Styles --- */
        .main-content { padding: 30px; }
        .form-card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); background: white; padding: 30px; }
        
        .image-preview-wrapper {
            width: 100%;
            min-height: 200px;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background-color: #fcfcfc;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .image-preview-wrapper img {
            max-width: 100%;
            max-height: 300px;
            display: none;
            object-fit: contain;
        }

        .upload-placeholder {
            text-align: center;
            color: #6c757d;
        }

        .upload-placeholder i { font-size: 3rem; margin-bottom: 10px; color: #0d6efd; }

        .form-label { font-weight: 600; color: #444; }
        
        .btn-submit {
            background-color: #0d6efd;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-submit:hover { background-color: #0b5ed7; transform: translateY(-2px); }

    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand ms-3" href="#"><i class="bi bi-airplane-engines-fill me-2"></i>FlyWing.1000X</a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav me-3">
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-light rounded-pill px-3" href="login.html"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
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
                    <h6 class="sidebar-heading px-3 mt-2 mb-3 text-muted text-uppercase small">Main Menu</h6>
                    <ul class="nav flex-column px-2">

                         <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house-fill"></i>
                                Home
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people-fill"></i>
                                Users
                            </a>
                        </li>
                        
                        <!-- 2. Active & Inactive Users -->
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-person-lines-fill"></i>
                                Active & Inactive Users
                            </a>
                        </li>
                        
                        <!-- 3. Payment Table -->
                        <li class="nav-item">
                            <a class="nav-link" href="payment_table.php">
                                <i class="bi bi-table"></i>
                                Payment Table
                            </a>
                        </li>
                        
                        <!-- 4. Payment Add -->
                        <li class="nav-item">
                            <a class="nav-link active" href="payment_method_add.php">
                                <i class="bi bi-credit-card-2-front-fill"></i>
                                Payment Add
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="mb-4">
                    <h2 class="fw-bold">Manually Add Payment</h2>
                    <p class="text-muted">Enter transaction details and upload a proof of payment screenshot.</p>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-card">
                            <form method="POST" enctype="multipart/form-data" id="paymentForm">
                                <div class="row g-3">
                                            

                                    <!-- Screenshot Upload with Preview -->
                                    <div class="col-12 mt-4">
                                        <label class="form-label">Payment Screenshot</label>
                                        <div class="image-preview-wrapper" id="dropZone">
                                            <div class="upload-placeholder" id="placeholder">
                                                <i class="bi bi-cloud-arrow-up"></i>
                                                <h5>Drop screenshot here or click to upload</h5>
                                                <p class="small">Accepted formats: JPG, PNG, WEBP</p>
                                            </div>
                                            <img src="" id="imagePreview" alt="Screenshot Preview">
                                        </div>
                                        <input type="file"
                                                       class="form-control"
                                                       id="fileInput"
                                                       name="payment_screenshot"
                                                       accept="image/*"
                                                       required>
                                    </div>

                                    <!-- Submit Buttons -->
                                    <div class="col-12 mt-4 pt-2 border-top">
                                        <button type="submit" name="submit" class="btn btn-primary btn-submit">
                                            <i class="bi bi-check2-circle me-1"></i> Submit Payment Record
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary ms-2" onclick="resetPreview()">
                                            Clear Form
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar Instructions (Optional) -->
                    <div class="col-lg-4 mt-4 mt-lg-0">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <h5 class="fw-bold"><i class="bi bi-info-circle text-primary me-2"></i>Quick Tips</h5>
                            <hr>
                            <ul class="text-muted small">
                                <li class="mb-2">Verify the <strong>Transaction ID</strong> matches the bank statement.</li>
                                <li class="mb-2">Ensure the <strong>Screenshot</strong> clearly shows the amount and date.</li>
                                <li class="mb-2">Manual additions are logged under your admin account for audit.</li>
                            </ul>
                            <div class="bg-light p-3 rounded-3 mt-auto">
                                <p class="mb-0 small text-dark fw-bold">System Status</p>
                                <span class="badge bg-success"><i class="bi bi-shield-check"></i> Secure Connection</span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Image Preview Script -->
    <script>
        const fileInput = document.getElementById('fileInput');
        const imagePreview = document.getElementById('imagePreview');
        const placeholder = document.getElementById('placeholder');

        fileInput.onchange = evt => {
            const [file] = fileInput.files;
            if (file) {
                imagePreview.src = URL.createObjectURL(file);
                imagePreview.style.display = 'block';
                placeholder.style.display = 'none';
            }
        }

        function resetPreview() {
            imagePreview.style.display = 'none';
            placeholder.style.display = 'block';
            imagePreview.src = '';
        }

        // Mock Submission
        // document.getElementById('paymentForm').onsubmit = (e) => {
        //     e.preventDefault();
        //     alert('Success: Payment record added to the database!');
        //     // You would normally send the data via AJAX here
        // }
    </script>
</body>
</html>