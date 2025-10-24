<?php
session_start();
require_once('../includes/db_connect.php');

$msg = "";
$redirect_to_login = false; // Flag to control redirection

// Simple security check to prevent public access
// This can be a secret password or key known only to you
$secret_key = "1234";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['secret_key']) && $_POST['secret_key'] === $secret_key) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $contact_no = trim($_POST['contact_no']);
        $address = trim($_POST['address']);

        // Set role to 'admin' for this registration
        $role = 'admin';

        if (!empty($full_name) && !empty($email) && !empty($password) && !empty($contact_no) && !empty($address)) {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $msg = "❌ This email is already registered!";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new admin user into database
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, contact_no, address, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $full_name, $email, $hashed_password, $contact_no, $address, $role);

                if ($stmt->execute()) {
                    $msg = "✅ Admin registration successful! You will be redirected to the login page shortly.";
                    $redirect_to_login = true;
                } else {
                    $msg = "❌ Error: " . $stmt->error;
                }
            }
            $stmt->close();
        } else {
            $msg = "⚠️ Please fill all fields!";
        }
    } else {
        $msg = "❌ Invalid secret key. Access denied!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Registration</title>
    <link rel="stylesheet" href="../assets/form.css?v=1">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php if ($redirect_to_login): ?>
        <meta http-equiv="refresh" content="3;url=login.php">
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

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        header {
            background: var(--bg-header);
            border-bottom: 2px solid var(--border-color);
        }

        .nav-logo {
            color: var(--accent-primary);
        }

        .nav-menu a {
            color: var(--text-primary);
        }

        .nav-menu a:hover {
            color: var(--accent-primary);
        }

        .form-container {
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .form-container input,
        .form-container textarea {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .form-container input:focus,
        .form-container textarea:focus {
            border-color: var(--accent-hover);
            background: var(--bg-card);
        }

        .form-container button {
            background: var(--accent-primary);
            color: #000;
        }

        .form-container button:hover {
            background: var(--accent-hover);
        }

        footer {
            background-color: var(--footer-bg);
            border-top: 2px solid var(--border-color);
            color: var(--text-primary);
        }

        .login-link a {
            color: var(--accent-primary);
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

        [data-theme="light"] .form-container input::placeholder,
        [data-theme="light"] .form-container textarea::placeholder {
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
        }
    </style>
</head>
<body>
    <header>
        <h1 class="nav-logo">MealMate</h1>
        <nav class="nav-menu">
            <a href="../index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </nav>
    </header>

    <div class="form-container">
        <h2>Admin Registration</h2>

        <?php if ($msg != ""): ?>
            <div class="msg"><?= $msg ?></div>
        <?php endif; ?>

        <?php if (!$redirect_to_login): ?>
            <form action="register_admin.php" method="POST">
                <input type="text" name="full_name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="contact_no" placeholder="Contact Number" required>
                <textarea name="address" placeholder="Enter Address" required></textarea>
                <input type="password" name="secret_key" placeholder="Admin Secret Key" required>

                <button type="submit">Register as Admin</button>
            </form>

            <p class="login-link">
                Already have an account? <a href="login.php">Login here</a>
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