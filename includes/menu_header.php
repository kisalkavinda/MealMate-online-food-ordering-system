<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$base_path = '/MealMate-online-food-ordering-system';

// Determine which page is active for highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$menu_active = ($current_page == 'menu.php') ? 'active' : '';
$cart_active = ($current_page == 'cart.php' || $current_page == 'checkout.php') ? 'active' : '';
$orders_active = (in_array($current_page, ['my_orders.php', 'order_details.php', 'track_order.php'])) ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MealMate - Food Menu</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>/food_management/menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-header">
                <h1 class="nav-logo">MealMate</h1>
                <div class="nav-toggle" id="navToggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
            <ul class="nav-menu" id="navMenu">
                <li><a href="<?php echo $base_path; ?>/index.php">Home</a></li>
                <li><a href="<?php echo $base_path; ?>/food_management/menu.php" class="<?php echo $menu_active; ?>">Menu</a></li>
                <li><a href="<?php echo $base_path; ?>/cart/cart.php" class="<?php echo $cart_active; ?>">Cart</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $base_path; ?>/orders/my_orders.php" class="<?php echo $orders_active; ?>">My Orders</a></li>
                    <li><a href="<?php echo $base_path; ?>/users/profile.php">Profile</a></li>
                    <li><a href="<?php echo $base_path; ?>/users/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $base_path; ?>/users/login.php">Login</a></li>
                    <li><a href="<?php echo $base_path; ?>/users/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <script>
        // Mobile menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const navToggle = document.getElementById('navToggle');
            const navMenu = document.getElementById('navMenu');
            
            if (navToggle && navMenu) {
                navToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('nav-menu-active');
                    navToggle.classList.toggle('toggle-active');
                });
                
                // Close menu when clicking on a link
                const navLinks = navMenu.querySelectorAll('a');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        navMenu.classList.remove('nav-menu-active');
                        navToggle.classList.remove('toggle-active');
                    });
                });
                
                // Close menu when clicking outside - FIXED to not interfere with theme toggle
                document.addEventListener('click', function(event) {
                    const isThemeToggle = event.target.closest('.theme-toggle-btn') || event.target.closest('.theme-toggle-container');
                    
                    if (!isThemeToggle && !navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                        navMenu.classList.remove('nav-menu-active');
                        navToggle.classList.remove('toggle-active');
                    }
                });
            }
        });
    </script>
</document_content>