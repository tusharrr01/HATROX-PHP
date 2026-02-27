<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) { header('Location: /hatrox-project/admin/login.php'); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
  // block
  if (isset($_POST['block_id'])) {
    $id = (int)$_POST['block_id'];
    if ($id !== (int)$_SESSION['user_id']) { 
      if ($stmt = $mysqli->prepare('UPDATE users SET is_blocked = 1 WHERE id = ?')) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
    }
    header('Location: /hatrox-project/admin/users.php');
    exit;
  }

  // unblock
  if (isset($_POST['unblock_id'])) {
    $id = (int)$_POST['unblock_id'];
    if ($stmt = $mysqli->prepare('UPDATE users SET is_blocked = 0 WHERE id = ?')) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
    header('Location: /hatrox-project/admin/users.php');
    exit;
  }
}

$q = trim((string)($_GET['q'] ?? ''));
$users = [];
if ($q !== '') {
  $like = "%$q%";
  if ($stmt = $mysqli->prepare('SELECT id, full_name, email, is_admin, is_blocked, created_at FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY id DESC')) {
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $users[] = $row; }
    $stmt->close();
  }
} else if ($res = $mysqli->query('SELECT id, full_name, email, is_admin, is_blocked, created_at FROM users ORDER BY id DESC')) {
  while ($row = $res->fetch_assoc()) { $users[] = $row; }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <h1 class="text-2xl font-semibold mb-6">Users</h1>
  <form method="get" class="mb-4 grid md:grid-cols-3 gap-3 bg-neutral-900 border border-neutral-800 p-4 rounded">
    <div class="relative md:col-span-1">
      <i class="ri-search-2-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"></i>
      <input name="q" value="<?= e($q) ?>" placeholder="Search by name or email" class="pl-10 w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2">
    </div>
  </form>
  <div class="overflow-x-auto bg-neutral-900 border border-neutral-800 rounded">
    <table class="min-w-full text-sm">
      <thead class="bg-neutral-950">
        <tr>
          <th class="text-left p-3">ID</th>
          <th class="text-left p-3">Name</th>
          <th class="text-left p-3">Email</th>
          <th class="text-left p-3">Admin</th>
          <th class="text-left p-3">Joined</th>
          <th class="p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr class="border-t border-neutral-800">
            <td class="p-3"><?= (int)$u['id'] ?></td>
            <td class="p-3"><?= e($u['full_name']) ?></td>
            <td class="p-3"><?= e($u['email']) ?></td>
            <td class="p-3"><?= $u['is_admin'] ? 'Yes' : 'No' ?></td>
            <td class="p-3"><?= $u['is_blocked'] ? '<span class="text-sm text-rose">Blocked</span>' : '<span class="text-sm text-green-400">Active</span>' ?></td>
            <td class="p-3">
              <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                <?php if ((int)$u['is_blocked'] === 1): ?>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Unblock this user?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="unblock_id" value="<?= (int)$u['id'] ?>">
                    <button title="Unblock" class="inline-flex items-center space-x-2 px-3 py-1 bg-neutral-800 hover:bg-neutral-700 rounded text-sm text-white">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 text-green-400" aria-hidden="true" focusable="false">
                        <path d="M12 2l7 4v6c0 5-3.58 9.74-7 10-3.42-.26-7-5-7-10V6l7-4z" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                      <span>Unblock</span>
                    </button>
                  </form>
                <?php else: ?>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Block this user?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="block_id" value="<?= (int)$u['id'] ?>">
                    <button title="Block" class="inline-flex items-center gap-2 px-3 py-1 text-sm rounded font-medium border border-neutral-700 hover:border-gold transition" aria-label="Block user">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 text-rose" aria-hidden="true" focusable="false">
                        <path d="M12 2l7 4v6c0 5-3.58 9.74-7 10-3.42-.26-7-5-7-10V6l7-4z" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12 11a3 3 0 100-6 3 3 0 000 6z" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                      <span>Block</span>
                    </button>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-xs text-gray-500">(You)</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>


