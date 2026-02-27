<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: /hatrox-project/admin/login.php');
    exit;
}

$total_users = $total_products = $total_orders = 0; $total_income = 0.0;
if ($res = $mysqli->query('SELECT COUNT(*) AS c FROM users')) { $total_users = (int)$res->fetch_assoc()['c']; }
if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM products WHERE status='active'")) { $total_products = (int)$res->fetch_assoc()['c']; }
if ($res = $mysqli->query('SELECT COUNT(*) AS c FROM orders')) { $total_orders = (int)$res->fetch_assoc()['c']; }
if ($res = $mysqli->query('SELECT COALESCE(SUM(total_amount),0) AS s FROM orders')) { $total_income = (float)$res->fetch_assoc()['s']; }

$recent_orders = [];
if ($res = $mysqli->query('SELECT id, total_amount, status, created_at FROM orders ORDER BY id DESC LIMIT 5')) {
    while ($row = $res->fetch_assoc()) { $recent_orders[] = $row; }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <h1 class="text-3xl font-semibold mb-6">Admin Dashboard</h1>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-neutral-900 border border-neutral-800 p-5 rounded">
      <div class="text-gray-400 text-sm">Users</div>
      <div class="text-2xl font-semibold mt-1"><?= $total_users ?></div>
    </div>
    <div class="bg-neutral-900 border border-neutral-800 p-5 rounded">
      <div class="text-gray-400 text-sm">Products</div>
      <div class="text-2xl font-semibold mt-1"><?= $total_products ?></div>
    </div>
    <div class="bg-neutral-900 border border-neutral-800 p-5 rounded">
      <div class="text-gray-400 text-sm">Orders</div>
      <div class="text-2xl font-semibold mt-1"><?= $total_orders ?></div>
    </div>
    <div class="bg-neutral-900 border border-neutral-800 p-5 rounded">
      <div class="text-gray-400 text-sm">Income</div>
      <div class="text-2xl font-semibold mt-1 text-gold">$<?= number_format($total_income, 2) ?></div>
    </div>
  </div>

  <div class="mt-8 grid md:grid-cols-2 gap-6">
    <div class="bg-neutral-900 border border-neutral-800 p-5 rounded">
      <h2 class="font-semibold mb-3">Recent Orders</h2>
      <div class="space-y-3">
        <?php foreach ($recent_orders as $o): ?>
          <div class="flex items-center justify-between border-b border-neutral-800 pb-2">
            <div>#<?= (int)$o['id'] ?> <span class="text-xs text-gray-400 ml-2"><?= e(date('M j, Y', strtotime($o['created_at']))) ?></span></div>
            <div class="text-gold">$<?= number_format((float)$o['total_amount'], 2) ?></div>
            <div class="text-xs text-gray-400 uppercase"><?= e($o['status']) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (!$recent_orders): ?>
          <div class="text-gray-400">No recent orders.</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="bg-neutral-900 border border-neutral-800 p-5 rounded">
      <h2 class="font-semibold mb-3">Quick Links</h2>
      <div class="grid grid-cols-2 gap-3 text-sm">
        <a class="border border-neutral-700 rounded p-3 hover:border-gold" href="/hatrox-project/admin/users.php">Manage Users</a>
        <a class="border border-neutral-700 rounded p-3 hover:border-gold" href="/hatrox-project/admin/products.php">Manage Products</a>
        <a class="border border-neutral-700 rounded p-3 hover:border-gold" href="/hatrox-project/admin/orders.php">Manage Orders</a>
        <a class="border border-neutral-700 rounded p-3 hover:border-gold" href="/hatrox-project/admin/comments.php">Moderate Comments</a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>


