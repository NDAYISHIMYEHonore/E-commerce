<?php
require_once 'config/database.php';

$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; color: #333; }
        body.dark-mode { background: #1a1a2e; color: #fff; }
        
        /* Navbar */
        .navbar { background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        .dark-mode .navbar { background: #2a2a3e; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .nav-logo { font-size: 1.5rem; font-weight: bold; color: #ff6b6b; text-decoration: none; }
        .nav-menu { display: flex; gap: 2rem; align-items: center; }
        .nav-link { text-decoration: none; color: #333; transition: 0.3s; }
        .dark-mode .nav-link { color: #fff; }
        .nav-link:hover { color: #ff6b6b; }
        .nav-user { display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #ff6b6b; color: #fff; border-radius: 25px; }
        
        /* Container */
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        /* Hero Section */
        .hero-section { background: linear-gradient(135deg, #ff6b6b, #4ecdc4); color: white; padding: 80px 40px; border-radius: 20px; text-align: center; margin-bottom: 50px; }
        .hero-section h1 { font-size: 3rem; margin-bottom: 20px; }
        .hero-section p { font-size: 1.2rem; max-width: 600px; margin: 0 auto 30px; }
        .hero-buttons { display: flex; gap: 20px; justify-content: center; }
        
        /* Buttons */
        .btn { padding: 12px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; transition: 0.3s; display: inline-block; border: none; cursor: pointer; }
        .btn-primary { background: white; color: #ff6b6b; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
        .btn-secondary { background: transparent; color: white; border: 2px solid white; }
        .btn-secondary:hover { background: white; color: #ff6b6b; transform: translateY(-2px); }
        
        /* Products */
        .products-section h2 { text-align: center; margin-bottom: 30px; font-size: 2.5rem; }
        .category-tabs { text-align: center; margin-bottom: 30px; }
        .tab-btn { padding: 10px 25px; margin: 0 10px; border: none; background: #fff; cursor: pointer; border-radius: 25px; transition: 0.3s; }
        .dark-mode .tab-btn { background: #2a2a3e; color: #fff; }
        .tab-btn.active, .tab-btn:hover { background: #ff6b6b; color: white; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        .product-card { background: #fff; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: 0.3s; }
        .dark-mode .product-card { background: #2a2a3e; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .product-image { width: 100%; height: 200px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 4rem; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-info { padding: 20px; }
        .product-info h3 { margin-bottom: 10px; }
        .product-description { color: #666; font-size: 0.9rem; margin-bottom: 10px; }
        .dark-mode .product-description { color: #ccc; }
        .product-price { font-size: 1.5rem; color: #ff6b6b; font-weight: bold; margin: 10px 0; }
        .btn-add-to-cart { width: 100%; padding: 10px; background: #ff6b6b; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; transition: 0.3s; }
        .btn-add-to-cart:hover { background: #ee5a5a; transform: translateY(-2px); }
        
        /* Footer */
        .footer { background: #2c3e50; color: white; margin-top: 50px; }
        .dark-mode .footer { background: #16213e; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 50px 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; }
        .footer-section h3, .footer-section h4 { margin-bottom: 20px; }
        .footer-section a { display: block; color: #ecf0f1; text-decoration: none; margin-bottom: 10px; opacity: 0.8; }
        .footer-section a:hover { opacity: 1; color: #ff6b6b; }
        .social-links { display: flex; gap: 15px; }
        .social-links a { font-size: 1.5rem; }
        .footer-bottom { text-align: center; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        
        /* Mode Toggle Button */
        .mode-toggle-btn { position: fixed; bottom: 20px; right: 20px; background: #ff6b6b; color: white; border: none; padding: 12px 20px; border-radius: 30px; cursor: pointer; z-index: 999; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-container { flex-direction: column; gap: 15px; }
            .nav-menu { flex-wrap: wrap; justify-content: center; }
            .hero-section h1 { font-size: 2rem; }
            .hero-buttons { flex-direction: column; }
            .products-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo"><i class="fas fa-store"></i> E-Commerce Store</a>
        <div class="nav-menu">
            <a href="index.php" class="nav-link">Home</a>
            <a href="#products" class="nav-link">Products</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" class="nav-link">Admin Panel</a>
                <?php else: ?>
                    <a href="user/dashboard.php" class="nav-link">My Account</a>
                <?php endif; ?>
                <a href="logout.php" class="nav-link">Logout</a>
                <span class="nav-user"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
                <a href="register.php" class="nav-link">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main>
    <div class="container">
        <div class="hero-section">
            <h1>Discover Amazing Products</h1>
            <p>Shop from variety of items at the best prices. Quality products, fast delivery, and easy returns. Enjoy the best shopping experience.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                </div>
            <?php else: ?>
                <div class="hero-buttons">
                    <a href="#products" class="btn btn-primary">Shop Now</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="products-section" id="products">
            <h2>Our Products</h2>
            <div class="category-tabs">
                <button class="tab-btn active" data-category="all">All Products</button>
                <button class="tab-btn" data-category="shoes">Shoes</button>
                <button class="tab-btn" data-category="clothing">Clothing</button>
            </div>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-category="<?php echo $product['category']; ?>">
                        <div class="product-image">
                            <img src="assets/uploads/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" onerror="this.src='https://via.placeholder.com/200'">
                        </div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            <?php if (isset($_SESSION['user_id']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')): ?>
                                <button class="btn-add-to-cart" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>', <?php echo $product['price']; ?>)">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <a href="login.php" class="btn-add-to-cart" style="display: block; text-align: center; text-decoration: none;">Login to Purchase</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3><i class="fas fa-store"></i> E-Commerce Store</h3>
            <p>Your one-stop shop for amazing products.</p>
        </div>
        <div class="footer-section">
            <h4>Quick Links</h4>
            <a href="index.php">Home</a>
            <a href="#products">Products</a>
        </div>
        <div class="footer-section">
            <h4>Contact</h4>
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

<button class="mode-toggle-btn" onclick="toggleDarkMode()"><i class="fas fa-moon"></i> Dark/Light Mode</button>

<script>
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    }
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
    
    // Category filter
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const category = this.dataset.category;
            document.querySelectorAll('.product-card').forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Cart functions
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    function addToCart(id, name, price) {
        const existing = cart.find(item => item.id === id);
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({ id, name, price, quantity: 1 });
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        alert(name + ' added to cart!');
    }
</script>
</body>
</html>