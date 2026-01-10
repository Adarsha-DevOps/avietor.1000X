<?php
session_start();
include "db.php";

/* ---- SIGNUP LOGIC (UNTOUCHED) ---- */
if (isset($_POST['signup'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $check = $conn->prepare("SELECT id FROM users_tbl WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo "<script>alert('❌ Phone No already registered');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users_tbl (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        if ($stmt->execute()) {
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name']  = $name;
            echo "<script>alert('✅ Account created successfully'); window.location.href = 'index.php';</script>";
            exit;
        } else {
            echo "<script>alert('❌ Registration failed');</script>";
        }
        $stmt->close();
    }
    $check->close();
}

/* ---- LOGIN LOGIC (UNTOUCHED) ---- */
if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, name, password FROM users_tbl WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $email;
            echo "<script>alert('✅ Login successful'); window.location.href = 'user_dash.php';</script>";
            exit;
        } else {
            echo "<script>alert('❌ Incorrect password');</script>";
        }
    } else {
        echo "<script>alert('❌ Phone No not found');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlyWing.1000X | Pilot Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #f12c4c; /* Aviator Red */
            --bg-dark: #0b0e14;
            --card-bg: #161b22;
            --text-main: #ffffff;
            --text-dim: #8b949e;
        }

        * { box-sizing: border-box; }

        /* --- PROFESSIONAL LOADER --- */
        #loader-wrapper {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: var(--bg-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
        }
        .loader-content { text-align: center; }
        .loader-plane {
            font-size: 4rem;
            color: var(--primary);
            animation: planePulse 1.5s infinite ease-in-out;
            filter: drop-shadow(0 0 15px var(--primary));
        }
        .loader-bar {
            width: 200px;
            height: 3px;
            background: #1a1d23;
            border-radius: 10px;
            margin-top: 20px;
            overflow: hidden;
            position: relative;
        }
        .loader-fill {
            position: absolute;
            width: 0%;
            height: 100%;
            background: var(--primary);
            box-shadow: 0 0 10px var(--primary);
            animation: fillProgress 2s forwards ease-in-out;
        }
        @keyframes planePulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
        }
        @keyframes fillProgress { to { width: 100%; } }

        /* --- HELPDESK BUTTON --- */
        .helpdesk-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(241, 44, 76, 0.4);
            color: white;
            text-decoration: none;
            z-index: 1000;
            transition: 0.3s;
        }
        .helpdesk-btn:hover {
            transform: scale(1.1);
            background: white;
            color: var(--primary);
        }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(241, 44, 76, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(241, 44, 76, 0.1) 0%, transparent 40%);
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            color: var(--text-main);
        }

        h1 { font-family: 'Rajdhani', sans-serif; font-weight: 700; margin: 0; font-size: 2rem; text-transform: uppercase; letter-spacing: 1px; }
        p { font-size: 14px; line-height: 20px; color: var(--text-dim); margin: 15px 0 25px; }

        .container {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 0 40px rgba(0,0,0,0.7), inset 0 0 2px rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
            width: 850px;
            max-width: 95%;
            min-height: 550px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
            background: var(--card-bg);
        }

        .sign-in-container { left: 0; width: 50%; z-index: 2; }
        .container.right-panel-active .sign-in-container { transform: translateX(100%); }

        .sign-up-container { left: 0; width: 50%; opacity: 0; z-index: 1; }
        .container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: show 0.6s;
        }

        @keyframes show { 0%, 49.99% { opacity: 0; z-index: 1; } 50%, 100% { opacity: 1; z-index: 5; } }

        form {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            height: 100%;
            text-align: center;
        }

        /* Input Group Icons */
        .input-box {
            position: relative;
            width: 100%;
            margin: 8px 0;
        }
        .input-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
        }
        .input-box input {
            padding-left: 45px;
            margin: 0;
        }

        input {
            background-color: #0d1117;
            border: 1px solid #30363d;
            padding: 12px 15px;
            width: 100%;
            border-radius: 8px;
            color: white;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(241, 44, 76, 0.2);
        }

        button {
            border-radius: 8px;
            border: none;
            background-color: var(--primary);
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 700;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in, background 0.2s;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
            font-family: 'Rajdhani', sans-serif;
        }

        button:hover { background: #d01a39; }
        button:active { transform: scale(0.98); }
        button.ghost { background-color: transparent; border: 2px solid #FFFFFF; margin-top: 0; width: auto; }

        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }

        .container.right-panel-active .overlay-container { transform: translateX(-100%); }

        .overlay {
            background: linear-gradient(135deg, #b31217 0%, #f12c4c 100%);
            color: #FFFFFF;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .container.right-panel-active .overlay { transform: translateX(50%); }

        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
        }

        .overlay-left { transform: translateX(-20%); transition: transform 0.6s ease-in-out; }
        .container.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); transition: transform 0.6s ease-in-out; }
        .container.right-panel-active .overlay-right { transform: translateX(20%); }

        .brand-icon { font-size: 3rem; color: var(--primary); margin-bottom: 10px; filter: drop-shadow(0 0 10px var(--primary)); }
        
        .mobile-toggle-text { display: none; margin-top: 20px; font-size: 14px; color: var(--text-dim); }
        .mobile-toggle-link { color: var(--primary); font-weight: bold; cursor: pointer; text-decoration: underline; }

        @media (max-width: 768px) {
            .container { width: 90%; min-height: 550px; }
            .overlay-container { display: none; }
            .sign-in-container, .sign-up-container { width: 100%; }
            .container.right-panel-active .sign-in-container { transform: translateX(-100%); opacity: 0; }
            .container.right-panel-active .sign-up-container { transform: translateX(0); opacity: 1; }
            .mobile-toggle-text { display: block; }
            form { padding: 0 25px; }
        }
    </style>
</head>
<body>

<!-- PRE-FLIGHT LOADER -->
<div id="loader-wrapper">
    <div class="loader-content">
        <i class="bi bi-airplane-engines-fill loader-plane"></i>
        <div class="loader-bar"><div class="loader-fill"></div></div>
        <p style="color: var(--text-dim); font-family: 'Rajdhani'; margin-top: 10px; letter-spacing: 2px;">CALIBRATING INSTRUMENTS...</p>
    </div>
</div>

<!-- FLOATING HELPDESK -->
<a href="https://wa.me/+918798144910" target="_blank" class="helpdesk-btn">
    <i class="bi bi-headset fs-3"></i>
</a>

<div class="container" id="container">
    
    <!-- SIGN UP FORM -->
    <div class="form-container sign-up-container">
        <form method="POST" action="">
            <i class="bi bi-airplane-engines-fill brand-icon"></i>
            <h1>Pilot Signup</h1>
            <p>Join FlyWing.1000X for the ultimate flight.</p>
            <div class="input-box">
                <i class="bi bi-person"></i>
                <input type="text" name="name" placeholder="Full Name" required />
            </div>
            <div class="input-box">
                <i class="bi bi-telephone"></i>
                <input type="number" name="email" placeholder="Phone Number" required />
            </div>
            <div class="input-box">
                <i class="bi bi-shield-lock"></i>
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <button type="submit" name="signup">Launch Account</button>
            <p class="mobile-toggle-text">
                Already registered? <span class="mobile-toggle-link" id="mobileToSignIn">Login</span>
            </p>
        </form>
    </div>

    <!-- SIGN IN FORM -->
    <div class="form-container sign-in-container">
        <form method="POST" action="">
            <i class="bi bi-airplane-engines-fill brand-icon"></i>
            <h1>Pilot Login</h1>
            <p>Ready for takeoff? Sign in to your cockpit.</p>
            <div class="input-box">
                <i class="bi bi-telephone"></i>
                <input type="number" name="email" placeholder="Phone Number" required />
            </div>
            <div class="input-box">
                <i class="bi bi-shield-lock"></i>
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <a href="#" style="color: var(--text-dim); font-size: 13px; margin-top: 10px;">Forgot Password?</a>
            <button type="submit" name="login">Enter Cockpit</button>
            <p class="mobile-toggle-text">
                New pilot? <span class="mobile-toggle-link" id="mobileToSignUp">Register</span>
            </p>
        </form>
    </div>

    <!-- OVERLAY (Desktop Only) -->
    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <h1>Welcome Back!</h1>
                <p>To keep flying with FlyWing.1000X please login with your pilot credentials</p>
                <button class="ghost" id="signIn">Sign In</button>
            </div>
            <div class="overlay-panel overlay-right">
                <h1>Hello, Aviator!</h1>
                <p>Start your journey and reach multipliers up to 1000X today.</p>
                <button class="ghost" id="signUp">Register Now</button>
            </div>
        </div>
    </div>
</div>

<script>
    const container = document.getElementById('container');
    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const mobileToSignUp = document.getElementById('mobileToSignUp');
    const mobileToSignIn = document.getElementById('mobileToSignIn');

    signUpButton.addEventListener('click', () => container.classList.add("right-panel-active"));
    signInButton.addEventListener('click', () => container.classList.remove("right-panel-active"));
    mobileToSignUp.addEventListener('click', () => container.classList.add("right-panel-active"));
    mobileToSignIn.addEventListener('click', () => container.classList.remove("right-panel-active"));

    // Loader logic
    window.addEventListener('load', () => {
        setTimeout(() => {
            const loader = document.getElementById('loader-wrapper');
            loader.style.opacity = '0';
            loader.style.transition = 'opacity 0.5s ease';
            setTimeout(() => loader.style.display = 'none', 500);
        }, 1800);
    });
</script>

</body>
</html>