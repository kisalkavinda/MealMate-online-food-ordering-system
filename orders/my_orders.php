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

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get orders and total count
$orders = getUserOrders($conn, $user_id, $status_filter, $per_page, $offset);
$total_orders = getUserOrdersCount($conn, $user_id, $status_filter);
$total_pages = ceil($total_orders / $per_page);

$page_title = "My Orders - MealMate";
$page_name = "orders";

// Include header
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
            margin-bottom: 2.5rem;
        }

        .page-header .icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            display: block;
            color: var(--accent-primary);
        }

        .page-header h1 {
            font-size: 2.8rem;
            color: var(--accent-primary);
            margin: 0;
            font-weight: 700;
        }

        .page-header p {
            font-size: 1.3rem;
            color: var(--text-secondary);
            margin: 1rem 0;
            line-height: 1.5;
        }

        /* Filter Section */
        .filters-section {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: 15px;
            border: 2px solid var(--border-color);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 6px 20px var(--shadow-color);
        }

        .filter-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group label {
            color: var(--accent-primary);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .filter-select {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.8rem 1.2rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--accent-hover);
            box-shadow: 0 0 0 3px rgba(255, 69, 0, 0.2);
        }

        .filter-select option {
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .orders-summary {
            display: flex;
            gap: 1rem;
            margin-left: auto;
            flex-wrap: wrap;
        }

        .summary-item {
            background: rgba(255, 69, 0, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 69, 0, 0.3);
            font-size: 0.9rem;
            color: #FFD700;
            font-weight: 600;
        }

        /* Orders List */
        .orders-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .order-card {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: 15px;
            border: 2px solid var(--border-color);
            padding: 2rem;
            box-shadow: 0 6px 20px var(--shadow-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-hover));
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-info h3 {
            color: var(--accent-primary);
            font-size: 1.5rem;
            margin: 0 0 0.5rem;
            font-weight: 700;
        }

        .order-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .meta-item i {
            color: var(--accent-primary);
            width: 16px;
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

        .order-items {
            margin-bottom: 1.5rem;
        }

        .items-preview {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .item-tag {
            background: rgba(255, 69, 0, 0.1);
            color: #FFD700;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            border: 1px solid rgba(255, 69, 0, 0.3);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .order-totals {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 2px dashed rgba(255, 69, 0, 0.3);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .total-amount {
            font-size: 1.4rem;
            font-weight: bold;
            color: #FFD700;
        }

        .order-actions {
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

        .btn-secondary:hover {
            background: linear-gradient(135deg, #555, #777);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
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

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 0.8rem 1.2rem;
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 8px;
            border: 2px solid rgba(255, 69, 0, 0.3);
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .pagination a:hover {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover));
            color: #000;
            transform: translateY(-2px);
        }

        .pagination .current {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover));
            color: #000;
            border-color: var(--accent-primary);
        }

        /* Empty State */
        .empty-orders {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-orders .icon {
            font-size: 5rem;
            color: var(--accent-primary);
            margin-bottom: 1.5rem;
            display: block;
        }

        .empty-orders h3 {
            color: var(--accent-primary);
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .empty-orders p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
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

        /* Focus and Accessibility Improvements */
        .theme-toggle-btn:focus,
        .btn:focus,
        .filter-select:focus {
            outline: 3px solid var(--accent-primary);
            outline-offset: 2px;
        }

        /* Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {
            .theme-toggle-btn,
            .btn,
            .order-card,
            .pagination a {
                transition: none;
            }
            
            .theme-toggle-btn:hover,
            .btn:hover,
            .order-card:hover,
            .pagination a:hover {
                transform: none;
            }
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .content {
                padding: 1.5rem;
            }

            .filter-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .orders-summary {
                margin-left: 0;
                margin-top: 1rem;
            }

            .order-header {
                flex-direction: column;
                align-items: stretch;
            }

            .order-actions {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2.2rem;
            }

            .order-card {
                padding: 1.5rem;
            }

            .order-meta {
                flex-direction: column;
                gap: 0.8rem;
            }

            .order-totals {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }

            .order-actions {
                width: 100%;
                justify-content: stretch;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }

            .pagination {
                gap: 0.3rem;
            }

            .pagination a, .pagination span {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
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
            .content {
                padding: 1rem;
            }

            .filters-section {
                padding: 1rem;
            }

            .order-card {
                padding: 1.2rem;
            }

            .items-preview {
                flex-direction: column;
                gap: 0.5rem;
            }

            .summary-item {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body class="orders-page">
    <div class="main-container">
        <div class="content">
            <div class="page-header">
                <i class="fas fa-receipt icon"></i>
                <h1>My Orders</h1>
                <p>Track your order history and current orders</p>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filter-controls">
                    <div class="filter-group">
                        <label for="statusFilter">Filter by Status:</label>
                        <select id="statusFilter" class="filter-select" onchange="filterOrders()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                            <option value="out_for_delivery" <?php echo $status_filter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="orders-summary">
                        <div class="summary-item">
                            <i class="fas fa-shopping-bag"></i>
                            Total: <?php echo $total_orders; ?> Orders
                        </div>
                        <div class="summary-item">
                            <i class="fas fa-filter"></i>
                            Showing: <?php echo count($orders); ?> Orders
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders List -->
            <div class="orders-container">
                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <i class="fas fa-clipboard-list icon"></i>
                        <h3>No orders found</h3>
                        <p><?php echo $status_filter === 'all' ? 'You haven\'t placed any orders yet.' : 'No orders found with the selected status.'; ?></p>
                        <a href="../food_management/menu.php" class="btn btn-primary">
                            <i class="fas fa-utensils"></i>
                            Browse Menu
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                                    <div class="order-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo getTimeSinceOrder($order['created_at']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-utensils"></i>
                                            <span><?php echo $order['item_count']; ?> Item<?php echo $order['item_count'] > 1 ? 's' : ''; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="order-status status-<?php echo $order['order_status']; ?>">
                                    <i class="fas fa-circle"></i>
                                    <span><?php echo formatOrderStatus($order['order_status']); ?></span>
                                </div>
                            </div>

                            <div class="order-items">
                                <div class="items-preview">
                                    <?php 
                                    // Show first few items as preview
                                    $preview_items = array_slice(explode(', ', 'Item 1, Item 2, Item 3'), 0, 3);
                                    foreach ($preview_items as $index => $item):
                                        if ($index < 3):
                                    ?>
                                        <span class="item-tag"><?php echo $order['item_count'] > 1 ? $order['item_count'] . ' Items' : '1 Item'; ?></span>
                                        <?php break; ?>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                    <?php if ($order['item_count'] > 3): ?>
                                        <span class="item-tag">+<?php echo $order['item_count'] - 3; ?> more</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="order-totals">
                                <div class="total-amount">
                                    Total: Rs.<?php echo number_format($order['grand_total'], 2); ?>
                                </div>
                                <div class="order-actions">
                                    <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                    <?php if (in_array($order['order_status'], ['out_for_delivery', 'preparing', 'ready'])): ?>
                                        <a href="track_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-secondary">
                                            <i class="fas fa-map-marker-alt"></i>
                                            Track Order
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($order['order_status'] === 'delivered'): ?>
                                        <a href="order_invoice.php?id=<?php echo $order['order_id']; ?>" class="btn btn-secondary">
                                            <i class="fas fa-file-invoice"></i>
                                            Invoice
                                        </a>
                                    <?php endif; ?>
                                    <?php if (in_array($order['order_status'], ['pending', 'confirmed'])): ?>
                                        <button onclick="cancelOrder(<?php echo $order['order_id']; ?>)" class="btn btn-danger">
                                            <i class="fas fa-times"></i>
                                            Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?status=<?php echo $status_filter; ?>&page=1">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $total_pages; ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle-container">
        <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme" type="button">
            <i class="fas fa-sun theme-icon sun-icon"></i>
            <i class="fas fa-moon theme-icon moon-icon"></i>
        </button>
    </div>

    <!-- Load theme toggle script -->
    <script src="../theme-toggle.js"></script>
    
    <script>
        function filterOrders() {
            const status = document.getElementById('statusFilter').value;
            window.location.href = `?status=${status}&page=1`;
        }

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

        // Auto-refresh page for active orders
        document.addEventListener('DOMContentLoaded', function() {
            const activeStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery'];
            const currentStatus = '<?php echo $status_filter; ?>';
            
            if (activeStatuses.includes(currentStatus) || currentStatus === 'all') {
                // Refresh every 30 seconds for active orders
                setTimeout(() => {
                    location.reload();
                }, 30000);
            }
        });
    </script>
</body>
</html>

<?php include __DIR__ . '/../includes/simple_footer.php'; ?>