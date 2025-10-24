<?php
session_start();
require_once '../includes/db_connect.php';

// Set JSON header for all responses
header('Content-Type: application/json');

// Handle different actions based on the 'action' parameter
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add_to_cart':
        addToCart();
        break;
    case 'add_food':
        addFood();
        break;
    case 'edit_food':
        editFood();
        break;
    case 'delete_food':
        deleteFood();
        break;
    default:
        // Respond with an error for an invalid action
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// --- Function to add a food item to the database ---
function addFood() {
    global $conn;

    // Admin check
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category = $_POST['category'];

        // Handle image upload
        $image = 'default.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = uniqid() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $image);
        }

        $sql = "INSERT INTO foods (name, description, price, category, image, available) 
                VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdss", $name, $description, $price, $category, $image);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Food item added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding food item: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }
}

// --- Function to edit a food item in the database ---
function editFood() {
    global $conn;

    // Admin check
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $food_id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category = $_POST['category'];
        $available = isset($_POST['available']) ? 1 : 0;

        // Check if a new image was uploaded
        $image = $_POST['current_image'] ?? 'default.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = uniqid() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $image);
        }

        $sql = "UPDATE foods SET name = ?, description = ?, price = ?, category = ?, image = ?, available = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssii", $name, $description, $price, $category, $image, $available, $food_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Food item updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating food item: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }
}

// --- Function to soft delete a food item ---
function deleteFood() {
    global $conn;

    // Admin check
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        return;
    }

    $food_id = $_GET['id'] ?? 0;

    if ($food_id) {
        // Soft delete by setting available to 0
        $sql = "UPDATE foods SET available = 0 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $food_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Food item deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting food item: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Food ID not specified.']);
    }
}

// --- Function to add a food item to the user's cart ---
function addToCart() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first']);
        return;
    }
    
    $food_id = intval($_GET['food_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($food_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid food item']);
        return;
    }
    
    try {
        // Check if food exists and is available
        $stmt_check_food = $conn->prepare("SELECT id FROM foods WHERE id = ? AND available = 1");
        $stmt_check_food->bind_param("i", $food_id);
        $stmt_check_food->execute();
        $result_check = $stmt_check_food->get_result();
        
        if ($result_check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Food item not available']);
            return;
        }
        
        // Check if item already in cart
        $sql = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND food_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $food_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update quantity
            $row = $result->fetch_assoc();
            $new_quantity = $row['quantity'] + 1;
            $sql = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $new_quantity, $row['cart_id']);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating cart']);
            }
        } else {
            // Insert new item
            $sql = "INSERT INTO cart (user_id, food_id, quantity) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $food_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Added to cart successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
            }
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        error_log('Add to cart error: ' . $e->getMessage());
    }
}
?>