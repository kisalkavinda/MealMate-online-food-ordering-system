<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../cart/cart_controller.php';
require_once __DIR__ . '/../orders/order_controller.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

// Check if checkout data exists
if (!isset($_SESSION['checkout_data'])) {
    header('Location: checkout.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_total = calculateCartTotal($conn, $user_id);
$delivery_fee = 250.00;
$grand_total = $cart_total + $delivery_fee;
$checkout_data = $_SESSION['checkout_data'];

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $card_name = trim($_POST['card_name'] ?? '');
    $card_type = $_POST['card_type'] ?? '';
    $expiry_month = $_POST['expiry_month'] ?? '';
    $expiry_year = $_POST['expiry_year'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    $errors = [];
    
    // Validate card type
    if (empty($card_type)) {
        $errors[] = "Please select a card type.";
    }
    
    // Validate card number (16 digits)
    if (!preg_match('/^[0-9]{16}$/', $card_number)) {
        $errors[] = "Card number must be 16 digits.";
    }
    
    // Validate cardholder name (letters and spaces only)
    if (!preg_match('/^[a-zA-Z\s]{3,}$/', $card_name)) {
        $errors[] = "Cardholder name must contain only letters and spaces.";
    }
    
    // Validate expiry date
    if (empty($expiry_month) || empty($expiry_year)) {
        $errors[] = "Please select expiry month and year.";
    } else {
        $current_year = (int)date('Y');
        $current_month = (int)date('m');
        $exp_year = (int)$expiry_year;
        $exp_month = (int)$expiry_month;
        
        if ($exp_year < $current_year || ($exp_year == $current_year && $exp_month < $current_month)) {
            $errors[] = "Card has expired.";
        }
    }
    
    // Validate CVV (3 or 4 digits)
    if (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
        $errors[] = "CVV must be 3 or 4 digits.";
    }
    
    if (empty($errors)) {
        try {
            // Create order in database
            $order_id = createOrderFromCart($conn, $user_id, $checkout_data);

            if ($order_id) {
                // Get complete order details including address and total
                $order_sql = "SELECT o.*, 
                             o.delivery_address as address,
                             o.total_amount as total,
                             o.order_status,
                             o.created_at
                      FROM orders o 
                      WHERE o.order_id = ?";
                
                $stmt = $conn->prepare($order_sql);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $order_details = $stmt->get_result()->fetch_assoc();

                // Add these details explicitly
                $order_details['address'] = $checkout_data['address'];
                $order_details['city'] = $checkout_data['city'];
                $order_details['postal_code'] = $checkout_data['postal_code'];
                $order_details['phone'] = $checkout_data['phone'];
                $order_details['payment_method'] = 'Online Payment';
                $order_details['total'] = $grand_total;

                $_SESSION['payment_success'] = true;
                $_SESSION['order_details'] = $order_details;

                // Clear cart and redirect
                clearCart($conn, $user_id);
                unset($_SESSION['checkout_data']);
                header('Location: payment_success.php');
                exit();
            } else {
                $errors[] = "Failed to create order. Please try again.";
            }
        } catch (Exception $e) {
            $errors[] = "Payment processing error: " . $e->getMessage();
            error_log("Payment error: " . $e->getMessage());
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['payment_error'] = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d0d0d">
    <title>Secure Payment - MealMate</title>
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
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .payment-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .payment-box {
            max-width: 600px;
            width: 100%;
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: 15px;
            border: 2px solid var(--border-color);
            padding: 2.5rem;
            box-shadow: 0 10px 40px var(--shadow-color);
        }

        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(255, 69, 0, 0.3);
        }

        .payment-header .icon {
            font-size: 3rem;
            color: var(--accent-primary);
            margin-bottom: 1rem;
        }

        .payment-header h1 {
            font-size: 2rem;
            color: var(--accent-primary);
            margin: 0 0 0.5rem;
        }

        .payment-header p {
            color: var(--text-secondary);
            margin: 0;
        }

        .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 1rem;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }

        .amount-display {
            background: rgba(255, 69, 0, 0.1);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .amount-display h3 {
            margin: 0 0 0.5rem;
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .amount-display .amount {
            font-size: 2.5rem;
            color: #FFD700;
            font-weight: bold;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        [data-theme="light"] .alert-danger {
            background: rgba(220, 53, 69, 0.05);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--accent-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 1rem;
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--accent-hover);
            box-shadow: 0 0 0 3px rgba(255, 69, 0, 0.2);
        }

        .form-group small {
            display: block;
            margin-top: 0.3rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .card-type-option {
            position: relative;
        }

        .card-type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .card-type-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            padding: 1.2rem 0.8rem;
            background: rgba(255, 69, 0, 0.05);
            border: 2px solid rgba(255, 69, 0, 0.3);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-height: 120px;
            height: 100%;
        }

        .card-type-label:hover {
            background: rgba(255, 69, 0, 0.1);
        }

        .card-type-option input[type="radio"]:checked + .card-type-label {
            background: rgba(255, 69, 0, 0.15);
            border-color: var(--accent-primary);
            box-shadow: 0 0 10px var(--shadow-color);
        }

        .card-type-label i {
            font-size: 2.8rem;
            color: var(--accent-primary);
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .card-type-label span {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
            margin-top: auto;
        }

        .btn-payment {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #28a745, #32CD32);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-payment:hover:not(:disabled) {
            background: linear-gradient(135deg, #218838, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-payment:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
            opacity: 0.6;
        }

        .btn-cancel {
            width: 100%;
            padding: 1rem;
            background: transparent;
            color: var(--text-primary);
            border: 2px solid #666;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: rgba(255, 69, 0, 0.1);
            border-color: var(--accent-primary);
        }

        .security-note {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 69, 0, 0.2);
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .loading-content {
            text-align: center;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 69, 0, 0.3);
            border-top-color: var(--accent-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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

        @media (max-width: 768px) {
            .payment-box {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .amount-display .amount {
                font-size: 2rem;
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
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p style="color: #fff; font-size: 1.2rem;">Processing your payment...</p>
            <p style="color: rgba(255, 255, 255, 0.7);">Please do not close this window</p>
        </div>
    </div>

    <div class="payment-container">
        <div class="payment-box">
            <div class="payment-header">
                <i class="fas fa-lock icon"></i>
                <h1>Secure Payment</h1>
                <p>Enter your card details to complete the payment</p>
                <span class="secure-badge">
                    <i class="fas fa-shield-alt"></i>
                    SSL Encrypted
                </span>
            </div>

            <div class="amount-display">
                <h3>Amount to Pay</h3>
                <div class="amount">Rs.<?php echo number_format($grand_total, 2); ?></div>
            </div>

            <?php if (isset($_SESSION['payment_error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['payment_error'];
                    unset($_SESSION['payment_error']);
                    ?>
                </div>
            <?php endif; ?>

            <form id="paymentForm" method="POST" action="">
                <div class="form-group">
                    <label for="card_type">Card Type *</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem;">
                        <div class="card-type-option">
                            <input type="radio" id="visa" name="card_type" value="Visa" <?php if(($_POST['card_type'] ?? '') === 'Visa') echo 'checked'; ?>>
                            <label for="visa" class="card-type-label">
                                <i class="fab fa-cc-visa"></i>
                                <span>Visa</span>
                            </label>
                        </div>
                        <div class="card-type-option">
                            <input type="radio" id="mastercard" name="card_type" value="Mastercard" <?php if(($_POST['card_type'] ?? '') === 'Mastercard') echo 'checked'; ?>>
                            <label for="mastercard" class="card-type-label">
                                <i class="fab fa-cc-mastercard"></i>
                                <span>Mastercard</span>
                            </label>
                        </div>
                        <div class="card-type-option">
                            <input type="radio" id="amex" name="card_type" value="Amex" <?php if(($_POST['card_type'] ?? '') === 'Amex') echo 'checked'; ?>>
                            <label for="amex" class="card-type-label">
                                <i class="fab fa-cc-amex"></i>
                                <span>Amex</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="card_number">Card Number *</label>
                    <input type="text" id="card_number" name="card_number" 
                           placeholder="1234 5678 9012 3456" 
                           maxlength="19" 
                           autocomplete="off"
                           value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="card_name">Cardholder Name *</label>
                    <input type="text" id="card_name" name="card_name" 
                           placeholder="Name on Card" 
                           autocomplete="off"
                           value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry_month">Expiry Month *</label>
                        <select id="expiry_month" name="expiry_month">
                            <option value="">Month</option>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php if(($_POST['expiry_month'] ?? '') == str_pad($i, 2, '0', STR_PAD_LEFT)) echo 'selected'; ?>>
                                    <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="expiry_year">Expiry Year *</label>
                        <select id="expiry_year" name="expiry_year">
                            <option value="">Year</option>
                            <?php 
                            $current_year = (int)date('Y');
                            for($i = 0; $i <= 10; $i++): 
                                $year = $current_year + $i;
                            ?>
                                <option value="<?php echo $year; ?>" <?php if(($_POST['expiry_year'] ?? '') == $year) echo 'selected'; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cvv">CVV/CVC *</label>
                    <input type="text" id="cvv" name="cvv" 
                           placeholder="123" 
                           maxlength="4" 
                           autocomplete="off"
                           value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>">
                    <small>3 or 4 digit security code on the back of your card</small>
                </div>

                <button type="submit" name="process_payment" class="btn-payment" id="submitBtn">
                    <i class="fas fa-lock"></i>
                    Pay Rs.<?php echo number_format($grand_total, 2); ?>
                </button>

                <button type="button" class="btn-cancel" onclick="window.location.href='checkout.php'">
                    <i class="fas fa-times"></i> Cancel Payment
                </button>
            </form>

            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                Your payment information is encrypted and secure.
                <br>We do not store your card details.
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

        // Form validation and formatting
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        document.getElementById('cvv').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        document.getElementById('card_name').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
        });

        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            document.getElementById('loadingOverlay').style.display = 'flex';
            document.getElementById('submitBtn').disabled = true;
        });
    </script>
</body>
</html>