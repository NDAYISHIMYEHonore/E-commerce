<?php
require_once 'config/database.php';

echo "<h2>Admin Account Check</h2>";

// Check if admin exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'honore123'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    echo "✅ Admin user found: " . $admin['username'] . "<br>";
    echo "Role: " . $admin['role'] . "<br>";
    
    // Test password
    if (password_verify('hono@123', $admin['password'])) {
        echo "✅ Password 'hono@123' is CORRECT!<br>";
        echo "<div style='color: green; font-size: 18px; margin-top: 20px;'>You can now login at <a href='login.php'>login.php</a></div>";
    } else {
        echo "❌ Password is incorrect. Fixing...<br>";
        $new_hash = password_hash('hono@123', PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'honore123'");
        $update->execute([$new_hash]);
        echo "✅ Password has been reset to 'hono@123'<br>";
        echo "<div style='color: green; margin-top: 20px;'>Try logging in now at <a href='login.php'>login.php</a></div>";
    }
} else {
    echo "❌ Admin user not found. Creating...<br>";
    $new_hash = password_hash('hono@123', PASSWORD_DEFAULT);
    $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $insert->execute(['honore123', 'admin@ecommerce.com', $new_hash, 'admin']);
    echo "✅ Admin user created!<br>";
    echo "Username: honore123<br>";
    echo "Password: hono@123<br>";
    echo "<div style='color: green; margin-top: 20px;'>Try logging in now at <a href='login.php'>login.php</a></div>";
}

// List all users
echo "<h3>All Users in Database:</h3>";
$users = $pdo->query("SELECT id, username, email, role FROM users")->fetchAll();
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "</tr>";
}
echo "</table>";
?>