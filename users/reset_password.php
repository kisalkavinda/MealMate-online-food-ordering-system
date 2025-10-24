<?php
session_start();
require_once('../includes/db_connect.php');

$msg = "";
$showForm = false;
$token = "";

// Check for token in URL
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Validate token
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $showForm = true;
        $user = $result->fetch_assoc();
    } else {
        $msg = "❌ Invalid or expired token!";
    }
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $token = trim($_POST['token']);

    // Validate token again for security
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Password strength validation (underscore included as special character)
        if (strlen($newPassword) < 8) {
            $msg = "⚠️ Password must be at least 8 characters long!";
            $showForm = true;
        } elseif (!preg_match("/[A-Z]/", $newPassword)) {
            $msg = "⚠️ Password must contain at least one uppercase letter!";
            $showForm = true;
        } elseif (!preg_match("/[a-z]/", $newPassword)) {
            $msg = "⚠️ Password must contain at least one lowercase letter!";
            $showForm = true;
        } elseif (!preg_match("/[0-9]/", $newPassword)) {
            $msg = "⚠️ Password must contain at least one number!";
            $showForm = true;
        } elseif (!preg_match("/[\W_]/", $newPassword)) { // underscore included
            $msg = "⚠️ Password must contain at least one special character!";
            $showForm = true;
        } elseif ($newPassword !== $confirmPassword) {
            $msg = "❌ Passwords do not match!";
            $showForm = true;
        } else {
            // Hash password and update DB
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=?, reset_token=NULL WHERE reset_token=?");
            $stmt->bind_param("ss", $hashedPassword, $token);
            $stmt->execute();

            $msg = "✅ Password reset successfully! You can now <a href='login.php'>login</a>.";
            $showForm = false;
        }
    } else {
        $msg = "❌ Invalid or expired token!";
        $showForm = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password - MealMate</title>
<link rel="stylesheet" href="../assets/form.css?v=1">
<link rel="stylesheet" href="../assets/style.css">
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
    display: flex; 
    flex-direction: column; 
    min-height: 100vh;
    background-color: var(--bg-primary);
    color: var(--text-primary);
    transition: background-color 0.3s ease, color 0.3s ease;
    font-family: 'Poppins', sans-serif;
}

.main-content { 
    flex-grow: 1; 
}

.form-container { 
    flex: 1; 
    display: flex; 
    flex-direction: column; 
    justify-content: center; 
    align-items: center; 
    padding: 20px; 
}

.simple-footer { 
    margin-top: auto; 
    background-color: var(--footer-bg);
    color: var(--text-primary);
    padding: 20px 0; 
    text-align: center; 
    font-family: 'Poppins', sans-serif; 
    font-size: 14px; 
    width: 100%; 
    position: relative; 
    border-top: 2px solid var(--border-color);
}

.simple-footer::before { 
    content: ''; 
    position: absolute; 
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 2px; 
    background-color: var(--accent-primary); 
}

header {
    background: var(--bg-header);
    border-bottom: 2px solid var(--border-color);
}

.nav-logo { 
    color: var(--accent-primary); 
    font-size: 32px; 
    font-weight: 700; 
    text-shadow: 3px 3px 6px #000; 
    margin-right: auto; 
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

.msg { 
    margin-bottom: 15px; 
    color: var(--accent-primary); 
    text-align: center; 
}

form input { 
    width: 100%; 
    padding: 10px; 
    margin-bottom: 15px; 
    border: 1px solid var(--border-color); 
    border-radius: 5px; 
}

form button { 
    background-color: var(--accent-primary); 
    color: #000; 
    border: none; 
    padding: 10px 20px; 
    cursor: pointer; 
    border-radius: 5px; 
    transition: all 0.3s ease;
}

form button:hover { 
    background-color: var(--accent-hover); 
    transform: translateY(-2px);
}

.back-login { 
    margin-top: 15px; 
    font-size: 14px; 
    text-align: center; 
}

.back-login a { 
    color: var(--accent-primary); 
    text-decoration: none; 
}

.back-login a:hover { 
    text-decoration: underline; 
}

/* Password rules box */
.password-rules { 
    background-color: var(--bg-secondary); 
    border: 1px solid var(--border-color); 
    padding: 10px; 
    border-radius: 6px; 
    color: var(--text-primary); 
    font-size: 14px; 
    margin-bottom: 15px;
    line-height: 1.4;
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
</style>
</head>
<body>
<div class="main-content">
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
        <h2>Reset Password</h2>

        <?php if ($msg != ""): ?>
            <div class="msg"><?= $msg ?></div>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <div class="password-rules">
                <strong>Password must contain:</strong>
                <ul>
                    <li>At least 8 characters</li>
                    <li>At least 1 uppercase letter</li>
                    <li>At least 1 lowercase letter</li>
                    <li>At least 1 number</li>
                    <li>At least 1 special character (including _)</li>
                </ul>
            </div>

            <form action="reset_password.php" method="POST" id="resetForm">
                <input type="password" name="password" id="password" placeholder="New Password" required>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="back-login">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</div>

<!-- Theme Toggle Button -->
<div class="theme-toggle-container">
    <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
        <i class="fas fa-sun theme-icon sun-icon"></i>
        <i class="fas fa-moon theme-icon moon-icon"></i>
    </button>
</div>

<footer>
    &copy; <?= date('Y') ?> MealMate. All rights reserved.
</footer>

<!-- Load the single theme-toggle script from project root to avoid path issues -->
<script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>

<script>
    // Tab switching logic
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.target;

            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // Add active class to the clicked tab and the corresponding content
            tab.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });
</script>
</body>
</html>