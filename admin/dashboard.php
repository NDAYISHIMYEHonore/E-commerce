<?php
require_once '../config/database.php';
requireAdmin();

// Prevent back button access after logout
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$recentUsers = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        // Prevent back button after logout
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
        
        // Check session periodically
        setInterval(function() {
            fetch('check_session.php')
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
        .sidebar { width: 280px; background: linear-gradient(180deg, #ff6b6b, #ee5a5a); color: white; position: fixed; height: 100%; left: 0; top: 0; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-nav { padding: 20px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 14px 25px; color: white; text-decoration: none; gap: 12px; transition: 0.3s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.2); border-left: 4px solid white; }
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #fff; border-radius: 12px; margin-bottom: 30px; }
        .dark-mode .top-bar { background: #2a2a3e; }
        .mode-toggle { padding: 10px 20px; background: #ff6b6b; color: white; border: none; border-radius: 25px; cursor: pointer; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 15px; display: flex; align-items: center; gap: 20px; }
        .dark-mode .stat-card { background: #2a2a3e; }
        .stat-icon { width: 60px; height: 60px; background: linear-gradient(135deg, #ff6b6b, #4ecdc4); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: white; }
        .stat-info h3 { font-size: 2rem; }
        .data-table { width: 100%; background: #fff; border-radius: 12px; overflow: hidden; }
        .dark-mode .data-table { background: #2a2a3e; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .data-table th { background: #ff6b6b; color: white; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar-header h3, .sidebar-nav a span { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-store"></i> Admin Panel</h3>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="manage_products.php"><i class="fas fa-box"></i> <span>Manage Products</span></a>
            <a href="manage_users.php"><i class="fas fa-users"></i> <span>Manage Users</span></a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> <span>My Profile</span></a>
            <a href="../logout.php" onclick="return confirmLogout()"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <button class="mode-toggle" onclick="document.body.classList.toggle('dark-mode')"><i class="fas fa-moon"></i> Dark/Light Mode</button>
            <div class="user-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3><?php echo $totalUsers; ?></h3><p>Total Users</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-info"><h3><?php echo $totalProducts; ?></h3><p>Total Products</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info"><h3>0</h3><p>Total Orders</p></div>
            </div>
        </div>
        
        <div class="recent-section">
            <h2><i class="fas fa-users"></i> Recent Users</h2>
            <table class="data-table">
                <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Joined Date</th></tr></thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function confirmLogout() {
        return confirm('Are you sure you want to logout?');
    }
    
    // Disable back button
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
</body>
</html>