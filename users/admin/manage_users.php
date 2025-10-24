<?php
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

// Define the base path for consistent navigation
$base_path = '/MealMate-online-food-ordering-system';

// Fetch all users
$users = [];
$sql = "SELECT * FROM users ORDER BY user_id ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - MealMate Admin</title>
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

        /* === Global & Navbar Styles from admin_dashboard.php === */
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

        /* === Page Specific Styles (Adapted from original code) === */
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 120px auto 20px auto;
            padding: 0 50px;
            flex: 1 0 auto;
        }

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
            /* Adjusted for a larger gap */
            margin-bottom: 2rem;
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

        .search-container {
            padding: 8px 10px;
            background: var(--bg-card);
            position: sticky;
            top: 72px; /* Adjusted to be below the navbar */
            z-index: 10; /* Set z-index to make sure it's on top */
            border-bottom: 2px solid var(--border-color);
            border-top: 2px solid var(--border-color);
        }

        .search-container input {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            width: 250px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .search-container input:focus {
            outline: none;
            border-color: var(--accent-hover);
            box-shadow: 0 0 10px var(--shadow-color);
            background: var(--bg-card);
        }

        /* Removed max-height and overflow-y from this container to allow the body to scroll */
        .user-table-container {
            background: var(--bg-card);
            border-radius: 12px;
            border: 2px solid var(--border-color);
            box-shadow: 0 4px 20px var(--shadow-color);
            /* Increased margin for the gap as requested */
            margin-top: 40px; 
            margin-bottom: 40px;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-secondary);
            min-width: 900px;
        }

        .user-table th, .user-table td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #444;
            font-size: 14px;
        }

        .user-table th {
            background-color: var(--accent-primary);
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
            position: sticky;
            /* New sticky position to sit below the search bar. */
            top: 116px;
            z-index: 9;
        }

        .user-table tr:nth-child(even) {
            background-color: var(--bg-secondary);
        }

        .user-table tr:hover {
            background-color: var(--bg-header);
        }

        .user-table .actions {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }

        .user-table .actions a {
            color: var(--text-primary);
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .user-table .actions .edit-btn:hover {
            color: #4CAF50;
        }

        .user-table .actions .delete-btn:hover {
            color: #F44336;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--bg-card);
            padding: 30px;
            border: 2px solid var(--accent-primary);
            border-radius: 10px;
            width: 80%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.5);
        }

        .modal-content h3 {
            margin-top: 0;
            color: var(--accent-primary);
        }

        .modal-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .modal-buttons .confirm {
            background-color: #F44336;
            color: white;
        }

        .modal-buttons .confirm:hover {
            background-color: #d32f2f;
        }

        .modal-buttons .cancel {
            background-color: #555;
            color: white;
        }

        .modal-buttons .cancel:hover {
            background-color: #777;
        }
        
        /* === Footer styles for the copyright text === */
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
        
        .simple-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--accent-primary);
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

        [data-theme="light"] .search-container input {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        [data-theme="light"] .search-container input::placeholder {
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
<body class="manage-users">
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">MealMate</h1>
            <ul class="nav-menu">
                <li><a href="<?php echo $base_path; ?>/index.php">Home</a></li>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="manage_food.php">Manage Food</a></li>
                <li><a href="orders/admin_orders.php">Manage Orders</a></li>
                <li><a href="manage_users.php" class="active">Manage Users</a></li>
                <li><a href="<?php echo $base_path; ?>/users/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h2>Manage Users</h2> 
            <p>View, update, or delete registered users.</p>
        </div>

        <div class="user-table-container">
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search users...">
            </div>
            <table class="user-table" id="userTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['user_id'] ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['contact_no']) ?></td>
                            <td><?= htmlspecialchars($user['address']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td class="actions">
                                <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="edit-btn"><i class="fas fa-edit"></i></a>
                                <a href="#" class="delete-btn" onclick="showDeleteModal(<?= $user['user_id'] ?>); return false;"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this user? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button class="confirm" onclick="confirmDelete()">Delete</button>
                <button class="cancel" onclick="hideDeleteModal()">Cancel</button>
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
    <script>
        // Delete modal
        let userIdToDelete = null;
        function showDeleteModal(userId) {
            userIdToDelete = userId;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        function hideDeleteModal() {
            userIdToDelete = null;
            document.getElementById('deleteModal').style.display = 'none';
        }
        function confirmDelete() {
            if (userIdToDelete !== null) {
                window.location.href = 'delete_user.php?id=' + userIdToDelete;
            }
        }

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const userTable = document.getElementById('userTable').getElementsByTagName('tbody')[0];

        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();
            const rows = userTable.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let match = false;
                for (let j = 0; j < cells.length - 1; j++) { // ignore Actions column
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        });
    </script>
</body>
</html>