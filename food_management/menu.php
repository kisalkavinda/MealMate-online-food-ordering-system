<?php
session_start();

include '../includes/menu_header.php';
require_once '../includes/db_connect.php';
require_once '../cart/cart_controller.php';

// Fetch cart items for initial page load if the user is logged in
$cart_items = [];
$cart_total = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_items = getCartItems($conn, $user_id);
    $cart_total = calculateCartTotal($conn, $user_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Menu - MealMate</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="menu-page">
    <!-- Beautiful Confirmation Modal -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="confirmation-content">
            <button class="close-confirm-btn" onclick="hideConfirmationModal()" aria-label="Close">&times;</button>
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

    <div class="sliding-cart-overlay" onclick="toggleCart()"></div>

    <div id="sliding-cart">
        <div class="cart-header">
            <h2>Your Cart</h2>
            <button class="close-cart-btn" onclick="toggleCart()" aria-label="Close cart">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="cart-items-container">
            <p class="empty-cart-message">Your cart is empty.</p>
        </div>
        <div class="cart-total-section">
            <span>Total:</span>
            <span class="total-price" id="cart-total-price">Rs.0.00</span>
        </div>
        <a href="../orders/checkout.php" class="btn-checkout">Proceed to Checkout</a>
    </div>

    <div class="container">
        <div class="header">
            <h2>🍕 Food Menu</h2>
            <p>Discover our delicious offerings</p>
            
            <!-- Search Control -->
            <div class="menu-controls">
                <div class="search-box">
                    <input type="text" id="menuSearch" placeholder="Search for your favorite dish..." aria-label="Search menu">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <!-- Category Button Bar -->
            <div class="category-buttons">
                <button class="category-btn active" data-category="all">
                    🍽️ All Menu
                </button>
                <button class="category-btn" data-category="burgers-and-sandwiches">
                    🍔 Burgers & Sandwiches
                </button>
                <button class="category-btn" data-category="pizzas">
                    🍕 Pizzas
                </button>
                <button class="category-btn" data-category="pastas">
                    🍝 Pastas
                </button>
                <button class="category-btn" data-category="appetizers">
                    🍟 Appetizers
                </button>
                <button class="category-btn" data-category="desserts">
                    🍰 Desserts
                </button>
            </div>
            
            <div class="cart-icon" id="main-cart-icon" onclick="toggleCart()" aria-label="Open cart">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>

        <!-- No Results Message -->
        <div class="no-results" id="noResultsMessage">
            <i class="fas fa-search"></i>
            <p>No items found matching your search.</p>
        </div>

        <?php
        if ($conn !== null && $conn->connect_error === null) {
            // Fetch distinct categories in a specific order
            $sql_categories = "SELECT DISTINCT category FROM foods WHERE available = 1
                               ORDER BY
                                   CASE category
                                       WHEN 'Burgers and Sandwiches' THEN 1
                                       WHEN 'Pizzas' THEN 2
                                       WHEN 'Pastas' THEN 3
                                       WHEN 'Appetizers' THEN 4
                                       WHEN 'Desserts' THEN 5
                                       ELSE 6
                                   END, category";
            $result_categories = $conn->query($sql_categories);

            if ($result_categories && $result_categories->num_rows > 0) {
                while ($category_row = $result_categories->fetch_assoc()) {
                    $category_name = $category_row['category'];
                    $category_id = strtolower(str_replace(' ', '-', $category_name));
                    
                    // Normalize category data attribute to match button values
                    $category_data = strtolower(str_replace(' ', '-', $category_name));
                    // Handle "Pasta" vs "Pastas" inconsistency
                    if ($category_data === 'pasta') {
                        $category_data = 'pastas';
                    }
                    
                    // Category icon mapping
                    $category_icons = [
                        'Burgers and Sandwiches' => '🍔',
                        'Pizzas' => '🍕',
                        'Pastas' => '🍝',
                        'Pasta' => '🍝',
                        'Appetizers' => '🍟',
                        'Desserts' => '🍰'
                    ];
                    $icon = $category_icons[$category_name] ?? '🍽️';

                    echo '<div class="category-section" id="' . htmlspecialchars($category_id) . '" data-category="' . htmlspecialchars($category_data) . '">';
                    echo '<h2 class="category-title">' . $icon . ' ' . htmlspecialchars($category_name) . '</h2>';
                    echo '<div class="menu-grid">';

                    // Fetch food items for the current category
                    $sql_foods = "SELECT * FROM foods WHERE available = 1 AND category = ? ORDER BY name";
                    $stmt_foods = $conn->prepare($sql_foods);
                    $stmt_foods->bind_param("s", $category_name);
                    $stmt_foods->execute();
                    $result_foods = $stmt_foods->get_result();

                    if ($result_foods && $result_foods->num_rows > 0) {
                        while ($food_row = $result_foods->fetch_assoc()) {
                            // Determine the correct image folder name based on the category name
                            $image_folder_name = strtolower($food_row['category']);
                            if ($image_folder_name === 'burgers and sandwiches') {
                                $image_folder_name = 'burgers';
                            } elseif ($image_folder_name === 'pasta') {
                                $image_folder_name = 'pastas';
                            }

                            // Construct the image path relative to the menu.php file
                            $image_path = '../assets/images/menu/' . $image_folder_name . '/' . $food_row['image'];

                            echo '
                            <div class="menu-item">
                                <div class="food-image">
                                    <img src="' . htmlspecialchars($image_path) . '" 
                                        alt="' . htmlspecialchars($food_row["name"]) . '"
                                        loading="lazy"
                                        onerror="this.src=\'../assets/images/menu/default.jpg\';">
                                </div>
                                <h3>' . htmlspecialchars($food_row["name"]) . '</h3>
                                <p>' . htmlspecialchars($food_row["description"]) . '</p>
                                <div class="item-footer">
                                    <span class="price">Rs.' . htmlspecialchars($food_row["price"]) . '</span>
                                    <button class="add-to-cart" onclick="addToCart(' . $food_row["id"] . ', this)" aria-label="Add ' . htmlspecialchars($food_row["name"]) . ' to cart">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>';
                        }
                    }
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-results show">';
                echo '<i class="fas fa-utensils"></i>';
                echo '<p>No food items are currently available.</p>';
                echo '</div>';
            }
        } else {
            echo '<div class="no-results show">';
            echo '<i class="fas fa-exclamation-circle"></i>';
            echo '<p>Error: Could not connect to the database.</p>';
            echo '</div>';
        }
        ?>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle-container">
        <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
            <i class="fas fa-sun theme-icon sun-icon"></i>
            <i class="fas fa-moon theme-icon moon-icon"></i>
        </button>
    </div>

    <!-- Footer -->
<?php include '../includes/simple_footer.php'; ?>

    <!-- JavaScript -->
    <script src="menu.js"></script>
    <script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>
</body>
</html>