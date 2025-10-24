/**
 * Asynchronously updates a food item's quantity in the shopping cart.
 * This function sends an AJAX request to the server and updates the UI based on the response.
 * @param {number} cartId The unique ID of the item in the cart.
 * @param {number} change The quantity to add or subtract (e.g., 1 or -1).
 */
async function updateQuantity(cartId, change) {
    try {
        const response = await fetch('update_cart_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_id=${cartId}&change=${change}`
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Update quantity response:', result);

        if (result.success) {
            updateCartUI(result);
            if (result.action === 'removed') {
                showNotification('Item removed from cart.');
            }
        } else {
            showNotification(`Error: ${result.message}`, 'error');
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
        showNotification('Network error occurred. Please try again.', 'error');
    }
}

/**
 * Shows a confirmation dialog before removing an item from the cart
 * @param {number} cartId The unique ID of the item in the cart.
 * @param {string} itemName The name of the item to be removed.
 */
function showRemoveConfirm(cartId, itemName = 'this item') {
    showConfirmationModal(
        'Remove Item',
        `Are you sure you want to remove "${itemName}" from your cart?`,
        async () => {
            await removeFromCart(cartId);
        }
    );
}

/**
 * Asynchronously removes a food item from the shopping cart after confirmation.
 * @param {number} cartId The unique ID of the item in the cart.
 */
async function removeFromCart(cartId) {
    try {
        const response = await fetch('remove_from_cart_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_id=${cartId}`
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Remove from cart response:', result);

        if (result.success) {
            updateCartUI(result);
            showNotification('Item removed successfully!');
        } else {
            showNotification(`Error: ${result.message}`, 'error');
        }
    } catch (error) {
        console.error('Error removing item:', error);
        showNotification('Network error occurred. Please try again.', 'error');
    }
}

/**
 * Updates the cart's user interface with new data received from the server.
 * @param {object} data The response data from the server, containing updated cart information.
 */
function updateCartUI(data) {
    const container = document.getElementById('cart-items-container');
    const subtotalSpan = document.getElementById('cart-subtotal');
    const totalSpan = document.getElementById('cart-total');

    console.log('Updating cart UI with data:', data);

    // Clear existing items
    if (container) {
        container.innerHTML = '';
    }

    if (!data.items || data.items.length === 0) {
        // Show empty cart state
        showEmptyCartState();
        if (subtotalSpan) subtotalSpan.textContent = 'Rs.0.00';
        if (totalSpan) totalSpan.textContent = 'Rs.250.00';
        return;
    }

    // Hide empty cart page and show cart items
    hideEmptyCartState();

    // Populate the cart items list
    data.items.forEach(item => {
        const itemHTML = createCartItemHTML(item);
        if (container) {
            container.innerHTML += itemHTML;
        }
    });

    // Update totals
    const deliveryFee = 250.00;
    const newTotal = parseFloat(data.total) + deliveryFee;

    if (subtotalSpan) subtotalSpan.textContent = `Rs.${parseFloat(data.total).toFixed(2)}`;
    if (totalSpan) totalSpan.textContent = `Rs.${newTotal.toFixed(2)}`;
}

/**
 * Creates HTML for a single cart item
 * @param {object} item The cart item object
 * @returns {string} HTML string for the cart item
 */
function createCartItemHTML(item) {
    const itemTotal = (item.price * item.quantity).toFixed(2);
    const itemPrice = parseFloat(item.price).toFixed(2);
    
    return `
        <div class="cart-item" data-cart-id="${item.cart_id}">
            <div class="item-image">
                <img src="../assets/images/menu/${item.image}" alt="${escapeHtml(item.food_name)}" 
                     onerror="this.src='../assets/images/menu/default.jpg'; this.onerror=null;">
            </div>
            <div class="item-details">
                <h3>${escapeHtml(item.food_name)}</h3>
                <p class="item-description">${escapeHtml(item.description || 'Delicious food item')}</p>
                <p class="item-price">Rs.${itemPrice} each</p>
            </div>
            <div class="quantity-controls">
                <button class="qty-btn" onclick="updateQuantity(${item.cart_id}, -1)" aria-label="Decrease quantity">-</button>
                <input type="text" value="${item.quantity}" class="qty-input" readonly>
                <button class="qty-btn" onclick="updateQuantity(${item.cart_id}, 1)" aria-label="Increase quantity">+</button>
            </div>
            <div class="item-total">Rs.${itemTotal}</div>
            <div class="delete-btn-container">
                <button class="delete-btn" onclick="showRemoveConfirm(${item.cart_id}, '${escapeHtml(item.food_name)}')" aria-label="Remove item">
                    <i class="fas fa-trash"></i>
                    <span class="tooltip">Remove Item</span>
                </button>
            </div>
        </div>
    `;
}

/**
 * Shows the empty cart state
 */
function showEmptyCartState() {
    const emptyCartPage = document.querySelector('.empty-cart-page');
    const cartItemsDiv = document.querySelector('.cart-items');
    const cartSummary = document.querySelector('.cart-summary');
    
    if (emptyCartPage) {
        emptyCartPage.style.display = 'flex';
    }
    if (cartItemsDiv) {
        cartItemsDiv.style.display = 'none';
    }
    if (cartSummary) {
        cartSummary.style.display = 'none';
    }
}

/**
 * Hides the empty cart state
 */
function hideEmptyCartState() {
    const emptyCartPage = document.querySelector('.empty-cart-page');
    const cartItemsDiv = document.querySelector('.cart-items');
    const cartSummary = document.querySelector('.cart-summary');
    
    if (emptyCartPage) {
        emptyCartPage.style.display = 'none';
    }
    if (cartItemsDiv) {
        cartItemsDiv.style.display = 'block';
    }
    if (cartSummary) {
        cartSummary.style.display = 'block';
    }
}

/**
 * Escapes HTML characters to prevent XSS
 * @param {string} text The text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Shows a notification message
 * @param {string} message The message to show
 * @param {string} type The type of notification (success, error)
 */
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
            <span>${message}</span>
        </div>
    `;

    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'error' ? 'linear-gradient(135deg, #dc3545, #ff6b6b)' : 'linear-gradient(135deg, #28a745, #20c997)'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        min-width: 250px;
    `;

    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    `;
    document.head.appendChild(style);

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
        if (style.parentNode) {
            style.remove();
        }
    }, 3000);
}

/**
 * Shows a confirmation modal
 * @param {string} title The modal title
 * @param {string} message The confirmation message
 * @param {function} onConfirm Callback function when confirmed
 */
function showConfirmationModal(title, message, onConfirm) {
    const modal = document.getElementById('confirmationModal');
    const titleElement = document.querySelector('.confirmation-title');
    const messageElement = document.getElementById('confirmationMessage');
    const confirmBtn = document.getElementById('confirmRemove');
    const cancelBtn = document.getElementById('cancelRemove');

    if (!modal || !messageElement) {
        // Fallback to browser confirm
        if (confirm(message)) {
            onConfirm();
        }
        return;
    }

    if (titleElement) titleElement.textContent = title;
    messageElement.textContent = message;
    modal.classList.add('active');
    modal.style.display = 'block';

    // Remove previous event listeners by cloning the button
    if (confirmBtn) {
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        newConfirmBtn.addEventListener('click', () => {
            hideConfirmationModal();
            onConfirm();
        });
    }

    if (cancelBtn) {
        const newCancelBtn = cancelBtn.cloneNode(true);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        
        newCancelBtn.addEventListener('click', hideConfirmationModal);
    }
}

/**
 * Hides the confirmation modal
 */
function hideConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

/**
 * Initializes cart page functionality
 */
function initializeCart() {
    // Set up modal close handlers
    const modal = document.getElementById('confirmationModal');
    const closeBtn = document.querySelector('.close-confirm-btn');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', hideConfirmationModal);
    }

    // Close modal when clicking outside
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                hideConfirmationModal();
            }
        });
    }

    // Handle keyboard events
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideConfirmationModal();
        }
    });

    // Initialize quantity input validation
    document.addEventListener('input', function(event) {
        if (event.target.classList.contains('qty-input')) {
            const value = parseInt(event.target.value);
            if (isNaN(value) || value < 1) {
                event.target.value = 1;
            } else if (value > 99) {
                event.target.value = 99;
            }
        }
    });

    console.log('Cart initialized successfully');
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeCart);

// Legacy support for existing modal functions
function showModal(message) {
    showNotification(message);
}

function showConfirmModal(message, onConfirm) {
    showConfirmationModal('Confirm Action', message, onConfirm);
}

function closeModal() {
    hideConfirmationModal();
}