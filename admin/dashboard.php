<?php
require_once '../config/database.php';
requireAdmin();

// ========== FIX FOR DUPLICATE PRODUCTS ON REFRESH ==========
$flash_message = '';
$flash_type = '';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    
    $image_filename = 'default-product.jpg';
    $upload_error = null;
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        // Get file info
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $original_name = $_FILES['product_image']['name'];
        $file_size = $_FILES['product_image']['size'];
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        // ALLOW ALL IMAGE TYPES - Expanded list
        $allowed = [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 
            'bmp', 'svg', 'ico', 'tiff', 'tif',
            'jfif', 'pjpeg', 'pjp'
        ];
        
        // Check file size (max 10MB)
        if ($file_size > 10 * 1024 * 1024) {
            $upload_error = "File is too large. Maximum size is 10MB.";
        }
        // Check if it's actually an image using getimagesize
        else if (in_array($file_extension, $allowed)) {
            // Verify it's a valid image
            $check_image = @getimagesize($file_tmp);
            if ($check_image !== false) {
                // Create unique filename
                $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $image_filename = $new_filename;
                } else {
                    $upload_error = "Failed to upload image. Check folder permissions.";
                }
            } else {
                $upload_error = "File is not a valid image.";
            }
        } else {
            $upload_error = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF, WEBP, BMP, SVG";
        }
    } else {
        // If no image uploaded, use default
        $image_filename = 'default-product.jpg';
    }
    
    if (!$upload_error) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $price, $category, $image_filename])) {
            $_SESSION['flash_message'] = 'Product added successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to add product to database.';
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = $upload_error;
        $_SESSION['flash_type'] = 'error';
    }
    
    // Redirect to prevent duplicate on refresh
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Handle Delete Product
if (isset($_GET['delete_product'])) {
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete_product']]);
    $product = $stmt->fetch();
    
    if ($product && $product['image'] !== 'default-product.jpg') {
        $file_path = '../assets/uploads/' . $product['image'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$_GET['delete_product']])) {
        $_SESSION['flash_message'] = 'Product deleted successfully!';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to delete product!';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Handle Delete User
if (isset($_GET['delete_user']) && $_GET['delete_user'] != $_SESSION['user_id']) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
    if ($stmt->execute([$_GET['delete_user']])) {
        $_SESSION['flash_message'] = 'User deleted successfully!';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to delete user!';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    
    $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check->execute([$username, $email, $_SESSION['user_id']]);
    
    if ($check->fetch()) {
        $_SESSION['flash_message'] = 'Username or email already exists!';
        $_SESSION['flash_type'] = 'error';
    } else {
        $profile_pic = $admin['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
            if (in_array($ext, $allowed)) {
                $profile_pic = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_pic);
            }
        }
        
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?");
        if ($stmt->execute([$username, $email, $profile_pic, $_SESSION['user_id']])) {
            $_SESSION['username'] = $username;
            $_SESSION['profile_pic'] = $profile_pic;
            $_SESSION['flash_message'] = 'Profile updated successfully!';
            $_SESSION['flash_type'] = 'success';
        }
    }
    
    if (!empty($new_password)) {
        if (password_verify($current_password, $admin['password'])) {
            if (strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $_SESSION['user_id']]);
                $_SESSION['flash_message'] = 'Profile and password updated successfully!';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Password must be at least 6 characters!';
                $_SESSION['flash_type'] = 'error';
            }
        } else {
            $_SESSION['flash_message'] = 'Current password is incorrect!';
            $_SESSION['flash_type'] = 'error';
        }
    }
    
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Get flash messages from session
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Get statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalPurchases = $pdo->query("SELECT COUNT(*) FROM purchases")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(total_amount) FROM purchases")->fetchColumn();

// Get today's sales
$todaySales = $pdo->query("SELECT SUM(total_amount) FROM purchases WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayCount = $pdo->query("SELECT COUNT(*) FROM purchases WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Get this month's sales
$monthSales = $pdo->query("SELECT SUM(total_amount) FROM purchases WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$monthCount = $pdo->query("SELECT COUNT(*) FROM purchases WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

// Get this year's sales
$yearSales = $pdo->query("SELECT SUM(total_amount) FROM purchases WHERE YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$yearCount = $pdo->query("SELECT COUNT(*) FROM purchases WHERE YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

// Get all purchases with user details
$allPurchases = $pdo->query("
    SELECT p.*, 
           GROUP_CONCAT(CONCAT(pi.product_name, ' (x', pi.quantity, ')') SEPARATOR ', ') as items
    FROM purchases p
    LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
")->fetchAll();

// Get monthly sales data for graph (last 12 months)
$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M Y', strtotime("-$i months"));
    $sales = $pdo->query("SELECT SUM(total_amount) FROM purchases WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetchColumn();
    $monthlyData[] = ['month' => $monthName, 'sales' => (float)$sales ?: 0];
}

// Get products sales data
$topProducts = $pdo->query("
    SELECT pi.product_name, SUM(pi.quantity) as total_sold, SUM(pi.price * pi.quantity) as total_revenue
    FROM purchase_items pi
    GROUP BY pi.product_name
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll();

// Get recent purchases
$recentPurchases = $pdo->query("
    SELECT p.*, 
           GROUP_CONCAT(CONCAT(pi.product_name, ' (x', pi.quantity, ')') SEPARATOR ', ') as items
    FROM purchases p
    LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 10
")->fetchAll();

// Get all products
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();

// Get all users
$users = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC")->fetchAll();

// Get admin data for sidebar profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

function formatRWF($amount) {
    return number_format($amount, 0, ',', '.') . ' Frw';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Commerce Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; transition: all 0.3s ease; }
        body.dark-mode { background: #1a1a2e; color: #fff; }
        
        .dashboard-container { display: flex; min-height: 100vh; }
        
        /* Sidebar with Profile on Top */
        .sidebar { width: 280px; background: linear-gradient(180deg, #ff6b6b, #ee5a5a); color: white; position: fixed; height: 100%; overflow-y: auto; z-index: 100; }
        .sidebar-profile { text-align: center; padding: 30px 20px; border-bottom: 1px solid rgba(255,255,255,0.2); cursor: pointer; transition: 0.3s; }
        .sidebar-profile:hover { background: rgba(255,255,255,0.1); }
        .sidebar-avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 15px; background: white; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 3px solid white; }
        .sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar-avatar i { font-size: 3rem; color: #ff6b6b; }
        .sidebar-name { font-size: 1.2rem; font-weight: bold; margin-bottom: 5px; }
        .sidebar-email { font-size: 0.8rem; opacity: 0.9; word-break: break-all; }
        .sidebar-role { display: inline-block; background: rgba(255,255,255,0.2); padding: 3px 12px; border-radius: 20px; font-size: 0.7rem; margin-top: 8px; }
        
        .sidebar-nav { padding: 20px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 12px 25px; color: white; text-decoration: none; gap: 12px; transition: 0.3s; cursor: pointer; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.2); border-left: 4px solid white; }
        .sidebar-nav a i { width: 24px; }
        
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        .top-bar { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dark-mode .top-bar { background: #2a2a3e; }
        .mode-toggle { background: #ff6b6b; color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; transition: 0.3s; }
        .mode-toggle:hover { transform: translateY(-2px); }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 15px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: 0.3s; }
        .dark-mode .stat-card { background: #2a2a3e; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #ff6b6b, #4ecdc4); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
        .stat-info h3 { font-size: 24px; }
        .stat-info p { color: #666; font-size: 0.85rem; }
        .dark-mode .stat-info p { color: #ccc; }
        
        /* Section Titles */
        .section-title { margin: 30px 0 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 10px; border-left: 4px solid #ff6b6b; padding-left: 15px; }
        
        /* Charts Grid */
        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .chart-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dark-mode .chart-card { background: #2a2a3e; }
        .chart-card h3 { margin-bottom: 15px; font-size: 1.1rem; }
        canvas { max-height: 300px; width: 100%; }
        
        /* Sales Summary */
        .sales-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .summary-card { background: white; border-radius: 15px; padding: 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dark-mode .summary-card { background: #2a2a3e; }
        .summary-card h4 { color: #666; margin-bottom: 10px; }
        .summary-card .amount { font-size: 1.8rem; font-weight: bold; color: #ff6b6b; }
        .summary-card .count { color: #4caf50; margin-top: 5px; }
        
        /* Tables */
        .data-table { width: 100%; background: white; border-radius: 12px; overflow: hidden; border-collapse: collapse; margin-bottom: 20px; }
        .dark-mode .data-table { background: #2a2a3e; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .dark-mode .data-table th, .dark-mode .data-table td { border-bottom-color: #555; }
        .data-table th { background: #ff6b6b; color: white; font-weight: 600; }
        .data-table tr:hover { background: rgba(0,0,0,0.02); }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; display: inline-block; }
        .badge-completed { background: #4caf50; color: white; }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; }
        .btn-icon { background: none; border: none; cursor: pointer; font-size: 1.1rem; padding: 5px; border-radius: 5px; transition: 0.2s; }
        .btn-delete { color: #f44336; }
        .btn-delete:hover { background: #ffebee; transform: scale(1.1); }
        .phone-cell { font-family: monospace; font-weight: bold; color: #2196f3; }
        
        /* Tabs */
        .tab-buttons { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; flex-wrap: wrap; }
        .tab-btn { background: none; border: none; padding: 10px 25px; cursor: pointer; font-size: 1rem; border-radius: 8px; transition: 0.2s; font-weight: 500; }
        .tab-btn:hover { background: #f0f0f0; }
        .dark-mode .tab-btn:hover { background: #3a3a4e; }
        .tab-btn.active { background: #ff6b6b; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); align-items: center; justify-content: center; }
        .modal-content { background: white; width: 90%; max-width: 500px; border-radius: 20px; animation: slideIn 0.3s ease; }
        .dark-mode .modal-content { background: #2a2a3e; }
        .modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { color: #ff6b6b; }
        .close-modal { font-size: 28px; cursor: pointer; transition: 0.3s; }
        .close-modal:hover { color: #ff6b6b; }
        .modal-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .dark-mode .form-group input, .dark-mode .form-group textarea, .dark-mode .form-group select { background: #3a3a4e; color: white; border-color: #555; }
        .btn-primary { background: #ff6b6b; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.3s; }
        .btn-primary:hover { background: #ee5a5a; transform: translateY(-1px); }
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .profile-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto 15px; display: block; border: 3px solid #ff6b6b; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-profile .sidebar-name, .sidebar-profile .sidebar-email, .sidebar-profile .sidebar-role, .sidebar-nav a span { display: none; }
            .sidebar-avatar { width: 40px; height: 40px; }
            .sidebar-avatar i { font-size: 1.5rem; }
            .main-content { margin-left: 70px; }
            .stats-grid { grid-template-columns: 1fr; }
            .charts-grid { grid-template-columns: 1fr; }
            .sales-summary { grid-template-columns: 1fr; }
            .data-table { font-size: 0.7rem; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar with Profile on Top -->
    <div class="sidebar">
        <div class="sidebar-profile" onclick="openProfileModal()">
            <div class="sidebar-avatar">
                <?php if ($admin['profile_picture'] && file_exists('../assets/uploads/' . $admin['profile_picture'])): ?>
                    <img src="../assets/uploads/<?php echo $admin['profile_picture']; ?>" alt="Profile">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-name"><?php echo htmlspecialchars($admin['username']); ?></div>
            <div class="sidebar-email"><?php echo htmlspecialchars($admin['email']); ?></div>
            <div class="sidebar-role"><i class="fas fa-shield-alt"></i> Administrator</div>
        </div>
        <nav class="sidebar-nav">
            <a class="active" onclick="showTab('dashboard')"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a onclick="showTab('reports')"><i class="fas fa-chart-line"></i> <span>Reports & Analytics</span></a>
            <a onclick="showTab('purchases')"><i class="fas fa-shopping-cart"></i> <span>Customer Purchases</span></a>
            <a onclick="showTab('products')"><i class="fas fa-box"></i> <span>Manage Products</span></a>
            <a onclick="showTab('users')"><i class="fas fa-users"></i> <span>Manage Users</span></a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-chart-line"></i> Admin Control Panel</h2>
            <button class="mode-toggle" onclick="toggleDarkMode()"><i class="fas fa-moon"></i> Dark Mode</button>
        </div>
        
        <?php if (isset($message) && $message): ?>
            <div class="alert alert-<?php echo $message_type ?? 'success'; ?>">
                <i class="fas <?php echo ($message_type ?? 'success') === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Dashboard Tab -->
        <div id="dashboardTab" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-info"><h3><?php echo $totalUsers; ?></h3><p>Total Users</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-box"></i></div><div class="stat-info"><h3><?php echo $totalProducts; ?></h3><p>Total Products</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-shopping-cart"></i></div><div class="stat-info"><h3><?php echo $totalPurchases; ?></h3><p>Total Orders</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div><div class="stat-info"><h3><?php echo formatRWF($totalRevenue ?? 0); ?></h3><p>Total Revenue</p></div></div>
            </div>
            
            <div class="sales-summary">
                <div class="summary-card">
                    <h4><i class="fas fa-calendar-day"></i> Today</h4>
                    <div class="amount"><?php echo formatRWF($todaySales ?? 0); ?></div>
                    <div class="count"><?php echo $todayCount; ?> orders</div>
                </div>
                <div class="summary-card">
                    <h4><i class="fas fa-calendar-month"></i> This Month</h4>
                    <div class="amount"><?php echo formatRWF($monthSales ?? 0); ?></div>
                    <div class="count"><?php echo $monthCount; ?> orders</div>
                </div>
                <div class="summary-card">
                    <h4><i class="fas fa-calendar-year"></i> This Year</h4>
                    <div class="amount"><?php echo formatRWF($yearSales ?? 0); ?></div>
                    <div class="count"><?php echo $yearCount; ?> orders</div>
                </div>
            </div>
            
            <div class="section-title"><i class="fas fa-clock"></i> Recent Orders</div>
            <table class="data-table">
                <thead><tr><th>Order ID</th><th>Customer</th><th>Phone</th><th>Products</th><th>Total</th><th>Date</th></tr></thead>
                <tbody>
                    <?php if (empty($recentPurchases)): ?>
                        <tr><td colspan="6" style="text-align: center;">No orders yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentPurchases as $purchase): ?>
                        <tr>
                            <td><?php echo $purchase['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($purchase['username']); ?></td>
                            <td><?php echo $purchase['user_phone']; ?></td>
                            <td><small><?php echo htmlspecialchars(substr($purchase['items'], 0, 40)); ?>...</small></td>
                            <td><?php echo formatRWF($purchase['total_amount']); ?></td>
                            <td><?php echo date('M d, H:i', strtotime($purchase['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Reports & Analytics Tab -->
        <div id="reportsTab" class="tab-content">
            <div class="section-title"><i class="fas fa-chart-bar"></i> Sales Analytics</div>
            
            <div class="charts-grid">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Monthly Sales (Last 12 Months)</h3>
                    <canvas id="monthlySalesChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Top Selling Products</h3>
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
            
            <div class="section-title"><i class="fas fa-table"></i> Detailed Sales Report</div>
            <table class="data-table">
                <thead>
                    <tr><th>Date</th><th>Orders</th><th>Total Sales</th><th>Average Order</th></tr>
                </thead>
                <tbody>
                    <?php
                    $dailySales = $pdo->query("
                        SELECT DATE(created_at) as date, COUNT(*) as order_count, SUM(total_amount) as total_sales, AVG(total_amount) as avg_order
                        FROM purchases GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30
                    ")->fetchAll();
                    ?>
                    <?php if (empty($dailySales)): ?>
                        <tr><td colspan="4" style="text-align: center;">No sales data available</td></tr>
                    <?php else: ?>
                        <?php foreach ($dailySales as $day): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                            <td><?php echo $day['order_count']; ?></td>
                            <td><?php echo formatRWF($day['total_sales']); ?></td>
                            <td><?php echo formatRWF($day['avg_order']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Customer Purchases Tab -->
        <div id="purchasesTab" class="tab-content">
            <div class="section-title"><i class="fas fa-shopping-cart"></i> All Customer Purchases</div>
            <?php if (empty($allPurchases)): ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 12px;">No purchases yet</div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Phone Number</th>
                            <th>Provider</th>
                            <th>Products Purchased</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allPurchases as $purchase): ?>
                        <tr>
                            <td><strong><?php echo $purchase['order_id']; ?></strong></td>
                            <td><i class="fas fa-user-circle"></i> <strong><?php echo htmlspecialchars($purchase['username']); ?></strong><br><small><?php echo $purchase['user_email']; ?></small></td>
                            <td class="phone-cell"><i class="fas fa-phone"></i> <?php echo $purchase['user_phone']; ?></td>
                            <td><i class="fas fa-building"></i> <?php echo $purchase['provider']; ?></td>
                            <td><small><?php echo htmlspecialchars($purchase['items']); ?></small></td>
                            <td><strong><?php echo formatRWF($purchase['total_amount']); ?></strong></td>
                            <td><?php echo date('M d, Y H:i', strtotime($purchase['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Products Tab -->
        <div id="productsTab" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-box"></i> Manage Products</h2>
                <button class="btn-primary" style="width: auto; padding: 10px 20px; background: #4caf50;" onclick="openAddProductModal()"><i class="fas fa-plus"></i> Add Product</button>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Image</th><th>Name</th><th>Description</th><th>Price</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if ($product['image'] != 'default-product.jpg' && file_exists('../assets/uploads/' . $product['image'])): ?>
                                <img src="../assets/uploads/<?php echo $product['image']; ?>" class="product-img" alt="<?php echo $product['name']; ?>">
                            <?php else: ?>
                                <div class="product-img" style="background: #e0e0e0;"><i class="fas fa-image" style="color: #999;"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                        <td><small><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small></td>
                        <td><?php echo formatRWF($product['price']); ?></td>
                        <td><a href="?delete_product=<?php echo $product['id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i> Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Users Tab -->
        <div id="usersTab" class="tab-content">
            <h2><i class="fas fa-users"></i> Manage Users</h2>
            <table class="data-table">
                <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Joined Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td><a href="?delete_user=<?php echo $user['id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i> Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="fas fa-plus-circle"></i> Add New Product</h3><span class="close-modal" onclick="closeAddProductModal()">&times;</span></div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" required placeholder="Enter product name">
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" rows="3" required placeholder="Enter product description"></textarea>
                </div>
                <div class="form-group">
                    <label>Price (RWF) *</label>
                    <input type="number" name="price" required placeholder="Enter price in Rwandan Francs">
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category">
                        <option value="shoes">👟 Shoes</option>
                        <option value="clothing">👕 Clothing</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Product Image *</label>
                    <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,image/svg+xml" required>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Supported formats: JPG, JPEG, PNG, GIF, WEBP, BMP, SVG (Max 10MB)
                    </small>
                </div>
                <button type="submit" name="add_product" class="btn-primary">Add Product</button>
            </form>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="fas fa-user-circle"></i> My Profile</h3><span class="close-modal" onclick="closeProfileModal()">&times;</span></div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                <div style="text-align: center;">
                    <?php if ($admin['profile_picture'] && file_exists('../assets/uploads/' . $admin['profile_picture'])): ?>
                        <img src="../assets/uploads/<?php echo $admin['profile_picture']; ?>" class="profile-preview" id="profilePreview">
                    <?php else: ?>
                        <div class="profile-preview" style="background: #e0e0e0; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user fa-3x" style="color: #999;"></i>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="profile_picture" id="profilePicInput" accept="image/*" style="display: none;">
                    <button type="button" class="btn-primary" style="width: auto; padding: 8px 20px; margin-bottom: 15px;" onclick="document.getElementById('profilePicInput').click()">Change Photo</button>
                </div>
                <div class="form-group"><label>Username</label><input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required></div>
                <div class="form-group"><label>Current Password</label><input type="password" name="current_password" placeholder="Enter to change password"></div>
                <div class="form-group"><label>New Password</label><input type="password" name="new_password" placeholder="Min 6 characters"></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" placeholder="Confirm new password"></div>
                <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Tab switching
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.getElementById(tabName + 'Tab').classList.add('active');
        document.querySelectorAll('.sidebar-nav a').forEach(link => link.classList.remove('active'));
        event.target.closest('a').classList.add('active');
    }
    
    // Product Modal
    function openAddProductModal() { document.getElementById('addProductModal').style.display = 'flex'; }
    function closeAddProductModal() { document.getElementById('addProductModal').style.display = 'none'; }
    
    // Profile Modal
    function openProfileModal() { document.getElementById('profileModal').style.display = 'flex'; }
    function closeProfileModal() { document.getElementById('profileModal').style.display = 'none'; }
    
    // Profile picture preview
    document.getElementById('profilePicInput')?.addEventListener('change', function(e) {
        if (e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.getElementById('profilePreview');
                if (preview.tagName === 'IMG') {
                    preview.src = event.target.result;
                } else {
                    const newImg = document.createElement('img');
                    newImg.src = event.target.result;
                    newImg.className = 'profile-preview';
                    newImg.id = 'profilePreview';
                    preview.parentNode.replaceChild(newImg, preview);
                }
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    
    // Dark Mode Toggle
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    }
    if (localStorage.getItem('darkMode') === 'true') document.body.classList.add('dark-mode');
    
    // Close modals when clicking outside
    window.onclick = function(e) {
        if (e.target === document.getElementById('addProductModal')) closeAddProductModal();
        if (e.target === document.getElementById('profileModal')) closeProfileModal();
    }
    
    // Charts
    <?php if (!empty($monthlyData)): ?>
    const monthlyCtx = document.getElementById('monthlySalesChart')?.getContext('2d');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyData, 'month')); ?>,
                datasets: [{
                    label: 'Sales (RWF)',
                    data: <?php echo json_encode(array_column($monthlyData, 'sales')); ?>,
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: { callbacks: { label: function(context) { return context.raw.toLocaleString('en-RW') + ' Frw'; } } }
                },
                scales: { y: { ticks: { callback: function(value) { return value.toLocaleString('en-RW') + ' Frw'; } } } }
            }
        });
    }
    <?php endif; ?>
    
    <?php if (!empty($topProducts)): ?>
    const productsCtx = document.getElementById('topProductsChart')?.getContext('2d');
    if (productsCtx) {
        new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($topProducts, 'product_name')); ?>,
                datasets: [{
                    label: 'Quantity Sold',
                    data: <?php echo json_encode(array_column($topProducts, 'total_sold')); ?>,
                    backgroundColor: '#4ecdc4',
                    borderRadius: 8
                }]
            },
            options: { responsive: true }
        });
    }
    <?php endif; ?>
</script>
</body>
</html>