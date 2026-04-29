<?php
require_once 'config/database.php';
header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT p.*, 
           GROUP_CONCAT(CONCAT(pi.product_name, '|', pi.quantity, '|', pi.price) SEPARATOR ';;') as items_data
    FROM purchases p
    LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$purchases = $stmt->fetchAll();

foreach ($purchases as &$purchase) {
    $items = [];
    if ($purchase['items_data']) {
        $items_parts = explode(';;', $purchase['items_data']);
        foreach ($items_parts as $part) {
            list($name, $qty, $price) = explode('|', $part);
            $items[] = ['product_name' => $name, 'quantity' => $qty, 'price' => $price];
        }
    }
    $purchase['items'] = $items;
    $purchase['date'] = date('M d, Y H:i', strtotime($purchase['created_at']));
    unset($purchase['items_data']);
}

echo json_encode($purchases);
?>