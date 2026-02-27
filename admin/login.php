<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

// Redirect if already admin
if (!empty($_SESSION['is_admin'])) {
    header('Location: /hatrox-project/admin/dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password==='') {
            $errors[] = 'Invalid credentials.';
        } else if ($stmt = $mysqli->prepare('SELECT id, full_name, email, password_hash, is_admin FROM users WHERE email = ? AND is_admin = 1')) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($u = $res->fetch_assoc()) {
                if (password_verify($password, $u['password_hash'])) {
                    $_SESSION['user_id'] = (int)$u['id'];
                    $_SESSION['full_name'] = $u['full_name'];
                    $_SESSION['email'] = $u['email'];
                    $_SESSION['is_admin'] = 1;
                    header('Location: /hatrox-project/admin/dashboard.php');
                    exit;
                }
            }
            $errors[] = 'Invalid credentials.';
            $stmt->close();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-3xl font-semibold mb-6">Admin Sign In</h1>
  <?php if ($errors): ?>
    <div class="mb-4 p-3 bg-rose-900/40 border border-rose-800 text-rose-200 rounded">
      <?= e(implode("\n", $errors)) ?>
    </div>
  <?php endif; ?>
  <form method="post" class="space-y-4 bg-neutral-900 p-6 rounded border border-neutral-800">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div>
      <label class="block text-sm mb-1">Email</label>
      <input required type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2 focus:outline-none focus:border-gold" />
    </div>
    <div>
      <label class="block text-sm mb-1">Password</label>
      <input required type="password" name="password" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2 focus:outline-none focus:border-gold" />
    </div>
    <button class="w-full bg-gold text-black font-medium py-2 rounded hover:bg-rose transition">Login</button>
  </form>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>


