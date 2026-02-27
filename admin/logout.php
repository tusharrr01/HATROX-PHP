<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';
if (!empty($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  if ($stmt = $mysqli->prepare('UPDATE users SET remember_token = NULL WHERE id = ?')) { $stmt->bind_param('i',$uid); $stmt->execute(); $stmt->close(); }
}
setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', true);

$_SESSION = [];

// If session uses cookies, remove the session cookie for the current session name
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', true);
}

session_unset();
session_destroy();

header('Location: /hatrox-project/admin/login.php');
exit;


