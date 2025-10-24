<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once 'order_controller.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    header('Location: my_orders.php');
    exit();
}

// Get order details
$order = getOrderDetails($conn, $order_id, $user_id);

if (!$order) {
    $_SESSION['error_message'] = "Order not found or you don't have permission to view it.";
    header('Location: my_orders.php');
    exit();
}

$page_title = "Order Details - MealMate";
include '../includes/menu_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d0d0d">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === CSS Variables for Theme === */
        :root {
            --bg-primary: #0d0d0d;
            --bg-secondary: #1a1a1a;
            --bg-card: #222;
            --bg-header: rgba(0, 0, 0, 0.8);
            --text-primary: #fff;
            --text-secondary: #ddd;
            --text-muted: #ccc;
            --accent-primary: #FF4500;
            --accent-hover: #FF6B35;
            --border-color: #FF4500;
            --shadow-color: rgba(255, 69, 0, 0.3);
            --footer-bg: rgba(0, 0, 0, 0.9);
            --footer-border: #333;
        }

        [data-theme="light"] {
            --bg-primary: #fafafa;
            --bg-secondary: #f0f0f0;
            --bg-card: #fff;
            --bg-header: rgba(255, 255, 255, 0.98);
            --text-primary: #1a1a1a;
            --text-secondary: #333;
            --text-muted: #555;
            --accent-primary: #FF4500;
            --accent-hover: #FF3300;
            --border-color: #FF4500;
            --shadow-color: rgba(255, 69, 0, 0.25);
            --footer-bg: #f8f8f8;
            --footer-border: #ddd;
        }

        /* Order Details Page Styling */
        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-size: 16px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .main-container {
            display: block !important;
            min-height: 100vh;
            padding-top: 80px;
            width: 100%;
            overflow-x: hidden;
        }

        .content {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: var(--accent-primary);
            margin: 0;
            font-weight: 700;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .breadcrumb a {
            color: var(--accent-primary);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb span {
            color: var(--text-muted);
        }

        .order-details-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .order-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .order-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .card {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: 15px;
            border: 2px solid var(--border-color);
            padding: 2rem;
            box-shadow: 0 6px 20px var(--shadow-color);
        }

        .card-title {
            font-size: 1.5rem;
            color: var(--accent-primary);
            margin: 0 0 1.5rem;
            font-weight: 700;
            border-bottom: 2px solid rgba(255, 69, 0, 0.3);
            padding-bottom: 0.5rem;
        }

        /* Order Summary */
        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-number {
            font-size: 1.8rem;
            color: var(--accent-primary);
            font-weight: 700;
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Status colors */
        .status-pending {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.1));
            color: #FFD700;
            border: 1px solid rgba(255, 215, 0, 0.5);
        }

        .status-confirmed {
            background: linear-gradient(135deg, rgba(0, 191, 255, 0.2), rgba(0, 191, 255, 0.1));
            color: #00BFFF;
            border: 1px solid rgba(0, 191, 255, 0.5);
        }

        .status-preparing {
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.2), rgba(255, 140, 0, 0.1));
            color: #FF8C00;
            border: 1px solid rgba(255, 140, 0, 0.5);
        }

        .status-ready {
            background: linear-gradient(135deg, rgba(50, 205, 50, 0.2), rgba(50, 205, 50, 0.1));
            color: #32CD32;
            border: 1px solid rgba(50, 205, 50, 0.5);
        }

        .status-out_for_delivery {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.2), rgba(255, 107, 53, 0.1));
            color: #FF6B35;
            border: 1px solid rgba(255, 107, 53, 0.5);
        }

        .status-delivered {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(40, 167, 69, 0.1));
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }

        .status-cancelled {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(220, 53, 69, 0.1));
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.5);
        }

        /* Order Items */
        .order-items-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            background: rgba(255, 69, 0, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 69, 0, 0.2);
            transition: all 0.3s ease;
        }

        .order-item:hover {
            background: rgba(255, 69, 0, 0.1);
            transform: translateY(-2px);
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(255, 69, 0, 0.25);
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.3rem;
            color: var(--accent-primary);
            margin: 0 0 0.5rem;
            font-weight: 600;
        }

        .item-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .item-unit-price {
            color: #FFD700;
            font-weight: 600;
        }

        .item-quantity {
            text-align: center;
            color: var(--accent-primary);
            font-weight: 600;
            background: rgba(255, 69, 0, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 69, 0, 0.3);
            min-width: 60px;
        }

        .item-total {
            text-align: right;
            color: #FFD700;
            font-size: 1.2rem;
            font-weight: bold;
            min-width: 120px;
        }

        /* Status Timeline */
        .status-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }

        .timeline-item:before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            background: var(--accent-primary);
            border-radius: 50%;
            border: 3px solid var(--bg-primary);
        }

        .timeline-item:after {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 1.5rem;
            width: 2px;
            height: calc(100% + 1rem);
            background: linear-gradient(180deg, var(--accent-primary), rgba(255, 69, 0, 0.3));
        }

        .timeline-item:last-child:after {
            display: none;
        }

        .timeline-item.current:before {
            background: #32CD32;
            box-shadow: 0 0 10px rgba(50, 205, 50, 0.5);
        }

        .timeline-content h4 {
            color: var(--accent-primary);
            margin: 0 0 0.5rem;
            font-size: 1.1rem;
        }

        .timeline-content .time {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Delivery Info */
        .info-grid {
            display: grid;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 69, 0, 0.2);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--accent-primary);
            font-weight: 600;
            min-width: 120px;
        }

        .info-value {
            color: var(--text-secondary);
            text-align: right;
            flex: 1;
        }

        /* Order Totals */
        .totals-section {
            border-top: 2px dashed rgba(255, 69, 0, 0.3);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
        }

        .total-row.grand-total {
            font-size: 1.4rem;
            font-weight: bold;
            color: #FFD700;
            border-top: 2px solid rgba(255, 69, 0, 0.3);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover));
            color: #000;
            box-shadow: 0 4px 12px rgba(255, 69, 0, 0.35);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e63e00, #FF5A29);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(255, 69, 0, 0.5);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #444, #666);
            color: #fff;
            border: 2px solid #666;
        }

        [data-theme="light"] .btn-secondary {
            background: linear-gradient(135deg, #e0e0e0, #d0d0d0);
            color: var(--text-primary);
            border: 2px solid #ccc;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #555, #777);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
        }

        [data-theme="light"] .btn-secondary:hover {
            background: linear-gradient(135deg, #d0d0d0, #c0c0c0);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #fff;
            border: 2px solid #dc3545;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        /* Theme Toggle Button */
        .theme-toggle-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
        }

        .theme-toggle-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--accent-primary);
            border: 3px solid var(--bg-card);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            box-shadow: 0 8px 25px var(--shadow-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .theme-toggle-btn:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 12px 35px var(--shadow-color);
        }

        .theme-toggle-btn:active {
            transform: scale(0.95);
        }

        .theme-toggle-btn .theme-icon {
            position: absolute;
            transition: all 0.3s ease;
        }

        .theme-toggle-btn .sun-icon {
            opacity: 0;
            transform: rotate(-90deg) scale(0);
        }

        .theme-toggle-btn .moon-icon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        [data-theme="light"] .theme-toggle-btn .sun-icon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        [data-theme="light"] .theme-toggle-btn .moon-icon {
            opacity: 0;
            transform: rotate(90deg) scale(0);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .order-details-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .order-sidebar {
                order: -1;
            }

            .content {
                padding: 1.5rem;
            }

            .order-summary {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .action-buttons {
                justify-content: center;
            }
            
            .theme-toggle-container {
                bottom: 20px;
                right: 20px;
            }
            
            .theme-toggle-btn {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .card {
                padding: 1.5rem;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .item-details {
                width: 100%;
            }

            .item-quantity,
            .item-total {
                align-self: flex-end;
            }

            .info-item {
                flex-direction: column;
                gap: 0.5rem;
            }

            .info-value {
                text-align: left;
            }

            .status-timeline {
                padding-left: 1.5rem;
            }

            .timeline-item:before {
                left: -1.5rem;
            }

            .timeline-item:after {
                left: -1rem;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 1rem;
            }

            .card {
                padding: 1.2rem;
            }

            .breadcrumb {
                font-size: 0.9rem;
                gap: 0.3rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="content">
            <div class="breadcrumb">
                <a href="my_orders.php"><i class="fas fa-receipt"></i> My Orders</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <span>Order #<?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>

            <div class="page-header">
                <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
            </div>

            <div class="order-details-container">
                <div class="order-main">
                    <!-- Order Summary Card -->
                    <div class="card">
                        <div class="order-summary">
                            <div>
                                <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                                <p style="color: var(--text-muted); margin: 0.5rem 0;">
                                    Placed on <?php echo date('M d, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <div class="order-status status-<?php echo $order['order_status']; ?>">
                                <i class="fas fa-circle"></i>
                                <span><?php echo formatOrderStatus($order['order_status']); ?></span>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <?php if (in_array($order['order_status'], ['out_for_delivery', 'preparing', 'ready'])): ?>
                                <a href="track_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Track Order
                                </a>
                            <?php endif; ?>
                            <?php if ($order['order_status'] === 'delivered'): ?>
                                <a href="order_invoice.php?id=<?php echo $order['order_id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-file-invoice"></i>
                                    Download Invoice
                                </a>
                            <?php endif; ?>
                            <?php if (in_array($order['order_status'], ['pending', 'confirmed'])): ?>
                                <button onclick="cancelOrder(<?php echo $order['order_id']; ?>)" class="btn btn-danger">
                                    <i class="fas fa-times"></i>
                                    Cancel Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Items Card -->
                    <div class="card">
                        <h2 class="card-title">
                            <i class="fas fa-utensils"></i>
                            Order Items (<?php echo count($order['items']); ?>)
                        </h2>
                        <div class="order-items-list">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <?php
                                        $category = isset($item['category']) ? $item['category'] : '';
                                        $image_folder = strtolower($category);
                                        if ($image_folder === 'burgers and sandwiches') {
                                            $image_folder = 'burgers';
                                        } elseif ($image_folder === 'pasta') {
                                            $image_folder = 'pastas';
                                        }
                                        $server_path = $_SERVER['DOCUMENT_ROOT'] . '/MealMate-online-food-ordering-system/assets/images/menu/' . $image_folder . '/' . $item['image'];
                                        $web_path = '/MealMate-online-food-ordering-system/assets/images/menu/' . $image_folder . '/' . $item['image'];
                                        if (empty($item['image']) || !is_file($server_path)) {
                                            $web_path = 'https://placehold.co/80x80/0d0d0d/FFFFFF?text=No+Image';
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($web_path); ?>" 
                                             alt="<?php echo htmlspecialchars($item['food_name']); ?>"
                                             onerror="this.src='https://placehold.co/80x80/0d0d0d/FFFFFF?text=No+Image'; this.onerror=null;">
                                    </div>
                                    <div class="item-details">
                                        <h3 class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></h3>
                                        <?php if (!empty($item['description'])): ?>
                                            <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <?php endif; ?>
                                        <div class="item-unit-price">Rs.<?php echo number_format($item['unit_price'], 2); ?> each</div>
                                    </div>
                                    <div class="item-quantity">
                                        Qty: <?php echo $item['quantity']; ?>
                                    </div>
                                    <div class="item-total">
                                        Rs.<?php echo number_format($item['total_price'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="totals-section">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span>Rs.<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Delivery Fee:</span>
                                <span>Rs.<?php echo number_format($order['delivery_fee'], 2); ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Total Amount:</span>
                                <span>Rs.<?php echo number_format($order['grand_total'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-sidebar">
                    <!-- Delivery Information -->
                    <div class="card">
                        <h3 class="card-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Delivery Information
                        </h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Address:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">City:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['city']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Postal Code:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['postal_code']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                            </div>
                            <?php if (!empty($order['special_instructions'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Instructions:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['special_instructions']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Timeline -->
                    <div class="card">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i>
                            Order Status History
                        </h3>
                        <div class="status-timeline">
                            <?php foreach ($order['status_history'] as $index => $status): ?>
                                <div class="timeline-item <?php echo $index === count($order['status_history']) - 1 ? 'current' : ''; ?>">
                                    <div class="timeline-content">
                                        <h4><?php echo formatOrderStatus($status['new_status']); ?></h4>
                                        <div class="time">
                                            <?php echo date('M d, Y \a\t g:i A', strtotime($status['created_at'])); ?>
                                        </div>
                                        <?php if (!empty($status['change_reason'])): ?>
                                            <p style="color: var(--text-muted); margin-top: 0.5rem; font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($status['change_reason']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Delivery Tracking -->
                    <?php if (!empty($order['tracking'])): ?>
                        <div class="card">
                            <h3 class="card-title">
                                <i class="fas fa-truck"></i>
                                Delivery Tracking
                            </h3>
                            <div class="info-grid">
                                <?php if (!empty($order['tracking']['delivery_person_name'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Delivery Person:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($order['tracking']['delivery_person_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($order['tracking']['delivery_person_phone'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Contact:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($order['tracking']['delivery_person_phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($order['tracking']['current_location'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Current Location:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($order['tracking']['current_location']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($order['tracking']['estimated_arrival'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Est. Arrival:</span>
                                        <span class="info-value"><?php echo date('g:i A', strtotime($order['tracking']['estimated_arrival'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Order Timing -->
                    <div class="card">
                        <h3 class="card-title">
                            <i class="fas fa-clock"></i>
                            Timing Information
                        </h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Order Placed:</span>
                                <span class="info-value"><?php echo date('M d, Y g:i A', strtotime($order['created_at'])); ?></span>
                            </div>
                            <?php if (!empty($order['estimated_delivery_time'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Est. Delivery:</span>
                                    <span class="info-value"><?php echo date('M d, Y g:i A', strtotime($order['estimated_delivery_time'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($order['actual_delivery_time'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Delivered At:</span>
                                    <span class="info-value"><?php echo date('M d, Y g:i A', strtotime($order['actual_delivery_time'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <span class="info-label">Last Updated:</span>
                                <span class="info-value"><?php echo date('M d, Y g:i A', strtotime($order['updated_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle-container">
        <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle theme" title="Switch theme" type="button">
            <i class="fas fa-sun theme-icon sun-icon"></i>
            <i class="fas fa-moon theme-icon moon-icon"></i>
        </button>
    </div>

    <script>
        // Standalone theme toggle system
        (function() {
            function getTheme() {
                return localStorage.getItem('mealmate-theme') || 'dark';
            }
            
            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('mealmate-theme', theme);
                const meta = document.querySelector('meta[name="theme-color"]');
                if (meta) {
                    meta.setAttribute('content', theme === 'light' ? '#fafafa' : '#0d0d0d');
                }
            }
            
            const currentTheme = getTheme();
            applyTheme(currentTheme);
            
            const btn = document.getElementById('themeToggleBtn');
            if (btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const current = getTheme();
                    const newTheme = current === 'dark' ? 'light' : 'dark';
                    applyTheme(newTheme);
                    this.style.transform = 'scale(1.2) rotate(360deg)';
                    setTimeout(() => { this.style.transform = ''; }, 300);
                });
            }
        })();

        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        reason: 'Cancelled by customer'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order cancelled successfully');
                        location.reload();
                    } else {
                        alert('Error cancelling order: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the order');
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const activeStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery'];
            const orderStatus = '<?php echo $order['order_status']; ?>';
            
            if (activeStatuses.includes(orderStatus)) {
                setTimeout(() => {
                    location.reload();
                }, 30000);
            }
        });
    </script>
</body>
</html>

<?php include __DIR__ . '/../includes/simple_footer.php'; ?>