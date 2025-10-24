// Global variable to store the cart ID for removal confirmation
let pendingCartId = null;

// ===== ALERT FUNCTION =====
// Shows a temporary notification message (success or error)
function showAlert(message, isError = false) {
    // Create a new div element for the alert
    const alertBox = document.createElement('div');
    alertBox.textContent = message;
    alertBox.classList.add('custom-alert');
    
    // Add error class if it's an error message
    if (isError) {
        alertBox.classList.add('error');
    }
    
    // Add the alert to the page
    document.body.appendChild(alertBox);

    // Auto-remove the alert after 3 seconds
    setTimeout(() => {
        alertBox.style.opacity = '0'; // Fade out
        setTimeout(() => alertBox.remove(), 500); // Remove from DOM
    }, 3000);
}

// ===== CONFIRMATION MODAL FUNCTIONS =====
// Shows a confirmation dialog before removing items from cart
function showConfirmationModal(cartId, itemName) {
    const modal = document.getElementById('confirmationModal');
    const message = document.getElementById('confirmationMessage');
    
    // Set the confirmation message with item name
    if (itemName) {
        message.textContent = `Are you sure you want to remove "${itemName}" from your cart?`;
    } else {
        message.textContent = 'Are you sure you want to remove this item from your cart?';
    }
    
    // Store the cart ID for later use
    pendingCartId = cartId;
    // Show the modal
    modal.classList.add('active');
}

// Hides the confirmation modal
function hideConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    modal.classList.remove('active');
    pendingCartId = null; // Clear the pending cart ID
}

// ===== CART BADGE UPDATE =====
// Updates the cart item count badge on the cart icon
function updateCartBadge(count) {
    let badge = document.querySelector('.cart-badge');
    const cartIcon = document.getElementById('main-cart-icon');
    
    if (count > 0) {
        // Create badge container if it doesn't exist
        if (!badge) {
            let container = document.querySelector('.cart-badge-container');
            if (!container && cartIcon) {
                // Create container to hold cart icon and badge
                container = document.createElement('div');
                container.className = 'cart-badge-container';
                cartIcon.parentNode.insertBefore(container, cartIcon);
                container.appendChild(cartIcon);
            }
            
            // Create the badge element
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            if (container) {
                container.appendChild(badge);
            }
        }
        // Update badge count
        badge.textContent = count;
    } else if (badge) {
        // Remove badge if cart is empty
        badge.remove();
    }
}

// ===== UPDATE SLIDING CART =====
// Fetches and displays cart items in the sliding cart panel
function updateSlidingCart() {
    // Make API call to get cart items
    fetch('../cart/cart_items_api.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok.');
            }
            return response.json(); // Parse JSON response
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to load cart data');
            }

            const cartContainer = document.getElementById('cart-items-container');
            const cartTotal = document.getElementById('cart-total-price');
            cartContainer.innerHTML = ''; // Clear existing items

            // Update cart badge with item count
            updateCartBadge(data.items.length);

            // Check if cart is empty
            if (data.items.length === 0) {
                cartContainer.innerHTML = '<p class="empty-cart-message">Your cart is empty.</p>';
                cartTotal.textContent = 'Rs.0.00';
            } else {
                // Loop through each cart item and create HTML
                data.items.forEach(item => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'cart-item';
                    itemDiv.setAttribute('data-cart-id', item.cart_id);
                    
                    // Build the item HTML with image, name, price, and controls
                    itemDiv.innerHTML = `
                        <div class="cart-item-image">
                            <img src="../assets/images/menu/${item.image}" alt="${item.food_name}" 
                                 onerror="this.src='../assets/images/menu/default.jpg';">
                        </div>
                        <div class="cart-item-details">
                            <h4>${item.food_name}</h4>
                            <p class="price">Rs.${parseFloat(item.price).toFixed(2)} each</p>
                            <div class="cart-item-controls">
                                <button class="cart-qty-btn" onclick="updateCartQuantity(${item.cart_id}, -1)" aria-label="Decrease quantity">-</button>
                                <span class="cart-item-quantity">${item.quantity}</span>
                                <button class="cart-qty-btn" onclick="updateCartQuantity(${item.cart_id}, 1)" aria-label="Increase quantity">+</button>
                                <button class="cart-remove-btn" onclick="showConfirmationModal(${item.cart_id}, '${item.food_name.replace(/'/g, "\\'")}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    cartContainer.appendChild(itemDiv);
                });
                // Update total price
                cartTotal.textContent = `Rs.${parseFloat(data.total).toFixed(2)}`;
            }
        })
        .catch(error => {
            console.error('Error fetching cart data:', error);
            showAlert('Failed to load cart.', true);
        });
}

// ===== UPDATE CART QUANTITY =====
// Increases or decreases the quantity of an item in cart
function updateCartQuantity(cartId, change) {
    // Send request to update quantity API
    fetch('../cart/update_cart_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_id=${cartId}&change=${change}` // Send cart ID and change amount
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSlidingCart(); // Refresh cart display
            if (data.action === 'removed') {
                showAlert('Item removed from cart.');
            }
        } else {
            showAlert(`Error: ${data.message}`, true);
        }
    })
    .catch(error => {
        console.error('Error updating quantity:', error);
        showAlert('Network error occurred. Please try again.', true);
    });
}

// ===== REMOVE ITEM FROM CART =====
// Completely removes an item from the cart
function removeCartItem(cartId) {
    // Send request to remove item API
    fetch('../cart/remove_from_cart_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_id=${cartId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSlidingCart(); // Refresh cart display
            showAlert('Item removed from cart successfully!');
        } else {
            showAlert(`Error: ${data.message}`, true);
        }
    })
    .catch(error => {
        console.error('Error removing item:', error);
        showAlert('Network error occurred. Please try again.', true);
    });
}

// ===== ADD TO CART FUNCTION =====
// Adds a food item to the user's cart with visual feedback
function addToCart(foodId, buttonElement) {
    // Show loading state on button
    if (buttonElement) {
        buttonElement.disabled = true; // Disable to prevent double-clicks
        const originalText = buttonElement.innerHTML;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        
        // Send request to add item to cart
        fetch('../cart/add_to_cart_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `food_id=${foodId}&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Added to cart successfully!');
                updateSlidingCart(); // Refresh cart
                
                // Show success state on button
                buttonElement.innerHTML = '<i class="fas fa-check"></i> Added!';
                setTimeout(() => {
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalText; // Restore original text
                }, 1500);
            } else {
                // Check if user needs to login
                if (data.message.includes('login') || data.message.includes('logged')) {
                    showAlert('Please log in to add items to cart.', true);
                    setTimeout(() => {
                        window.location.href = '../users/login.php'; // Redirect to login
                    }, 2000);
                } else {
                    showAlert('Error: ' + data.message, true);
                }
                
                buttonElement.disabled = false;
                buttonElement.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            showAlert('Failed to connect to server.', true);
            
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalText;
        });
    }
}

// ===== TOGGLE CART PANEL =====
// Opens and closes the sliding cart panel
function toggleCart() {
    const cart = document.getElementById('sliding-cart');
    const overlay = document.querySelector('.sliding-cart-overlay');
    const cartIcon = document.getElementById('main-cart-icon');
    
    // Toggle open class
    cart.classList.toggle('open');
    overlay.style.display = cart.classList.contains('open') ? 'block' : 'none';
    
    if (cart.classList.contains('open')) {
        cartIcon.classList.add('hidden'); // Hide floating cart icon
        updateSlidingCart(); // Load cart items
        document.body.style.overflow = 'hidden'; // Prevent body scroll
    } else {
        cartIcon.classList.remove('hidden'); // Show floating cart icon
        document.body.style.overflow = ''; // Restore body scroll
    }
}

// ===== SEARCH AND FILTER INITIALIZATION =====
// Sets up event listeners for search and category filters
function initializeSearch() {
    const searchInput = document.getElementById('menuSearch');
    const categoryButtons = document.querySelectorAll('.category-btn');
    
    // Add input event listener for search
    if (searchInput) {
        searchInput.addEventListener('input', filterMenu);
    }
    
    // Add click event listeners for category buttons
    if (categoryButtons.length > 0) {
        categoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                // Filter menu based on selection
                filterMenu();
            });
        });
    }
}

// ===== FILTER MENU ITEMS =====
// Filters menu items based on search term and selected category
function filterMenu() {
    const searchInput = document.getElementById('menuSearch');
    const activeButton = document.querySelector('.category-btn.active');
    const noResultsDiv = document.getElementById('noResultsMessage');
    
    // Get current search term (lowercase and trimmed)
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    // Get selected category from active button
    const selectedCategory = activeButton ? activeButton.getAttribute('data-category') : 'all';
    
    let visibleCount = 0;
    const categorySections = document.querySelectorAll('.category-section');
    
    // Loop through each category section
    categorySections.forEach(section => {
        const sectionCategory = section.getAttribute('data-category');
        const menuItems = section.querySelectorAll('.menu-item');
        let visibleInCategory = 0;
        
        // Loop through each menu item in the section
        menuItems.forEach(item => {
            const itemName = item.querySelector('h3').textContent.toLowerCase();
            const itemDesc = item.querySelector('p').textContent.toLowerCase();
            
            // Check if item matches search term
            const matchesSearch = searchTerm === '' || 
                                itemName.includes(searchTerm) || 
                                itemDesc.includes(searchTerm);
            // Check if item matches selected category
            const matchesCategory = selectedCategory === 'all' || 
                                   sectionCategory === selectedCategory;
            
            // Show or hide item based on filters
            if (matchesSearch && matchesCategory) {
                item.style.display = 'flex';
                visibleInCategory++;
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Hide category section if no items are visible
        section.style.display = visibleInCategory > 0 ? 'block' : 'none';
    });
    
    // Show/hide "no results" message
    if (noResultsDiv) {
        if (visibleCount === 0) {
            noResultsDiv.classList.add('show');
        } else {
            noResultsDiv.classList.remove('show');
        }
    }
}

// ===== BACK TO TOP BUTTON =====
// Creates and manages the back to top button
function initializeBackToTop() {
    let backToTopBtn = document.querySelector('.back-to-top');
    
    // Create button if it doesn't exist
    if (!backToTopBtn) {
        backToTopBtn = document.createElement('button');
        backToTopBtn.className = 'back-to-top';
        backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        backToTopBtn.setAttribute('aria-label', 'Back to top');
        document.body.appendChild(backToTopBtn);
    }
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) { // Show after scrolling 300px
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });
    
    // Scroll to top when clicked
    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth' // Smooth scrolling animation
        });
    });
}

// ===== INITIALIZATION ON PAGE LOAD =====
// Runs when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // Load cart items on page load
    updateSlidingCart();
    
    // Initialize search and filter functionality
    initializeSearch();
    
    // Initialize back to top button
    initializeBackToTop();
    
    // ===== CONFIRMATION MODAL EVENT LISTENERS =====
    const confirmBtn = document.getElementById('confirmRemove');
    const cancelBtn = document.getElementById('cancelRemove');
    const modal = document.getElementById('confirmationModal');
    const closeBtn = document.querySelector('.close-confirm-btn');
    
    // Confirm button - remove item from cart
    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            if (pendingCartId) {
                removeCartItem(pendingCartId);
                hideConfirmationModal();
            }
        });
    }
    
    // Cancel button - close modal without removing
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideConfirmationModal);
    }
    
    // Close button (X) - close modal
    if (closeBtn) {
        closeBtn.addEventListener('click', hideConfirmationModal);
    }
    
    // Click outside modal to close
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target.id === 'confirmationModal') {
                hideConfirmationModal();
            }
        });
    }
    
    // ===== KEYBOARD SHORTCUTS =====
    // Close cart or modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const cart = document.getElementById('sliding-cart');
            // Close cart if open
            if (cart && cart.classList.contains('open')) {
                toggleCart();
            }
            // Close modal if open
            if (modal && modal.classList.contains('active')) {
                hideConfirmationModal();
            }
        }
    });
    
    // ===== UPDATE ADD TO CART BUTTONS =====
    // Replace onclick attributes with proper event listeners
    document.querySelectorAll('.add-to-cart').forEach(button => {
        const originalOnclick = button.getAttribute('onclick');
        if (originalOnclick) {
            // Extract food ID from onclick attribute
            const foodId = originalOnclick.match(/\d+/)[0];
            button.removeAttribute('onclick');
            // Add new event listener that passes button element
            button.addEventListener('click', function() {
                addToCart(foodId, this);
            });
        }
    });
});