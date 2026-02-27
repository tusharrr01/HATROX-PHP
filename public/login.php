<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

$errors = [];
// If redirected due to being blocked, show a friendly message (only for GET requests)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['blocked'])) {
  $errors[] = 'Your account has been blocked. Please contact support.';
}
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '/hatrox-project/public/index.php';
if (!is_string($redirect) || strpos($redirect, '/hatrox-project/') !== 0) {
    $redirect = '/hatrox-project/public/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
        if ($password === '') $errors[] = 'Enter your password.';

        if (!$errors) {
            if ($stmt = $mysqli->prepare('SELECT id, full_name, email, password_hash, is_admin, COALESCE(is_blocked,0) AS is_blocked FROM users WHERE email = ?')) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($user = $result->fetch_assoc()) {
                if ((int)$user['is_admin'] === 1) {
                  $errors[] = 'Invalid credentials.';
                } elseif ((int)($user['is_blocked'] ?? 0) === 1) {
                  $errors[] = 'Your account has been blocked. Please contact support.';
                } elseif (password_verify($password, $user['password_hash'])) {
                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['is_admin'] = (int)$user['is_admin'];

                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            if ($up = $mysqli->prepare('UPDATE users SET remember_token = ? WHERE id = ?')) {
                                $up->bind_param('si', $token, $user['id']);
                                $up->execute();
                                $up->close();
                                setcookie('remember_token', $token, time()+60*60*24*30, '/', '', false, true);
                            }
                        }

                        header('Location: ' . $redirect);
                        exit;
                    } else {
                        $errors[] = 'Invalid credentials.';
                    }
                } else {
                    $errors[] = 'Invalid credentials.';
                }
                $stmt->close();
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-3xl font-semibold mb-6">Sign In</h1>
  <?php if ($errors): ?>
    <div class="mb-4 p-3 bg-rose-900/40 border border-rose-800 text-rose-200 rounded">
      <?= e(implode("\n", $errors)) ?>
    </div>
  <?php endif; ?>
  <form method="post" class="space-y-4 bg-neutral-900 p-6 rounded border border-neutral-800">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
    <div>
      <label class="block text-sm mb-1">Email</label>
      <input required type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2 focus:outline-none focus:border-gold" />
    </div>
    <div>
      <label class="block text-sm mb-1">Password</label>
      <input required type="password" name="password" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2 focus:outline-none focus:border-gold" />
    </div>
    <label class="inline-flex items-center space-x-2 text-sm">
      <input type="checkbox" name="remember" class="rounded border-neutral-700 bg-neutral-800" />
      <span>Remember Me</span>
    </label>
    <button class="w-full bg-gold text-black font-medium py-2 rounded hover:bg-rose transition">Login</button>
    <p class="text-sm text-gray-400">New here? <a href="/hatrox-project/public/register.php" class="hover:text-gold">Create an account</a></p>
  </form>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>


