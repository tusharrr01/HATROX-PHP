<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

if (!empty($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    if ($stmt = $mysqli->prepare('UPDATE users SET remember_token = NULL WHERE id = ?')) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
}

setcookie('remember_token', '', time() - 3600, '/', '', false, true);
session_unset();
session_destroy();

header('Location: /hatrox-project/public/index.php');
exit;


