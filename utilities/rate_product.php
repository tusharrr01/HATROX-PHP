<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) { http_response_code(400); exit('Bad request'); }
if (empty($_SESSION['user_id'])) { header('Location: /hatrox-project/public/login.php'); exit; }

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
$comment = trim((string)($_POST['comment'] ?? ''));

if ($product_id <= 0) { header('Location: /hatrox-project/public/shop.php'); exit; }

$hasPurchased = false;
if ($stmt = $mysqli->prepare('SELECT COUNT(*) FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ?')) {
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    $stmt->bind_result($cnt);
    if ($stmt->fetch() && (int)$cnt > 0) {
        $hasPurchased = true;
    }
    $stmt->close();
}

if ($hasPurchased) {
    if ($stmt = $mysqli->prepare('INSERT INTO product_ratings (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)')) {
      $stmt->bind_param('iiis', $product_id, $user_id, $rating, $comment);
      $stmt->execute();
      $stmt->close();
    }
    $extra = '&rated=1';
} else {
    $extra = '&not_purchased=1';
}

header('Location: /hatrox-project/public/product-detail.php?id=' . $product_id . $extra);
exit;


