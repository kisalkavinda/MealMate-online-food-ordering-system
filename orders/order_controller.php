<?php
// Order Controller for MealMate

// --- Admin-specific functions (added to fix the error) ---

/**
 * Get all orders with filtering and pagination for the admin dashboard.
 */
function getAllOrders($conn, $status_filter, $date_from, $date_to, $limit, $offset) {
    $query = "
        SELECT 
            o.*, 
            u.full_name, 
            u.email,
            COUNT(oi.order_item_id) AS item_count,
            TIMEDIFF(NOW(), o.created_at) AS time_since_order,
            TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) AS minutes_since_order
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
    ";
    
    $where_clauses = [];
    $params = [];
    $param_types = '';

    if ($status_filter !== 'all') {
        $where_clauses[] = "o.order_status = ?";
        $params[] = $status_filter;
        $param_types .= 's';
    }

    if ($date_from) {
        $where_clauses[] = "o.created_at >= ?";
        $params[] = $date_from . ' 00:00:00';
        $param_types .= 's';
    }

    if ($date_to) {
        $where_clauses[] = "o.created_at <= ?";
        $params[] = $date_to . ' 23:59:59';
        $param_types .= 's';
    }

    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $query .= " GROUP BY o.order_id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
}

/**
 * Get overall order statistics for the admin dashboard.
 */
function getOrderStatistics($conn, $date_from = null, $date_to = null) {
    $query = "
        SELECT 
            COUNT(*) AS total_orders,
            SUM(grand_total) AS total_revenue,
            AVG(grand_total) AS average_order_value,
            SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
            SUM(CASE WHEN order_status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_orders,
            SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders
        FROM orders
    ";

    $where_clauses = [];
    $params = [];
    $param_types = '';

    if ($date_from) {
        $where_clauses[] = "created_at >= ?";
        $params[] = $date_from . ' 00:00:00';
        $param_types .= 's';
    }

    if ($date_to) {
        $where_clauses[] = "created_at <= ?";
        $params[] = $date_to . ' 23:59:59';
        $param_types .= 's';
    }

    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return ['overall' => $row];
}

/**
 * Get urgent orders for the admin dashboard.
 */
function getOrdersRequiringAttention($conn) {
    $query = "
        SELECT 
            o.*, 
            u.full_name, 
            TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) AS minutes_since_order
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_status IN ('pending', 'confirmed', 'preparing', 'ready')
        ORDER BY o.created_at ASC LIMIT 10
    ";
    $result = $conn->query($query);
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
}

// --- User-specific functions (your original code) ---

/**
 * Generate a unique order number.
 */
function generateOrderNumber($conn) {
    $year = date('Y');
    $prefix = 'MM' . $year;
    
    $stmt = $conn->prepare("SELECT order_number FROM orders WHERE order_number LIKE ? ORDER BY order_id DESC LIMIT 1");
    $search_pattern = $prefix . '%';
    $stmt->bind_param("s", $search_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $last_number = intval(substr($row['order_number'], strlen($prefix)));
        $new_number = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $new_number = '001';
    }
    
    return $prefix . $new_number;
}

/**
 * Create a new order from the user's cart.
 */
function createOrderFromCart($conn, $user_id, $delivery_details) {
    $conn->begin_transaction();
    
    try {
        // Get cart items
        $cart_stmt = $conn->prepare("
            SELECT c.*, f.name as food_name, f.price, f.image 
            FROM cart c 
            JOIN foods f ON c.food_id = f.id 
            WHERE c.user_id = ?
        ");
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_items = $cart_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($cart_items)) {
            throw new Exception("Cart is empty");
        }
        
        // Calculate totals
        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        
        $delivery_fee = 250.00;
        $grand_total = $total_amount + $delivery_fee;
        $order_number = generateOrderNumber($conn);
        $estimated_delivery = date('Y-m-d H:i:s', strtotime('+45 minutes'));
        
        // Insert order
        $order_stmt = $conn->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, delivery_fee, grand_total, 
                                 delivery_address, city, postal_code, phone, special_instructions, 
                                 estimated_delivery_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $order_stmt->bind_param("isdddssssss", 
            $user_id, $order_number, $total_amount, $delivery_fee, $grand_total,
            $delivery_details['address'], $delivery_details['city'], 
            $delivery_details['postal_code'], $delivery_details['phone'],
            $delivery_details['special_instructions'], $estimated_delivery
        );
        
        if (!$order_stmt->execute()) {
            throw new Exception("Failed to create order");
        }
        
        $order_id = $conn->insert_id;
        
        // Insert order items
        $item_stmt = $conn->prepare("
            INSERT INTO order_items (order_id, food_id, quantity, unit_price, total_price)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($cart_items as $item) {
            $total_price = $item['price'] * $item['quantity'];
            $item_stmt->bind_param("iiidd", 
                $order_id, $item['food_id'], $item['quantity'], 
                $item['price'], $total_price
            );
            
            if (!$item_stmt->execute()) {
                throw new Exception("Failed to create order items");
            }
        }
        
        // Insert status history
        $history_stmt = $conn->prepare("
            INSERT INTO order_status_history (order_id, new_status, change_reason)
            VALUES (?, 'pending', 'Order placed by customer')
        ");
        $history_stmt->bind_param("i", $order_id);
        $history_stmt->execute();
        
        // Clear cart
        $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear_cart_stmt->bind_param("i", $user_id);
        $clear_cart_stmt->execute();
        
        $conn->commit();
        return $order_id;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Get orders for a specific user.
 */
function getUserOrders($conn, $user_id, $status = null, $limit = 10, $offset = 0) {
    $where_clause = "WHERE o.user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if ($status && $status !== 'all') {
        $where_clause .= " AND o.order_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $query = "
        SELECT o.*, COUNT(oi.order_item_id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        $where_clause
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get the total count of orders for a user.
 */
function getUserOrdersCount($conn, $user_id, $status = null) {
    $where_clause = "WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if ($status && $status !== 'all') {
        $where_clause .= " AND order_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders $where_clause");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc()['total'];
}

/**
 * Get details for a single order.
 */
function getOrderDetails($conn, $order_id, $user_id = null) {
    $where_clause = "WHERE o.order_id = ?";
    $params = [$order_id];
    $types = "i";
    
    if ($user_id) {
        $where_clause .= " AND o.user_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare("
        SELECT o.*, u.full_name, u.email
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        $where_clause
    ");
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        return null;
    }
    
    // Get order items - FIXED: Added category field
    $items_stmt = $conn->prepare("
        SELECT oi.*, f.name as food_name, f.description, f.image, f.category
        FROM order_items oi
        JOIN foods f ON oi.food_id = f.id
        WHERE oi.order_id = ?
        ORDER BY oi.order_item_id
    ");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $order['items'] = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get status history
    $history_stmt = $conn->prepare("
        SELECT * FROM order_status_history
        WHERE order_id = ?
        ORDER BY created_at ASC
    ");
    $history_stmt->bind_param("i", $order_id);
    $history_stmt->execute();
    $order['status_history'] = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get delivery tracking
    $tracking_stmt = $conn->prepare("
        SELECT * FROM delivery_tracking
        WHERE order_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $tracking_stmt->bind_param("i", $order_id);
    $tracking_stmt->execute();
    $tracking_result = $tracking_stmt->get_result();
    $order['tracking'] = $tracking_result->fetch_assoc();
    
    return $order;
}

/**
 * Format order status string.
 */
function formatOrderStatus($status) {
    $statuses = [
        'pending' => 'Order Placed',
        'confirmed' => 'Order Confirmed',
        'preparing' => 'Preparing Food',
        'ready' => 'Ready for Pickup',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
    return $statuses[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

/**
 * Get time elapsed since order was created.
 */
function getTimeSinceOrder($created_at) {
    $now = new DateTime();
    $order_time = new DateTime($created_at);
    $interval = $now->diff($order_time);
    
    if ($interval->days > 0) {
        return $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' ago';
    } elseif ($interval->h > 0) {
        return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
    } elseif ($interval->i > 0) {
        return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}

/**
 * Updates the status of a specific order.
 */
function updateOrderStatus($conn, $order_id, $new_status) {
    $stmt = $conn->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    return $stmt->execute();
}

/**
 * Logs an order status change in the history table.
 */
function logOrderStatusChange($conn, $order_id, $new_status, $reason, $user_id) {
    $stmt = $conn->prepare("INSERT INTO order_status_history (order_id, new_status, change_reason, changed_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $order_id, $new_status, $reason, $user_id);
    return $stmt->execute();
}

/**
 * Cancel an order (can only cancel pending or confirmed orders).
 */
function cancelOrder($conn, $order_id, $user_id, $reason = 'Cancelled by customer') {
    $conn->begin_transaction();
    
    try {
        // First verify the order exists and belongs to the user
        $stmt = $conn->prepare("
            SELECT order_status 
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
        
        // Check if order can be cancelled
        if (!in_array($order['order_status'], ['pending', 'confirmed'])) {
            throw new Exception('Order cannot be cancelled at this stage');
        }
        
        // Update order status to cancelled
        $update_stmt = $conn->prepare("
            UPDATE orders 
            SET order_status = 'cancelled', updated_at = NOW() 
            WHERE order_id = ?
        ");
        $update_stmt->bind_param("i", $order_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update order status');
        }
        
        // Log the status change
        $history_stmt = $conn->prepare("
            INSERT INTO order_status_history (order_id, old_status, new_status, change_reason)
            VALUES (?, ?, 'cancelled', ?)
        ");
        $history_stmt->bind_param("iss", $order_id, $order['order_status'], $reason);
        
        if (!$history_stmt->execute()) {
            throw new Exception('Failed to log status change');
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>