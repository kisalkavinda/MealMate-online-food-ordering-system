<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db_connect.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../users/login.php");
    exit();
}

$message = '';

// Check for food ID in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../users/admin/manage_food.php");
    exit();
}

$food_id = $_GET['id'];
$food_item = null;

// Fetch food item data
$stmt = $conn->prepare("SELECT * FROM foods WHERE id = ?");
$stmt->bind_param("i", $food_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $food_item = $result->fetch_assoc();
} else {
    $_SESSION['message'] = '<p class="message error">Food item not found.</p>';
    header("Location: ../users/admin/manage_food.php");
    exit();
}
$stmt->close();

// Function to determine folder based on category
function getImageFolder($category)
{
    $map = [
        'Appetizers' => 'appetizers',
        'Burgers and Sandwiches' => 'burgers',
        'Pastas' => 'pastas',
        'Pizzas' => 'pizzas',
        'Desserts' => 'desserts'
    ];
    return $map[$category] ?? 'miscellaneous';
}

// Function to ensure folder exists
function ensureFolderExists($folder)
{
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category = trim($_POST['category']);
    $current_image = !empty($_POST['current_image']) ? $_POST['current_image'] : 'no-image.jpg';
    $image_filename = $current_image;

    if (empty($name) || empty($description) || empty($price) || empty($category)) {
        $message = '<p class="message error">All fields are required.</p>';
    } elseif (!is_numeric($price) || $price <= 0) {
        $message = '<p class="message error">Price must be a positive number.</p>';
    } else {
        // --- Image Handling Logic (Updated) ---
        $upload_base = $_SERVER['DOCUMENT_ROOT'] . '/MealMate-online-food-ordering-system/assets/images/menu';
        $new_folder = getImageFolder($category);
        $old_folder = getImageFolder($food_item['category']);

        // Ensure the target folder for the new image exists
        ensureFolderExists($upload_base . '/' . $new_folder);

        // Check if a new image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($ext, $allowed)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
                $image_filename = $slug . '.' . $ext;

                $upload_path = $upload_base . '/' . $new_folder . '/' . $image_filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Successfully uploaded new image, now delete the old one
                    // Only delete if the old image is not the placeholder and it exists
                    $old_path = $upload_base . '/' . $old_folder . '/' . $current_image;
                    if ($current_image !== 'no-image.jpg' && file_exists($old_path)) {
                        if (!unlink($old_path)) {
                            // Deletion failed, but continue with the rest of the script
                            // Note: The new image is already uploaded
                            $message = '<p class="message error">Failed to delete old image at ' . $old_path . '</p>';
                        }
                    }
                } else {
                    // New image upload failed
                    $message = '<p class="message error">Failed to upload new image to ' . $upload_path . '. Check folder permissions.</p>';
                }
            } else {
                $message = '<p class="message error">Invalid image type. Only JPG, JPEG, PNG, GIF are allowed.</p>';
            }
        }
        // If no new image was uploaded, but the food name or category changed, rename the old file
        elseif ($current_image !== 'no-image.jpg' && ($name !== $food_item['name'] || $category !== $food_item['category'])) {
            $old_path = $upload_base . '/' . $old_folder . '/' . $current_image;
            $ext = pathinfo($current_image, PATHINFO_EXTENSION);
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            $new_path = $upload_base . '/' . $new_folder . '/' . $slug . '.' . $ext;

            // Ensure the destination folder for the renamed file exists
            ensureFolderExists(dirname($new_path));

            if (file_exists($old_path)) {
                if (rename($old_path, $new_path)) {
                    $image_filename = basename($new_path);
                } else {
                    $message = '<p class="message error">Failed to rename existing image.</p>';
                }
            }
        }
        // --- End of Image Handling Logic ---

        // Update database only if there were no critical errors
        if (empty($message)) {
            $stmt = $conn->prepare("UPDATE foods SET name=?, description=?, price=?, category=?, image=? WHERE id=?");
            $stmt->bind_param("ssdssi", $name, $description, $price, $category, $image_filename, $food_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = '<p class="message success">Food item updated successfully!</p>';
                // Note: The header redirect needs to be to a valid location, check your file structure
                header("Location: ../users/admin/manage_food.php");
                exit();
            } else {
                $message = '<p class="message error">DB Error: ' . $stmt->error . '</p>';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Food Item - MealMate Admin</title>
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

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
            background-color: var(--bg-primary);
            background-image: url('../assets/images/bg-dark.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            scroll-behavior: smooth;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .navbar {
            background-color: var(--bg-header);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--border-color);
            padding: 20px 50px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        .nav-container {
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
            text-shadow: 3px 3px 6px #000;
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

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--accent-primary);
        }

        .nav-menu a:hover::after,
        .nav-menu a.active::after {
            width: 100%;
        }

        .form-container {
            padding-top: 150px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 150px);
            padding-bottom: 50px;
        }

        .form-card {
            background: var(--bg-card);
            padding: 40px;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            box-shadow: 0 4px 20px var(--shadow-color);
            width: 100%;
            max-width: 500px;
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

        .form-card input,
        .form-card textarea,
        .form-card select {
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            border: 2px solid var(--accent-primary);
            border-radius: 8px;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 1rem;
        }

        .form-card textarea {
            resize: vertical;
        }

        .form-card button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 5px;
            background-color: var(--accent-primary);
            color: #000;
            font-weight: bold;
            font-size: 1.1em;
            cursor: pointer;
        }

        .form-card button:hover {
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

        .current-image-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .current-image-container img {
            max-width: 140px;
            height: auto;
            border-radius: 8px;
            border: 2px solid var(--accent-primary);
        }

        .simple-footer {
            background-color: var(--footer-bg);
            color: var(--text-primary);
            padding: 20px 0;
            text-align: center;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            width: 100%;
            margin-top: 50px;
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

        @media(max-width:600px) {
            .form-card {
                padding: 25px 15px;
            }

            .nav-container {
                flex-direction: column;
                gap: 15px;
            }

            .nav-menu {
                flex-direction: column;
                gap: 10px;
                align-items: center;
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
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">MealMate</h1>
            <ul class="nav-menu">
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
            <h2>Edit Food Item</h2>
            <?php echo $message; ?>
            <form action="edit_food.php?id=<?= $food_id ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="current_image"
                    value="<?= htmlspecialchars($food_item['image'] ?: 'no-image.jpg') ?>">

                <label for="name">Food Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($food_item['name']) ?>" required>

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"
                    required><?= htmlspecialchars($food_item['description']) ?></textarea>

                <label for="price">Price (Rs.)</label>
                <input type="number" id="price" name="price" step="0.01"
                    value="<?= htmlspecialchars($food_item['price']) ?>" required>

                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="Appetizers" <?= ($food_item['category'] == 'Appetizers') ? 'selected' : '' ?>>Appetizers
                    </option>
                    <option value="Burgers and Sandwiches" <?= ($food_item['category'] == 'Burgers and Sandwiches') ? 'selected' : '' ?>>Burgers and Sandwiches</option>
                    <option value="Pastas" <?= ($food_item['category'] == 'Pastas') ? 'selected' : '' ?>>Pastas</option>
                    <option value="Pizzas" <?= ($food_item['category'] == 'Pizzas') ? 'selected' : '' ?>>Pizzas</option>
                    <option value="Desserts" <?= ($food_item['category'] == 'Desserts') ? 'selected' : '' ?>>Desserts</option>
                </select>

                <div class="current-image-container">
                    <p>Current Image:</p>
                    <?php
                    $image_folder = getImageFolder($food_item['category']);
                    $image_file = $food_item['image'] ?: 'no-image.jpg';
                    $web_path = '/MealMate-online-food-ordering-system/assets/images/menu/' . $image_folder . '/' . $image_file;

                    // Check if the image exists on the server before displaying it
                    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $web_path)) {
                        $web_path = 'https://placehold.co/150x150/0d0d0d/FFFFFF?text=No+Image';
                    }
                    ?>
                    <img src="<?= htmlspecialchars($web_path) ?>" alt="Current image">
                </div>

                <label for="image">Change Image</label>
                <input type="file" id="image" name="image" accept="image/*">

                <button type="submit">Update Food Item</button>
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

    <div class="simple-footer">
        &copy; <?= date('Y') ?> MealMate. All rights reserved.
    </div>

    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
</body>

</html>
