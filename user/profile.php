<?php
require_once '../config/database.php';
requireLogin();

$message = '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        $profile_pic = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $profile_pic = time() . '_' . $_FILES['profile_picture']['name'];
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_pic);
        }
        
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?");
        if ($stmt->execute([$username, $email, $profile_pic, $_SESSION['user_id']])) {
            $_SESSION['username'] = $username;
            $_SESSION['profile_pic'] = $profile_pic;
            $message = 'Profile updated!';
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }
    
    if (isset($_POST['change_password'])) {
        if (password_verify($_POST['current_password'], $user['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password'] && strlen($_POST['new_password']) >= 6) {
                $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed, $_SESSION['user_id']])) $message = 'Password changed!';
                else $error = 'Password change failed!';
            } else $error = 'Passwords do not match or too short!';
        } else $error = 'Current password is incorrect!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .profile-container { max-width: 800px; margin: 0 auto; }
        .profile-section { background: #fff; border-radius: 15px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dark-mode .profile-section { background: #2a2a3e; }
        .profile-section h2 { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ff6b6b; }
        .profile-preview { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid #ff6b6b; }
        .profile-picture-section { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .dark-mode .form-group input { background: #3a3a4e; color: #fff; border-color: #555; }
        .btn-primary { padding: 12px 30px; background: #ff6b6b; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar-header h3, .sidebar-nav a span { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header"><h3><i class="fas fa-store"></i> My Store</h3></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="dashboard.php"><i class="fas fa-shopping-bag"></i> <span>Products</span></a>
            <a href="dashboard.php"><i class="fas fa-shopping-cart"></i> <span>Cart</span></a>
            <a href="profile.php" class="active"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <button class="mode-toggle" onclick="document.body.classList.toggle('dark-mode')"><i class="fas fa-moon"></i> Dark Mode</button>
            <div><span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>
        
        <div class="profile-container">
            <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
            
            <div class="profile-section">
                <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-picture-section">
                        <img src="../assets/uploads/<?php echo $user['profile_picture'] ?? 'default-avatar.png'; ?>" class="profile-preview" id="preview">
                        <input type="file" name="profile_picture" id="profile_pic" accept="image/*" style="display: none;">
                        <button type="button" class="btn-primary" onclick="document.getElementById('profile_pic').click()">Change Photo</button>
                    </div>
                    <div class="form-group"><label>Username</label><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                    <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
                </form>
            </div>
            
            <div class="profile-section">
                <h2><i class="fas fa-key"></i> Change Password</h2>
                <form method="POST">
                    <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
                    <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
                    <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
                    <button type="submit" name="change_password" class="btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('profile_pic')?.addEventListener('change', function(e) {
        if (e.target.files[0]) {
            document.getElementById('preview').src = URL.createObjectURL(e.target.files[0]);
        }
    });
</script>
</body>
</html>