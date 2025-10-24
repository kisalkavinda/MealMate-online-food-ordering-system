<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db_connect.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /MealMate-online-food-ordering-system/users/login.php");
    exit();
}

// Function to determine folder based on category (copied from edit_food.php for consistency)
function getImageFolder($category) {
    $map = [
        'Appetizers'=>'appetizers',
        'Burgers and Sandwiches'=>'burgers',
        'Pastas'=>'pastas',
        'Pizzas'=>'pizzas',
        'Desserts'=>'desserts'
    ];
    return $map[$category] ?? 'miscellaneous';
}

// Check if a food item ID is provided via GET request
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $food_id = $_GET['id'];

    // Step 1: Get both the image filename and the category before deleting the record
    // The category is crucial for building the correct file path.
    $sql_select = "SELECT image, category FROM foods WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $food_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_name = $row['image'];
        $image_category = $row['category'];
        
        // Use the function to get the correct subfolder
        $image_folder = getImageFolder($image_category);
        
        // Construct the correct file path using the category folder
        $image_path = __DIR__ . '/../assets/images/menu/' . $image_folder . '/' . $image_name;

    } else {
        // ID not found
        header("Location: /MealMate-online-food-ordering-system/users/admin/manage_food.php?msg=not_found");
        exit();
    }
    $stmt_select->close();

    // Step 2: Delete the database record
    $sql_delete = "DELETE FROM foods WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    if ($stmt_delete === false) {
        header("Location: /MealMate-online-food-ordering-system/users/admin/manage_food.php?msg=error");
        exit();
    }
    $stmt_delete->bind_param("i", $food_id);

    if ($stmt_delete->execute()) {
        // Step 3: Delete the image file if it exists and is not the placeholder
        // Check if the image path exists and if the image name is not the placeholder
        if ($image_name !== 'no-image.jpg' && file_exists($image_path)) {
            unlink($image_path);
        }

        header("Location: /MealMate-online-food-ordering-system/users/admin/manage_food.php?msg=deleted");
        exit();
    } else {
        header("Location: /MealMate-online-food-ordering-system/users/admin/manage_food.php?msg=error");
        exit();
    }

    $stmt_delete->close();
} else {
    header("Location: /MealMate-online-food-ordering-system/users/admin/manage_food.php?msg=no_id");
    exit();
}
$conn->close();

?>
