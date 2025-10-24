<?php 
include 'includes/header.php';
?>
<section id="home" class="section home">
    <div class="intro-text">
        <h1>Welcome to MealMate</h1>
        <p>Delicious food delivered to your doorstep!</p>
    </div>
</section>

<!-- Services -->
<section id="services" class="section services">
    <h2>Our Services</h2>
    <div class="features-wrapper">
        <div class="features">
            <div class="feature-box">
                <img src="assets/images/slide1.png" alt="Easy Ordering">
                <h3>Easy Ordering</h3>
                <p>Order your favorite meals in just a few clicks!</p>
            </div>
            <div class="feature-box">
                <img src="assets/images/slide2.jpg" alt="Real-Time Tracking">
                <h3>Real-Time Tracking</h3>
                <p>Track your order live from kitchen to your door!</p>
            </div>
            <div class="feature-box">
                <img src="assets/images/slide3.jpg" alt="Secure Payments">
                <h3>Secure Payments</h3>
                <p>Pay safely online using our secure payment system.</p>
            </div>
            <div class="feature-box">
                <img src="assets/images/slide4.jpg" alt="Delicious Meals">
                <h3>Delicious Meals Delivered</h3>
                <p>Fresh and tasty meals delivered straight to you!</p>
            </div>
        </div>
    </div>
</section>

<!-- About -->
<section id="about" class="section about">
    <h2>About Us</h2>
    <p>We are committed to delivering fresh and tasty meals from your favorite restaurants.</p>
</section>

<!-- Reviews -->
<section id="reviews" class="section reviews">
    <h2>Customer Reviews</h2>
    <div class="review-list">
        <div class="review-card">
            <p>"Amazing food and quick delivery!"</p>
            <span>- Jane</span>
        </div>
        <div class="review-card">
            <p>"Great experience, will order again!"</p>
            <span>- Mike</span>
        </div>
        <div class="review-card">
            <p>"Highly recommended, excellent service!"</p>
            <span>- Sarah</span>
        </div>
    </div>
</section>

<!-- Contact -->
<section id="contact" class="section contact">
    <h2>Contact Us</h2>
    <div id="contactMessage"></div>
    <form id="contactForm" method="POST" action="contact.php">
        <input type="text" name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Your Email" required>
        <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
        <button type="submit" name="submit">Send Message</button>
    </form>
</section>

<!-- Theme Toggle -->
<div class="theme-toggle-container">
    <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
        <i class="fas fa-sun theme-icon sun-icon"></i>
        <i class="fas fa-moon theme-icon moon-icon"></i>
    </button>
</div>

<?php include 'includes/footer.php'; ?>

<script src="index.js" defer></script>
<script src="theme-toggle.js" defer></script>
</body>
</html>
