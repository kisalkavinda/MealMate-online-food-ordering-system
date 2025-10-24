<?php
session_start();
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An unexpected error occurred.'
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'User not logged in. Please log in to add items to cart.';
        echo json_encode($response);
        exit();
    }

    // Include necessary files
    require_once __DIR__ . '/../includes/db_connect.php';
    require_once __DIR__ . '/cart_controller.php';

    // Check if the database connection is valid
    if (!$conn) {
        throw new Exception("Database connection failed.");
    }

    // Get parameters
    $food_id = isset($_POST['food_id']) ? intval($_POST['food_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $user_id = intval($_SESSION['user_id']);

    // Validate parameters
    if ($food_id <= 0) {
        $response['message'] = 'Invalid food item selected.';
        echo json_encode($response);
        exit();
    }

    if ($quantity <= 0) {
        $response['message'] = 'Invalid quantity specified.';
        echo json_encode($response);
        exit();
    }

    // Check if the food item exists and is available
    $stmt = $conn->prepare("SELECT id, name FROM foods WHERE id = ? AND available = 1");
    $stmt->bind_param("i", $food_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response['message'] = 'Food item not found or not available.';
        echo json_encode($response);
        exit();
    }

    $food = $result->fetch_assoc();
    $stmt->close();

    // Add to cart
    if (addToCart($conn, $user_id, $food_id, $quantity)) {
        $response['success'] = true;
        $response['message'] = 'Item added to cart successfully.';
    } else {
        $response['message'] = 'Failed to add item to cart. Please try again.';
    }

} catch (Exception $e) {
    error_log('Add to cart API error: ' . $e->getMessage());
    $response['message'] = 'An internal server error occurred. Please try again.';
}

echo json_encode($response);
exit();
?>