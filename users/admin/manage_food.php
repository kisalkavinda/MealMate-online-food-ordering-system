<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure the database connection file exists and is accessible.
// This path is relative to the current file's location.
require_once __DIR__ . '/../../includes/db_connect.php';

// Check if user is logged in and has admin role.
// This is a crucial security check to prevent unauthorized access.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../users/login.php");
    exit();
}

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') {
        $message = '<div class="alert success-alert">Food item deleted successfully!</div>';
    } elseif ($_GET['msg'] == 'not_found') {
        $message = '<div class="alert error-alert">Error: Food item not found.</div>';
    } elseif ($_GET['msg'] == 'error') {
        $message = '<div class="alert error-alert">Error deleting food item. Please try again.</div>';
    }
}

// Fetch all food items from the database.
// The ORDER BY clause ensures the newest items are at the top.
$food_items = [];
$sql = "SELECT * FROM foods ORDER BY id DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $food_items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Food - MealMate Admin</title>
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

        /* === Main Content Container === */
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 120px auto 2rem auto;
            padding: 0 50px;
            flex: 1 0 auto;
        }

        /* === Header Section === */
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0.5rem 0;
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

        .add-food-btn {
            background: var(--accent-primary);
            color: #000;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s, transform 0.3s;
            display: inline-block;
            margin-bottom: 20px;
        }

        .add-food-btn:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        /* === Food Table Styles === */
        .food-table-container {
            overflow-x: auto;
            background: var(--bg-card);
            border-radius: 12px;
            border: 2px solid var(--border-color);
            box-shadow: 0 4px 20px var(--shadow-color);
            margin-top: 20px;
        }

        .food-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-primary);
            min-width: 700px;
        }

        .food-table th, .food-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #444;
        }

        .food-table th {
            background-color: var(--accent-primary);
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        .food-table tr:hover {
            background-color: var(--bg-secondary);
        }

        .food-table td {
            min-height: 110px;
            box-sizing: border-box;
        }

        /* Use normal table-cell alignment */
        .food-table td.image-cell,
        .food-table td.actions-cell {
            text-align: center;
            vertical-align: middle;
            height: 100px;
            padding: 10px;
        }

        .food-table td.description-cell {
            vertical-align: top;
        }

        /* Image sizing */
        .food-table td img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Actions centered neatly */
        .food-table .actions {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }

        .food-table .actions a {
            color: var(--text-primary);
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .food-table .actions .edit-btn:hover {
            color: #4CAF50;
        }

        .food-table .actions .delete-btn:hover {
            color: #F44336;
        }

        /* Alert Message Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            animation: fadeInOut 5s forwards;
        }

        .alert.success-alert {
            background-color: #28a745;
            color: #fff;
        }

        .alert.error-alert {
            background-color: #dc3545;
            color: #fff;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
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

        /* === Footer Styles === */
        .simple-footer {
            background-color: var(--footer-bg);
            color: var(--text-primary);
            padding: 20px 0;
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

        /* === Responsive Design === */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            .container {
                margin: 100px auto 1.5rem auto;
                padding: 0 20px;
            }
            .header h2 {
                font-size: 1.8rem;
            }
            .header p {
                font-size: 1rem;
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
            .header h2 {
                font-size: 1.5rem;
            }
            .header p {
                font-size: 0.9rem;
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
    </style>
</head>

<body class="manage-food">
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">MealMate</h1>
            <ul class="nav-menu">
                <li><a href="/MealMate-online-food-ordering-system/index.php">Home</a></li>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="/MealMate-online-food-ordering-system/food_management/manage_food.php" class="active">Manage Food</a></li>
                <li><a href="/MealMate-online-food-ordering-system/users/admin/orders/admin_orders.php">Manage Orders</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="/MealMate-online-food-ordering-system/users/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="header">
            <h2>Manage Food Items</h2>
            <p>Add, edit, or delete food items from your menu.</p>
        </div>
        
        <?php echo $message; ?>
        
        <a href="/MealMate-online-food-ordering-system/food_management/add_food.php" class="add-food-btn">Add New Food Item</a>
        
        <div class="food-table-container">
            <table class="food-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price (Rs.)</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($food_items)): ?>
                    <?php foreach ($food_items as $item): ?>
                        <?php
                            $image_folder = strtolower($item['category']);
                            if ($image_folder === 'burgers and sandwiches') {
                                $image_folder = 'burgers';
                            } elseif ($image_folder === 'pasta') {
                                $image_folder = 'pastas';
                            }
                            
                            // Construct filesystem path (server)
                            $server_path = $_SERVER['DOCUMENT_ROOT'] . '/MealMate-online-food-ordering-system/assets/images/menu/' . $image_folder . '/' . $item['image'];
                            
                            // Construct web path (for <img src>)
                            $web_path = '/MealMate-online-food-ordering-system/assets/images/menu/' . $image_folder . '/' . $item['image'];
                            
                            // If file doesn't exist or is empty, use a fallback placeholder image
                            if (empty($item['image']) || !is_file($server_path)) {
                                $web_path = 'https://placehold.co/70x70/0d0d0d/FFFFFF?text=No+Image';
                            }
                        ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($web_path) ?>" alt="<?= htmlspecialchars($item['name']) ?>"></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td><?= htmlspecialchars(number_format($item['price'], 2)) ?></td>
                            <td><?= htmlspecialchars($item['category']) ?></td>
                            <td class="actions">
                                <a href="/MealMate-online-food-ordering-system/food_management/edit_food.php?id=<?= $item['id'] ?>" class="edit-btn"><i class="fas fa-edit"></i></a>
                                <a href="#" class="delete-btn" onclick="showDeleteModal(<?= $item['id'] ?>); return false;">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No food items found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this food item? This action cannot be undone.</p>
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
        &copy; <?= date('Y') ?> MealMate. All rights reserved.
    </div>

    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
    <script>
        let foodIdToDelete = null;

        function showDeleteModal(foodId) {
            foodIdToDelete = foodId;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function hideDeleteModal() {
            foodIdToDelete = null;
            document.getElementById('deleteModal').style.display = 'none';
        }

        function confirmDelete() {
            if (foodIdToDelete !== null) {
                window.location.href = '/MealMate-online-food-ordering-system/food_management/delete_food.php?id=' + foodIdToDelete;
            }
        }
    </script>
</body>
</html>