<?php
// Start a new session or resume the existing one.
session_start();

// Set the content type header to JSON, so the browser knows to expect JSON data.
header('Content-Type: application/json');

// Initialize a response array with default failure values. This is good practice.
$response = [
    'success' => false,
    'message' => 'An unexpected error occurred.',
    'items' => [],
    'total' => 0
];

try {
    // 1. Check if the user is logged in. If not, return an error.
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'User not logged in. Please log in to view your cart.';
        echo json_encode($response);
        exit();
    }

    // 2. Include necessary files and validate their existence.
    $db_connect_path = __DIR__ . '/../includes/db_connect.php';
    $cart_controller_path = __DIR__ . '/cart_controller.php';

    if (!file_exists($db_connect_path) || !file_exists($cart_controller_path)) {
        $response['message'] = 'Error: A required file is missing from the server.';
        echo json_encode($response);
        exit();
    }
    require_once $db_connect_path;
    require_once $cart_controller_path;

    // Check if the database connection is valid
    if (!$conn) {
        throw new Exception("Database connection failed.");
    }

    // 3. Get the user ID from the session.
    $user_id = $_SESSION['user_id'];

    // 4. Validate that the required functions from cart_controller.php exist.
    if (!function_exists('getCartItems') || !function_exists('calculateCartTotal')) {
        $response['message'] = 'Error: Required functions are missing in the cart controller.';
        echo json_encode($response);
        exit();
    }

    // 5. Fetch the cart items and calculate the total.
    $cart_items = getCartItems($conn, $user_id);
    $cart_total = calculateCartTotal($conn, $user_id);

    // 6. Populate the success response.
    $response['success'] = true;
    $response['message'] = 'Cart items fetched successfully.';
    $response['items'] = $cart_items;
    $response['total'] = $cart_total;

} catch (Exception $e) {
    // 7. Handle any general exceptions, such as database connection issues.
    error_log('Cart API error: ' . $e->getMessage());
    $response['message'] = 'An internal server error occurred. Please try again.';
}

echo json_encode($response);
exit();