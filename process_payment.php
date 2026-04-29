<?php
require_once 'config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = $_POST['user_id'];
$username = $_POST['username'];
$user_email = $_POST['user_email'];
$user_phone = $_POST['user_phone'];
$provider = $_POST['provider'];
$total_amount = $_POST['total_amount'];
$items = json_decode($_POST['items'], true);

try {
    $pdo->beginTransaction();
    $order_id = 'ORD-' . time() . '-' . rand(1000, 9999);
    
    $stmt = $pdo->prepare("INSERT INTO purchases (order_id, user_id, username, user_email, user_phone, provider, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
    $stmt->execute([$order_id, $user_id, $username, $user_email, $user_phone, $provider, $total_amount]);
    $purchase_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $product_stmt = $pdo->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
        $product_stmt->execute([$item['name']]);
        $product = $product_stmt->fetch();
        $product_id = $product ? $product['id'] : 0;
        $stmt->execute([$purchase_id, $product_id, $item['name'], $item['quantity'], $item['price']]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>