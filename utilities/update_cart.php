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
$action = (string)($_POST['action'] ?? '');

// custom validation settings
$MAX_Q = 10; // application maximum per-item quantity

// helper to set a per-item flash error and redirect
function cart_error_and_redirect(int $pid, string $msg) {
    $_SESSION['cart_errors'] = $_SESSION['cart_errors'] ?? [];
    $_SESSION['cart_errors'][$pid] = $msg;
    header('Location: /hatrox-project/public/cart.php');
    exit;
}

if ($action === 'remove') {
    if ($stmt = $mysqli->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?')) {
        $stmt->bind_param('ii', $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
    }
} else {
    // sanitize and validate quantity
    $raw = $_POST['quantity'] ?? '';
    if (!is_numeric($raw) || (int)$raw < 1) {
        cart_error_and_redirect($product_id, 'Quantity must be a positive integer.');
    }
    $quantity = (int)$raw;

    if ($quantity > $MAX_Q) {
        cart_error_and_redirect($product_id, "Maximum allowed quantity is {$MAX_Q}.");
    }

    // check product exists and optionally available stock
    if ($stmt = $mysqli->prepare("SELECT stock_quantity, status FROM products WHERE id = ?")) {
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $stmt->bind_result($stock_quantity, $status);
        if (!$stmt->fetch()) {
            $stmt->close();
            cart_error_and_redirect($product_id, 'Product not found.');
        }
        $stmt->close();

        if ($status !== 'active') {
            cart_error_and_redirect($product_id, 'This product is not available.');
        }

        // if stock is not null, enforce it
        if (is_numeric($stock_quantity)) {
            $stock = (int)$stock_quantity;
            if ($stock <= 0) {
                // remove item and notify
                if ($del = $mysqli->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?')) {
                    $del->bind_param('ii', $user_id, $product_id);
                    $del->execute();
                    $del->close();
                }
                cart_error_and_redirect($product_id, 'Product is out of stock.');
            }
            if ($quantity > $stock) {
                cart_error_and_redirect($product_id, 'Requested quantity exceeds available stock.');
            }
        }
    }

    // passed validation: clear any previous per-item error
    if (!empty($_SESSION['cart_errors'][$product_id])) {
        unset($_SESSION['cart_errors'][$product_id]);
    }

    if ($stmt = $mysqli->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?')) {
        $stmt->bind_param('iii', $quantity, $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
    }
}

header('Location: /hatrox-project/public/cart.php');
exit;


