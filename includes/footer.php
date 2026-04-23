<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3><i class="fas fa-store"></i> E-Commerce Store</h3>
            <p>Your one-stop shop for amazing products at best prices.</p>
        </div>
        <div class="footer-section">
            <h4>Quick Links</h4>
            <a href="index.php">Home</a>
            <a href="index.php#products">Products</a>
            <?php if (isLoggedIn()): ?>
                <a href="user/dashboard.php">My Account</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
        <div class="footer-section">
            <h4>Contact Us</h4>
            <p><i class="fas fa-envelope"></i> support@ecommerce.com</p>
            <p><i class="fas fa-phone"></i> +1 234 567 890</p>
        </div>
        <div class="footer-section">
            <h4>Follow Us</h4>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2024 E-Commerce Store. All rights reserved.</p>
    </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>