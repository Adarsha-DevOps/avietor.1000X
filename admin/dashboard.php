<?php
include "db.php";

/* CLOSE OLD RUNNING ROUND */
// $conn->query("
// UPDATE game_rounds_tbl 
// SET status='crashed', ended_at=NOW()
// WHERE status='running'
// ");

// /* CREATE NEW ROUND */
// $conn->query("
// INSERT INTO game_rounds_tbl (round_code,status,started_at)
// VALUES (
//     CONCAT('RND-',UNIX_TIMESTAMP()),
//     'running',
//     NOW()
// )
// ");

// header("Location: dashboard.php");
// exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlyWing.1000X Dashboard</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        /* Navbar Styling */
        .navbar-custom {
            background-color: #0d6efd; /* Primary Blue */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            color: #fff !important;
            letter-spacing: 1px;
        }
        .btn-logout {
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s;
        }
        .btn-logout:hover {
            background-color: #fff;
            color: #0d6efd;
        }

        /* Sidebar Styling */
        .sidebar {
            min-height: 100vh;
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            padding-top: 20px;
        }

        .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 12px 20px;
            margin-bottom: 5px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }

        .nav-link i {
            font-size: 1.2rem;
            margin-right: 15px;
            width: 25px; /* Fixed width for alignment */
            text-align: center;
        }

        .nav-link:hover {
            background-color: #e9ecef;
            color: #0d6efd;
            transform: translateX(5px);
        }

        .nav-link.active {
            background-color: #e7f1ff;
            color: #0d6efd;
            font-weight: 600;
        }

        /* Main Content Area */
        .main-content {
            padding: 30px;
        }
        
        .card-custom {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .card-custom:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

<!--     <script>
const SESSION_KEY = "FLYWING_ADMIN_SESSION";

const session = localStorage.getItem(SESSION_KEY);

if (!session) {
    alert("Unauthorized access!");
    window.location.href = "index.php";
}

const admin = JSON.parse(session);
document.write("<h2>Welcome, " + admin.name + "</h2>");
</script> -->


    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container-fluid">
            <!-- Brand Name -->
            <a class="navbar-brand ms-3" href="#">
                <i class="bi bi-airplane-engines-fill me-2"></i>FlyWing.1000X
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Logout Option (Right Aligned) -->
            <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
                <ul class="navbar-nav me-3">
                    <li class="nav-item">
                        <a class="btn btn-sm btn-logout" href="index.php">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-2 mb-3 text-muted text-uppercase">
                        <span>Menu</span>
                    </h6>
                    <ul class="nav flex-column px-2">

                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="bi bi-house-fill"></i>
                                Home
                            </a>
                        </li>

                        <!-- 1. Users -->
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
                            <a class="nav-link" href="payment_method_add.php">
                                <i class="bi bi-credit-card-2-front-fill"></i>
                                Payment Add
                            </a>
                        </li>

                        <!-- 4. Payment Add -->
                        <li class="nav-item">
                            <a class="nav-link" href="admin_withdrawals.php">
                               <i class="bi bi-wallet"></i>
                                Withdrawl Request
                            </a>
                        </li>

                        <li class="nav-item"><a class="nav-link" href="admin_bet_control.php"><i class="bi bi-joystick me-2"></i> Bet Control</a></li>
                    </ul>

                    <!-- Decorative Divider -->
                    <hr class="my-4 mx-3">
                    
                    <ul class="nav flex-column px-2">
                         <li class="nav-item">
                            <a class="nav-link text-danger" href="#">
                                <i class="bi bi-gear-fill"></i>
                                Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content Area (Placeholder) -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Overview</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                    </div>
                </div>

                <!-- Example Cards Content -->
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="card card-custom p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Total Users</p>
                                    <h4 class="mb-0">1,024</h4>
                                </div>
                                <div class="fs-1 text-primary"><i class="bi bi-people"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-custom p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Revenue</p>
                                    <h4 class="mb-0">💰 45,200</h4>
                                </div>
                                <div class="fs-1 text-success"><i class="bi bi-currency-dollar"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
const globalToken = localStorage.getItem("admin_active_token");
const sessionToken = sessionStorage.getItem("admin_session_token");

if(!globalToken || !sessionToken || globalToken !== sessionToken){
    alert("⚠️ You have been logged out. Another device logged in.");
    localStorage.removeItem("admin_active_token");
    sessionStorage.removeItem("admin_session_token");
    window.location.href = "index.php";
}
</script>
</body>
</html>