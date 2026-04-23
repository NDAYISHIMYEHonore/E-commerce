<?php
require_once 'config/database.php';

// Check if admin already exists
$check = $pdo->prepare("SELECT * FROM users WHERE username = 'honore123'");
$check->execute();

if ($check->rowCount() > 0) {
    // Update existing admin with correct password
    $hashed_password = password_hash('hono@123', PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE username = 'honore123'");
    $update->execute([$hashed_password]);
    echo "Admin password updated successfully!<br>";
    echo "Username: honore123<br>";
    echo "Password: hono@123<br>";
} else {
    // Create new admin
    $hashed_password = password_hash('hono@123', PASSWORD_DEFAULT);
    $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $insert->execute(['honore123', 'admin@ecommerce.com', $hashed_password, 'admin']);
    echo "Admin user created successfully!<br>";
    echo "Username: honore123<br>";
    echo "Password: hono@123<br>";
}

// Also create sample products if none exist
$productCheck = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
if ($productCheck == 0) {
    $products = [
        ['Nike Running Shoes', 'Comfortable running shoes with breathable mesh', 49.99, 'shoes', 'running-shoes.jpg'],
        ['Adidas Sneakers', 'Stylish sneakers for everyday wear', 59.99, 'shoes', 'sneakers.jpg'],
        ['Premium Cotton T-Shirt', '100% cotton comfortable t-shirt', 19.99, 'clothing', 'tshirt.jpg'],
        ['Classic Denim Jeans', 'Classic fit denim jeans', 39.99, 'clothing', 'jeans.jpg'],
        ['Winter Puffer Jacket', 'Warm and stylish winter jacket', 89.99, 'clothing', 'jacket.jpg'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
    foreach ($products as $product) {
        $stmt->execute($product);
    }
    echo "<br>Sample products added successfully!";
}

echo "<br><br><a href='login.php'>Go to Login Page</a>";
?>