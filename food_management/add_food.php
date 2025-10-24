
<?php
// Start the session to manage user data
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
// The path is now one level up to reach the root, then into 'includes'
require_once __DIR__ . '/../includes/db_connect.php';

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Path to get from food_management to the users folder
    header("Location: ../users/login.php");
    exit();
}

// Initialize variables to store form data and messages
$message = ''; // FIX: Initialized the message variable to an empty string
$name = '';
$description = '';
$price = '';
$category = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category = trim($_POST['category']);

    // Check if all required fields are filled
    if (empty($name) || empty($description) || empty($price) || empty($category)) {
        $message = '<p class="message error">All fields are required.</p>';
    } elseif (!is_numeric($price) || $price <= 0) {
        // Validate price to ensure it is a positive number
        $message = '<p class="message error">Price must be a positive number.</p>';
    } else {
        // --- ADDED: Check for duplicate food name before inserting ---
        $check = $conn->prepare("SELECT id FROM foods WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = '<p class="message error">Food item with this name already exists.</p>';
        } else {
            $image_filename = 'no-image.jpg'; // Default placeholder image

            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                $file_info = pathinfo($_FILES['image']['name']);
                $extension = strtolower($file_info['extension']);

                if (in_array($extension, $allowed_extensions)) {
                    // --- ADDED: Rename image to a slugified food name for consistency ---
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
                    $image_filename = $slug . '.' . $extension;

                    // Determine the correct folder for the image based on your folder structure
                    $image_folder = 'miscellaneous'; // Default folder
                    switch ($category) {
                        case 'Appetizers':
                            $image_folder = 'appetizers';
                            break;
                        case 'Burgers and Sandwiches':
                            $image_folder = 'burgers';
                            break;
                        case 'Pizzas':
                            $image_folder = 'pizzas';
                            break;
                        case 'Pastas':
                            $image_folder = 'pastas';
                            break;
                        case 'Desserts':
                            $image_folder = 'desserts';
                            break;
                    }

                    // Corrected path to upload images to the project's assets folder
                    $upload_dir = dirname(__DIR__) . "/assets/images/menu/" . $image_folder;
                    if (!is_dir($upload_dir)) {
                        // Create the directory if it doesn't exist
                        mkdir($upload_dir, 0777, true);
                    }
                    $upload_path = $upload_dir . '/' . $image_filename;

                    // Move the uploaded file to the destination
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $message = '<p class="message error">Failed to upload image. Please try again.</p>';
                        $image_filename = 'no-image.jpg'; // Reset to placeholder on failure
                    }
                } else {
                    $message = '<p class="message error">Invalid image file type. Please upload a JPG, JPEG, PNG, or GIF.</p>';
                }
            }

            // Only proceed with database insertion if there are no errors
            if (empty($message)) {
                // Prepare and execute the SQL query to insert the new food item
                // --- FIXED: Corrected bind types from 'ssdis' to 'ssdss' to match data types ---
                $stmt = $conn->prepare("INSERT INTO foods (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdss", $name, $description, $price, $category, $image_filename);

                if ($stmt->execute()) {
                    $_SESSION['message'] = '<p class="message success">Food item added successfully!</p>';
                    // The path now correctly redirects from food_management to users/admin/manage_food
                    header("Location: ../users/admin/manage_food.php");
                    exit();
                } else {
                    $message = '<p class="message error">Error: ' . $stmt->error . '</p>';
                }

                $stmt->close();
            }
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Food Item - MealMate Admin</title>
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

        /* Shared Styles for Admin Pages */
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

        /* === Form Styles === */
        .form-container {
            padding-top: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-card {
            background: var(--bg-card);
            padding: 40px;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            box-shadow: 0 4px 20px var(--shadow-color);
            width: 500px;
            max-width: 90%;
            margin: 20px auto;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .form-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px var(--shadow-color);
        }

        .form-card h2 {
            text-align: center;
            color: var(--accent-primary);
            font-size: 2em;
            margin-bottom: 20px;
        }

        .form-card label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-secondary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--accent-primary);
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-radius: 8px;
            font-size: 1rem;
            transition: box-shadow 0.3s, transform 0.2s;
        }

        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 40px;
            background-image: url("data:image/svg+xml;utf8,<svg fill='%23ff4500' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            box-shadow: 0 0 12px var(--accent-primary);
            transform: scale(1.01);
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            background-color: var(--bg-secondary);
            border: 2px solid var(--accent-primary);
            border-radius: 8px;
            cursor: pointer;
            color: var(--text-primary);
        }

        input[type="file"]:focus {
            outline: none;
            box-shadow: 0 0 12px var(--accent-primary);
        }

        /* Style the browse button */
        input[type="file"]::-webkit-file-upload-button {
            background-color: var(--accent-primary);
            color: #000;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        input[type="file"]::-webkit-file-upload-button:hover {
            background-color: var(--accent-hover);
        }

        .form-actions {
            text-align: center;
            margin-top: 20px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            background-color: var(--accent-primary);
            color: #000;
            font-weight: bold;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--accent-hover);
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .success {
            background-color: #4CAF50;
            color: white;
        }

        .error {
            background-color: #F44336;
            color: white;
        }

        /* Footer styles for the copyright text */
        .simple-footer {
            background-color: var(--footer-bg);
            color: var(--text-primary);
            padding: 20px 0;
            text-align: center;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            position: relative;
            width: 100%;
            margin-top: 50px;
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

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
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
                <!-- Links now correctly reference their location from the root -->
                <li><a href="../index.php">Home</a></li>
                <li><a href="../users/admin/admin_dashboard.php">Dashboard</a></li>
                <li><a href="../users/admin/manage_food.php" class="active">Manage Food</a></li>
                <li><a href="../users/admin/manage_users.php">Manage Users</a></li>
                <li><a href="../users/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    <div class="form-container">
        <div class="form-card">
            <h2>Add New Food Item</h2>
            <?php echo $message; ?>
            <form action="add_food.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Food Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?= htmlspecialchars($description); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (Rs.)</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?= htmlspecialchars($price); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select a Category</option>
                        <option value="Appetizers" <?= $category === 'Appetizers' ? 'selected' : '' ?>>Appetizers</option>
                        <option value="Burgers and Sandwiches" <?= $category === 'Burgers and Sandwiches' ? 'selected' : '' ?>>Burgers and Sandwiches</option>
                        <option value="Pastas" <?= $category === 'Pastas' ? 'selected' : '' ?>>Pastas</option>
                        <option value="Pizzas" <?= $category === 'Pizzas' ? 'selected' : '' ?>>Pizzas</option>
                        <option value="Desserts" <?= $category === 'Desserts' ? 'selected' : '' ?>>Desserts</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="image">Food Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn"> Add Food Item</button>
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

    <div class="simple-footer"> &copy; <?= date('Y') ?> MealMate. All rights reserved. </div>

    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
</body>

</html>
