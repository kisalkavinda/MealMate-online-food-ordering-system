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

// Check if order is trackable
$trackable_statuses = ['confirmed', 'preparing', 'ready', 'out_for_delivery'];
if (!in_array($order['order_status'], $trackable_statuses)) {
    $_SESSION['error_message'] = "This order is not currently trackable.";
    header('Location: order_details.php?id=' . $order_id);
    exit();
}

$page_title = "Track Order - MealMate";
include '../includes/menu_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === CSS Variables for Theme === */
        :root {
            --bg-primary: #0d0d0d;
            --bg-secondary: #1a1a1a;
            --bg-card: #222;
            --text-primary: #fff;
            --text-secondary: #ddd;
            --text-muted: #ccc;
            --accent-primary: #FF4500;
            --accent-hover: #FF6B35;
            --border-color: #FF4500;
            --shadow-color: rgba(255, 69, 0, 0.3);
        }

        [data-theme="light"] {
            --bg-primary: #fafafa;
            --bg-secondary: #f0f0f0;
            --bg-card: #fff;
            --text-primary: #1a1a1a;
            --text-secondary: #333;
            --text-muted: #555;
            --accent-primary: #FF4500;
            --accent-hover: #FF3300;
            --border-color: #FF4500;
            --shadow-color: rgba(255, 69, 0, 0.25);
        }

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
            max-width: 1000px;
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

        .page-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin: 1rem 0;
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

        /* Order Info Card */
        .order-info-card {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: 15px;
            border: 2px solid var(--border-color);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 6px 20px var(--shadow-color);
        }

        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            color: var(--accent-hover);
            border: 1px solid rgba(255, 107, 53, 0.5);
        }

        /* Progress Tracker */
        .progress-tracker {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: 15px;
            border: 2px solid var(--border-color);
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 6px 20px var(--shadow-color);
        }

        .tracker-title {
            text-align: center;
            font-size: 1.8rem;
            color: var(--accent-primary);
            margin-bottom: 2rem;
            font-weight: 700;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin: 2rem 0;
        }

        .progress-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 69, 0, 0.2);
            transform: translateY(-50%);
            z-index: 1;
        }

        .progress-line-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-hover));
            border-radius: 2px;
            transition: width 0.8s ease;
        }

        .progress-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: var(--bg-primary);
            padding: 1rem;
            border-radius: 50%;
            min-width: 100px;
        }

        .step-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.8rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .step-circle.completed {
            background: linear-gradient(135deg, #28a745, #32CD32);
            color: #000;
            box-shadow: 0 0 20px rgba(40, 167, 69, 0.5);
        }

        .step-circle.active {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover));
            color: #000;
            box-shadow: 0 0 20px rgba(255, 69, 0, 0.6);
            animation: pulse 2s infinite;
        }

        .step-circle.pending {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: var(--text-muted);
        }

        [data-theme="light"] .step-circle.pending {
            background: rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(0, 0, 0, 0.3);
            color: var(--text-muted);
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 20px rgba(255, 69, 0, 0.6); }
            50% { transform: scale(1.05); box-shadow: 0 0 30px rgba(255, 69, 0, 0.8); }
            100% { transform: scale(1); box-shadow: 0 0 20px rgba(255, 69, 0, 0.6); }
        }

        .step-label {
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .step-label.completed {
            color: #28a745;
        }

        .step-label.active {
            color: var(--accent-primary);
        }

        .step-label.pending {
            color: var(--text-muted);
        }

        .step-time {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
        }

        /* Current Status Card */
        .current-status-card {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: 15px;
            border: 2px solid var(--border-color);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 6px 20px var(--shadow-color);
        }

        .status-info {
            text-align: center;
            padding: 2rem;
        }

        .status-icon {
            font-size: 4rem;
            color: var(--accent-primary);
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% { transform: translate3d(0,0,0); }
            40%, 43% { transform: translate3d(0,-15px,0); }
            70% { transform: translate3d(0,-7px,0); }
            90% { transform: translate3d(0,-2px,0); }
        }

        .status-message {
            font-size: 1.4rem;
            color: var(--accent-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .status-description {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .estimated-time {
            background: rgba(255, 69, 0, 0.1);
            border: 1px solid rgba(255, 69, 0, 0.3);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
        }

        .estimated-time h4 {
            color: var(--accent-primary);
            margin: 0 0 0.5rem;
            font-size: 1.2rem;
        }

        .estimated-time p {
            color: #FFD700;
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Delivery Info */
        .delivery-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: 15px;
            border: 2px solid var(--border-color);
            padding: 1.5rem;
            box-shadow: 0 6px 20px var(--shadow-color);
        }

        .info-card h3 {
            color: var(--accent-primary);
            margin: 0 0 1rem;
            font-size: 1.3rem;
            border-bottom: 2px solid rgba(255, 69, 0, 0.3);
            padding-bottom: 0.5rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.8rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 69, 0, 0.1);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .info-label {
            color: var(--accent-primary);
            font-weight: 600;
            min-width: 100px;
        }

        .info-value {
            color: var(--text-secondary);
            text-align: right;
            flex: 1;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
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

        .btn-secondary:hover {
            background: linear-gradient(135deg, #555, #777);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
        }

        /* Auto-refresh indicator */
        .refresh-indicator {
            position: fixed;
            top: 90px;
            right: 20px;
            background: rgba(255, 69, 0, 0.9);
            color: #000;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }

        .refresh-indicator.visible {
            opacity: 1;
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

        /* Autofill and focus fix */
        input:-webkit-autofill,
        textarea:-webkit-autofill,
        select:-webkit-autofill {
            -webkit-box-shadow: 0 0 0px 1000px var(--bg-secondary) inset !important;
            box-shadow: 0 0 0px 1000px var(--bg-secondary) inset !important;
            -webkit-text-fill-color: var(--text-primary) !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .delivery-info {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .content {
                padding: 1.5rem;
            }

            .progress-steps {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .progress-line {
                display: none;
            }

            .progress-step {
                min-width: auto;
                flex: 1;
                min-width: 120px;
            }

            .order-summary {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 1rem;
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

            .progress-tracker {
                padding: 1.5rem;
            }

            .tracker-title {
                font-size: 1.5rem;
            }

            .step-circle {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }

            .step-label {
                font-size: 0.8rem;
            }

            .status-icon {
                font-size: 3rem;
            }

            .status-message {
                font-size: 1.2rem;
            }

            .status-description {
                font-size: 1rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 1rem;
            }

            .order-info-card,
            .progress-tracker,
            .current-status-card,
            .info-card {
                padding: 1.2rem;
            }

            .breadcrumb {
                font-size: 0.85rem;
                gap: 0.3rem;
            }

            .progress-steps {
                flex-direction: column;
                align-items: stretch;
            }

            .progress-step {
                flex-direction: row;
                text-align: left;
                padding: 1rem;
                border-radius: 10px;
                background: rgba(255, 69, 0, 0.05);
                border: 1px solid rgba(255, 69, 0, 0.2);
            }

            .step-circle {
                margin-bottom: 0;
                margin-right: 1rem;
            }

            .refresh-indicator {
                position: static;
                margin-bottom: 1rem;
                text-align: center;
                border-radius: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="refresh-indicator" id="refreshIndicator">
        <i class="fas fa-sync-alt fa-spin"></i> Updating order status...
    </div>

    <div class="main-container">
        <div class="content">
            <div class="breadcrumb">
                <a href="my_orders.php"><i class="fas fa-receipt"></i> My Orders</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <a href="order_details.php?id=<?php echo $order['order_id']; ?>">Order #<?php echo htmlspecialchars($order['order_number']); ?></a>
                <span><i class="fas fa-chevron-right"></i></span>
                <span>Track Order</span>
            </div>

            <div class="page-header">
                <h1>Track Your Order</h1>
                <p>Real-time updates on your delicious order</p>
            </div>

            <!-- Order Info -->
            <div class="order-info-card">
                <div class="order-summary">
                    <div>
                        <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                        <p style="color: var(--text-secondary); margin: 0.5rem 0;">
                            Placed on <?php echo date('M d, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                    <div class="order-status status-<?php echo $order['order_status']; ?>">
                        <i class="fas fa-circle"></i>
                        <span><?php echo formatOrderStatus($order['order_status']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Progress Tracker -->
            <div class="progress-tracker">
                <h2 class="tracker-title">Order Progress</h2>
                
                <div class="progress-steps">
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: <?php
                            $progress_percentage = 0;
                            switch($order['order_status']) {
                                case 'pending': $progress_percentage = 0; break;
                                case 'confirmed': $progress_percentage = 25; break;
                                case 'preparing': $progress_percentage = 50; break;
                                case 'ready': $progress_percentage = 75; break;
                                case 'out_for_delivery': $progress_percentage = 90; break;
                                case 'delivered': $progress_percentage = 100; break;
                            }
                            echo $progress_percentage;
                        ?>%;"></div>
                    </div>

                    <?php
                    $steps = [
                        'confirmed' => ['icon' => 'fa-check', 'label' => 'Order Confirmed'],
                        'preparing' => ['icon' => 'fa-utensils', 'label' => 'Preparing Food'],
                        'ready' => ['icon' => 'fa-bell', 'label' => 'Ready for Pickup'],
                        'out_for_delivery' => ['icon' => 'fa-truck', 'label' => 'Out for Delivery'],
                        'delivered' => ['icon' => 'fa-home', 'label' => 'Delivered']
                    ];

                    foreach($steps as $step_status => $step_data):
                        $step_class = 'pending';
                        $step_time = '';
                        
                        // Find matching status in history
                        foreach($order['status_history'] as $history) {
                            if($history['new_status'] === $step_status) {
                                $step_time = date('g:i A', strtotime($history['created_at']));
                                break;
                            }
                        }
                        
                        // Determine step state
                        if($step_time) {
                            $step_class = ($step_status === $order['order_status']) ? 'active' : 'completed';
                        } else if($step_status === $order['order_status']) {
                            $step_class = 'active';
                        }
                    ?>
                        <div class="progress-step">
                            <div class="step-circle <?php echo $step_class; ?>">
                                <i class="fas <?php echo $step_data['icon']; ?>"></i>
                            </div>
                            <div class="step-label <?php echo $step_class; ?>">
                                <?php echo $step_data['label']; ?>
                                <?php if($step_time): ?>
                                    <div class="step-time"><?php echo $step_time; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Current Status -->
            <div class="current-status-card">
                <div class="status-info">
                    <?php
                    $status_config = [
                        'confirmed' => [
                            'icon' => 'fa-check-circle',
                            'message' => 'Order Confirmed!',
                            'description' => 'Your order has been confirmed and is being processed.'
                        ],
                        'preparing' => [
                            'icon' => 'fa-utensils',
                            'message' => 'Preparing Your Food!',
                            'description' => 'Our chefs are carefully preparing your delicious meal.'
                        ],
                        'ready' => [
                            'icon' => 'fa-bell',
                            'message' => 'Order Ready!',
                            'description' => 'Your order is ready and waiting for our delivery person to pick it up.'
                        ],
                        'out_for_delivery' => [
                            'icon' => 'fa-truck',
                            'message' => 'On the Way!',
                            'description' => 'Your order is out for delivery and will arrive soon!'
                        ]
                    ];

                    $current_config = $status_config[$order['order_status']] ?? [
                        'icon' => 'fa-info-circle',
                        'message' => 'Order Status Updated',
                        'description' => 'Your order status has been updated.'
                    ];
                    ?>
                    
                    <div class="status-icon">
                        <i class="fas <?php echo $current_config['icon']; ?>"></i>
                    </div>
                    <div class="status-message"><?php echo $current_config['message']; ?></div>
                    <div class="status-description"><?php echo $current_config['description']; ?></div>
                    
                    <?php if($order['estimated_delivery_time']): ?>
                        <div class="estimated-time">
                            <h4><i class="fas fa-clock"></i> Estimated Delivery Time</h4>
                            <p><?php echo date('M d, Y \a\t g:i A', strtotime($order['estimated_delivery_time'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="delivery-info">
                <div class="info-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Delivery Address</h3>
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
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                    <div class="info-item">
                        <span class="info-label">Items:</span>
                        <span class="info-value"><?php echo count($order['items']); ?> Items</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Subtotal:</span>
                        <span class="info-value">Rs.<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Delivery:</span>
                        <span class="info-value">Rs.<?php echo number_format($order['delivery_fee'], 2); ?></span>
                    </div>
                    <div class="info-item" style="border-top: 2px solid rgba(255, 69, 0, 0.3); padding-top: 0.8rem; margin-top: 0.8rem;">
                        <span class="info-label" style="color: #FFD700; font-size: 1.1rem;">Total:</span>
                        <span class="info-value" style="color: #FFD700; font-size: 1.1rem; font-weight: bold;">Rs.<?php echo number_format($order['grand_total'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Delivery Tracking Info (if available) -->
            <?php if($order['tracking'] && $order['order_status'] === 'out_for_delivery'): ?>
                <div class="info-card" style="margin-bottom: 2rem;">
                    <h3><i class="fas fa-truck"></i> Delivery Tracking</h3>
                    <?php if($order['tracking']['delivery_person_name']): ?>
                        <div class="info-item">
                            <span class="info-label">Delivery Person:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['tracking']['delivery_person_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if($order['tracking']['delivery_person_phone']): ?>
                        <div class="info-item">
                            <span class="info-label">Contact:</span>
                            <span class="info-value">
                                <a href="tel:<?php echo htmlspecialchars($order['tracking']['delivery_person_phone']); ?>" 
                                   style="color: var(--accent-primary); text-decoration: none;">
                                    <?php echo htmlspecialchars($order['tracking']['delivery_person_phone']); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if($order['tracking']['current_location']): ?>
                        <div class="info-item">
                            <span class="info-label">Current Location:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['tracking']['current_location']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if($order['tracking']['estimated_arrival']): ?>
                        <div class="info-item">
                            <span class="info-label">Est. Arrival:</span>
                            <span class="info-value" style="color: #FFD700; font-weight: bold;">
                                <?php echo date('g:i A', strtotime($order['tracking']['estimated_arrival'])); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-eye"></i>
                    View Full Details
                </a>
                <a href="my_orders.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i>
                    Back to Orders
                </a>
                <?php if($order['order_status'] === 'out_for_delivery'): ?>
                    <button onclick="refreshTracking()" class="btn btn-secondary" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i>
                        Refresh Status
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle-container">
        <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
            <i class="fas fa-sun theme-icon sun-icon"></i>
            <i class="fas fa-moon theme-icon moon-icon"></i>
        </button>
    </div>

    <!-- CORRECTED JavaScript Path -->
    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
    
    <script>
        let refreshTimer;
        let isRefreshing = false;

        function showRefreshIndicator() {
            const indicator = document.getElementById('refreshIndicator');
            indicator.classList.add('visible');
            setTimeout(() => {
                indicator.classList.remove('visible');
            }, 2000);
        }

        function refreshTracking() {
            if (isRefreshing) return;
            
            isRefreshing = true;
            const refreshBtn = document.getElementById('refreshBtn');
            const originalText = refreshBtn.innerHTML;
            
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;
            
            showRefreshIndicator();
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Auto-refresh for active orders
        document.addEventListener('DOMContentLoaded', function() {
            const orderStatus = '<?php echo $order['order_status']; ?>';
            const activeStatuses = ['confirmed', 'preparing', 'ready', 'out_for_delivery'];
            
            if (activeStatuses.includes(orderStatus)) {
                // Show refresh indicator and refresh every 30 seconds
                refreshTimer = setInterval(() => {
                    showRefreshIndicator();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }, 30000);
            }

            // Add visual feedback for progress line animation
            setTimeout(() => {
                const progressFill = document.querySelector('.progress-line-fill');
                if (progressFill) {
                    progressFill.style.transition = 'width 2s ease-in-out';
                }
            }, 500);
        });

        // Clean up timer when page is unloaded
        window.addEventListener('beforeunload', function() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
            }
        });

        // Handle visibility change (pause auto-refresh when tab is not active)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                if (refreshTimer) {
                    clearInterval(refreshTimer);
                }
            } else {
                // Resume auto-refresh when tab becomes active again
                const orderStatus = '<?php echo $order['order_status']; ?>';
                const activeStatuses = ['confirmed', 'preparing', 'ready', 'out_for_delivery'];
                
                if (activeStatuses.includes(orderStatus)) {
                    refreshTimer = setInterval(() => {
                        showRefreshIndicator();
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }, 30000);
                }
            }
        });
    </script>
</body>
</html>

<?php include __DIR__ . '/../includes/simple_footer.php'; ?>