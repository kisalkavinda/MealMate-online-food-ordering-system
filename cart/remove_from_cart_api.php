<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';
require_once 'cart_controller.php';

$response = [
    'success' => false,
    'message' => 'Invalid request',
    'items' => [],
    'total' => 0
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_id = intval($_POST['cart_id']);
    
    try {
        // Remove item from cart
        $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Item removed successfully';
            
            // Get updated cart data
            $response['items'] = getCartItems($conn, $user_id);
            $response['total'] = calculateCartTotal($conn, $user_id);
        } else {
            $response['message'] = 'Failed to remove item';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Database error occurred';
        error_log('Remove from cart error: ' . $e->getMessage());
    }
}

echo json_encode($response);
?>