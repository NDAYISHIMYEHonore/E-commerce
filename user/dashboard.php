<?php
require_once '../config/database.php';
requireLogin();

// Prevent back button access after logout
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
        
        setInterval(function() {
            fetch('../check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.logged_in) {
                        window.location.href = '../login.php';
                    }
                });
        }, 30000);
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        body.dark-mode { background: #1a1a2e; color: #fff; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: linear-gradient(180deg, #4ecdc4, #3dbdb4); color: white; position: fixed; height: 100%; left: 0; top: 0; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-nav { padding: 20px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 14px 25px; color: white; text-decoration: none; gap: 12px; transition: 0.3s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.2); border-left: 4px solid white; }
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #fff; border-radius: 12px; margin-bottom: 30px; }
        .dark-mode .top-bar { background: #2a2a3e; }
        .mode-toggle { padding: 10px 20px; background: #ff6b6b; color: white; border: none; border-radius: 25px; cursor: pointer; }
        .welcome-card { background: linear-gradient(135deg, #ff6b6b, #4ecdc4); color: white; padding: 40px; border-radius: 20px; text-align: center; margin-bottom: 30px; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .feature-card { background: #fff; padding: 30px; border-radius: 15px; text-align: center; }
        .dark-mode .feature-card { background: #2a2a3e; }
        .feature-card i { font-size: 3rem; color: #ff6b6b; margin-bottom: 15px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .product-card { background: #fff; border-radius: 15px; overflow: hidden; }
        .dark-mode .product-card { background: #2a2a3e; }
        .product-image { height: 180px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
        .product-info { padding: 20px; }
        .product-price { font-size: 1.3rem; color: #ff6b6b; font-weight: bold; margin: 10px 0; }
        .btn-add { width: 100%; padding: 10px; background: #ff6b6b; color: white; border: none; border-radius: 8px; cursor: pointer; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar-header h3, .sidebar-nav a span { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header"><h3><i class="fas fa-store"></i> My Store</h3></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="#products" onclick="document.getElementById('productsSection').scrollIntoView({behavior: 'smooth'})"><i class="fas fa-shopping-bag"></i> <span>Products</span></a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
            <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <button class="mode-toggle" onclick="document.body.classList.toggle('dark-mode')"><i class="fas fa-moon"></i> Dark Mode</button>
            <div><span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>
        
        <div class="welcome-card">
            <h2>Welcome back, <?php echo htmlspecialchars($user['username']); ?>! 👋</h2>
            <p>Ready to discover amazing products?</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card"><i class="fas fa-truck-fast"></i><h3>Fast Delivery</h3><p>Get your orders within 3-5 days</p></div>
            <div class="feature-card"><i class="fas fa-undo-alt"></i><h3>Easy Returns</h3><p>30-day return policy</p></div>
            <div class="feature-card"><i class="fas fa-lock"></i><h3>Secure Payment</h3><p>100% secure transactions</p></div>
        </div>
        
        <div id="productsSection">
            <h2>Our Products</h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">🛍️</div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            <button class="btn-add" onclick="alert('Product added to cart!')"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
</body>
</html>