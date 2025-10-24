CREATE DATABASE online_food_ordering_system; 
USE online_food_ordering_system;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL, 
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    contact_no VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    role ENUM('customer','admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    image VARCHAR(100) DEFAULT 'default.jpg',
    available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `food_id` INT(11) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT '1',
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `unique_user_food` (`user_id`, `food_id`),
  KEY `food_id` (`food_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 250.00,
    grand_total DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT 'Cash on Delivery',
    order_status ENUM('pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    delivery_address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    special_instructions TEXT,

    estimated_delivery_time DATETIME,
    actual_delivery_time DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Order items table (linking orders to foods)
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- Order status history for tracking changes
CREATE TABLE order_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    change_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Simple delivery tracking
CREATE TABLE delivery_tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    delivery_person_name VARCHAR(100),
    delivery_person_phone VARCHAR(15),
    current_location VARCHAR(255),
    estimated_arrival DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(order_status);
CREATE INDEX idx_orders_date ON orders(created_at);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);

-- Burgers and Sandwiches
INSERT INTO foods (name, description, price, category, image) VALUES
('Classic Cheeseburger', 'A juicy beef patty with melted cheese, lettuce, tomato, and onion.', 800.00, 'Burgers and Sandwiches', 'classic-cheeseburger.jpg'),
('BBQ Bacon Burger', 'A savory beef patty topped with crispy bacon and tangy BBQ sauce.', 950.00, 'Burgers and Sandwiches', 'bbq-bacon-burger.jpg'),
('Spicy Chicken Sandwich', 'Crispy chicken breast with a spicy kick, served on a toasted bun.', 750.00, 'Burgers and Sandwiches', 'spicy-chicken-sandwich.jpg'),
('Veggie Burger', 'A plant-based patty with fresh vegetables and a special sauce.', 700.00, 'Burgers and Sandwiches', 'veggie-burger.jpg');

-- Pizzas
INSERT INTO foods (name, description, price, category, image) VALUES
('Margherita Pizza', 'Classic pizza with fresh mozzarella, basil, and tomato sauce.', 1200.00, 'Pizzas', 'margherita-pizza.jpg'),
('Pepperoni Pizza', 'A classic favorite with generous slices of pepperoni.', 1400.00, 'Pizzas', 'pepperoni-pizza.jpg'),
('Supreme Pizza', 'Loaded with pepperoni, sausage, peppers, onions, and olives.', 1600.00, 'Pizzas', 'supreme-pizza.jpg'),
('Veggie Delight Pizza', 'Topped with bell peppers, mushrooms, onions, and black olives.', 1350.00, 'Pizzas', 'veggie-delight-pizza.jpg');

-- Pasta
INSERT INTO foods (name, description, price, category, image) VALUES
('Spaghetti Carbonara', 'Creamy pasta with pancetta, egg, and parmesan cheese.', 1100.00, 'Pasta', 'spaghetti-carbonara.jpg'),
('Fettuccine Alfredo', 'Fettuccine noodles tossed in a rich and creamy parmesan sauce.', 1000.00, 'Pasta', 'fettuccine-alfredo.jpg'),
('Penne Arrabiata', 'Penne pasta in a spicy tomato sauce with garlic and red chili flakes.', 950.00, 'Pasta', 'penne-arrabiata.jpg'),
('Lasagna', 'Layers of pasta, meat sauce, and creamy cheese, baked to perfection.', 1300.00, 'Pasta', 'lasagna.jpg');

-- Appetizers
INSERT INTO foods (name, description, price, category, image) VALUES
('Garlic Breadsticks', 'Warm breadsticks brushed with garlic butter and herbs.', 450.00, 'Appetizers', 'garlic-breadsticks.jpg'),
('Chicken Wings', 'Crispy chicken wings with your choice of BBQ or hot sauce.', 850.00, 'Appetizers', 'chicken-wings.jpg'),
('Mozzarella Sticks', 'Fried mozzarella sticks with a side of marinara sauce.', 600.00, 'Appetizers', 'mozzarella-sticks.jpg'),
('Onion Rings', 'Crispy, golden-fried onion rings.', 500.00, 'Appetizers', 'onion-rings.jpg');

-- Desserts
INSERT INTO foods (name, description, price, category, image) VALUES
('Chocolate Lava Cake', 'A warm chocolate cake with a gooey, molten chocolate center.', 750.00, 'Desserts', 'chocolate-lava-cake.jpg'),
('New York Cheesecake', 'Rich and creamy cheesecake with a graham cracker crust.', 800.00, 'Desserts', 'new-york-cheesecake.jpg'),
('Tiramisu', 'A classic Italian dessert with coffee-soaked ladyfingers and mascarpone cream.', 900.00, 'Desserts', 'tiramisu.jpg'),
('Brownie Sundae', 'A warm chocolate brownie topped with vanilla ice cream and chocolate sauce.', 850.00, 'Desserts', 'brownie-sundae.jpg');