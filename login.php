<?php
require_once 'config/database.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Prevent caching of login page
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Check if admin exists and update password if needed
    $checkAdmin = $pdo->prepare("SELECT * FROM users WHERE username = 'honore123'");
    $checkAdmin->execute();
    $adminUser = $checkAdmin->fetch();
    
    if (!$adminUser) {
        $hashed = password_hash('hono@123', PASSWORD_DEFAULT);
        $createAdmin = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $createAdmin->execute(['honore123', 'admin@ecommerce.com', $hashed, 'admin']);
        $success = 'Admin account created! Please login again.';
    } else {
        if (!password_verify('hono@123', $adminUser['password'])) {
            $newHash = password_hash('hono@123', PASSWORD_DEFAULT);
            $updateAdmin = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'honore123'");
            $updateAdmin->execute([$newHash]);
        }
    }
    
    // Now try to login
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_pic'] = $user['profile_picture'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Set secure session cookie parameters
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to true if using HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        if ($user['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit();
    } else {
        $error = 'Invalid username or password!<br>Admin credentials: honore123 / hono@123';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Commerce Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #ff6b6b, #4ecdc4); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .auth-card { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        .dark-mode .auth-card { background: #2a2a3e; color: #fff; }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .auth-header h2 { margin-bottom: 10px; color: #ff6b6b; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        .dark-mode .form-group input { background: #3a3a4e; color: #fff; border-color: #555; }
        .btn-auth { width: 100%; padding: 12px; background: #ff6b6b; color: white; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; transition: 0.3s; }
        .btn-auth:hover { background: #ee5a5a; transform: translateY(-2px); }
        .auth-footer { text-align: center; margin-top: 20px; }
        .auth-footer a { color: #ff6b6b; text-decoration: none; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .admin-note { margin-top: 15px; padding: 10px; background: #e3f2fd; border-radius: 8px; font-size: 0.9rem; }
        .mode-toggle-btn { position: fixed; bottom: 20px; right: 20px; background: #ff6b6b; color: white; border: none; padding: 10px 20px; border-radius: 30px; cursor: pointer; z-index: 999; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-header">
        <h2>Welcome Back</h2>
        <p>Login to your account</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label><i class="fas fa-user"></i> Username or Email</label>
            <input type="text" name="username" required placeholder="Enter your username or email">
        </div>
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Password</label>
            <input type="password" name="password" required placeholder="Enter your password">
        </div>
        <button type="submit" class="btn-auth">Login</button>
    </form>
    
    <div class="auth-footer">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
        
    </div>
</div>

<button class="mode-toggle-btn" onclick="document.body.classList.toggle('dark-mode')"><i class="fas fa-moon"></i> Dark Mode</button>
</body>
</html>