<?php
session_start();
require_once('../includes/db_connect.php');

$msg = "";
$redirectUrl = ""; // store where to redirect

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Start session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Choose redirect page by role
                if ($user['role'] === 'admin') {
                    // Fix applied here
                    $_SESSION['is_admin'] = true;
                    $_SESSION['admin_id'] = $user['user_id'];
                    $msg = "✅ Login successful! Redirecting to Admin Dashboard...";
                    $redirectUrl = "../users/admin/admin_dashboard.php";
                } else {
                    $msg = "✅ Login successful! Redirecting to Menu...";
                    $redirectUrl = "../food_management/menu.php";
                }

                // Remember Me
                if ($remember) {
                    setcookie('email', $email, time() + (86400 * 30), "/");
                    setcookie('password', $password, time() + (86400 * 30), "/");
                } else {
                    setcookie('email', '', time() - 3600, "/");
                    setcookie('password', '', time() - 3600, "/");
                }

            } else {
                $msg = "❌ Invalid password!";
            }
        } else {
            $msg = "❌ Email not registered!";
        }
    } else {
        $msg = "⚠️ Please fill all fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - MealMate</title>
    <link rel="stylesheet" href="../assets/form.css?v=1">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <?php if (!empty($redirectUrl)): ?>
        <meta http-equiv="refresh" content="2;url=<?= $redirectUrl ?>">
    <?php endif; ?>
    <style>
        /* === CSS Variables for Theme === */
        :root {
            --bg-primary: #0d0d0d;
            --bg-secondary: #1a1a1a;
            --bg-card: #222;
            --bg-header: rgba(0, 0, 0, 0.8);
            --text-primary: #fff;
            --text-secondary: #ddd;
            --text-muted: #ccc;
            --accent-primary: #FF4500;
            --accent-hover: #FF6B35;
            --border-color: #FF4500;
            --shadow-color: rgba(255, 69, 0, 0.3);
            --footer-bg: rgba(0, 0, 0, 0.9);
            --footer-border: #333;
        }

        [data-theme="light"] {
            --bg-primary: #fafafa;
            --bg-secondary: #f0f0f0;
            --bg-card: #fff;
            --bg-header: rgba(255, 255, 255, 0.98);
            --text-primary: #1a1a1a;
            --text-secondary: #333;
            --text-muted: #555;
            --accent-primary: #FF4500;
            --accent-hover: #FF3300;
            --border-color: #FF4500;
            --shadow-color: rgba(255, 69, 0, 0.25);
            --footer-bg: #f8f8f8;
            --footer-border: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: var(--bg-header);
            border-bottom: 2px solid var(--border-color);
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .nav-logo {
            color: var(--accent-primary);
            font-size: 32px;
            font-weight: 700;
            text-shadow: 3px 3px 6px #000;
        }

        [data-theme="light"] .nav-logo {
            text-shadow: 2px 2px 4px rgba(255, 69, 0, 0.2);
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-menu a {
            color: var(--text-primary);
            text-decoration: none;
            font-size: 18px;
            font-weight: 400;
            position: relative;
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent-primary);
            transition: width 0.3s;
        }

        .nav-menu a:hover, .nav-menu a.active {
            color: var(--accent-primary);
        }
        
        .nav-menu a:hover::after, .nav-menu a.active::after {
            width: 100%;
        }

        .form-container {
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            box-shadow: 0 10px 30px var(--shadow-color);
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            margin: 50px auto;
        }

        .form-container h2 {
            margin-bottom: 20px;
            text-align: center;
        }

        .form-container input {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-container input:focus {
            border-color: var(--accent-hover);
            background: var(--bg-card);
            outline: none;
        }

        .form-container button {
            background: var(--accent-primary);
            color: #000;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        .form-container button:hover {
            background: var(--accent-hover);
        }

        /* Forgot password link */
        .forgot-password {
            margin-top: 10px;
            font-size: 14px;
            text-align: center;
        }

        .forgot-password a {
            color: var(--accent-primary);
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        /* Login link */
        .login-link {
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
        }

        .login-link a {
            color: var(--accent-primary);
        }

        .msg {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            background: var(--bg-secondary);
            color: var(--accent-primary);
            text-align: center;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 10px 0;
            color: var(--text-primary);
        }

        .remember-me input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        footer {
            margin-top: auto;
            background-color: var(--footer-bg);
            color: var(--text-primary);
            padding: 20px 0;
            text-align: center;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            border-top: 2px solid var(--border-color);
        }

        /* === Theme Toggle Button === */
        .theme-toggle-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
        }

        .theme-toggle-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--accent-primary);
            border: 3px solid var(--bg-card);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            box-shadow: 0 8px 25px var(--shadow-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .theme-toggle-btn:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 12px 35px var(--shadow-color);
        }

        .theme-toggle-btn:active {
            transform: scale(0.95);
        }

        .theme-toggle-btn .theme-icon {
            position: absolute;
            transition: all 0.3s ease;
        }

        .theme-toggle-btn .sun-icon {
            opacity: 0;
            transform: rotate(-90deg) scale(0);
        }

        .theme-toggle-btn .moon-icon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        [data-theme="light"] .theme-toggle-btn .sun-icon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        [data-theme="light"] .theme-toggle-btn .moon-icon {
            opacity: 0;
            transform: rotate(90deg) scale(0);
        }

        /* Autofill and focus fix */
        input:-webkit-autofill,
        textarea:-webkit-autofill,
        select:-webkit-autofill {
            -webkit-box-shadow: 0 0 0px 1000px var(--bg-secondary) inset !important;
            box-shadow: 0 0 0px 1000px var(--bg-secondary) inset !important;
            -webkit-text-fill-color: var(--text-primary) !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        [data-theme="light"] .form-container input,
        [data-theme="light"] .form-container textarea,
        [data-theme="light"] .form-container select {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        [data-theme="light"] .form-container input::placeholder {
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .theme-toggle-container {
                bottom: 20px;
                right: 20px;
            }
            
            .theme-toggle-btn {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            
            header {
                padding: 15px 20px;
            }
            
            .nav-logo {
                font-size: 24px;
            }
            
            .nav-menu {
                gap: 1rem;
            }
            
            .nav-menu a {
                font-size: 14px;
            }

            .form-container {
                margin: 30px 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1 class="nav-logo">MealMate</h1>
        <nav class="nav-menu">
            <a href="../index.php">Home</a>
            <a href="register.php">Register</a>
            <a href="../food_management/menu.php">Menu</a>
            <a href="../cart/cart.php">Cart</a>
        </nav>
    </header>

    <div class="form-container">
        <?php if (!empty($redirectUrl)): ?>
            <h2><?= $msg ?></h2>
        <?php else: ?>
            <h2>User Login</h2>

            <?php if ($msg != ""): ?>
                <div class="msg"><?= $msg ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Email Address" required
                        value="<?= isset($_COOKIE['email']) ? $_COOKIE['email'] : '' ?>">
                <input type="password" name="password" placeholder="Password" required
                        value="<?= isset($_COOKIE['password']) ? $_COOKIE['password'] : '' ?>">

                <div class="remember-me">
                    <input type="checkbox" name="remember" id="remember" <?= isset($_COOKIE['email']) ? 'checked' : '' ?>>
                    <label for="remember">Remember Me</label>
                </div>

                <button type="submit">Login</button>

                <p class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </p>
            </form>

            <p class="login-link">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle-container">
        <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
            <i class="fas fa-sun theme-icon sun-icon"></i>
            <i class="fas fa-moon theme-icon moon-icon"></i>
        </button>
    </div>

    <footer>
        &copy; <?= date('Y'); ?> MealMate. All rights reserved.
    </footer>

    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
</body>
</html>