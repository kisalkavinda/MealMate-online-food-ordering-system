<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once __DIR__ . '/../../includes/db_connect.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../users/login.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = intval($_GET['id']);
$error = "";
$success = "";

// Fetch user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_users.php");
    exit();
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $contact_no = trim($_POST['contact_no']);
    $address    = trim($_POST['address']);
    $role       = $_POST['role'];

    // Basic validation
    if (empty($full_name) || empty($email) || empty($contact_no) || empty($address)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Update user in DB
        $update_sql = "UPDATE users SET full_name = ?, email = ?, contact_no = ?, address = ?, role = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssssi", $full_name, $email, $contact_no, $address, $role, $user_id);

        if ($update_stmt->execute()) {
            $success = "User updated successfully!";
            // Refresh user data
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['contact_no'] = $contact_no;
            $user['address'] = $address;
            $user['role'] = $role;
        } else {
            $error = "Failed to update user. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - MealMate Admin</title>
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

        body.edit-user {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Navbar */
        .navbar {
            background-color: var(--bg-header);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--border-color);
            padding: 20px 50px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 20;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            color: var(--accent-primary);
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            text-shadow: 3px 3px 6px #000;
        }

        [data-theme="light"] .nav-logo {
            text-shadow: 2px 2px 4px rgba(255, 69, 0, 0.2);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-menu a {
            color: var(--text-primary);
            text-decoration: none;
            font-size: 18px;
            font-weight: 400;
            letter-spacing: 0.5px;
            position: relative;
            transition: color 0.3s ease;
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent-primary);
            transition: width 0.3s ease;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--accent-primary);
        }

        .nav-menu a:hover::after,
        .nav-menu a.active::after {
            width: 100%;
        }

        /* Main container */
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 120px auto 0 auto;
            padding: 0 50px;
            flex: 1 0 auto;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .header h2 {
            color: var(--accent-primary);
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .header::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            width: 100vw;
            height: 2px;
            background-color: var(--accent-primary);
            margin-left: calc(-50vw + 50%);
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 120px auto 50px auto;
            padding: 20px;
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 20px var(--shadow-color);
            flex: 1 0 auto;
        }

        h2 {
            text-align: center;
            color: var(--accent-primary);
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input, select {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-hover);
            box-shadow: 0 0 8px var(--shadow-color);
            background: var(--bg-card);
        }

        button {
            padding: 12px;
            background: var(--accent-primary);
            color: #000;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        button:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        .message {
            text-align: center;
            font-weight: bold;
        }

        .message.error {
            color: #F44336;
        }

        .message.success {
            color: #4CAF50;
        }

        a.back-link {
            display: inline-block;
            margin-top: 10px;
            color: var(--accent-primary);
            text-decoration: none;
        }

        a.back-link:hover {
            text-decoration: underline;
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

        [data-theme="light"] input,
        [data-theme="light"] textarea,
        [data-theme="light"] select {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        [data-theme="light"] input::placeholder {
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
<body class="edit-user">
    <!-- Navbar/Header -->
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">MealMate</h1>
            <ul class="nav-menu">
                <li><a href="/MealMate-online-food-ordering-system/index.php">Home</a></li>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="edit_user.php" class="active">Edit Users</a></li>
                <li><a href="/MealMate-online-food-ordering-system/users/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Form Container -->
    <div class="container">
        <h2>Edit User</h2>

        <?php if($error): ?>
            <div class="message error"><?= $error ?></div>
        <?php elseif($success): ?>
            <div class="message success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <input type="text" name="contact_no" placeholder="Contact Number" value="<?= htmlspecialchars($user['contact_no']) ?>" required>
            <input type="text" name="address" placeholder="Address" value="<?= htmlspecialchars($user['address']) ?>" required>
            <select name="role" required>
                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <button type="submit">Update User</button>
        </form>

        <a href="manage_users.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Manage Users</a>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle-container">
        <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
            <i class="fas fa-sun theme-icon sun-icon"></i>
            <i class="fas fa-moon theme-icon moon-icon"></i>
        </button>
    </div>

    <!-- Footer -->
    <?php include '../../includes/simple_footer.php'; ?>

    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
</body>
</html>