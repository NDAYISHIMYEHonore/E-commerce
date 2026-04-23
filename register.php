<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $success = 'Registration successful! Redirecting to login...';
                header('refresh:2;url=login.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - E-Commerce Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #ff6b6b, #4ecdc4); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .auth-card { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); width: 100%; max-width: 450px; }
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
        .mode-toggle-btn { position: fixed; bottom: 20px; right: 20px; background: #ff6b6b; color: white; border: none; padding: 10px 20px; border-radius: 30px; cursor: pointer; z-index: 999; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-header">
        <h2>Create Account</h2>
        <p>Join us for amazing shopping experience</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label><i class="fas fa-user"></i> Username</label>
            <input type="text" name="username" required placeholder="Choose a username">
        </div>
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" name="email" required placeholder="Enter your email">
        </div>
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Password</label>
            <input type="password" name="password" required placeholder="Create a password (min 6 characters)">
        </div>
        <div class="form-group">
            <label><i class="fas fa-check-circle"></i> Confirm Password</label>
            <input type="password" name="confirm_password" required placeholder="Confirm your password">
        </div>
        <button type="submit" class="btn-auth">Register</button>
    </form>
    
    <div class="auth-footer">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

<button class="mode-toggle-btn" onclick="document.body.classList.toggle('dark-mode')"><i class="fas fa-moon"></i> Dark Mode</button>
</body>
</html>