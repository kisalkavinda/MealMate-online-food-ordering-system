<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Corrected include path for db_connect.php
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg_profile = $msg_password = "";

// --- Handle Profile Update ---
if (isset($_POST['update_profile'])) {
    $user_id    = $_SESSION['user_id'];
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $contact_no = trim($_POST['contact_no']);
    $address    = trim($_POST['address']);

    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, contact_no=?, address=? WHERE user_id=?");
    $stmt->bind_param("ssssi", $full_name, $email, $contact_no, $address, $user_id);

    if ($stmt->execute()) {
        $msg_profile = "✅ Profile updated successfully!";
        // Update session variables after successful profile update
        $_SESSION['full_name'] = $full_name;
    } else {
        $msg_profile = "❌ Error updating profile: " . $conn->error;
    }
}

// --- Handle Password Change ---
if (isset($_POST['change_password'])) {
    $user_id          = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_pass = $result->fetch_assoc();

    if (!$user_pass || !password_verify($current_password, $user_pass['password'])) {
        $msg_password = "❌ Current password is incorrect!";
    } elseif ($new_password !== $confirm_password) {
        $msg_password = "❌ New password and confirm password do not match!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt_update = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt_update->bind_param("si", $hashed_password, $user_id);

        if ($stmt_update->execute()) {
            $msg_password = "✅ Password changed successfully!";
        } else {
            $msg_password = "❌ Error updating password: " . $conn->error;
        }
    }
}

// Fetch user data again, as it might have been updated
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email, contact_no, address, role, created_at FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$is_admin = $user['role'] === 'admin';

$base_path = '/MealMate-online-food-ordering-system';
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'view_profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Profile - MealMate</title>
<link rel="stylesheet" href="../assets/form.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
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
    transition: background-color 0.3s ease, color 0.3s ease;
}

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

/* === Tabs Styling === */
.tabs {
    display: flex;
    justify-content: center;
    margin-top: 120px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    z-index: 1;
}

.tab {
    padding: 10px 25px;
    background: var(--bg-card);
    border-radius: 8px;
    margin: 0 5px;
    color: var(--accent-primary);
    font-weight: bold;
    transition: 0.3s;
    cursor: pointer;
    border: 2px solid transparent;
}

.tab:hover { 
    background: var(--accent-primary); 
    color: #000; 
}

.tab.active {
    background: var(--accent-primary);
    color: #000;
    border-color: var(--accent-primary);
}

/* === Card Styling === */
.tab-content {
    display: none;
    background: var(--bg-card);
    padding: 25px;
    border-radius: 12px;
    border: 2px solid var(--border-color);
    box-shadow: 0 4px 20px var(--shadow-color);
    width: 400px;
    max-width: 90%;
    margin: 10px auto 50px auto;
    position: relative;
    z-index: 15;
    transition: transform 0.3s, box-shadow 0.3s;
}

.tab-content.active { 
    display: block; 
}

.tab-content:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 25px var(--shadow-color);
}

/* === Form Inputs === */
.tab-content input,
.tab-content textarea,
.tab-content button {
    width: 95%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 6px;
    border: none;
    font-size: 14px;
    transition: all 0.3s ease;
}

.tab-content input, .tab-content textarea { 
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.tab-content input:focus, .tab-content textarea:focus {
    outline: none;
    border-color: var(--accent-hover);
    background: var(--bg-card);
    box-shadow: 0 0 10px var(--shadow-color);
}

.tab-content button { 
    background: var(--accent-primary); 
    color: #000; 
    cursor: pointer; 
    font-weight: bold;
    transition: all 0.3s ease;
}

.tab-content button:hover { 
    background: var(--accent-hover); 
    transform: translateY(-2px);
}

/* === Footer === */
footer {
    text-align: center;
    padding: 25px;
    background: var(--footer-bg);
    color: var(--text-primary);
    font-size: 16px;
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

/* === Responsive === */
@media (max-width: 768px) {
    .navbar {
        padding: 15px 20px;
    }
    .tabs {
        margin-top: 100px;
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
        font-size: 24px;
    }
    .nav-menu {
        gap: 1rem;
    }
    .nav-menu a {
        font-size: 12px;
    }
}
</style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">MealMate</h1>
            <ul class="nav-menu">
                <li><a href="<?php echo $base_path; ?>/index.php">Home</a></li>
                <?php if ($is_admin): ?>
                    <!-- Admin Navigation -->
                    <li><a href="<?php echo $base_path; ?>/users/admin/admin_dashboard.php">Dashboard</a></li>
                    <li><a href="profile.php" class="active">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <!-- User Navigation -->
                    <li><a href="<?php echo $base_path; ?>/food_management/menu.php">Menu</a></li>
                    <li><a href="<?php echo $base_path; ?>/cart/cart.php">Cart</a></li>
                    <li><a href="profile.php" class="active">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- === Tabs === -->
    <div class="tabs">
        <div class="tab <?= ($current_tab == 'view_profile') ? 'active' : '' ?>" data-tab="view_profile">View Profile</div>
        <div class="tab <?= ($current_tab == 'edit_profile') ? 'active' : '' ?>" data-tab="edit_profile">Edit Profile</div>
        <div class="tab <?= ($current_tab == 'change_password') ? 'active' : '' ?>" data-tab="change_password">Change Password</div>
    </div>

    <!-- === Tab Contents (Cards) === -->
    <div id="view_profile" class="tab-content <?= ($current_tab == 'view_profile') ? 'active' : '' ?>">
        <h2>Your Profile</h2>
        <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Contact No:</strong> <?= htmlspecialchars($user['contact_no']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($user['address']) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>
        <p><strong>Joined On:</strong> <?= htmlspecialchars($user['created_at']) ?></p>
    </div>

    <div id="edit_profile" class="tab-content <?= ($current_tab == 'edit_profile') ? 'active' : '' ?>">
        <h2>Edit Profile</h2>
        <?php if ($msg_profile != ""): ?><div class="msg"><?= $msg_profile ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <input type="text" name="contact_no" value="<?= htmlspecialchars($user['contact_no']) ?>" required>
            <textarea name="address" required><?= htmlspecialchars($user['address']) ?></textarea>
            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </div>

    <div id="change_password" class="tab-content <?= ($current_tab == 'change_password') ? 'active' : '' ?>">
        <h2>Change Password</h2>
        <?php if ($msg_password != ""): ?><div class="msg"><?= $msg_password ?></div><?php endif; ?>
        <form method="POST">
            <input type="password" name="current_password" placeholder="Current Password" required>
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" name="change_password">Update Password</button>
        </form>
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

    <!-- Use project base path to reliably load the root theme-toggle.js -->
    <script src="<?php echo $base_path; ?>/theme-toggle.js"></script>

    <script>
        // Tab switching logic
        const tabs = document.querySelectorAll('.tab');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab.dataset.tab);
                window.history.pushState({}, '', url);

                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));

                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });
    </script>
</body>
</html>