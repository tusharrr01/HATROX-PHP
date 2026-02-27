<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

if (!empty($_SESSION['is_admin'])) {
    header('Location: /hatrox-project/admin/dashboard.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: /hatrox-project/public/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$items = [];
if ($stmt = $mysqli->prepare('SELECT c.product_id, c.quantity, p.name, p.price, p.discount_percent, p.image_url, p.stock_quantity FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?')) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $row['discount_percent'] = isset($row['discount_percent']) ? (int)$row['discount_percent'] : 0;
    $price = (float)$row['price'];
    $disc = $row['discount_percent'];
    $row['effective_price'] = $disc > 0 ? $price * (1 - $disc / 100) : $price;
    // normalize stock_quantity to int or null
    $row['stock_quantity'] = is_numeric($row['stock_quantity']) ? (int)$row['stock_quantity'] : null;
    $items[] = $row;
  }
    $stmt->close();
}

$subtotal = 0.0;
foreach ($items as $it) { $subtotal += (float)$it['effective_price'] * (int)$it['quantity']; }

$recentOrders = [];
$orderItemsById = [];
if ($stmt = $mysqli->prepare('SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5')) {
	$stmt->bind_param('i', $user_id);
	$stmt->execute();
	$res = $stmt->get_result();
	while ($row = $res->fetch_assoc()) { $recentOrders[] = $row; }
	$stmt->close();
}
if ($recentOrders) {
	$orderIds = array_map(fn($o) => (int)$o['id'], $recentOrders);
	$ph = implode(',', array_fill(0, count($orderIds), '?'));
	if ($stmt = $mysqli->prepare("SELECT oi.order_id, oi.quantity, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($ph) ORDER BY oi.order_id DESC, oi.id ASC")) {
		$types = str_repeat('i', count($orderIds));
		$stmt->bind_param($types, ...$orderIds);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$oid = (int)$row['order_id'];
			if (!isset($orderItemsById[$oid])) { $orderItemsById[$oid] = []; }
			$orderItemsById[$oid][] = $row;
		}
		$stmt->close();
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout']) && verify_csrf()) {
  if ($items) {
    // Validate quantities against MAX_ITEM_QUANTITY and product stock before placing order
    foreach ($items as $it) {
      $qty = max(1, (int)$it['quantity']);
      if (defined('MAX_ITEM_QUANTITY') && $qty > MAX_ITEM_QUANTITY) {
        header('Location: /hatrox-project/public/cart.php?error=quantity_limit');
        exit;
      }
      $pid = (int)$it['product_id'];
      if ($stmt = $mysqli->prepare("SELECT stock_quantity FROM products WHERE id = ?")) {
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $stmt->bind_result($stock_q);
        if ($stmt->fetch()) {
          $stock_q = (int)$stock_q;
          if ($qty > $stock_q) {
            header('Location: /hatrox-project/public/cart.php?error=insufficient_stock');
            $stmt->close();
            exit;
          }
        }
        $stmt->close();
      }
    }

    $mysqli->begin_transaction();
        try {
            if ($stmt = $mysqli->prepare('INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, "processing")')) {
                $stmt->bind_param('id', $user_id, $subtotal);
                $stmt->execute();
                $order_id = $stmt->insert_id;
                $stmt->close();
            }
            if ($stmt = $mysqli->prepare('INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)')) {
                foreach ($items as $it) {
                    $pid = (int)$it['product_id'];
                    $qty = (int)$it['quantity'];
                    $price = (float)$it['effective_price'];
                    $stmt->bind_param('iiid', $order_id, $pid, $qty, $price);
                    $stmt->execute();
                    if ($up = $mysqli->prepare('UPDATE products SET sold_count = sold_count + ? WHERE id = ?')) { $up->bind_param('ii', $qty, $pid); $up->execute(); $up->close(); }
                }
                $stmt->close();
            }
            if ($stmt = $mysqli->prepare('DELETE FROM cart WHERE user_id = ?')) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $stmt->close();
            }
            $mysqli->commit();

            $to = $_SESSION['email'] ?? '';
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $subject = 'Your HATRO:X Order #' . $order_id;
                $message = "Thank you for your order. Total: $" . number_format($subtotal,2) . "\r\nOrder ID: " . $order_id;
                @mail($to, $subject, $message, 'From: no-reply@hatrox.com');
            }

            header('Location: /hatrox-project/public/cart.php?success=1');
            exit;
        } catch (Throwable $e) {
            $mysqli->rollback();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="max-w-5xl mx-auto px-4 py-12">
  <h1 class="text-3xl font-semibold mb-6">Your Cart</h1>
  <style>
    /* hide scrollbars but keep scroll behavior */
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .cart-error { color: #f87171; }
    /* hide number input steppers (spinners) cross-browser */
    /* Chrome, Safari, Edge, Opera */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    /* Firefox */
    input[type=number] {
      -moz-appearance: textfield;
    }

    /* Responsive: convert table rows into stacked cards on small screens */
    @media (max-width: 768px) {
      .cart-table, .cart-table thead, .cart-table tbody, .cart-table th, .cart-table td, .cart-table tr { display: block; }
      .cart-table thead { display: none; }
      .cart-table tbody { padding: 0; }
      /* two-column card: image column + details column */
      .cart-row { border-top: none; margin-bottom: 1rem; padding: 0.9rem; background: rgba(255,255,255,0.02); border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.03); display: grid; grid-template-columns: 56px 1fr; gap: 0.75rem; align-items: start; }
      .cart-row td { padding: 0; border: none; }
      /* put first cell (product) into column 1, other cells occupy column 2 stacked */
      .cart-row td:first-child { grid-column: 1; }
      .cart-row td:not(:first-child) { grid-column: 2; display: block; padding: 0.25rem 0; }
      .cart-row td[data-label] { position: relative; padding-left: 0; }
      /* label above value for better wrapping */
      .cart-row td[data-label]::before { content: attr(data-label); font-weight: 600; display: block; margin-bottom: 0.25rem; color: #9ca3af; font-size: .95rem; }
      .product-cell { display:flex; align-items:flex-start; gap:0.75rem; }
      .product-cell img { width:56px; height:56px; flex: 0 0 auto; border-radius:6px; }
      .product-name { word-break: break-word; white-space: normal; color: #e5e7eb; font-size: 1rem; }
      .stock-pill { display:inline-block; padding: 0.12rem 0.5rem; border-radius: 999px; font-size: 0.75rem; line-height: 1; margin-top: 0.25rem; }
      .stock-in { background: rgba(16,185,129,0.08); color: #34d399; }
      .stock-out { background: rgba(239,68,68,0.06); color: #fb7185; }
      .stock-na { color: #9ca3af; }
      .qty-input { width:3.25rem; }
      .cart-actions { display:flex; gap:0.5rem; justify-content:flex-end; }

      /* buttons and controls - larger tap targets on mobile */
      .step-btn { padding: 0.45rem 0.6rem; min-width: 36px; border-radius: 6px; }
      .update-btn { display:inline-block; padding: 0.5rem 0.75rem; border-radius: 6px; background: #fbbf24; color: #000; border: none; font-weight:600; }
      .remove-btn { display:inline-block; padding: 0.45rem 0.75rem; border-radius: 6px; background: transparent; border: 1px solid rgba(239,68,68,0.7); color: #fca5a5; }
      .cart-row form.inline-flex { flex-wrap: wrap; gap: 0.5rem; }
    }
  </style>
  <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 p-3 bg-emerald-900/40 border border-emerald-800 text-emerald-200 rounded">Order placed! We'll email confirmation.</div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'quantity_limit'): ?>
      <div class="mb-4 p-3 bg-rose-900/40 border border-rose-800 text-rose-200 rounded">One or more items exceed the allowed maximum quantity. Please reduce quantities.</div>
    <?php elseif ($_GET['error'] === 'insufficient_stock' || $_GET['error'] === 'out_of_stock'): ?>
      <div class="mb-4 p-3 bg-rose-900/40 border border-rose-800 text-rose-200 rounded">One or more items have insufficient stock. Please adjust your cart.</div>
    <?php endif; ?>
  <?php endif; ?>
  <?php
    // capture per-item cart errors for rendering, then clear the session copy
    $cart_item_errors = $_SESSION['cart_errors'] ?? [];
    if (!empty($_SESSION['cart_errors'])) {
        unset($_SESSION['cart_errors']);
    }
  ?>
  <div class="overflow-x-auto bg-neutral-900 border border-neutral-800 rounded no-scrollbar">
    <table class="cart-table min-w-full text-sm">
      <thead class="bg-neutral-950">
        <tr>
          <th class="text-left p-3">Product</th>
          <th class="text-left p-3">Price</th>
          <th class="text-left p-3">Quantity</th>
          <th class="text-left p-3">Total</th>
          <th class="p-3"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr id="cart-row-<?= (int)$it['product_id'] ?>" class="cart-row border-t border-neutral-800">
            <td class="p-3" data-label="Product">
              <div class="product-cell flex items-center space-x-3">
                <img src="<?= e($it['image_url']) ?>" class="w-12 h-10 md:w-16 md:h-12 object-cover rounded border border-neutral-800" alt="<?= e($it['name']) ?>">
                <div>
                  <div class="product-name"><?= e($it['name']) ?></div>
                  <?php
                    // render stock badge inline with product name for better layout
                    if (is_numeric($it['stock_quantity'])) {
                        if ($it['stock_quantity'] > 0) {
                            $stockBadgeClass = 'text-emerald-300';
                            $stockBadgeText = 'In stock: ' . (int)$it['stock_quantity'];
                        } else {
                            $stockBadgeClass = 'text-rose-300';
                            $stockBadgeText = 'Out of stock';
                        }
                    } else {
                        $stockBadgeClass = 'text-gray-400';
                        $stockBadgeText = 'Stock: N/A';
                    }
                  ?>
                  <div class="mt-1 text-xs <?= $stockBadgeClass ?>" aria-live="polite"><?= e($stockBadgeText) ?></div>
                </div>
              </div>
            </td>
            <td class="p-3" data-label="Price">$<?= number_format((float)$it['effective_price'], 2) ?></td>
            <td class="p-3" data-label="Quantity">
              <form method="post" action="/hatrox-project/utilities/update_cart.php" class="inline-flex items-center space-x-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
                <button type="button" class="step-btn px-2 border border-neutral-700 rounded" onclick="const q=this.parentElement.querySelector('input[name=quantity]'); q.stepDown(); q.dispatchEvent(new Event('change',{bubbles:true}));">-</button>
                <?php
                  $clientMax = 5;
                  if (is_numeric($it['stock_quantity'])) {
                      $clientMax = min(5, (int)$it['stock_quantity']);
                  }
                ?>
                <input type="number" name="quantity" min="1" step="1" max="<?= $clientMax ?>" value="<?= (int)$it['quantity'] ?>" data-product-id="<?= (int)$it['product_id'] ?>" class="w-16 text-center bg-neutral-800 border border-neutral-700 rounded py-1 qty-input" />
                <button type="button" class="step-btn px-2 border border-neutral-700 rounded" onclick="const q=this.parentElement.querySelector('input[name=quantity]'); q.stepUp(); q.dispatchEvent(new Event('change',{bubbles:true}));">+</button>
                <button class="update-btn rounded px-2 py-1 border border-neutral-700 hover:border-gold transition" <?php if (is_numeric($it['stock_quantity']) && $it['stock_quantity'] <= 0) echo 'disabled'; ?>>Update</button>
              </form>
              <?php
                // show any per-item errors (kept separate from stock badge)
                $pid = (int)$it['product_id'];
                if (!empty($cart_item_errors[$pid])): ?>
                  <div class="mt-1 text-xs cart-error" id="cart-err-<?= $pid ?>"><?= e($cart_item_errors[$pid]) ?></div>
              <?php endif; ?>
            </td>
            <td class="p-3" data-label="Total">$<?= number_format((float)$it['effective_price'] * (int)$it['quantity'], 2) ?></td>
            <td class="p-3" data-label="Actions">
              <div class="cart-actions">
                <form method="post" action="/hatrox-project/utilities/update_cart.php">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
                  <input type="hidden" name="action" value="remove">
                  <button class="remove-btn rounded px-2 py-1 border border-neutral-700 hover:border-gold transition">Remove</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
          <tr><td class="p-3" colspan="5">Your cart is empty.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-6 flex items-center justify-between">
    <div class="text-xl">Subtotal: <span class="text-gold">$<?= number_format($subtotal, 2) ?></span></div>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="checkout" value="1">
      <button class="bg-gold text-black font-medium px-6 py-2 rounded hover:bg-rose transition" <?= $items ? '' : 'disabled' ?>>Proceed to Checkout</button>
    </form>
  </div>

  <?php if ($recentOrders): ?>
  <div class="mt-10">
    <h2 class="text-2xl font-semibold mb-4">Recent purchases</h2>
    <div class="space-y-3">
      <?php foreach ($recentOrders as $o): ?>
        <div class="bg-neutral-900 border border-neutral-800 rounded p-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="font-medium">Order #<?= (int)$o['id'] ?></div>
              <div class="text-xs text-gray-400"><?= e(date('M j, Y H:i', strtotime($o['created_at']))) ?></div>
            </div>
            <div class="text-right">
              <div class="uppercase text-xs <?= $o['status']==='completed' ? 'text-emerald-400' : ($o['status']==='processing' ? 'text-amber-300' : ($o['status']==='cancelled' ? 'text-rose-300' : 'text-gray-300')) ?>"><?= e($o['status']) ?></div>
              <div class="text-gold">$<?= number_format((float)$o['total_amount'], 2) ?></div>
            </div>
          </div>
          <?php $itemsList = $orderItemsById[(int)$o['id']] ?? []; if ($itemsList): ?>
          <div class="mt-3 text-sm text-gray-300">
            <?php
              $names = array_map(function($r){ return $r['name'].' x'.(int)$r['quantity']; }, $itemsList);
              echo e(implode(', ', $names));
            ?>
          </div>
          <?php endif; ?>

          <?php if ($o['status'] === 'processing'): ?>
            <div class="mt-3">
              <form method="post" action="/hatrox-project/utilities/cancel_order.php" onsubmit="return confirm('Cancel this order?');">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <button class="px-3 py-1 border border-rose-700 text-rose-200 rounded hover:bg-rose/10">Cancel Order</button>
              </form>
            </div>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

  <script>
document.addEventListener('DOMContentLoaded', function(){
  // client-side will respect each input's `max` attribute (set server-side)

  function showInlineError(pid, msg){
    let el = document.getElementById('cart-err-' + pid);
    if (!el) {
      const row = document.getElementById('cart-row-' + pid);
      if (!row) return;
      el = document.createElement('div');
      el.className = 'mt-1 text-xs cart-error';
      el.id = 'cart-err-' + pid;
      row.querySelector('td').appendChild(el);
    }
    el.textContent = msg;
  }

  function clearInlineError(pid){
    const el = document.getElementById('cart-err-' + pid);
    if (el) el.textContent = '';
  }

  document.querySelectorAll('.qty-input').forEach(function(input){
    const pid = input.dataset.productId;
    input.addEventListener('input', function(){
      let v = input.value;
      if (v === '') return;
      if (!/^-?\d+$/.test(v)) {
        showInlineError(pid, 'Quantity must be an integer.');
        return;
      }
      let n = parseInt(v, 10);
      if (n < 1) {
        showInlineError(pid, 'Minimum quantity is 1.');
        input.value = 1;
        return;
      }
      const maxAttr = parseInt(input.getAttribute('max') || '0', 10) || 0;
      if (maxAttr > 0 && n > maxAttr) {
        showInlineError(pid, 'Maximum allowed is ' + maxAttr + '.');
        input.value = maxAttr;
        return;
      }
      clearInlineError(pid);
    });

    // prevent non-numeric paste
    input.addEventListener('paste', function(e){
      const t = e.clipboardData.getData('text');
      if (!/^-?\d+$/.test(t)) e.preventDefault();
    });
  });
});
</script>


