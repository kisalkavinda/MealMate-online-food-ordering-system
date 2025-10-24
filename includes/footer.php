<style>
    /* === Footer Styles with Theme Support === */
    footer {
        background-color: var(--footer-bg);
        backdrop-filter: blur(10px);
        border-top: 2px solid var(--border-color);
        padding: 50px 30px 20px;
        color: var(--text-primary);
        font-family: 'Poppins', sans-serif;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .footer-container {
        width: 0 auto;
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 40px;
    }

    .footer-section {
        flex: 1;
        min-width: 220px;
        text-align: center;
    }

    .footer-section h3 {
        color: var(--accent-primary);
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 18px;
        position: relative;
        padding-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
        width: 100%;
        transition: color 0.3s ease;
    }

    .footer-section h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 2px;
        background-color: var(--accent-primary);
        border-radius: 2px;
        transition: background-color 0.3s ease;
    }

    .footer-section p,
    .footer-section ul,
    .footer-section a {
        font-size: 14px;
        color: var(--text-muted);
        line-height: 1.7;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .footer-section p {
        margin-bottom: 12px;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-section ul li {
        margin-bottom: 10px;
    }

    .footer-section a:hover {
        color: var(--accent-primary);
    }

    /* Footer Logo Section */
    .footer-logo {
        color: var(--accent-primary);
        font-size: 28px;
        font-weight: 700;
        text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        margin-bottom: 12px;
        display: inline-block;
        position: relative;
        padding-bottom: 6px;
        width: 100%;
        transition: color 0.3s ease;
    }

    [data-theme="light"] .footer-logo {
        text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
    }

    .footer-logo::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 2px;
        background-color: var(--accent-primary);
        border-radius: 2px;
        transition: background-color 0.3s ease;
    }

    /* Social Icons */
    .social-icons {
        display: flex;
        gap: 15px;
        margin-top: 18px;
        justify-content: center;
    }

    .social-icons a {
        color: var(--text-primary);
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border: 1px solid var(--footer-border);
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .social-icons a:hover {
        color: var(--accent-primary);
        border-color: var(--accent-primary);
        transform: translateY(-3px);
        box-shadow: 0 4px 12px var(--shadow-color);
    }

    /* Copyright Section */
    .footer-bottom {
        text-align: center;
        border-top: 1px solid var(--footer-border);
        padding-top: 20px;
        margin-top: 40px;
        font-size: 13px;
        color: var(--text-muted);
        letter-spacing: 0.5px;
        transition: border-color 0.3s ease, color 0.3s ease;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .footer-container {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
    }
</style>

<footer>
    <div class="footer-container">
        <!-- About Section -->
        <div class="footer-section">
            <h1 class="footer-logo">MealMate</h1>
            <p>Your ultimate destination for delicious food delivered right to your doorstep. We are committed to
                providing the best dining experience.</p>
            <div class="social-icons">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>

        <!-- Quick Links Section -->
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="#">Home</a></li>
                <li><a href="/MealMate-online-food-ordering-system/food_management/menu.php">Menu</a></li>
                <li><a href="#">Orders</a></li>
                <li><a href="#">Contact Us</a></li>
                <li><a href="#">Privacy Policy</a></li>
            </ul>
        </div>

        <!-- Contact Section -->
        <div class="footer-section">
            <h3>Contact Info</h3>
            <p><strong>Address:</strong><br>123 Foodie Street,<br>Cuisine City, 12345</p>
            <p><strong>Phone:</strong> 091 1234567</p>
            <p><strong>Email:</strong> info@mealmate.com</p>
        </div>

        <!-- Working Hours Section -->
        <div class="footer-section">
            <h3>Working Hours</h3>
            <p>
                Monday - Friday: 9am - 10pm<br>
                Saturday: 10am - 11pm<br>
                Sunday: 10am - 9pm
            </p>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; <?= date('Y') ?> MealMate. All rights reserved.
    </div>

</footer>