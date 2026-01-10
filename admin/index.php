<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - FlyWing.1000X</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            max-width: 450px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            background: #fff;
        }
        .brand-logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0d6efd;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="auth-card p-4">
    <div class="text-center mb-4">
        <div class="brand-logo">
            <i class="bi bi-airplane-engines-fill me-2"></i>FlyWing.1000X
        </div>
        <small class="text-muted">Admin Secure Login</small>
    </div>

    <form id="loginForm">
        <div class="form-floating mb-3">
            <input type="email" class="form-control" id="email" placeholder="Email" required>
            <label>Email</label>
        </div>

        <div class="form-floating mb-3">
            <input type="password" class="form-control" id="password" placeholder="Password" required>
            <label>Password</label>
        </div>

        <div class="alert alert-danger d-none" id="errorBox"></div>

        <button class="btn btn-primary w-100 py-2">
            LOGIN <i class="bi bi-box-arrow-in-right ms-1"></i>
        </button>
    </form>

    <div class="text-center mt-3">
        <small class="text-muted">© FlyWing.1000X Admin</small>
    </div>
</div>

<script>
/* ===============================
   CONFIG (CHANGE IF NEEDED)
================================ */
const ADMIN_EMAIL = "admin@flywing.com";
const ADMIN_PASSWORD = "FlyWing@1000X";

/* ===============================
   LOGIN HANDLER
================================ */
document.getElementById("loginForm").addEventListener("submit", function(e){
    e.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const errorBox = document.getElementById("errorBox");

    errorBox.classList.add("d-none");

    if(email !== ADMIN_EMAIL || password !== ADMIN_PASSWORD){
        errorBox.innerText = "Invalid admin credentials";
        errorBox.classList.remove("d-none");
        return;
    }

    // Generate unique session token
    const sessionToken = crypto.randomUUID();

    // 🔒 GLOBAL LOCK (only one device allowed)
    localStorage.setItem("admin_active_token", sessionToken);
    sessionStorage.setItem("admin_session_token", sessionToken);

    window.location.href = "dashboard.php";
});
</script>

</body>
</html>
