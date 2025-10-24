<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once 'order_controller.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$order_id = intval($data['order_id']);
$reason = $data['reason'] ?? 'Cancelled by customer';

try {
    // Verify order belongs to user and can be cancelled
    $stmt = $conn->prepare("
        SELECT order_status, order_number 
        FROM orders 
        WHERE order_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        throw new Exception('Order not found or access denied');
    }
    
    if (!in_array($order['order_status'], ['pending', 'confirmed'])) {
        throw new Exception('Order cannot be cancelled at this stage');
    }
    
    // Cancel the order
    $success = cancelOrder($conn, $order_id, $user_id, $reason);
    
    if ($success) {
        // Log the cancellation for admin reference
        error_log("Order cancelled: #{$order['order_number']} by user ID: $user_id, Reason: $reason");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order cancelled successfully',
            'order_number' => $order['order_number']
        ]);
    } else {
        throw new Exception('Failed to cancel order');
    }
    
} catch (Exception $e) {
    error_log("Order cancellation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>