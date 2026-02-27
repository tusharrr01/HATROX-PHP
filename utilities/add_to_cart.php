<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    http_response_code(400);
    exit('Bad request');
}

if (!empty($_SESSION['is_admin'])) {
    header('Location: /hatrox-project/admin/dashboard.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: /hatrox-project/public/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
// enforce server-side maximum per-item (keep consistent with cart update)
$MAX_Q = 10;
$requested = max(1, (int)($_POST['quantity'] ?? 1));

// existing quantity in cart
$currentQty = 0;
if ($g = $mysqli->prepare('SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?')) {
    $g->bind_param('ii', $user_id, $product_id);
    $g->execute();
    $g->bind_result($existing);
    if ($g->fetch()) { $currentQty = (int)$existing; }
    $g->close();
}

$quantity = $requested;
if ($currentQty + $requested > $MAX_Q) {
    $_SESSION['cart_errors'] = $_SESSION['cart_errors'] ?? [];
    $_SESSION['cart_errors'][$product_id] = "Maximum allowed quantity is {$MAX_Q}.";
    header('Location: /hatrox-project/public/cart.php');
    exit;
}

if ($stmt = $mysqli->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'")) {
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $stmt->bind_result($stock);
    if (!$stmt->fetch()) {
        $stmt->close();
        header('Location: /hatrox-project/public/shop.php');
        exit;
    }
    $stmt->close();
}

if ($stmt = $mysqli->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)')) {
    $stmt->bind_param('iii', $user_id, $product_id, $quantity);
    $stmt->execute();
    $stmt->close();
}

header('Location: /hatrox-project/public/cart.php');
exit;


