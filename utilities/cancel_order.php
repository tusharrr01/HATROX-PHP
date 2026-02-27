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
$order_id = (int)($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: /hatrox-project/public/cart.php?error=1');
    exit;
}

if ($stmt = $mysqli->prepare('SELECT user_id, status FROM orders WHERE id = ?')) {
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $stmt->bind_result($db_user_id, $status);
    if ($stmt->fetch()) {
        $db_user_id = (int)$db_user_id;
        $status = (string)$status;
    } else {
        $stmt->close();
        header('Location: /hatrox-project/public/cart.php?error=1');
        exit;
    }
    $stmt->close();
} else {
    header('Location: /hatrox-project/public/cart.php?error=1');
    exit;
}

if ($db_user_id !== $user_id) {
    header('Location: /hatrox-project/public/cart.php?error=1');
    exit;
}

if ($status !== 'processing') {
    header('Location: /hatrox-project/public/cart.php?error=not_allowed');
    exit;
}

$mysqli->begin_transaction();
try {
    if ($up = $mysqli->prepare('UPDATE orders SET status = ? WHERE id = ?')) {
        $new = 'cancelled';
        $up->bind_param('si', $new, $order_id);
        $up->execute();
        $up->close();
    }

    if ($it = $mysqli->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?')) {
        $it->bind_param('i', $order_id);
        $it->execute();
        $res = $it->get_result();
        while ($row = $res->fetch_assoc()) {
            $pid = (int)$row['product_id'];
            $qty = (int)$row['quantity'];
            if ($dec = $mysqli->prepare('UPDATE products SET sold_count = GREATEST(0, sold_count - ?) WHERE id = ?')) {
                $dec->bind_param('ii', $qty, $pid);
                $dec->execute();
                $dec->close();
            }
        }
        $it->close();
    }

    $mysqli->commit();
    header('Location: /hatrox-project/public/cart.php?cancelled=1');
    exit;
} catch (Throwable $e) {
    $mysqli->rollback();
    header('Location: /hatrox-project/public/cart.php?error=1');
    exit;
}

