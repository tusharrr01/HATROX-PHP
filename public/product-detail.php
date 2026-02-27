<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
if ($stmt = $mysqli->prepare("SELECT id, name, description, price, discount_percent, image_url, stock_quantity FROM products WHERE id = ? AND status = 'active'")) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    $stmt->close();
}

$avg_rating = 0.0; $rating_count = 0;
if ($stmt = $mysqli->prepare('SELECT COALESCE(AVG(rating),0) AS avg_r, COUNT(*) AS cnt FROM product_ratings WHERE product_id = ?')) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($avg_rating, $rating_count);
    $stmt->fetch();
    $stmt->close();
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5; $offset = ($page - 1) * $perPage;
$reviews = [];
if ($stmt = $mysqli->prepare('SELECT pr.rating, pr.comment, pr.created_at, u.full_name FROM product_ratings pr LEFT JOIN users u ON u.id = pr.user_id WHERE pr.product_id = ? ORDER BY pr.id DESC LIMIT ? OFFSET ?')) {
    $stmt->bind_param('iii', $id, $perPage, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $reviews[] = $row; }
    $stmt->close();
}

$canReview = false;
if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    if ($st = $mysqli->prepare('SELECT COUNT(*) FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ?')) {
        $st->bind_param('ii', $uid, $id);
        $st->execute();
        $st->bind_result($pc);
        if ($st->fetch() && (int)$pc > 0) {
            $canReview = true;
        }
        $st->close();
    }
}

include __DIR__ . '/../includes/header.php';
if (!$product): ?>
  <section class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-neutral-900 border border-neutral-800 p-6 rounded">Product not found.</div>
  </section>
<?php include __DIR__ . '/../includes/footer.php'; exit; endif; ?>

<section class="max-w-6xl mx-auto px-4 py-12 grid md:grid-cols-2 gap-8">
  <div>
    <div class="aspect-square overflow-hidden rounded border border-neutral-800">
      <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" class="w-full h-full object-cover" />
    </div>
  </div>
  <div>
    <h1 class="text-3xl font-semibold"><?= e($product['name']) ?></h1>
    <div class="mt-1 flex items-center space-x-2 text-sm text-gray-400">
      <?php for ($i=1;$i<=5;$i++): $on = $i <= round((float)$avg_rating); ?>
        <svg class="w-4 h-4 <?= $on?'text-gold':'text-neutral-700' ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.197 3.6a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.99 20.537a.562.562 0 01-.84-.61l1.285-5.386a.563.563 0 00-.182-.557l-4.197-3.6a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
      <?php endfor; ?>
      <span>(<?= number_format((float)$avg_rating,1) ?> · <?= (int)$rating_count ?>)</span>
    </div>
    <div class="text-gold text-xl mt-2">
      <?php $price=(float)$product['price']; $disc=(int)($product['discount_percent']??0); if($disc>0){ $new=$price*(1-$disc/100); ?>
        <span class="line-through text-gray-500 mr-2">$<?= number_format($price,2) ?></span>
        <span>$<?= number_format($new,2) ?></span>
        <span class="ml-2 text-xs bg-rose text-white px-2 py-0.5 rounded align-middle">-<?= $disc ?>%</span>
      <?php } else { ?>
        $<?= number_format($price,2) ?>
      <?php } ?>
    </div>
    <div class="mt-4 text-gray-300 leading-relaxed"><?= nl2br(e($product['description'])) ?></div>
    <div class="mt-3 text-sm <?= (int)$product['stock_quantity']>0 ? 'text-emerald-400' : 'text-rose-400' ?>">
      <?= (int)$product['stock_quantity']>0 ? 'In Stock' : 'Out of Stock' ?>
    </div>

    <?php if (empty($_SESSION['is_admin'])): ?>
    <form class="mt-6 space-y-3 bg-neutral-900 border border-neutral-800 p-4 rounded" method="post" action="/hatrox-project/utilities/add_to_cart.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
      <label class="block text-sm">Quantity</label>
      <div class="flex items-center space-x-2">
        <button type="button" id="qtyMinus" class="px-3 py-1 border border-neutral-700 rounded">-</button>
        <input id="qty" name="quantity" value="1" class="w-16 text-center bg-neutral-800 border border-neutral-700 rounded py-1" />
        <button type="button" id="qtyPlus" class="px-3 py-1 border border-neutral-700 rounded">+</button>
      </div>
      <button class="w-full bg-gold text-black font-medium py-2 rounded hover:bg-rose transition" <?= (int)$product['stock_quantity']>0 ? '' : 'disabled' ?>>Add to Cart</button>
    </form>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($_SESSION['user_id'])): ?>
<section class="max-w-6xl mx-auto px-4 pb-12">
  <h2 class="text-xl font-semibold mb-3">Rate this product</h2>
  <?php if ($canReview): ?>
    <form method="post" action="/hatrox-project/utilities/rate_product.php" class="bg-neutral-900 border border-neutral-800 p-4 rounded space-y-3">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
      <div id="rateStars" class="flex space-x-2 cursor-pointer">
        <?php for ($i=1;$i<=5;$i++): ?>
          <svg data-rate="<?= $i ?>" class="w-7 h-7 text-neutral-600 hover:text-gold" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.197 3.6a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.99 20.537a.562.562 0 01-.84-.61l1.285-5.386a.563.563 0 00-.182-.557l-4.197-3.6a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
        <?php endfor; ?>
      </div>
      <input type="hidden" id="ratingVal" name="rating" value="5">
      <div>
        <label class="block text-sm mb-1">Your review</label>
        <textarea name="comment" rows="3" placeholder="Share your experience..." class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2"></textarea>
      </div>
      <button class="bg-gold text-black px-4 py-2 rounded hover:bg-rose transition">Submit</button>
    </form>
  <?php else: ?>
    <div class="bg-neutral-900 border border-neutral-800 p-4 rounded text-sm text-gray-300">
      You can rate and review this product only after purchasing it (completed order).
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="max-w-6xl mx-auto px-4 pb-16">
  <h2 class="text-xl font-semibold mb-4">Recent reviews</h2>
  <div class="space-y-4">
    <?php foreach ($reviews as $r): ?>
      <div class="bg-neutral-900 border border-neutral-800 rounded p-4">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-300"><?= e($r['full_name'] ?? 'Anonymous') ?></div>
          <div class="text-xs text-gray-500"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></div>
        </div>
        <div class="mt-1 flex">
          <?php for ($i=1;$i<=5;$i++): ?>
            <svg class="w-4 h-4 <?= $i <= (int)$r['rating'] ? 'text-gold' : 'text-neutral-700' ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.197 3.6a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.99 20.537a.562.562 0 01-.84-.61l1.285-5.386a.563.563 0 00-.182-.557l-4.197-3.6a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
          <?php endfor; ?>
        </div>
        <?php if (!empty($r['comment'])): ?>
          <div class="mt-2 text-gray-300 break-words overflow-hidden comment-display" style="word-break: break-word; max-width: 100%; overflow-wrap: break-word;" data-full="<?= e($r['comment']) ?>" data-short="<?= e(strlen($r['comment']) > 70 ? substr($r['comment'], 0, 70) . '...' : $r['comment']) ?>">"<span class="comment-text"><?= e(strlen($r['comment']) > 70 ? substr($r['comment'], 0, 70) . '...' : $r['comment']) ?></span>"</div>
          <?php if (strlen($r['comment']) > 70): ?><button class="text-xs text-gold hover:text-rose transition mt-1 expand-btn" onclick="toggleComment(event)">Read more</button><?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$reviews): ?><div class="text-gray-400">No reviews yet. Be the first to review.</div><?php endif; ?>
  </div>
  <div class="mt-4 flex justify-between">
    <?php $prev = max(1, $page-1); $next = $page+1; ?>
    <a class="px-3 py-1 border border-neutral-700 rounded hover:border-gold <?= $page<=1?'pointer-events-none opacity-50':'' ?>" href="?id=<?= (int)$product['id'] ?>&page=<?= $prev ?>#reviews">Prev</a>
    <a class="px-3 py-1 border border-neutral-700 rounded hover:border-gold" href="?id=<?= (int)$product['id'] ?>&page=<?= $next ?>#reviews">Next</a>
  </div>
</section>

<script>
  function toggleComment(e) {
    e.preventDefault();
    const btn = e.target;
    const commentDiv = btn.previousElementSibling;
    const span = commentDiv.querySelector('.comment-text');
    const isExpanded = btn.textContent === 'Read less';
    if (isExpanded) {
      span.textContent = commentDiv.getAttribute('data-short');
      btn.textContent = 'Read more';
    } else {
      span.textContent = commentDiv.getAttribute('data-full');
      btn.textContent = 'Read less';
    }
  }

  const qty = document.getElementById('qty');
  document.getElementById('qtyMinus')?.addEventListener('click', ()=>{ const v = Math.max(1, parseInt(qty.value||'1')-1); qty.value = String(v); });
  document.getElementById('qtyPlus')?.addEventListener('click', ()=>{ const v = Math.min(5, Math.max(1, parseInt(qty.value||'1')+1)); qty.value = String(v); });
  (function(){
    const stars=[...document.querySelectorAll('#rateStars svg')]; const input=document.getElementById('ratingVal'); if(!stars.length) return;
    function paint(n){
      stars.forEach((s,i)=>{
        const on = i < n;
        if (on) { s.classList.add('text-gold'); s.classList.remove('text-neutral-600'); }
        else { s.classList.remove('text-gold'); s.classList.add('text-neutral-600'); }
      });
    }
    stars.forEach(s=> s.addEventListener('click', ()=>{ const n=parseInt(s.getAttribute('data-rate')); input.value=String(n); paint(n); }));
    stars.forEach(s=> s.addEventListener('mouseenter', ()=>{ const n=parseInt(s.getAttribute('data-rate')); paint(n); }));
    document.getElementById('rateStars')?.addEventListener('mouseleave', ()=>{ paint(parseInt(input.value||'5')); });
    paint(parseInt(input.value||'5'));
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


