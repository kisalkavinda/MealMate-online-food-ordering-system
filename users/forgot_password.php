<?php
session_start();
require_once('../includes/db_connect.php');
require '../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Generate a secure token
            $token = bin2hex(random_bytes(50));

            // Store token in DB (ensure column `reset_token` exists)
            $stmt2 = $conn->prepare("UPDATE users SET reset_token=? WHERE email=?");
            $stmt2->bind_param("ss", $token, $email);
            $stmt2->execute();

            // Reset link (update to your localhost or domain)
            $resetLink = "http://localhost/MealMate-online-food-ordering-system/users/reset_password.php?token=$token";

            // Send email using PHPMailer + Mailtrap
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'sandbox.smtp.mailtrap.io';
                $mail->SMTPAuth   = true;
                $mail->Port       = 2525;
                $mail->Username   = 'fead2ad7782a4b'; // Your Mailtrap username
                $mail->Password   = '159e7fe69f8d04'; // Your Mailtrap password

                $mail->setFrom('no-reply@mealmate.com', 'MealMate');
                $mail->addAddress($email, $user['full_name']);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "
                    <h3>Password Reset Request</h3>
                    <p>Hello {$user['full_name']},</p>
                    <p>We received a request to reset your password.</p>
                    <p>Click the link below to reset it:</p>
                    <p><a href='$resetLink'>$resetLink</a></p>
                    <br>
                    <p><strong>Password Requirements:</strong></p>
                    <ul>
                        <li>At least 8 characters</li>
                        <li>At least 1 uppercase letter</li>
                        <li>At least 1 lowercase letter</li>
                        <li>At least 1 number</li>
                        <li>At least 1 special character (including _)</li>
                    </ul>
                    <p>If you did not request this, please ignore this email.</p>
                ";

                $mail->send();
                $msg = "✅ Password reset link has been sent (check your Mailtrap inbox).";
            } catch (Exception $e) {
                $msg = "❌ Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

        } else {
            $msg = "❌ Email not found!";
        }
    } else {
        $msg = "⚠️ Please enter your email!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - MealMate</title>
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

        small {
            color: var(--text-secondary);
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
        <h2>Forgot Password</h2>

        <?php if ($msg != ""): ?>
            <div class="msg"><?= $msg ?></div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST" style="width:100%; max-width:400px;">
            <input type="email" name="email" placeholder="Enter your registered email" required>
            
            <!-- Tooltip / Password guide -->
            <small style="display:block; margin-bottom:10px; color:var(--text-secondary);">
                Note: The password you set after resetting must have at least 8 characters, including uppercase, lowercase, number, and special character (e.g., !@#$%^&* or _).
            </small>
            
            <button type="submit">Send Reset Link</button>

            <!-- Back to login slightly below the button -->
            <div class="back-login" style="margin-top:20px; text-align:center;">
                <a href="login.php" style="color:var(--accent-primary);">Back to Login</a>
            </div>
        </form>
    </div>
</div>

<!-- Theme Toggle Button -->
<div class="theme-toggle-container">
    <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
        <i class="fas fa-sun theme-icon sun-icon"></i>
        <i class="fas fa-moon theme-icon moon-icon"></i>
    </button>
</div>

<?php include '../includes/simple_footer.php'; ?>

<!-- Use canonical script path to ensure the toggle script loads correctly -->
<script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>

<!-- Optional JS: Live password feedback -->
<script>
const passwordInput = document.getElementById('password');
const resetForm = document.getElementById('resetForm');

passwordInput.addEventListener('input', () => {
    const pwd = passwordInput.value;
    let msg = '';

    if(pwd.length < 8) msg += '• At least 8 characters\n';
    if(!/[A-Z]/.test(pwd)) msg += '• At least 1 uppercase letter\n';
    if(!/[a-z]/.test(pwd)) msg += '• At least 1 lowercase letter\n';
    if(!/[0-9]/.test(pwd)) msg += '• At least 1 number\n';
    if(!/[\W_]/.test(pwd)) msg += '• At least 1 special character\n';

    passwordInput.setCustomValidity(msg);
});
</script>
</body>
</html>