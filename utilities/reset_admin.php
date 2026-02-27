<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';


if (!empty($_GET['confirm'])) {
    $email = 'error@hatrox.com';
    $hash = password_hash('error', PASSWORD_DEFAULT);
    if ($stmt = $mysqli->prepare('UPDATE users SET password_hash = ?, is_admin = 1 WHERE email = ?')) {
        $stmt->bind_param('ss', $hash, $email);
        $stmt->execute();
        $ok = $stmt->affected_rows >= 0;
        $stmt->close();
        echo $ok ? 'Admin password reset. You can now login with error@hatrox.com / error' : 'Update failed';
    } else {
        echo 'DB error';
    }
    exit;
}

?>
<!DOCTYPE html>
<html>
  <head><meta charset="utf-8"><title>Reset Admin</title></head>
  <body style="background:#111;color:#eee;font-family:Arial;padding:20px;">
    <h1>Reset Admin Password</h1>
    <p>This will set the admin (error@hatrox.com) password to <strong>error</strong> and ensure admin privileges.</p>
    <p><a href="?confirm=1" style="color:#d4af37;">Click here to confirm reset</a></p>
    <p><em>After success, delete this file: utilities/reset_admin.php</em></p>
  </body>
</html>


