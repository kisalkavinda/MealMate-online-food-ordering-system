<?php
session_start();
require_once __DIR__ . '/../../../includes/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to avoid breaking JSON response

// Function to log errors
function logError($message) {
    error_log("[Order Status Update] " . date('Y-m-d H:i:s') . " - " . $message);
}

try {
    // Check if admin is logged in - FIXED: Use consistent session check
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Unauthorized access. Please login as admin.'
        ]);
        exit();
    }

    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false, 
            'message' => 'Method not allowed. Use POST.'
        ]);
        exit();
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid JSON data: ' . json_last_error_msg()
        ]);
        exit();
    }

    // Handle test requests
    if (isset($data['test']) && $data['test'] === true) {
        echo json_encode([
            'success' => true, 
            'message' => 'update_order_status.php is working correctly'
        ]);
        exit();
    }

    // Required fields
    $order_id = $data['order_id'] ?? null;
    $new_status = $data['new_status'] ?? null;
    $reason = $data['reason'] ?? '';

    // Validate required fields
    if (empty($order_id) || empty($new_status)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Order ID and new status are required'
        ]);
        exit();
    }

    // Validate order ID is numeric
    if (!is_numeric($order_id)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid order ID format'
        ]);
        exit();
    }

    // Valid order statuses
    $valid_statuses = [
        'pending', 'confirmed', 'preparing', 'ready', 
        'out_for_delivery', 'delivered', 'cancelled'
    ];

    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid order status: ' . $new_status
        ]);
        exit();
    }

    // Check database connection
    if (!$conn || $conn->connect_error) {
        logError("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
        echo json_encode([
            'success' => false, 
            'message' => 'Database connection error'
        ]);
        exit();
    }

    // Check if order exists and get current status
    $stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ?");
    if (!$stmt) {
        logError("Prepare statement failed: " . $conn->error);
        echo json_encode([
            'success' => false, 
            'message' => 'Database query error'
        ]);
        exit();
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Order not found with ID: ' . $order_id
        ]);
        exit();
    }
    
    $order = $result->fetch_assoc();
    $current_status = $order['order_status'];
    
    // Check if trying to set the same status
    if ($current_status === $new_status) {
        echo json_encode([
            'success' => false, 
            'message' => "Order is already in '{$new_status}' status"
        ]);
        exit();
    }
    
    // Define valid status transitions
    $valid_transitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['out_for_delivery'],
        'out_for_delivery' => ['delivered'],
        'delivered' => [], // No transitions allowed from delivered
        'cancelled' => []  // No transitions allowed from cancelled
    ];
    
    // Check if transition is valid
    if (!isset($valid_transitions[$current_status])) {
        echo json_encode([
            'success' => false, 
            'message' => "Unknown current status: {$current_status}"
        ]);
        exit();
    }

    if (!in_array($new_status, $valid_transitions[$current_status])) {
        $valid_options = implode(', ', $valid_transitions[$current_status]);
        echo json_encode([
            'success' => false, 
            'message' => "Cannot change status from '{$current_status}' to '{$new_status}'. Valid options: {$valid_options}"
        ]);
        exit();
    }
    
    // Begin transaction
    $conn->autocommit(FALSE);
    
    try {
        // Update order status
        $update_stmt = $conn->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?");
        if (!$update_stmt) {
            throw new Exception("Failed to prepare update statement: " . $conn->error);
        }
        
        $update_stmt->bind_param("si", $new_status, $order_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update order status: " . $update_stmt->error);
        }

        // Check if any rows were affected
        if ($update_stmt->affected_rows === 0) {
            throw new Exception("No rows were updated. Order may not exist.");
        }
        
        // Try to log the status change (optional - won't fail if table doesn't exist)
        $admin_id = $_SESSION['user_id'] ?? 0;
        
        // Check if order_status_log table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'order_status_log'");
        if ($table_check && $table_check->num_rows > 0) {
            $log_stmt = $conn->prepare("
                INSERT INTO order_status_log (order_id, old_status, new_status, changed_by, reason, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            if ($log_stmt) {
                $log_stmt->bind_param("issis", $order_id, $current_status, $new_status, $admin_id, $reason);
                $log_stmt->execute(); // Don't throw error if this fails
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Success response
        echo json_encode([
            'success' => true, 
            'message' => 'Order status updated successfully',
            'old_status' => $current_status,
            'new_status' => $new_status,
            'order_id' => $order_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        logError("Transaction failed: " . $e->getMessage());
        
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update order status: ' . $e->getMessage()
        ]);
    }
    
    // Restore autocommit
    $conn->autocommit(TRUE);
    
} catch (Exception $e) {
    logError("General error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred'
    ]);
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>