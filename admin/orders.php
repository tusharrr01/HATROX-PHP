<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) { header('Location: /hatrox-project/admin/login.php'); exit; }

$notice='';
if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf()) {
    $id = (int)($_POST['id'] ?? 0);
    $status = trim((string)($_POST['status'] ?? ''));
    if ($id>0 && $status!=='') {
        if ($stmt = $mysqli->prepare('UPDATE orders SET status = ? WHERE id = ?')) { $stmt->bind_param('si',$status,$id); $stmt->execute(); $stmt->close(); $notice='Order updated.'; }
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15; $offset = ($page - 1) * $perPage; $total=0; $totalPages=1;

$orders = [];
$sql = "SELECT o.id, o.total_amount, o.status, o.created_at, u.full_name, u.email FROM orders o JOIN users u ON u.id = o.user_id WHERE 1=1";
$params = []; $types = '';
if ($statusFilter !== '') { $sql .= " AND o.status = ?"; $params[] = &$statusFilter; $types .= 's'; }
if ($q !== '') { $like = "%$q%"; $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR CAST(o.id AS CHAR) LIKE ?)"; $params[]=&$like; $params[]=&$like; $params[]=&$like; $types.='sss'; }
$sql .= " ORDER BY o.id DESC LIMIT ? OFFSET ?";

if ($stmt = $mysqli->prepare($sql)) {
    if ($params) { $types2 = $types.'ii'; $params[] = &$perPage; $params[] = &$offset; $stmt->bind_param($types2, ...$params); }
    else { $stmt->bind_param('ii', $perPage, $offset); }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row=$res->fetch_assoc()) { $orders[]=$row; }
    $stmt->close();
}

// Count total
$countSql = "SELECT COUNT(*) AS c FROM orders o JOIN users u ON u.id = o.user_id WHERE 1=1"; $countParams=[]; $countTypes='';
if ($statusFilter !== '') { $countSql .= " AND o.status = ?"; $countParams[]=&$statusFilter; $countTypes.='s'; }
if ($q !== '') { $countSql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR CAST(o.id AS CHAR) LIKE ?)"; $countParams[]=&$like; $countParams[]=&$like; $countParams[]=&$like; $countTypes.='sss'; }
if ($stmt = $mysqli->prepare($countSql)) { if ($countParams) { $stmt->bind_param($countTypes, ...$countParams); } $stmt->execute(); $stmt->bind_result($total); $stmt->fetch(); $stmt->close(); }
$totalPages = max(1, (int)ceil($total / $perPage));

function fetch_items(mysqli $db, int $order_id): array {
    $items = [];
    if ($stmt = $db->prepare('SELECT oi.product_id, oi.quantity, oi.price_at_time, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?')) {
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $items[] = $row; }
        $stmt->close();
    }
    return $items;
}

include __DIR__ . '/../includes/header.php';
?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <h1 class="text-2xl font-semibold mb-4">Orders</h1>
  <?php if ($notice): ?><div class="mb-4 p-3 bg-emerald-900/40 border border-emerald-800 text-emerald-200 rounded"><?= e($notice) ?></div><?php endif; ?>

  <form id="ordersFilterForm" method="get" class="mb-6 grid md:grid-cols-3 gap-3 bg-neutral-900 border border-neutral-800 p-4 rounded">
    <div class="relative">
      <i class="ri-search-2-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"></i>
      <input id="ordersSearch" name="q" value="<?= e($q) ?>" placeholder="Search by ID, name or email" class="pl-10 w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2">
    </div>
    <select id="ordersStatus" name="status" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2">
      <option value="">All statuses</option>
      <?php foreach (['processing','completed','cancelled'] as $st): ?>
        <option value="<?= $st ?>" <?= $statusFilter===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button class="bg-gold text-black rounded px-4 py-2 hover:bg-rose transition">Apply</button></noscript>
  </form>

  <?php foreach ($orders as $o): $items = fetch_items($mysqli, (int)$o['id']); ?>
    <div class="mb-4">
      <details class="bg-neutral-900 border border-neutral-800 rounded">
        <summary class="px-4 py-3 cursor-pointer">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <div class="font-semibold">Order #<?= (int)$o['id'] ?> · <?= e($o['full_name']) ?> <span class="text-xs text-gray-500">(<?= e($o['email']) ?>)</span></div>
              <div class="text-xs text-gray-400"><?= e(date('M j, Y H:i', strtotime($o['created_at']))) ?></div>
            </div>
            <div class="flex items-center gap-3">
              <span class="px-2 py-0.5 text-xs rounded uppercase <?php
                switch($o['status']){
                  case 'completed': echo 'bg-emerald-900/40 border border-emerald-800 text-emerald-300'; break;
                  case 'processing': echo 'bg-amber-900/40 border border-amber-800 text-amber-300'; break;
                  case 'cancelled': echo 'bg-rose-900/40 border border-rose-800 text-rose-300'; break;
                  default: echo 'bg-neutral-800 border border-neutral-700 text-gray-300';
                }
              ?>"><?= e(ucfirst($o['status'])) ?></span>
              <div class="text-gold font-medium">$<?= number_format((float)$o['total_amount'],2) ?></div>
            </div>
          </div>
        </summary>
        <div class="px-4 pb-4">
          <div class="flex flex-wrap items-center justify-end gap-2 mb-3">
            <div class="flex items-center gap-2">
              <?php foreach ([
                'processing'=>'Mark Processing',
                'completed'=>'Mark Completed',
                'cancelled'=>'Cancel'
              ] as $st=>$label): if ($o['status']!==$st): ?>
                <form method="post" class="inline">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                  <input type="hidden" name="status" value="<?= $st ?>">
                  <button class="px-3 py-1 border border-neutral-700 rounded hover:border-gold text-xs"><?= e($label) ?></button>
                </form>
              <?php endif; endforeach; ?>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-neutral-950">
                <tr><th class="text-left p-2">Product</th><th class="text-left p-2">Qty</th><th class="text-left p-2">Price</th></tr>
              </thead>
              <tbody>
                <?php foreach ($items as $it): ?>
                  <tr class="border-t border-neutral-800"><td class="p-2"><?= e($it['name']) ?></td><td class="p-2"><?= (int)$it['quantity'] ?></td><td class="p-2">$<?= number_format((float)$it['price_at_time'],2) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </details>
    </div>
  <?php endforeach; ?>

  <div class="mt-6 flex items-center justify-between">
    <?php $prev = max(1, $page-1); $next = min($totalPages, $page+1); ?>
    <a class="px-3 py-1 border border-neutral-700 rounded hover:border-gold <?= $page<=1?'pointer-events-none opacity-50':'' ?>" href="?<?= http_build_query(array_merge($_GET, ['page'=>$prev])) ?>">Prev</a>
    <div class="text-xs text-gray-400">Page <?= (int)$page ?> of <?= (int)$totalPages ?> · <?= (int)$total ?> orders</div>
    <a class="px-3 py-1 border border-neutral-700 rounded hover:border-gold <?= $page>=$totalPages?'pointer-events-none opacity-50':'' ?>" href="?<?= http_build_query(array_merge($_GET, ['page'=>$next])) ?>">Next</a>
  </div>
  </section>

<script>
(function(){
  const form = document.getElementById('ordersFilterForm');
  if (!form) return;
  const q = document.getElementById('ordersSearch');
  const st = document.getElementById('ordersStatus');
  let t = null;
  function submitSoon(){ if (t) clearTimeout(t); t = setTimeout(()=> form.requestSubmit ? form.requestSubmit() : form.submit(), 400); }
  if (q) { q.addEventListener('input', submitSoon); q.addEventListener('change', submitSoon); }
  if (st) { st.addEventListener('change', submitSoon); }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


