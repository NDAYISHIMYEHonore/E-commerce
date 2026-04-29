<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        // Check if username/email already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check->execute([$username, $email, $user_id]);
        
        if ($check->fetch()) {
            $error = 'Username or email already exists!';
        } else {
            // Handle profile picture upload
            $profile_pic = $user['profile_picture'];
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../assets/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $profile_pic = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_pic);
            }
            
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?");
            if ($stmt->execute([$username, $email, $profile_pic, $user_id])) {
                $_SESSION['username'] = $username;
                $_SESSION['profile_pic'] = $profile_pic;
                $message = 'Profile updated successfully!';
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = 'Failed to update profile!';
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters!';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $message = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password!';
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
    <title>User Profile - E-Commerce Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        body.dark-mode { background: #1a1a2e; color: #fff; }
        
        .dashboard-container { display: flex; min-height: 100vh; }
        
        .sidebar { width: 280px; background: linear-gradient(180deg, #4ecdc4, #3dbdb4); color: white; position: fixed; height: 100%; left: 0; top: 0; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-header h3 { font-size: 1.3rem; }
        .sidebar-nav { padding: 20px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 14px 25px; color: white; text-decoration: none; gap: 12px; transition: 0.3s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.2); border-left: 4px solid white; }
        
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        .top-bar { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .dark-mode .top-bar { background: #2a2a3e; }
        .mode-toggle { background: #ff6b6b; color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; }
        
        .profile-container { max-width: 800px; margin: 0 auto; }
        .profile-section { background: white; border-radius: 15px; padding: 30px; margin-bottom: 30px; }
        .dark-mode .profile-section { background: #2a2a3e; }
        .profile-section h2 { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ff6b6b; }
        .profile-preview { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid #ff6b6b; }
        .profile-picture-section { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .dark-mode .form-group input { background: #3a3a4e; color: white; border-color: #555; }
        .btn-primary { padding: 12px 30px; background: #ff6b6b; color: white; border: none; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        .btn-primary:hover { transform: translateY(-2px); }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h3, .sidebar-nav a span { display: none; }
            .main-content { margin-left: 70px; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-store"></i> My Store</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
                <a href="dashboard.php#products"><i class="fas fa-shopping-bag"></i> <span>Products</span></a>
                <a href="dashboard.php#cart"><i class="fas fa-shopping-cart"></i> <span>Cart</span></a>
                <a href="dashboard.php#orders"><i class="fas fa-box"></i> <span>Orders</span></a>
                <a href="profile.php" class="active"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <button class="mode-toggle" onclick="document.body.classList.toggle('dark-mode')">
                    <i class="fas fa-moon"></i> Dark/Light Mode
                </button>
                <div><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></div>
            </div>
            
            <div class="profile-container">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="profile-section">
                    <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="profile-picture-section">
                            <div>
                                <?php if ($user['profile_picture'] && file_exists('../assets/uploads/' . $user['profile_picture'])): ?>
                                    <img src="../assets/uploads/<?php echo $user['profile_picture']; ?>" class="profile-preview" id="preview">
                                <?php else: ?>
                                    <div class="profile-preview" style="background: linear-gradient(135deg, #ff6b6b, #4ecdc4); display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="profile_picture" id="profile_pic" accept="image/*" style="display: none;">
                            <button type="button" class="btn-primary" onclick="document.getElementById('profile_pic').click()" style="margin-top: 10px;">
                                <i class="fas fa-camera"></i> Change Photo
                            </button>
                        </div>
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <div class="profile-section">
                    <h2><i class="fas fa-key"></i> Change Password</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('profile_pic')?.addEventListener('change', function(e) {
            if (e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('preview');
                    if (preview) {
                        preview.src = event.target.result;
                    } else {
                        const newPreview = document.createElement('img');
                        newPreview.src = event.target.result;
                        newPreview.className = 'profile-preview';
                        newPreview.id = 'preview';
                        document.querySelector('.profile-picture-section div').innerHTML = '';
                        document.querySelector('.profile-picture-section div').appendChild(newPreview);
                    }
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }
        
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>