<?php
// === START: Error Reporting for debugging ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// === END: Error Reporting for debugging ===

if (session_status() == PHP_SESSION_NONE) {
session_start();
}
// Corrected include path to go up two directories
include '../../includes/db_connect.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../users/login.php");
    exit();
}

$base_path = '/MealMate-online-food-ordering-system';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - MealMate</title>
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

/* === Global Styles === */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    color: var(--text-primary);
    scroll-behavior: smooth;
    background-color: var(--bg-primary);
    overflow-x: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* === Navbar Styles === */
.navbar {
    background-color: var(--bg-header);
    backdrop-filter: blur(10px);
    border-bottom: 2px solid var(--border-color);
    padding: 20px 50px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
    z-index: 20;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
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
    padding: 0;
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

/* === Content Styling === */
.main-content {
    flex: 1;
}

.dashboard-container {
    padding-top: 150px; /* Adjusted to be below the fixed header */
    text-align: center;
}

.dashboard-card {
    background: var(--bg-card);
    padding: 40px;
    border-radius: 12px;
    border: 2px solid var(--border-color);
    box-shadow: 0 4px 20px var(--shadow-color);
    width: 600px;
    max-width: 90%;
    margin: 50px auto;
    transition: transform 0.3s, box-shadow 0.3s;
}

.dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 25px var(--shadow-color);
}

.dashboard-card h2 {
    color: var(--text-primary);
    font-size: 2.5em;
    margin-bottom: 10px;
}

.dashboard-card p {
    color: var(--text-secondary);
    font-size: 1.2em;
    margin-bottom: 20px;
}

.dashboard-card .quick-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
}

.dashboard-card .quick-links a {
    text-decoration: none;
    background: var(--accent-primary);
    color: #000;
    font-weight: bold;
    padding: 12px 25px;
    border-radius: 8px;
    transition: background 0.3s, transform 0.3s;
}

.dashboard-card .quick-links a:hover {
    background: var(--accent-hover);
    transform: translateY(-2px);
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

/* === Responsive Design === */
@media (max-width: 768px) {
    .navbar {
        padding: 15px 20px;
    }
    .nav-logo {
        font-size: 24px;
    }
    .nav-menu {
        gap: 1.5rem;
    }
    .nav-menu a {
        font-size: 16px;
    }
    .dashboard-container {
        padding-top: 120px;
    }
    .dashboard-card {
        padding: 30px;
    }
    .dashboard-card h2 {
        font-size: 2em;
    }
    .dashboard-card p {
        font-size: 1.1em;
    }
    
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

@media (max-width: 480px) {
    .navbar {
        padding: 10px 1rem;
    }
    .nav-logo {
        font-size: 20px;
    }
    .nav-menu {
        gap: 1rem;
    }
    .nav-menu a {
        font-size: 14px;
    }
    .dashboard-card {
        padding: 20px;
    }
    .dashboard-card h2 {
        font-size: 1.8em;
    }
    .dashboard-card p {
        font-size: 1em;
    }
    .dashboard-card .quick-links {
        flex-direction: column;
        gap: 10px;
    }
}

/* Footer styles for the copyright text */
.simple-footer {
    background-color: var(--footer-bg);
    color: var(--text-primary);
    padding: 10px 0;
    text-align: center;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    position: relative;
    width: 100%;
    margin-top: auto;
    border-top: 2px solid var(--border-color);
}

/* Orange line above the footer text */
.simple-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: var(--accent-primary);
}
</style>
</head>
<body>
    <div class="main-content">
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="nav-logo">MealMate</h1>
                <ul class="nav-menu">
                    <li><a href="<?php echo $base_path; ?>/index.php">Home</a></li>
                    <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="manage_food.php">Manage Food</a></li>
                    <li><a href="../admin/orders/admin_orders.php">Manage Orders</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
        <div class="dashboard-container">
            <div class="dashboard-card">
                <h2>Welcome, Admin <?= htmlspecialchars($_SESSION['full_name']) ?>!</h2>
                <p>This is your administrative control panel.</p>
                <div class="quick-links">
                    <a href="manage_food.php">Manage Food</a>
                    <a href="orders/admin_orders.php">Manage Orders</a>
                    <a href="manage_users.php">Manage Users</a>
                </div>
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

    <div class="simple-footer">
        &copy; 2025 MealMate. All rights reserved.
    </div>

    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
</body>
</html>