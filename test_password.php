<?php
require_once 'config/database.php';

// Get admin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'honore123'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    echo "Admin user found: " . $admin['username'] . "<br>";
    echo "Stored hash: " . $admin['password'] . "<br><br>";
    
    // Test the password
    $test_password = 'hono@123';
    if (password_verify($test_password, $admin['password'])) {
        echo "✅ Password 'hono@123' is CORRECT!<br>";
    } else {
        echo "❌ Password verification FAILED!<br>";
        
        // Create new hash
        $new_hash = password_hash('hono@123', PASSWORD_DEFAULT);
        echo "New hash for 'hono@123': " . $new_hash . "<br>";
        
        // Update with new hash
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'honore123'");
        $update->execute([$new_hash]);
        echo "Password has been updated! Try logging in now.<br>";
    }
} else {
    echo "Admin user not found! Running setup...<br>";
    $new_hash = password_hash('hono@123', PASSWORD_DEFAULT);
    $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $insert->execute(['honore123', 'admin@ecommerce.com', $new_hash, 'admin']);
    echo "Admin user created with password 'hono@123'<br>";
}

echo "<br><a href='login.php'>Go to Login Page</a>";
?>