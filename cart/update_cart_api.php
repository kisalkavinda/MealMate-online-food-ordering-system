<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db_connect.php';
require_once 'cart_controller.php';

$response = [
    'success' => false,
    'message' => 'Invalid request',
    'items' => [],
    'total' => 0,
    'action' => '',
    'debug' => [] // Add debug info
];

// Debug: Log the request
$response['debug']['request_method'] = $_SERVER['REQUEST_METHOD'];
$response['debug']['post_data'] = $_POST;
$response['debug']['session_user_id'] = $_SESSION['user_id'] ?? 'not_set';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id']) && isset($_POST['change'])) {
    $user_id = $_SESSION['user_id'];
    $cart_id = intval($_POST['cart_id']);
    $change = intval($_POST['change']);
    
    $response['debug']['parsed_values'] = [
        'user_id' => $user_id,
        'cart_id' => $cart_id,
        'change' => $change
    ];
    
    try {
        // Get current quantity
        $stmt = $conn->prepare("SELECT quantity FROM cart WHERE cart_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $response['message'] = 'Cart item not found';
            $response['debug']['error'] = 'No rows found for cart_id: ' . $cart_id . ' and user_id: ' . $user_id;
            echo json_encode($response);
            exit();
        }
        
        $current_quantity = $result->fetch_assoc()['quantity'];
        $new_quantity = $current_quantity + $change;
        
        $response['debug']['quantity_info'] = [
            'current_quantity' => $current_quantity,
            'change' => $change,
            'new_quantity' => $new_quantity
        ];
        
        if ($new_quantity <= 0) {
            // Remove item from cart
            $stmt_delete = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $stmt_delete->bind_param("ii", $cart_id, $user_id);
            
            if ($stmt_delete->execute()) {
                $response['success'] = true;
                $response['message'] = 'Item removed from cart';
                $response['action'] = 'removed';
            } else {
                $response['message'] = 'Failed to remove item';
                $response['debug']['sql_error'] = $conn->error;
            }
        } else {
            // Update quantity
            $stmt_update = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
            $stmt_update->bind_param("iii", $new_quantity, $cart_id, $user_id);
            
            if ($stmt_update->execute()) {
                $response['success'] = true;
                $response['message'] = 'Quantity updated';
                $response['action'] = 'updated';
            } else {
                $response['message'] = 'Failed to update quantity';
                $response['debug']['sql_error'] = $conn->error;
            }
        }
        
        // Get updated cart data
        if ($response['success']) {
            $response['items'] = getCartItems($conn, $user_id);
            $response['total'] = calculateCartTotal($conn, $user_id);
            $response['debug']['items_count'] = count($response['items']);
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Database error occurred';
        $response['debug']['exception'] = $e->getMessage();
        error_log('Update cart error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Missing required parameters';
    $response['debug']['missing_params'] = [
        'cart_id_isset' => isset($_POST['cart_id']),
        'change_isset' => isset($_POST['change']),
        'request_method' => $_SERVER['REQUEST_METHOD']
    ];
}

echo json_encode($response);
?>