<?php
// This file contains all the functions related to the shopping cart logic.

// We need to require the database connection file to interact with the database.
// The path is relative from the location of this file.
require_once __DIR__ . '/../includes/db_connect.php';

// Function to add a food item to the cart or update its quantity if it already exists.
function addToCart($conn, $userId, $foodId, $quantity = 1) {
    // Check if the user ID and food ID are valid integers to prevent issues.
    if (!is_int($userId) || !is_int($foodId) || $userId <= 0 || $foodId <= 0) {
        return false;
    }

    try {
        // First, check if the item already exists in the user's cart.
        $stmt_check = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND food_id = ?");
        $stmt_check->bind_param("ii", $userId, $foodId);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            // The item is already in the cart, so we update the quantity.
            $row = $result->fetch_assoc();
            $current_quantity = $row['quantity'];
            $new_quantity = $current_quantity + $quantity;
            $stmt_update = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND food_id = ?");
            $stmt_update->bind_param("iii", $new_quantity, $userId, $foodId);
            $success = $stmt_update->execute();
            $stmt_update->close();
        } else {
            // The item is not in the cart, so we insert a new record.
            $stmt_insert = $conn->prepare("INSERT INTO cart (user_id, food_id, quantity) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iii", $userId, $foodId, $quantity);
            $success = $stmt_insert->execute();
            $stmt_insert->close();
        }

        $stmt_check->close();
        return $success;
    } catch (mysqli_sql_exception $e) {
        // Log the error for debugging purposes.
        error_log("Database error in addToCart: " . $e->getMessage());
        return false;
    }
}

// Function to remove an item completely from the cart.
function removeFromCart($conn, $userId, $cartId) {
    if (!is_int($userId) || !is_int($cartId) || $userId <= 0 || $cartId <= 0) {
        return false;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND cart_id = ?");
        $stmt->bind_param("ii", $userId, $cartId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in removeFromCart: " . $e->getMessage());
        return false;
    }
}

// Function to update the quantity of a specific cart item.
function updateCartQuantity($conn, $cartId, $newQuantity, $userId = null) {
    if (!is_int($cartId) || !is_int($newQuantity) || $cartId <= 0 || $newQuantity < 0) {
        return false;
    }

    try {
        if ($newQuantity == 0) {
            // Remove the item if quantity is 0
            if ($userId) {
                $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $cartId, $userId);
            } else {
                $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ?");
                $stmt->bind_param("i", $cartId);
            }
        } else {
            if ($userId) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
                $stmt->bind_param("iii", $newQuantity, $cartId, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
                $stmt->bind_param("ii", $newQuantity, $cartId);
            }
        }
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in updateCartQuantity: " . $e->getMessage());
        return false;
    }
}

// Function to get all cart items for a specific user.
function getCartItems($conn, $userId) {
    if (!is_int($userId) || $userId <= 0) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                c.cart_id, 
                c.quantity, 
                f.id AS food_id,
                f.name AS food_name, 
                f.price, 
                f.image,
                f.category,
                f.description
            FROM cart c
            JOIN foods f ON c.food_id = f.id
            WHERE c.user_id = ? AND f.available = 1
            ORDER BY c.cart_id DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            // Determine the correct image folder name based on the category name
            $image_folder_name = strtolower($row['category']);
            if ($image_folder_name === 'burgers and sandwiches') {
                $image_folder_name = 'burgers';
            } elseif ($image_folder_name === 'pasta') {
                $image_folder_name = 'pastas';
            }
            
            // Add the full image path
            $row['image'] = $image_folder_name . '/' . $row['image'];
            $items[] = $row;
        }

        $stmt->close();
        return $items;
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in getCartItems: " . $e->getMessage());
        return [];
    }
}

// Function to calculate the total price of all items in the cart.
function calculateCartTotal($conn, $userId) {
    $total = 0;
    if (!is_int($userId) || $userId <= 0) {
        return $total;
    }

    try {
        $stmt = $conn->prepare("
            SELECT SUM(c.quantity * f.price) AS total_price
            FROM cart c
            JOIN foods f ON c.food_id = f.id
            WHERE c.user_id = ? AND f.available = 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total = floatval($row['total_price'] ?? 0);

        $stmt->close();
        return $total;
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in calculateCartTotal: " . $e->getMessage());
        return 0;
    }
}

// Function to clear a user's cart after an order is placed.
function clearCart($conn, $userId) {
    if (!is_int($userId) || $userId <= 0) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in clearCart: " . $e->getMessage());
        return false;
    }
}

// Function to get cart item count for a user
function getCartItemCount($conn, $userId) {
    if (!is_int($userId) || $userId <= 0) {
        return 0;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT SUM(c.quantity) AS item_count
            FROM cart c
            JOIN foods f ON c.food_id = f.id
            WHERE c.user_id = ? AND f.available = 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = intval($row['item_count'] ?? 0);

        $stmt->close();
        return $count;
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in getCartItemCount: " . $e->getMessage());
        return 0;
    }
}
?>