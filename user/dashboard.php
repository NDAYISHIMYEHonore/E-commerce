<?php
require_once '../config/database.php';
requireLogin();

if (isAdmin()) { header('Location: ../admin/dashboard.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();

function formatRWF($amount) { return number_format($amount, 0, ',', '.') . ' Frw'; }

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check->execute([$username, $email, $_SESSION['user_id']]);
    
    if ($check->fetch()) {
        $profile_error = 'Username or email already exists!';
    } else {
        $profile_pic = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $profile_pic = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_pic);
        }
        
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?");
        if ($stmt->execute([$username, $email, $profile_pic, $_SESSION['user_id']])) {
            $_SESSION['username'] = $username;
            $_SESSION['profile_pic'] = $profile_pic;
            $profile_success = 'Profile updated successfully!';
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }
    
    if (!empty($new_password)) {
        if (password_verify($current_password, $user['password'])) {
            if (strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $_SESSION['user_id']]);
                $profile_success = 'Profile and password updated!';
            } else {
                $profile_error = 'Password must be at least 6 characters!';
            }
        } elseif (!empty($current_password)) {
            $profile_error = 'Current password is incorrect!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - E-Commerce Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; transition: all 0.3s ease; }
        body.dark-mode { background: #1a1a2e; color: #fff; }
        
        .dashboard-container { display: flex; min-height: 100vh; }
        
        /* Sidebar with Profile on Top */
        .sidebar { width: 280px; background: linear-gradient(180deg, #4ecdc4, #3dbdb4); color: white; position: fixed; height: 100%; overflow-y: auto; }
        .sidebar-profile { text-align: center; padding: 30px 20px; border-bottom: 1px solid rgba(255,255,255,0.2); cursor: pointer; transition: 0.3s; }
        .sidebar-profile:hover { background: rgba(255,255,255,0.1); }
        .sidebar-avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 15px; background: white; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 3px solid white; }
        .sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar-avatar i { font-size: 3rem; color: #4ecdc4; }
        .sidebar-name { font-size: 1.2rem; font-weight: bold; margin-bottom: 5px; }
        .sidebar-email { font-size: 0.8rem; opacity: 0.9; }
        .sidebar-role { display: inline-block; background: rgba(255,255,255,0.2); padding: 3px 12px; border-radius: 20px; font-size: 0.7rem; margin-top: 8px; }
        
        .sidebar-nav { padding: 20px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 12px 25px; color: white; text-decoration: none; gap: 12px; transition: 0.3s; cursor: pointer; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.2); border-left: 4px solid white; }
        .sidebar-nav a i { width: 24px; }
        
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        
        /* Header */
        .top-bar { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dark-mode .top-bar { background: #2a2a3e; }
        .store-header-title { display: flex; align-items: center; gap: 10px; }
        .store-header-title h1 { font-size: 1.5rem; color: #ff6b6b; }
        .cart-icon { position: relative; cursor: pointer; font-size: 1.3rem; color: #ff6b6b; }
        .cart-count-badge { position: absolute; top: -8px; right: -12px; background: #ff6b6b; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.7rem; min-width: 18px; text-align: center; }
        .mode-toggle { background: #ff6b6b; color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; }
        
        /* Welcome Card */
        .welcome-card { background: linear-gradient(135deg, #ff6b6b, #4ecdc4); color: white; padding: 40px; border-radius: 20px; margin-bottom: 30px; text-align: center; }
        .welcome-card h2 { font-size: 2rem; margin-bottom: 10px; }
        .btn-shop { background: white; color: #ff6b6b; border: none; padding: 12px 30px; border-radius: 25px; font-weight: bold; cursor: pointer; }
        
        /* Features */
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .feature-card { background: white; padding: 30px; border-radius: 15px; text-align: center; transition: 0.3s; cursor: pointer; }
        .dark-mode .feature-card { background: #2a2a3e; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .feature-card i { font-size: 3rem; color: #ff6b6b; margin-bottom: 15px; }
        
        /* Products */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; margin-top: 20px; }
        .product-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: 0.3s; }
        .dark-mode .product-card { background: #2a2a3e; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .product-image { height: 200px; overflow: hidden; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .product-card:hover .product-image img { transform: scale(1.05); }
        .product-info { padding: 20px; }
        .product-category { display: inline-block; background: #e0e0e0; color: #666; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; margin-bottom: 10px; }
        .product-price { font-size: 1.5rem; color: #ff6b6b; font-weight: bold; margin: 10px 0; }
        .btn-add { width: 100%; padding: 10px; background: #ff6b6b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-add:hover { transform: translateY(-2px); }
        
        /* Cart */
        .cart-table { width: 100%; background: white; border-radius: 12px; overflow: hidden; border-collapse: collapse; }
        .dark-mode .cart-table { background: #2a2a3e; }
        .cart-table th, .cart-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .cart-table th { background: #ff6b6b; color: white; }
        .qty-btn { padding: 5px 10px; background: #ff6b6b; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 0 3px; }
        .cart-total { text-align: right; padding: 20px; font-size: 1.2rem; font-weight: bold; }
        .cart-item-image { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; margin-right: 10px; vertical-align: middle; }
        .empty-state { text-align: center; padding: 60px; color: #999; }
        
        /* Payment Modal */
        .payment-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); align-items: center; justify-content: center; }
        .payment-modal-content { background: white; border-radius: 20px; width: 90%; max-width: 400px; animation: slideIn 0.3s ease; }
        .dark-mode .payment-modal-content { background: #2a2a3e; }
        .payment-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .payment-header h3 { color: #ff6b6b; }
        .close-payment { font-size: 28px; cursor: pointer; }
        .payment-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .payment-summary { background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 20px 0; }
        .dark-mode .payment-summary { background: #1a1a2e; }
        .payment-summary p { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .payment-total { font-size: 1.2rem; font-weight: bold; color: #ff6b6b; border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px; }
        .btn-pay { width: 100%; padding: 12px; background: #ff6b6b; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-pay:hover { transform: translateY(-2px); }
        .payment-loader { display: none; text-align: center; padding: 20px; }
        .payment-loader i { font-size: 2rem; animation: spin 1s linear infinite; }
        
        /* History */
        .history-grid { display: grid; gap: 15px; }
        .history-card { background: white; border-radius: 12px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dark-mode .history-card { background: #2a2a3e; }
        .history-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #ff6b6b; }
        .history-id { font-weight: bold; color: #ff6b6b; font-size: 0.9rem; }
        .history-status { background: #4caf50; color: white; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; }
        .history-item { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; font-size: 0.85rem; }
        
        /* Profile Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); align-items: center; justify-content: center; }
        .modal-content { background: white; width: 90%; max-width: 500px; border-radius: 20px; animation: slideIn 0.3s ease; }
        .dark-mode .modal-content { background: #2a2a3e; }
        .modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { color: #ff6b6b; }
        .close-modal { font-size: 28px; cursor: pointer; }
        .modal-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-primary { background: #ff6b6b; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.3s; }
        .btn-primary:hover { background: #ee5a5a; transform: translateY(-1px); }
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .profile-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto 15px; display: block; border: 3px solid #ff6b6b; }
        
        .view { display: none; }
        .view.active { display: block; animation: fadeIn 0.3s ease; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-profile .sidebar-name, .sidebar-profile .sidebar-email, .sidebar-profile .sidebar-role, .sidebar-nav a span { display: none; }
            .sidebar-avatar { width: 40px; height: 40px; }
            .sidebar-avatar i { font-size: 1.5rem; }
            .main-content { margin-left: 70px; }
            .products-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar with Profile on Top -->
    <div class="sidebar">
        <div class="sidebar-profile" onclick="openProfileModal()">
            <div class="sidebar-avatar">
                <?php if ($user['profile_picture'] && file_exists('../assets/uploads/' . $user['profile_picture'])): ?>
                    <img src="../assets/uploads/<?php echo $user['profile_picture']; ?>" alt="Profile">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-name"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="sidebar-email"><?php echo htmlspecialchars($user['email']); ?></div>
            <div class="sidebar-role"><i class="fas fa-user"></i> Customer</div>
        </div>
        <nav class="sidebar-nav">
            <a class="active" onclick="showView('dashboard')"><i class="fas fa-home"></i> <span>Dashboard</span></a>
            <a onclick="showView('products')"><i class="fas fa-shopping-bag"></i> <span>Products</span></a>
            <a onclick="showView('cart')"><i class="fas fa-shopping-cart"></i> <span>Cart</span></a>
            <a onclick="showView('history')"><i class="fas fa-history"></i> <span>History</span></a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <div class="store-header-title"><i class="fas fa-store"></i><h1>My Store</h1></div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="cart-icon" onclick="showView('cart')"><i class="fas fa-shopping-cart"></i><span class="cart-count-badge" id="cartCount">0</span></div>
                <button class="mode-toggle" onclick="toggleDarkMode()"><i class="fas fa-moon"></i> Dark Mode</button>
            </div>
        </div>
        
        <!-- Dashboard View -->
        <div id="dashboardView" class="view active">
            <div class="welcome-card">
                <h2>Murakaza neza, <?php echo htmlspecialchars($user['username']); ?>! 🇷🇼</h2>
                <p>Shop amazing products with Mobile Money (MTN Rwanda / Airtel Rwanda)</p>
                <button class="btn-shop" onclick="showView('products')">Start Shopping →</button>
            </div>
            <div class="features-grid">
                <div class="feature-card" onclick="showView('products')"><i class="fas fa-shopping-bag"></i><h3>Shop Now</h3><p>Browse our collection</p></div>
                <div class="feature-card" onclick="showView('cart')"><i class="fas fa-shopping-cart"></i><h3>My Cart</h3><p>View selected items</p></div>
                <div class="feature-card" onclick="showView('history')"><i class="fas fa-history"></i><h3>History</h3><p>Track purchases</p></div>
            </div>
        </div>
        
        <!-- Products View -->
        <div id="productsView" class="view">
            <h2 class="section-title"><i class="fas fa-fire"></i> Our Products</h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image"><img src="../assets/uploads/<?php echo $product['image']; ?>" onerror="this.src='https://placehold.co/300x200?text=Product'"></div>
                        <div class="product-info">
                            <span class="product-category"><?php echo ucfirst($product['category']); ?></span>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><small><?php echo htmlspecialchars(substr($product['description'], 0, 60)); ?>...</small></p>
                            <div class="product-price"><?php echo formatRWF($product['price']); ?></div>
                            <button class="btn-add" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, '../assets/uploads/<?php echo $product['image']; ?>')"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Cart View -->
        <div id="cartView" class="view"><h2 class="section-title"><i class="fas fa-shopping-cart"></i> Your Cart</h2><div id="cartItemsContainer"></div></div>
        
        <!-- History View -->
        <div id="historyView" class="view"><h2 class="section-title"><i class="fas fa-history"></i> Purchase History</h2><div id="historyContainer"></div></div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="payment-modal">
    <div class="payment-modal-content">
        <div class="payment-header"><h3><i class="fas fa-mobile-alt"></i> Complete Your Purchase</h3><span class="close-payment" onclick="closePaymentModal()">&times;</span></div>
        <div class="payment-body">
            <div class="form-group"><label><i class="fas fa-phone"></i> Phone Number</label><input type="tel" id="phoneNumber" placeholder="0788XXXXXX"><small>Enter your MTN or Airtel Rwanda number</small></div>
            <div class="form-group"><label><i class="fas fa-building"></i> Mobile Money Provider</label><select id="provider"><option value="MTN Rwanda">📱 MTN Rwanda (MoMo)</option><option value="Airtel Rwanda">📱 Airtel Rwanda (Airtel Money)</option></select></div>
            <div class="payment-summary" id="paymentSummary">
                <p><span>Subtotal:</span> <span id="paymentSubtotal">0 Frw</span></p>
                <p><span>Delivery Fee:</span> <span>1,500 Frw</span></p>
                <p><span>Tax (10%):</span> <span id="paymentTax">0 Frw</span></p>
                <div class="payment-total"><span>Total to Pay:</span> <span id="paymentTotal">0 Frw</span></div>
            </div>
            <div id="paymentLoader" class="payment-loader"><i class="fas fa-spinner"></i><p>Processing your order...</p></div>
            <button class="btn-pay" onclick="processPayment()"><i class="fas fa-check-circle"></i> Confirm Order</button>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="fas fa-user-circle"></i> My Profile</h3><span class="close-modal" onclick="closeProfileModal()">&times;</span></div>
        <div class="modal-body">
            <?php if (isset($profile_success)): ?>
                <div class="alert alert-success"><?php echo $profile_success; ?></div>
            <?php endif; ?>
            <?php if (isset($profile_error)): ?>
                <div class="alert alert-error"><?php echo $profile_error; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div style="text-align: center;">
                    <img src="../assets/uploads/<?php echo $user['profile_picture'] ?? 'default-avatar.png'; ?>" class="profile-preview" id="profilePreview" onerror="this.src='https://placehold.co/100x100?text=User'">
                    <input type="file" name="profile_picture" id="profilePicInput" accept="image/*" style="display: none;">
                    <button type="button" class="btn-primary" style="width: auto; padding: 8px 20px; margin-bottom: 15px;" onclick="document.getElementById('profilePicInput').click()">Change Photo</button>
                </div>
                <div class="form-group"><label>Username</label><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                <div class="form-group"><label>Current Password (to change password)</label><input type="password" name="current_password" placeholder="Leave blank to keep current password"></div>
                <div class="form-group"><label>New Password</label><input type="password" name="new_password" placeholder="Min 6 characters"></div>
                <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" placeholder="Confirm new password"></div>
                <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<script>
const DELIVERY_FEE = 1500;
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function formatRWF(amount) { return amount.toLocaleString('en-RW') + ' Frw'; }
function updateCartCount() { document.getElementById('cartCount').innerHTML = cart.reduce((s, i) => s + i.quantity, 0); }

function addToCart(id, name, price, image) {
    let existing = cart.find(i => i.id === id);
    if (existing) existing.quantity++;
    else cart.push({ id, name, price, quantity: 1, image });
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    alert(name + ' added to cart!');
}

function displayCart() {
    const container = document.getElementById('cartItemsContainer');
    if (cart.length === 0) { container.innerHTML = '<div class="empty-state"><i class="fas fa-shopping-cart fa-3x"></i><p>Your cart is empty</p><button class="btn-shop" onclick="showView(\'products\')">Continue Shopping</button></div>'; return; }
    let total = 0, html = '<table class="cart-table"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr></thead><tbody>';
    cart.forEach((item, i) => {
        let itemTotal = item.price * item.quantity;
        total += itemTotal;
        html += `<tr><td><img src="${item.image}" class="cart-item-image"> ${item.name}</td><td>${formatRWF(item.price)}</td><td><button class="qty-btn" onclick="updateQty(${i}, -1)">-</button> ${item.quantity} <button class="qty-btn" onclick="updateQty(${i}, 1)">+</button></td><td>${formatRWF(itemTotal)}</td>。<button class="qty-btn" onclick="removeItem(${i})" style="background:#f44336;"><i class="fas fa-trash"></i></button></td></tr>`;
    });
    let tax = total * 0.1, grandTotal = total + DELIVERY_FEE + tax;
    html += `</tbody></table><div class="cart-total"><p>Subtotal: ${formatRWF(total)}</p><p>Delivery: ${formatRWF(DELIVERY_FEE)}</p><p>Tax: ${formatRWF(tax)}</p><h3>Total: ${formatRWF(grandTotal)}</h3></div><button class="btn-shop" onclick="openPaymentModal(${grandTotal})" style="width:100%"><i class="fas fa-mobile-alt"></i> Proceed to Payment</button>`;
    container.innerHTML = html;
}

function updateQty(i, change) { cart[i].quantity += change; if (cart[i].quantity <= 0) cart.splice(i, 1); localStorage.setItem('cart', JSON.stringify(cart)); displayCart(); updateCartCount(); }
function removeItem(i) { cart.splice(i, 1); localStorage.setItem('cart', JSON.stringify(cart)); displayCart(); updateCartCount(); }

function openPaymentModal(total) {
    let subtotal = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
    document.getElementById('paymentSubtotal').innerHTML = formatRWF(subtotal);
    document.getElementById('paymentTax').innerHTML = formatRWF(subtotal * 0.1);
    document.getElementById('paymentTotal').innerHTML = formatRWF(total);
    document.getElementById('paymentModal').style.display = 'flex';
}
function closePaymentModal() { document.getElementById('paymentModal').style.display = 'none'; document.getElementById('phoneNumber').value = ''; }

function processPayment() {
    let phone = document.getElementById('phoneNumber').value;
    let provider = document.getElementById('provider').value;
    if (!phone.match(/^(078|079|072|073)[0-9]{7}$/)) { alert('Enter valid Rwanda phone number'); return; }
    let subtotal = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
    let grandTotal = subtotal + DELIVERY_FEE + (subtotal * 0.1);
    document.getElementById('paymentLoader').style.display = 'block';
    document.querySelector('.btn-pay').disabled = true;
    let formData = new FormData();
    formData.append('user_id', <?php echo $_SESSION['user_id']; ?>);
    formData.append('username', '<?php echo $_SESSION['username']; ?>');
    formData.append('user_email', '<?php echo $user['email']; ?>');
    formData.append('user_phone', phone);
    formData.append('provider', provider);
    formData.append('total_amount', grandTotal);
    formData.append('items', JSON.stringify(cart));
    fetch('../process_payment.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            document.getElementById('paymentLoader').style.display = 'none';
            document.querySelector('.btn-pay').disabled = false;
            if (data.success) {
                cart = []; localStorage.setItem('cart', JSON.stringify(cart));
                updateCartCount(); displayCart(); loadPurchaseHistory(); showView('history');
                closePaymentModal();
                alert('✅ Order placed! Order ID: ' + data.order_id);
            } else alert('❌ Failed to place order');
        });
}

function loadPurchaseHistory() {
    fetch('../get_purchases.php?user_id=<?php echo $_SESSION['user_id']; ?>')
        .then(res => res.json())
        .then(data => {
            let container = document.getElementById('historyContainer');
            if (data.length === 0) { container.innerHTML = '<div class="empty-state"><i class="fas fa-history fa-3x"></i><p>No purchases yet</p><button class="btn-shop" onclick="showView(\'products\')">Start Shopping</button></div>'; return; }
            let html = '<div class="history-grid">';
            data.forEach(p => {
                let itemsHtml = '';
                p.items.forEach(item => { itemsHtml += `<div class="history-item"><span>${item.product_name} x ${item.quantity}</span><span>${formatRWF(item.price * item.quantity)}</span></div>`; });
                html += `<div class="history-card"><div class="history-header"><span class="history-id">${p.order_id}</span><span class="history-status">${p.status}</span></div>
                        <div><small>📅 ${p.date}</small></div>
                        <div><small>📱 ${p.user_phone} (${p.provider})</small></div>
                        <div class="history-items">${itemsHtml}</div>
                        <div class="payment-total">Total: ${formatRWF(p.total_amount)}</div></div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        });
}

function showView(view) {
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    document.getElementById(view + 'View').classList.add('active');
    document.querySelectorAll('.sidebar-nav a').forEach(link => link.classList.remove('active'));
    if (view === 'dashboard') document.querySelector('.sidebar-nav a:first-child').classList.add('active');
    if (view === 'products') document.querySelectorAll('.sidebar-nav a')[1].classList.add('active');
    if (view === 'cart') document.querySelectorAll('.sidebar-nav a')[2].classList.add('active');
    if (view === 'history') document.querySelectorAll('.sidebar-nav a')[3].classList.add('active');
    if (view === 'cart') displayCart();
    if (view === 'history') loadPurchaseHistory();
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}
function openProfileModal() { document.getElementById('profileModal').style.display = 'flex'; }
function closeProfileModal() { document.getElementById('profileModal').style.display = 'none'; }

document.getElementById('profilePicInput')?.addEventListener('change', function(e) {
    if (e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(event) { document.getElementById('profilePreview').src = event.target.result; }
        reader.readAsDataURL(e.target.files[0]);
    }
});

window.onclick = function(e) {
    if (e.target === document.getElementById('paymentModal')) closePaymentModal();
    if (e.target === document.getElementById('profileModal')) closeProfileModal();
}
if (localStorage.getItem('darkMode') === 'true') document.body.classList.add('dark-mode');
updateCartCount();
</script>
</body>
</html>