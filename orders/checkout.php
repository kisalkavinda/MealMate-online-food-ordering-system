<?php
session_start();
require_once __DIR__ . '/../includes/menu_header.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../cart/cart_controller.php';
require_once __DIR__ . '/../orders/order_controller.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($conn, $user_id);
$cart_total = calculateCartTotal($conn, $user_id);
$delivery_fee = 250.00;
$grand_total = $cart_total + $delivery_fee;

// Validation functions
function validatePhone($phone) {
    // Remove spaces and dashes for validation
    $phone = preg_replace('/[\s\-]/', '', $phone);
    // Check if it's a valid Sri Lankan phone number (10 digits starting with 0)
    return preg_match('/^0[0-9]{9}$/', $phone);
}

function validatePostalCode($postal_code) {
    // Postal code should only contain numbers (5 digits for Sri Lanka)
    return preg_match('/^[0-9]{5}$/', $postal_code);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle order confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_order'])) {
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $postal_code = sanitizeInput($_POST['postal_code'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $special_instructions = sanitizeInput($_POST['special_instructions'] ?? '');
        $payment_method = $_POST['payment_method'] ?? '';
        
        $errors = [];
        
        // Validate required fields
        if (empty($address)) {
            $errors[] = "Delivery address is required.";
        }
        if (empty($city)) {
            $errors[] = "City is required.";
        }
        if (empty($postal_code)) {
            $errors[] = "Postal code is required.";
        } elseif (!validatePostalCode($postal_code)) {
            $errors[] = "Postal code must be 5 digits only.";
        }
        if (empty($phone)) {
            $errors[] = "Phone number is required.";
        } elseif (!validatePhone($phone)) {
            $errors[] = "Please enter a valid 10-digit Sri Lankan phone number (e.g., 0771234567).";
        }
        if (empty($payment_method)) {
            $errors[] = "Please select a payment method.";
        }
        
        if (empty($errors)) {
            try {
                // Prepare delivery details
                $delivery_details = [
                    'address' => $address,
                    'city' => $city,
                    'postal_code' => $postal_code,
                    'phone' => $phone,
                    'special_instructions' => $special_instructions,
                    'payment_method' => $payment_method
                ];
                
                // Store delivery details in session for payment processing
                $_SESSION['checkout_data'] = $delivery_details;
                
                if ($payment_method === 'cash_on_delivery') {
                    // Create order immediately for COD
                    $order_id = createOrderFromCart($conn, $user_id, $delivery_details);
                    
                    if ($order_id) {
                        $order = getOrderDetails($conn, $order_id, $user_id);
                        
                        $_SESSION['order_success'] = "Your order has been placed successfully! Order #" . $order['order_number'];
                        $_SESSION['order_details'] = [
                            'order_id' => $order_id,
                            'order_number' => $order['order_number'],
                            'address' => $address,
                            'city' => $city,
                            'postal_code' => $postal_code,
                            'phone' => $phone,
                            'special_instructions' => $special_instructions,
                            'payment_method' => 'Cash on Delivery',
                            'total' => $grand_total
                        ];
                        
                        header("Location: checkout.php");
                        exit();
                    } else {
                        $errors[] = "There was an error placing your order. Please try again.";
                    }
                } else {
                    // Redirect to payment gateway
                    header("Location: payment_gateway.php");
                    exit();
                }
            } catch (Exception $e) {
                $errors[] = "Error: " . $e->getMessage();
                error_log("Order creation error: " . $e->getMessage());
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['order_error'] = implode("<br>", $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d0d0d">
    <title>Checkout - MealMate</title>
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
            overflow-x: hidden;
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

        .alert {
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #155724, #1e7e34);
            color: #fff;
            border: 2px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #721c24, #c82333);
            color: #fff;
            border: 2px solid #dc3545;
        }

        [data-theme="light"] .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        [data-theme="light"] .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .checkout-container {
            display: flex;
            flex-direction: row;
            gap: 2rem;
            align-items: flex-start;
            width: 100%;
        }

        .order-summary-section, .order-details-section {
            flex: 1;
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: 15px;
            border: 2px solid var(--border-color);
            padding: 2rem;
            box-shadow: 0 6px 20px var(--shadow-color);
        }

        .section-title {
            font-size: 1.8rem;
            color: var(--accent-primary);
            border-bottom: 3px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 700;
        }

        .order-items {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem;
            border-bottom: 1px solid rgba(255, 69, 0, 0.2);
            margin-bottom: 0.5rem;
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .item-image {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details h4 {
            font-size: 1.3rem;
            color: var(--accent-primary);
            margin: 0 0 0.3rem;
            font-weight: 600;
        }

        .item-details .item-price {
            font-size: 1.1rem;
            color: #FFD700;
            font-weight: 600;
        }

        .item-quantity {
            font-size: 1.1rem;
            color: var(--text-secondary);
            background: rgba(255, 69, 0, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 69, 0, 0.3);
        }

        .order-totals {
            border-top: 2px dashed rgba(255, 69, 0, 0.3);
            padding-top: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
        }

        .total-row span {
            font-size: 1.2rem;
            color: var(--text-secondary);
        }

        .total-row.grand-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent-primary);
            border-top: 2px dashed var(--border-color);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 1.1rem;
            color: var(--accent-primary);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 1rem;
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--text-muted);
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-hover);
            box-shadow: 0 0 0 3px rgba(255, 69, 0, 0.2);
        }

        .form-group.error input {
            border-color: #dc3545;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }

        .required::after {
            content: " *";
            color: var(--accent-primary);
        }

        .form-group small {
            display: block;
            margin-top: 0.3rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Payment Method Styles */
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-option {
            position: relative;
        }

        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .payment-label {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.2rem;
            background: rgba(255, 69, 0, 0.05);
            border: 2px solid rgba(255, 69, 0, 0.3);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-label:hover {
            background: rgba(255, 69, 0, 0.1);
        }

        .payment-option input[type="radio"]:checked + .payment-label {
            background: rgba(255, 69, 0, 0.15);
            border-color: var(--accent-primary);
        }

        .payment-icon {
            font-size: 2rem;
            color: var(--accent-primary);
            width: 50px;
            text-align: center;
        }

        .payment-info {
            flex: 1;
        }

        .payment-info h4 {
            margin: 0 0 0.3rem;
            color: var(--accent-primary);
            font-size: 1.2rem;
        }

        .payment-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .checkout-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-primary, .btn-secondary {
            display: block;
            width: 100%;
            padding: 1.2rem;
            text-align: center;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            font-size: 1.2rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover));
            color: #000;
            box-shadow: 0 4px 12px rgba(255, 69, 0, 0.35);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e63e00, #FF5A29);
            transform: translateY(-2px);
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
        }

        [data-theme="light"] .btn-secondary:hover {
            background: linear-gradient(135deg, #d0d0d0, #c0c0c0);
        }

        .order-success-details {
            background: linear-gradient(135deg, rgba(255, 69, 0, 0.1), rgba(255, 107, 53, 0.05));
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid rgba(255, 69, 0, 0.3);
        }

        .order-success-details h3 {
            color: var(--accent-primary);
            margin-top: 0;
            font-size: 1.5rem;
        }

        .order-detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed rgba(255, 69, 0, 0.2);
        }

        .cod-notice {
            background: rgba(255, 215, 0, 0.1);
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 1.2rem;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cod-notice i {
            font-size: 2rem;
            color: #FFD700;
        }

        .cod-notice-text h4 {
            margin: 0 0 0.5rem;
            color: #FFD700;
            font-size: 1.2rem;
        }

        .cod-notice-text p {
            margin: 0;
            color: var(--text-secondary);
        }

        .empty-cart-message {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
            font-size: 1.3rem;
        }

        .empty-cart-message .icon {
            font-size: 4rem;
            color: var(--accent-primary);
            margin-bottom: 1rem;
            display: block;
        }

        /* Autofill and focus fix */
        input:-webkit-autofill,
        textarea:-webkit-autofill,
        select:-webkit-autofill {
            -webkit-box-shadow: 0 0 0px 1000px var(--bg-primary) inset !important;
            box-shadow: 0 0 0px 1000px var(--bg-primary) inset !important;
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

        @media (max-width: 992px) {
            .checkout-container {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .content {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 2.2rem;
            }

            .order-summary-section, .order-details-section {
                padding: 1.5rem;
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
    </style>
</head>
<body>
    <div class="main-container">
        <div class="content">
            <div class="page-header">
                <i class="fas fa-credit-card icon"></i>
                <h1>Checkout</h1>
                <p>Complete your order with delivery details</p>
            </div>

            <?php
            if (isset($_SESSION['order_success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['order_success'] . '</div>';
                unset($_SESSION['order_success']);
            }
            if (isset($_SESSION['order_error'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['order_error'] . '</div>';
                unset($_SESSION['order_error']);
            }
            ?>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart-message">
                    <i class="fas fa-shopping-cart icon"></i>
                    <h3>Your cart is empty</h3>
                    <p>Please add items to your cart before proceeding to checkout.</p>
                    <a href="../food_management/menu.php" class="btn-primary" style="margin-top: 1.5rem; display: inline-block; text-decoration: none;">Browse Menu</a>
                </div>
            <?php else: ?>
                <div class="checkout-container">
                    <div class="order-summary-section">
                        <h2 class="section-title">Order Summary</h2>
                        
                        <ul class="order-items">
                            <?php foreach ($cart_items as $item): ?>
                                <li class="order-item">
                                    <div class="item-info">
                                        <div class="item-image">
                                            <img src="../assets/images/menu/<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['food_name']); ?>"
                                                 onerror="this.src='../assets/images/menu/default.jpg';">
                                        </div>
                                        <div class="item-details">
                                            <h4><?php echo htmlspecialchars($item['food_name']); ?></h4>
                                            <p class="item-price">Rs.<?php echo number_format($item['price'], 2); ?> each</p>
                                        </div>
                                    </div>
                                    <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span>Rs.<?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Delivery Fee:</span>
                                <span>Rs.<?php echo number_format($delivery_fee, 2); ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Total Amount:</span>
                                <span>Rs.<?php echo number_format($grand_total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-details-section">
                        <?php if (isset($_SESSION['order_details'])): ?>
                            <h2 class="section-title">Order Confirmed!</h2>
                            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 1.5rem;">Thank you for your order. Here are your order details:</p>
                            
                            <div class="order-success-details">
                                <h3>Order #<?php echo htmlspecialchars($_SESSION['order_details']['order_number']); ?></h3>
                                <div class="order-detail-item">
                                    <span>Address:</span>
                                    <span><?php echo htmlspecialchars($_SESSION['order_details']['address']); ?></span>
                                </div>
                                <div class="order-detail-item">
                                    <span>City:</span>
                                    <span><?php echo htmlspecialchars($_SESSION['order_details']['city']); ?></span>
                                </div>
                                <div class="order-detail-item">
                                    <span>Phone:</span>
                                    <span><?php echo htmlspecialchars($_SESSION['order_details']['phone']); ?></span>
                                </div>
                                <div class="order-detail-item">
                                    <span>Payment:</span>
                                    <span><?php echo htmlspecialchars($_SESSION['order_details']['payment_method']); ?></span>
                                </div>
                                <div class="order-detail-item grand-total">
                                    <span>Total Amount:</span>
                                    <span>Rs.<?php echo number_format($_SESSION['order_details']['total'], 2); ?></span>
                                </div>
                            </div>

                            <?php if ($_SESSION['order_details']['payment_method'] === 'Cash on Delivery'): ?>
                                <div class="cod-notice">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div class="cod-notice-text">
                                        <h4>Cash on Delivery</h4>
                                        <p>Please keep Rs.<?php echo number_format($_SESSION['order_details']['total'], 2); ?> ready to pay the delivery person upon arrival.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="checkout-actions">
                                <a href="../orders/my_orders.php" class="btn-primary">View My Orders</a>
                                <a href="../food_management/menu.php" class="btn-secondary">Order Again</a>
                            </div>
                            
                            <?php
                            unset($_SESSION['order_details']);
                            ?>
                            
                        <?php else: ?>
                            <h2 class="section-title">Delivery & Payment Details</h2>
                            <form action="checkout.php" method="POST" id="checkoutForm">
                                <div class="form-group">
                                    <label for="address" class="required">Delivery Address</label>
                                    <input type="text" id="address" name="address" placeholder="Enter your full address" required 
                                           value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="city" class="required">City</label>
                                    <input type="text" id="city" name="city" placeholder="Enter your city" required
                                           value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="postal_code" class="required">Postal Code</label>
                                    <input type="text" id="postal_code" name="postal_code" placeholder="5-digit postal code (e.g., 10100)" required maxlength="5"
                                           value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>">
                                    <small>Numbers only, 5 digits</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone" class="required">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" placeholder="10-digit number (e.g., 0771234567)" required maxlength="10"
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                    <small>Format: 07XXXXXXXX</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="special_instructions">Special Instructions (Optional)</label>
                                    <textarea id="special_instructions" name="special_instructions" rows="3" placeholder="Any special delivery instructions..."><?php echo isset($_POST['special_instructions']) ? htmlspecialchars($_POST['special_instructions']) : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="required">Payment Method</label>
                                    <div class="payment-methods">
                                        <div class="payment-option">
                                            <input type="radio" id="cod" name="payment_method" value="cash_on_delivery" required>
                                            <label for="cod" class="payment-label">
                                                <div class="payment-icon">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </div>
                                                <div class="payment-info">
                                                    <h4>Cash on Delivery</h4>
                                                    <p>Pay with cash when your order arrives</p>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <div class="payment-option">
                                            <input type="radio" id="online" name="payment_method" value="online_payment" required>
                                            <label for="online" class="payment-label">
                                                <div class="payment-icon">
                                                    <i class="fas fa-credit-card"></i>
                                                </div>
                                                <div class="payment-info">
                                                    <h4>Pay Online</h4>
                                                    <p>Secure payment with credit/debit card</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="checkout-actions">
                                    <button type="submit" name="confirm_order" class="btn-primary">
                                        <i class="fas fa-check-circle"></i> Confirm Order
                                    </button>
                                    <a href="../cart/cart.php" class="btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to Cart
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
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

    <script src="../theme-toggle.js"></script>
    <script>
        // Form validation
        document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
            const postalCode = document.getElementById('postal_code').value;
            const phone = document.getElementById('phone').value;
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            let errors = [];
            
            // Validate postal code - only numbers, 5 digits
            if (!/^[0-9]{5}$/.test(postalCode)) {
                errors.push('Postal code must be exactly 5 digits.');
            }
            
            // Validate phone - Sri Lankan format
            const cleanPhone = phone.replace(/[\s\-]/g, '');
            if (!/^0[0-9]{9}$/.test(cleanPhone)) {
                errors.push('Phone number must be 10 digits starting with 0 (e.g., 0771234567).');
            }
            
            // Validate payment method
            if (!paymentMethod) {
                errors.push('Please select a payment method.');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
                return false;
            }
        });

        // Real-time validation for postal code
        document.getElementById('postal_code')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Real-time validation for phone
        document.getElementById('phone')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>

<?php include __DIR__ . '/../includes/simple_footer.php'; ?>