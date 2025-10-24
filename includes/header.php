<?php
// Start the session at the very beginning of the file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
// Define the base path for your project
$base_path = '/MealMate-online-food-ordering-system';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>MealMate - Delicious Food Delivered</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <?php
    // Dynamically link CSS based on the current page
    if ($current_page == 'index.php') {
        echo '<link rel="stylesheet" href="' . $base_path . '/index.css">';
    } else if ($current_page == 'menu.php') {
        echo '<link rel="stylesheet" href="' . $base_path . '/food_management/menu.css">';
    } else {
        echo '<link rel="stylesheet" href="' . $base_path . '/assets/form.css">';
    }
    ?>
    
    <!-- Theme Toggle System - Load before body -->
    <script src="<?php echo $base_path; ?>/theme-toggle.js"></script>
</head>
<body>
    <header>
        <div class="logo">MealMate</div>
        
        <?php if ($current_page == 'index.php'): ?>
            <nav class="main-nav landing-nav">
                <a href="#home" class="active">Home</a>
                <a href="#services">Services</a>
                <a href="#about">About</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
            </nav>
        <?php else: ?>
            <nav class="main-nav internal-nav">
                <a href="<?php echo $base_path; ?>/index.php">HOME</a>
                <a href="<?php echo $base_path; ?>/food_management/menu.php">MENU</a>
                <a href="<?php echo $base_path; ?>/cart/cart.php">CART</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $base_path; ?>/users/profile.php">PROFILE</a>
                    <a href="<?php echo $base_path; ?>/users/logout.php">LOGOUT</a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>/users/login.php">LOGIN</a>
                    <a href="<?php echo $base_path; ?>/users/register.php">REGISTER</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <div class="menu-btn" id="menuBtn"><i class="fas fa-bars"></i></div>
    </header>

    <div id="sideNav" class="side-nav">
        <?php if ($current_page == 'index.php'): ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $base_path; ?>/users/profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="<?php echo $base_path; ?>/users/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>/users/register.php"><i class="fas fa-user-plus"></i> Register</a>
                <a href="<?php echo $base_path; ?>/users/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
            <a href="<?php echo $base_path; ?>/food_management/menu.php"><i class="fas fa-utensils"></i> Menu</a>
            <a href="<?php echo $base_path; ?>/cart/cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
        <?php else: ?>
            <a href="<?php echo $base_path; ?>/index.php"><i class="fas fa-home"></i> Home</a>
            <a href="<?php echo $base_path; ?>/food_management/menu.php"><i class="fas fa-utensils"></i> Menu</a>
            <a href="<?php echo $base_path; ?>/cart/cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $base_path; ?>/users/profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="<?php echo $base_path; ?>/users/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>/users/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="<?php echo $base_path; ?>/users/register.php"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>