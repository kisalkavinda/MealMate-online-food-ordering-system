<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$base_path = '/MealMate-online-food-ordering-system';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - MealMate</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>/cart/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">MealMate</h1>
            <ul class="nav-menu">
                <li><a href="<?php echo $base_path; ?>/index.php">Home</a></li>
                <li><a href="<?php echo $base_path; ?>/food_management/menu.php">Menu</a></li>
                <li><a href="<?php echo $base_path; ?>/cart/cart.php" class="active">Cart</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $base_path; ?>/users/profile.php">Profile</a></li>
                    <li><a href="<?php echo $base_path; ?>/users/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $base_path; ?>/users/login.php">Login</a></li>
                    <li><a href="<?php echo $base_path; ?>/users/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
