<?php
require_once '../config/database.php';
requireLogin(); // or requireAdmin() for admin pages

// Prevent back button access after logout
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

$message = '';
$error = '';

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$_GET['delete']])) {
        $message = 'Product deleted successfully!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $image = 'default-product.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['category'], $image])) {
        $message = 'Product added successfully!';
    }
}

$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        body.dark-mode { background: #1a1a2e; color: #fff; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: linear-gradient(180deg, #ff6b6b, #ee5a5a); color: white; position: fixed; height: 100%; left: 0; top: 0; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-nav { padding: 20px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 14px 25px; color: white; text-decoration: none; gap: 12px; transition: 0.3s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.2); border-left: 4px solid white; }
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #fff; border-radius: 12px; margin-bottom: 30px; }
        .dark-mode .top-bar { background: #2a2a3e; }
        .mode-toggle { padding: 10px 20px; background: #ff6b6b; color: white; border: none; border-radius: 25px; cursor: pointer; }
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-primary { padding: 10px 20px; background: #ff6b6b; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .data-table { width: 100%; background: #fff; border-radius: 12px; overflow: hidden; }
        .dark-mode .data-table { background: #2a2a3e; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .data-table th { background: #ff6b6b; color: white; }
        .btn-delete { padding: 5px 10px; background: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .badge { padding: 4px 12px; background: #4caf50; color: white; border-radius: 20px; font-size: 0.85rem; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 5% auto; padding: 30px; width: 90%; max-width: 500px; border-radius: 12px; }
        .dark-mode .modal-content { background: #2a2a3e; }
        .close { float: right; font-size: 28px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar-header h3, .sidebar-nav a span { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header"><h3><i class="fas fa-store"></i> Admin</h3></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="manage_products.php" class="active"><i class="fas fa-box"></i> <span>Products</span></a>
            <a href="manage_users.php"><i class="fas fa-users"></i> <span>Users</span></a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <button class="mode-toggle" onclick="document.body.classList.toggle('dark-mode')"><i class="fas fa-moon"></i> Dark Mode</button>
            <div class="user-info"><span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>
        
        <div class="content-header">
            <h2><i class="fas fa-box"></i> Manage Products</h2>
            <button class="btn-primary" onclick="document.getElementById('addModal').style.display='block'"><i class="fas fa-plus"></i> Add Product</button>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <table class="data-table">
            <thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><img src="../assets/uploads/<?php echo $product['image']; ?>" width="50" height="50" style="object-fit:cover; border-radius:8px;"></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><span class="badge"><?php echo $product['category']; ?></span></td>
                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                    <td><a href="?delete=<?php echo $product['id']; ?>" class="btn-delete" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i> Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
        <h3>Add New Product</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="3" required></textarea></div>
            <div class="form-group"><label>Price</label><input type="number" step="0.01" name="price" required></div>
            <div class="form-group"><label>Category</label><select name="category"><option value="shoes">Shoes</option><option value="clothing">Clothing</option></select></div>
            <div class="form-group"><label>Image</label><input type="file" name="image" accept="image/*"></div>
            <button type="submit" name="add_product" class="btn-primary">Add Product</button>
        </form>
    </div>
</div>

<script>
    window.onclick = function(e) { if (e.target == document.getElementById('addModal')) document.getElementById('addModal').style.display = 'none'; }
</script>
</body>
</html>