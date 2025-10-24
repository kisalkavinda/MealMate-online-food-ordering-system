<?php
session_start();
include '../includes/db_connect.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch user's current hashed password
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($current_password, $user['password'])) {
        $msg = "❌ Current password is incorrect!";
    } elseif ($new_password !== $confirm_password) {
        $msg = "❌ New password and confirm password do not match!";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update->bind_param("si", $hashed_password, $user_id);

        if ($update->execute()) {
            $msg = "✅ Password changed successfully!";
        } else {
            $msg = "❌ Error updating password: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - MealMate</title>
    <link rel="stylesheet" href="../assets/form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        header h1 {
            color: var(--accent-primary);
        }

        header nav a {
            color: var(--text-primary);
        }

        header nav a:hover {
            color: var(--accent-primary);
        }

        .form-container {
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .form-container input {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .form-container input:focus {
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

        .msg {
            margin-bottom: 15px;
            font-size: 14px;
            color: var(--accent-primary);
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
    <h1>MealMate</h1>
    <nav>
        <a href="../index.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="edit_user.php">Edit Profile</a>
        <a href="change_password.php">Change Password</a>
        <a href="../cart/cart.php">Cart</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="form-container">
    <h2>Change Password</h2>

    <?php if ($msg != ""): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>

    <form action="change_password.php" method="POST">
        <input type="password" name="current_password" placeholder="Current Password" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit">Update Password</button>
    </form>
</div>

<!-- Theme Toggle Button -->
<div class="theme-toggle-container">
    <button type="button" class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
        <i class="fas fa-sun theme-icon sun-icon"></i>
        <i class="fas fa-moon theme-icon moon-icon"></i>
    </button>
</div>

<footer>
    &copy; <?= date('Y') ?> MealMate. All rights reserved.
</footer>

<!-- Load canonical theme script from project root to avoid relative-path issues -->
<script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
</body>
</html>