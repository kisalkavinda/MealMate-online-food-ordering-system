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

// Check if order is delivered (only delivered orders can have invoices)
if ($order['order_status'] !== 'delivered') {
    $_SESSION['error_message'] = "Invoice is only available for delivered orders.";
    header('Location: my_orders.php');
    exit();
}

$page_title = "Invoice - Order #" . $order['order_number'] . " - MealMate";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Invoice Styles */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .invoice-header {
            background: linear-gradient(135deg, #FF4500, #FF6B35);
            color: white;
            padding: 2rem;
            position: relative;
        }

        .invoice-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: white;
            border-radius: 20px 20px 0 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .company-info h1 {
            font-size: 2.5rem;
            margin: 0 0 0.5rem;
            font-weight: bold;
        }

        .company-info p {
            margin: 0.2rem 0;
            opacity: 0.9;
        }

        .invoice-meta {
            text-align: right;
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 10px;
        }

        .invoice-meta h2 {
            font-size: 2rem;
            margin: 0 0 1rem;
        }

        .invoice-meta p {
            margin: 0.3rem 0;
            font-size: 1.1rem;
        }

        .invoice-body {
            padding: 2rem;
        }

        .billing-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #eee;
        }

        .info-section h3 {
            color: #FF4500;
            font-size: 1.3rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #FF4500;
            padding-bottom: 0.5rem;
        }

        .info-section p {
            margin: 0.5rem 0;
            line-height: 1.6;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .invoice-table th,
        .invoice-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .invoice-table th {
            background-color: #FF4500;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .invoice-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .invoice-table tr:hover {
            background-color: #fff5f0;
        }

        .quantity-col {
            text-align: center;
        }

        .price-col,
        .total-col {
            text-align: right;
        }

        .totals-section {
            margin-left: auto;
            width: 300px;
            background: #f9f9f9;
            border-radius: 10px;
            padding: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }

        .total-row.grand-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #FF4500;
            border-bottom: 3px solid #FF4500;
            border-top: 2px solid #FF4500;
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .payment-info {
            background: #f0f8ff;
            border: 1px solid #4CAF50;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
        }

        .payment-info h3 {
            color: #4CAF50;
            margin-top: 0;
        }

        .payment-status {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .invoice-footer {
            background: #f9f9f9;
            padding: 2rem;
            text-align: center;
            border-top: 2px solid #FF4500;
        }

        .footer-note {
            color: #666;
            font-style: italic;
            margin-bottom: 1rem;
        }

        .contact-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #FF4500;
            font-weight: bold;
        }

        /* Action Buttons */
        .invoice-actions {
            background: white;
            padding: 1.5rem 2rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
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
            background: linear-gradient(135deg, #FF4500, #FF6B35);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 69, 0, 0.35);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e63e00, #FF5A29);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-actions {
                display: none;
            }
            
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .invoice-header {
                background: #FF4500 !important;
                -webkit-print-color-adjust: exact;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .invoice-body {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .invoice-meta {
                text-align: left;
            }

            .billing-info {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .totals-section {
                width: 100%;
            }

            .invoice-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .invoice-table {
                font-size: 0.9rem;
            }

            .invoice-table th,
            .invoice-table td {
                padding: 0.5rem;
            }

            .contact-info {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .company-info h1 {
                font-size: 2rem;
            }

            .invoice-meta h2 {
                font-size: 1.5rem;
            }

            .invoice-table {
                font-size: 0.8rem;
            }

            .invoice-table th,
            .invoice-table td {
                padding: 0.3rem;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="header-content">
                <div class="company-info">
                    <h1><i class="fas fa-utensils"></i> MealMate</h1>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Food Street, Colombo 01, Sri Lanka</p>
                    <p><i class="fas fa-phone"></i> +94 11 234 5678</p>
                    <p><i class="fas fa-envelope"></i> info@mealmate.lk</p>
                    <p><i class="fas fa-globe"></i> www.mealmate.lk</p>
                </div>
                <div class="invoice-meta">
                    <h2>INVOICE</h2>
                    <p><strong>Invoice #:</strong> INV-<?php echo htmlspecialchars($order['order_number']); ?></p>
                    <p><strong>Order #:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['actual_delivery_time'] ?? $order['created_at'])); ?></p>
                    <p><strong>Due Date:</strong> PAID</p>
                </div>
            </div>
        </div>

        <!-- Invoice Body -->
        <div class="invoice-body">
            <!-- Billing Information -->
            <div class="billing-info">
                <div class="info-section">
                    <h3><i class="fas fa-user"></i> Bill To</h3>
                    <p><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                    <p><?php echo htmlspecialchars($order['email']); ?></p>
                    <p><?php echo htmlspecialchars($order['phone']); ?></p>
                    <p><?php echo htmlspecialchars($order['delivery_address']); ?></p>
                    <p><?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['postal_code']); ?></p>
                </div>
                <div class="info-section">
                    <h3><i class="fas fa-info-circle"></i> Order Details</h3>
                    <p><strong>Order Date:</strong> <?php echo date('M d, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                    <p><strong>Delivery Date:</strong> <?php echo date('M d, Y \a\t g:i A', strtotime($order['actual_delivery_time'])); ?></p>
                    <p><strong>Status:</strong> <span class="payment-status">DELIVERED</span></p>
                    <p><strong>Payment Method:</strong> Cash on Delivery</p>
                    <?php if (!empty($order['special_instructions'])): ?>
                        <p><strong>Special Instructions:</strong><br><?php echo htmlspecialchars($order['special_instructions']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items Table -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="quantity-col">Qty</th>
                        <th class="price-col">Unit Price</th>
                        <th class="total-col">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['food_name']); ?></strong>
                                <?php if (!empty($item['description'])): ?>
                                    <br><small style="color: #666;"><?php echo htmlspecialchars($item['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="quantity-col"><?php echo $item['quantity']; ?></td>
                            <td class="price-col">Rs.<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="total-col">Rs.<?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Totals Section -->
            <div class="totals-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>Rs.<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Delivery Fee:</span>
                    <span>Rs.<?php echo number_format($order['delivery_fee'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Tax (0%):</span>
                    <span>Rs.0.00</span>
                </div>
                <div class="total-row grand-total">
                    <span>Total Amount:</span>
                    <span>Rs.<?php echo number_format($order['grand_total'], 2); ?></span>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="payment-info">
                <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                <p><strong>Payment Status:</strong> <span class="payment-status">PAID</span></p>
                <p><strong>Payment Method:</strong> Cash on Delivery</p>
                <p><strong>Payment Date:</strong> <?php echo date('M d, Y \a\t g:i A', strtotime($order['actual_delivery_time'])); ?></p>
                <p><strong>Amount Paid:</strong> Rs.<?php echo number_format($order['grand_total'], 2); ?></p>
            </div>
        </div>

        <!-- Invoice Footer -->
        <div class="invoice-footer">
            <div class="footer-note">
                Thank you for choosing MealMate! We hope you enjoyed your meal.
            </div>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>+94 11 234 5678</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>support@mealmate.lk</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-globe"></i>
                    <span>www.mealmate.lk</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="invoice-actions">
            <a href="my_orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Orders
            </a>
            <div>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i>
                    Print Invoice
                </button>
                <button onclick="downloadPDF()" class="btn btn-primary" style="margin-left: 0.5rem;">
                    <i class="fas fa-download"></i>
                    Download PDF
                </button>
            </div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            // Simple approach - open print dialog in a new window for PDF saving
            const printWindow = window.open('', '_blank');
            const invoiceContent = document.querySelector('.invoice-container').outerHTML;
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Invoice - Order #<?php echo htmlspecialchars($order['order_number']); ?></title>
                    <style>
                        ${document.querySelector('style').innerHTML}
                        body { background: white; padding: 0; }
                        .invoice-actions { display: none; }
                        .invoice-container { box-shadow: none; border-radius: 0; }
                    </style>
                </head>
                <body>
                    ${invoiceContent}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        }

        // Prevent right-click and other actions for security (optional)
        document.addEventListener('keydown', function(e) {
            // Prevent Ctrl+S, Ctrl+A, etc. if needed
            if (e.ctrlKey && (e.key === 's' || e.key === 'a')) {
                // Allow these for user convenience
                return;
            }
        });
    </script>
</body>
</html>