<?php
require_once '../config/database.php';
requireLogin(); // or requireAdmin() for admin pages

// Prevent back button access after logout
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
?>
<?php
require_once '../config/database.php';
requireAdmin();

$message = '';
$error = '';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
        if ($stmt->execute([$id])) $message = 'User deleted!';
        else $error = 'Delete failed!';
    } else $error = 'Cannot delete yourself!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$_POST['username'], $_POST['email'], $hashed, $_POST['role']])) $message = 'User added!';
    else $error = 'Username/email exists!';
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; display: inline-block; color: white; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 5% auto; padding: 30px; width: 90%; max-width: 450px; border-radius: 12px; }
        .dark-mode .modal-content { background: #2a2a3e; }
        .close { float: right; font-size: 28px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar-header h3, .sidebar-nav a span { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header"><h3><i class="fas fa-store"></i> Admin</h3></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="manage_products.php"><i class="fas fa-box"></i> <span>Products</span></a>
            <a href="manage_users.php" class="active"><i class="fas fa-users"></i> <span>Users</span></a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <button class="mode-toggle" onclick="document.body.classList.toggle('dark-mode')"><i class="fas fa-moon"></i> Dark Mode</button>
            <div><span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>
        
        <div class="content-header">
            <h2><i class="fas fa-users"></i> Manage Users</h2>
            <button class="btn-primary" onclick="document.getElementById('addModal').style.display='block'"><i class="fas fa-plus"></i> Add User</button>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <table class="data-table">
            <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span class="badge" style="background: <?php echo $user['role'] == 'admin' ? '#ff6b6b' : '#4caf50'; ?>"><?php echo $user['role']; ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td><?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] != 'admin'): ?><a href="?delete=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i> Delete</a><?php else: ?>-<?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
        <h3>Add New User</h3>
        <form method="POST">
            <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <div class="form-group"><label>Role</label><select name="role"><option value="user">User</option><option value="admin">Admin</option></select></div>
            <button type="submit" name="add_user" class="btn-primary">Add User</button>
        </form>
    </div>
</div>

<script>
    window.onclick = function(e) { if (e.target == document.getElementById('addModal')) document.getElementById('addModal').style.display = 'none'; }
</script>
</body>
</html>