<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once 'cart_controller.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($conn, $user_id);
$cart_total = calculateCartTotal($conn, $user_id);

$page_title = "Shopping Cart - MealMate";
$page_name = "cart"; // For header highlighting

// Include header
include '../includes/menu_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
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

        /* Apply theme variables to existing cart styles */
        body {
            background-color: var(--bg-primary) !important;
            color: var(--text-primary) !important;
        }

        .cart-items {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card)) !important;
            border: 2px solid var(--border-color) !important;
            box-shadow: 0 6px 20px var(--shadow-color) !important;
        }

        .cart-summary {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card)) !important;
            border: 2px solid var(--border-color) !important;
            box-shadow: 0 6px 20px var(--shadow-color) !important;
        }

        .cart-item:hover {
            background: linear-gradient(135deg, rgba(255, 69, 0, 0.08), rgba(255, 107, 53, 0.05)) !important;
        }

        .item-details h3 {
            color: var(--accent-primary) !important;
        }

        .summary-title {
            color: var(--accent-primary) !important;
            border-bottom: 3px solid var(--accent-primary) !important;
        }

        .summary-row.total {
            background: rgba(255, 69, 0, 0.08) !important;
            border-top: 3px dashed var(--accent-primary) !important;
        }

        .confirmation-content {
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary)) !important;
            border: 2px solid var(--accent-primary) !important;
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

        /* Responsive theme toggle */
        @media (max-width: 768px) {
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
    </style>
</head>
<body class="cart-page">
    <!-- Beautiful Confirmation Modal -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="confirmation-content">
            <button class="close-confirm-btn" onclick="hideConfirmationModal()">&times;</button>
            <div class="confirmation-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirmation-title">Remove Item</h3>
            <p class="confirmation-message" id="confirmationMessage">Are you sure you want to remove this item from your cart?</p>
            <div class="confirmation-buttons">
                <button class="confirm-btn" id="confirmRemove">Yes, Remove</button>
                <button class="cancel-btn" id="cancelRemove">Cancel</button>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="content">
            <div class="page-header">
                <i class="fas fa-shopping-cart icon"></i>
                <h1>Your Shopping Cart</h1>
                <p>Review and manage your delicious items before checkout.</p>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart-page">
                    <i class="fas fa-box-open empty-icon"></i>
                    <h3>Your cart is empty.</h3>
                    <p>Looks like you haven't added anything to your cart yet.</p>
                    <a href="../food_management/menu.php" class="btn-primary" style="padding: 1rem 2rem; text-decoration: none; display: inline-block; border-radius: 8px; background: linear-gradient(135deg, #FF4500, #FF6B35); color: #000; font-weight: 600; margin-top: 1rem;">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-container">
                    <div class="cart-items">
                        <div id="cart-items-container">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item" data-cart-id="<?php echo htmlspecialchars($item['cart_id']); ?>">
                                    <div class="item-image">
                                        <img src="../assets/images/menu/<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['food_name']); ?>"
                                             onerror="this.src='../assets/images/menu/default.jpg'; this.onerror=null;">
                                    </div>
                                    <div class="item-details">
                                        <h3><?php echo htmlspecialchars($item['food_name']); ?></h3>
                                        <p class="item-description"><?php echo htmlspecialchars($item['description'] ?: 'Delicious food item'); ?></p>
                                        <p class="item-price">Rs.<?php echo htmlspecialchars(number_format($item['price'], 2)); ?> each</p>
                                    </div>
                                    <div class="quantity-controls">
                                        <button class="qty-btn" onclick="updateQuantity(<?php echo htmlspecialchars($item['cart_id']); ?>, -1)" aria-label="Decrease quantity">-</button>
                                        <input type="text" value="<?php echo htmlspecialchars($item['quantity']); ?>" class="qty-input" readonly>
                                        <button class="qty-btn" onclick="updateQuantity(<?php echo htmlspecialchars($item['cart_id']); ?>, 1)" aria-label="Increase quantity">+</button>
                                    </div>
                                    <div class="item-total">
                                        Rs.<?php echo htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)); ?>
                                    </div>
                                    <div class="delete-btn-container">
                                        <button class="delete-btn" 
                                                onclick="showRemoveConfirm(<?php echo htmlspecialchars($item['cart_id']); ?>, '<?php echo htmlspecialchars(addslashes($item['food_name'])); ?>')" 
                                                aria-label="Remove <?php echo htmlspecialchars($item['food_name']); ?>">
                                            <i class="fas fa-trash"></i>
                                            <span class="tooltip">Remove Item</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="cart-summary">
                        <h2 class="summary-title">Order Summary</h2>
                        
                        <div class="summary-row">
                            <span>Items (<?php echo count($cart_items); ?>):</span>
                            <span id="cart-subtotal">Rs.<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Delivery Fee:</span>
                            <span>Rs.250.00</span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="cart-total">Rs.<?php echo number_format($cart_total + 250.00, 2); ?></span>
                        </div>
                        
                        <div class="checkout-actions">
                            <a href="../orders/checkout.php" class="btn-primary">
                                <i class="fas fa-credit-card" style="margin-right: 0.5rem;"></i>
                                Proceed to Checkout
                            </a>
                            <a href="../food_management/menu.php" class="btn-secondary">
                                <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i>
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle-container">
        <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
            <i class="fas fa-sun theme-icon sun-icon"></i>
            <i class="fas fa-moon theme-icon moon-icon"></i>
        </button>
    </div>

    <!-- Footer -->
    <div class="simple-footer">
        &copy; <?php echo date('Y'); ?> MealMate. All rights reserved.
    </div>

    <!-- JavaScript -->
    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
    <script src="cart.js"></script>

    <!-- Add Font Awesome if not already included -->
    <script>
    // Check if Font Awesome is loaded, if not load it
    if (!document.querySelector('link[href*="font-awesome"]') && !document.querySelector('script[src*="font-awesome"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
        document.head.appendChild(link);
    }
    </script>
</body>
</html>