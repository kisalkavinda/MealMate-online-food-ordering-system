<?php
session_start();
require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../orders/order_controller.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../users/login.php");
    exit();
}

$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    header('Location: admin_orders.php');
    exit();
}

// Get order details (null for user_id to get any order as admin)
$order = getOrderDetails($conn, $order_id, null);

if (!$order) {
    $_SESSION['error_message'] = "Order not found.";
    header('Location: admin_orders.php');
    exit();
}

$page_title = "Order Details - Admin - MealMate";
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

        /* === Global Styles === */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
            scroll-behavior: smooth;
            background-color: var(--bg-primary);
            overflow-x: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* === Navbar Styles === */
        .navbar {
            background-color: var(--bg-header);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--border-color);
            padding: 20px 50px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            z-index: 20;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            color: var(--accent-primary);
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            text-shadow: 3px 3px 6px #000;
        }

        [data-theme="light"] .nav-logo {
            text-shadow: 2px 2px 4px rgba(255, 69, 0, 0.2);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-menu a {
            color: var(--text-primary);
            text-decoration: none;
            font-size: 18px;
            font-weight: 400;
            letter-spacing: 0.5px;
            padding: 0;
            position: relative;
            transition: color 0.3s ease;
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent-primary);
            transition: width 0.3s ease;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--accent-primary);
        }

        .nav-menu a:hover::after,
        .nav-menu a.active::after {
            width: 100%;
        }

        .main-container {
            display: block !important;
            min-height: 100vh;
            padding-top: 80px;
            width: 100%;
            overflow-x: hidden;
            flex: 1;
        }

        .content {
            width: 100%;
            max-width: 1400px;
            margin: 120px auto 2rem auto;
            padding: 0 50px;
            flex: 1;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0.5rem 0;
            position: relative;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: var(--accent-primary);
            margin: 0;
            font-weight: 700;
        }

        .page-header::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            width: 100vw;
            height: 2px;
            background-color: var(--accent-primary);
            margin-left: calc(-50vw + 50%);
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
            color: rgba(var(--text-primary), 0.6);
        }

        .order-details-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
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
            background: var(--bg-card);
            border-radius: 12px;
            border: 2px solid var(--border-color);
            padding: 2rem;
            box-shadow: 0 4px 20px var(--shadow-color);
        }

        .card-title {
            font-size: 1.5rem;
            color: var(--accent-primary);
            margin: 0 0 1.5rem;
            font-weight: 700;
            border-bottom: 2px solid rgba(var(--accent-primary), 0.3);
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

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .order-number {
            font-size: 1.8rem;
            color: var(--accent-primary);
            font-weight: 700;
        }

        .customer-info {
            color: #FFD700;
            font-weight: 600;
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
            background: rgba(var(--accent-primary), 0.05);
            border-radius: 12px;
            border: 1px solid rgba(var(--accent-primary), 0.2);
            transition: all 0.3s ease;
        }

        .order-item:hover {
            background: rgba(var(--accent-primary), 0.1);
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
            box-shadow: 0 4px 12px rgba(var(--accent-primary), 0.25);
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
            color: rgba(var(--text-primary), 0.8);
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
            background: rgba(var(--accent-primary), 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid rgba(var(--accent-primary), 0.3);
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
            background: linear-gradient(180deg, var(--accent-primary), rgba(var(--accent-primary), 0.3));
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
            color: rgba(var(--text-primary), 0.6);
            font-size: 0.9rem;
        }

        /* Info sections */
        .info-grid {
            display: grid;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(var(--accent-primary), 0.2);
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
            color: rgba(var(--text-primary), 0.9);
            text-align: right;
            flex: 1;
        }

        /* Order Totals */
        .totals-section {
            border-top: 2px dashed rgba(var(--accent-primary), 0.3);
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
            border-top: 2px solid rgba(var(--accent-primary), 0.3);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
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
            background: var(--accent-primary);
            color: #000;
            box-shadow: 0 4px 12px rgba(var(--accent-primary), 0.35);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(var(--accent-primary), 0.5);
        }

        .btn-secondary {
            background: #555;
            color: #fff;
            border: 2px solid #666;
        }

        .btn-secondary:hover {
            background: #777;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
        }

        .btn-success {
            background: #28a745;
            color: #fff;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.35);
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(40, 167, 69, 0.5);
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
            border: 2px solid #dc3545;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        /* Status Update Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--bg-card);
            margin: 10% auto;
            padding: 2rem;
            border: 2px solid var(--accent-primary);
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            color: var(--text-primary);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            color: var(--accent-primary);
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .close {
            color: var(--accent-primary);
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: var(--accent-hover);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            color: var(--accent-primary);
            font-weight: 600;
            font-size: 1rem;
        }

        .filter-input,
        .filter-select {
            background: var(--bg-secondary);
            border: 2px solid var(--accent-primary);
            color: var(--text-primary);
            padding: 0.8rem;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: var(--accent-hover);
            box-shadow: 0 0 0 3px rgba(var(--accent-primary), 0.2);
        }

        /* === Footer Styles === */
        .simple-footer {
            background-color: var(--footer-bg);
            color: var(--text-primary);
            padding: 20px 0;
            text-align: center;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            position: relative;
            width: 100%;
            margin-top: auto;
            border-top: 2px solid var(--border-color);
        }

        .simple-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--accent-primary);
        }

        /* === Theme Toggle Button === */
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
            .order-details-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .order-sidebar {
                order: -1;
            }

            .content {
                padding: 1.5rem 20px;
            }

            .order-summary {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .action-buttons {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .content {
                margin: 100px auto 1.5rem auto;
                padding: 0 15px;
            }

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

        @media (max-width: 480px) {
            .navbar {
                padding: 10px 1rem;
            }
            
            .nav-logo {
                font-size: 24px;
            }
            
            .nav-menu {
                gap: 1rem;
            }
            
            .nav-menu a {
                font-size: 14px;
            }

            .content {
                padding: 1rem 10px;
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

            .page-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">MealMate</h1>
            <ul class="nav-menu">
                <li><a href="/MealMate-online-food-ordering-system/index.php">Home</a></li>
                <li><a href="../../../admin/admin_dashboard.php">Dashboard</a></li>
                <li><a href="../../../food_management/manage_food.php">Manage Food</a></li>
                <li><a href="/MealMate-online-food-ordering-system/users/admin/orders/admin_orders.php" class="active">Manage Orders</a></li>
                <li><a href="../../../admin/manage_users.php">Manage Users</a></li>
                <li><a href="../../../users/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-container">
        <div class="content">
            <div class="breadcrumb">
                <a href="admin_orders.php"><i class="fas fa-tachometer-alt"></i> Orders Dashboard</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <span>Order #<?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>

            <div class="page-header">
                <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?> - Admin View</h1>
            </div>

            <div class="order-details-container">
                <div class="order-main">
                    <!-- Order Summary Card -->
                    <div class="card">
                        <div class="order-summary">
                            <div class="order-info">
                                <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                                <div class="customer-info">Customer: <?php echo htmlspecialchars($order['full_name']); ?></div>
                                <p style="color: rgba(var(--text-primary), 0.7); margin: 0.5rem 0;">
                                    Placed on <?php echo date('M d, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <div class="order-status status-<?php echo $order['order_status']; ?>">
                                <i class="fas fa-circle"></i>
                                <span><?php echo formatOrderStatus($order['order_status']); ?></span>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <?php if (!in_array($order['order_status'], ['delivered', 'cancelled'])): ?>
                                <button onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, '<?php echo $order['order_status']; ?>')" class="btn btn-success">
                                    <i class="fas fa-edit"></i>
                                    Update Status
                                </button>
                            <?php endif; ?>
                            <a href="admin_orders.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back to Orders
                            </a>
                            <?php if ($order['order_status'] === 'delivered'): ?>
                                <a href="generate_invoice.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-file-invoice"></i>
                                    Generate Invoice
                                </a>
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
                                        // Get category from the item data
                                        $category = isset($item['category']) ? $item['category'] : '';
                                        
                                        // Map category names to folder names (same logic as manage_food.php)
                                        $image_folder = strtolower($category);
                                        if ($image_folder === 'burgers and sandwiches') {
                                            $image_folder = 'burgers';
                                        } elseif ($image_folder === 'pasta') {
                                            $image_folder = 'pastas';
                                        }
                                        
                                        // Construct paths
                                        $server_path = $_SERVER['DOCUMENT_ROOT'] . '/MealMate-online-food-ordering-system/assets/images/menu/' . $image_folder . '/' . $item['image'];
                                        $web_path = '/MealMate-online-food-ordering-system/assets/images/menu/' . $image_folder . '/' . $item['image'];
                                        
                                        // Check if image exists and is not empty
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
                    <!-- Customer Information -->
                    <div class="card">
                        <h3 class="card-title">
                            <i class="fas fa-user"></i>
                            Customer Information
                        </h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                            </div>
                        </div>
                    </div>

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
                                            <p style="color: rgba(var(--text-primary), 0.7); margin-top: 0.5rem; font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($status['change_reason']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Delivery Tracking (if available) -->
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

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Update Order Status</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="statusUpdateForm">
                <input type="hidden" id="orderId" name="order_id" value="<?php echo $order['order_id']; ?>">
                <div class="filter-group" style="margin-bottom: 1rem;">
                    <label for="newStatus">New Status</label>
                    <select id="newStatus" name="new_status" class="filter-select" required>
                        <option value="">Select Status</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="preparing">Preparing</option>
                        <option value="ready">Ready</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="filter-group" style="margin-bottom: 1.5rem;">
                    <label for="changeReason">Reason (Optional)</label>
                    <input type="text" id="changeReason" name="reason" class="filter-input" 
                           placeholder="Optional reason for status change">
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle-container">
        <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
            <i class="fas fa-sun theme-icon sun-icon"></i>
            <i class="fas fa-moon theme-icon moon-icon"></i>
        </button>
    </div>

    <div class="simple-footer">
        &copy; <?= date('Y') ?> MealMate. All rights reserved.
    </div>

    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
    <script>
        function updateOrderStatus(orderId, currentStatus) {
            document.getElementById('orderId').value = orderId;
            document.getElementById('statusModal').style.display = 'block';
            
            // Filter status options based on current status
            const statusSelect = document.getElementById('newStatus');
            const options = statusSelect.querySelectorAll('option');
            
            // Define valid transitions
            const validTransitions = {
                'pending': ['confirmed', 'cancelled'],
                'confirmed': ['preparing', 'cancelled'],
                'preparing': ['ready', 'cancelled'],
                'ready': ['out_for_delivery'],
                'out_for_delivery': ['delivered'],
                'delivered': [],
                'cancelled': []
            };
            
            options.forEach(option => {
                if (option.value === '') return; // Keep the default option
                
                const isValid = validTransitions[currentStatus]?.includes(option.value) || false;
                option.style.display = isValid ? 'block' : 'none';
                option.disabled = !isValid;
            });
        }

        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
            document.getElementById('statusUpdateForm').reset();
        }

        // Handle status update form submission
        document.getElementById('statusUpdateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order status updated successfully!');
                    closeModal();
                    location.reload();
                } else {
                    alert('Error updating order status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the order status');
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>