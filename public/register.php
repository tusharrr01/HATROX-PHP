<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $full_name = trim((string)($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if ($full_name === '' || strlen($full_name) < 2) $errors[] = 'Please enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (!$errors) {
            // check for existing email 
            if ($stmt = $mysqli->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?)')) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $errors[] = 'Email already registered.';
                }
                $stmt->close();
            }

            if (!$errors) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($stmt = $mysqli->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)')) {
                    $stmt->bind_param('sss', $full_name, $email, $hash);
                    if ($stmt->execute()) {
                        header('Location: /hatrox-project/public/login.php?registered=1');
                        exit;
                    } else {
                        if ((int)$stmt->errno === 1062) {
                            $errors[] = 'Email already registered.';
                        } else {
                            $errors[] = 'Registration failed. Please try again.';
                        }
                    }
                    $stmt->close();
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-3xl font-semibold mb-6">Create Account</h1>
  <?php if ($errors): ?>
    <div class="mb-4 p-3 bg-rose-900/40 border border-rose-800 text-rose-200 rounded">
      <?= e(implode("\n", $errors)) ?>
    </div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="mb-4 p-3 bg-emerald-900/40 border border-emerald-800 text-emerald-200 rounded">
      <?= e($success) ?>
    </div>
  <?php endif; ?>
  <form method="post" class="space-y-4 bg-neutral-900 p-6 rounded border border-neutral-800">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div>
      <label class="block text-sm mb-1">Full Name</label>
      <input required name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2 focus:outline-none focus:border-gold" />
    </div>
    <div>
      <label class="block text-sm mb-1">Email</label>
      <input required type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2 focus:outline-none focus:border-gold" />
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1">Password</label>
        <input required type="password" name="password" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2 focus:outline-none focus:border-gold" />
      </div>
      <div>
        <label class="block text-sm mb-1">Confirm Password</label>
        <input required type="password" name="confirm_password" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2 focus:outline-none focus:border-gold" />
      </div>
    </div>
    <button class="w-full bg-gold text-black font-medium py-2 rounded hover:bg-rose transition">Register</button>
    <p class="text-sm text-gray-400">Already have an account? <a href="/hatrox-project/public/login.php" class="hover:text-gold">Login</a></p>
  </form>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>


