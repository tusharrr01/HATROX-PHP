<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

// New Arrivals (3)
$featured = [];
if ($stmt = $mysqli->prepare("SELECT id, name, price, discount_percent, image_url FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 3")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $featured[] = $row; }
    $stmt->close();
}

$testimonials = [];
if ($stmt = $mysqli->prepare('SELECT user_name, rating, comment_text FROM comments WHERE is_approved = 1 AND is_hidden = 0 ORDER BY created_at DESC LIMIT 6')) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $testimonials[] = $row; }
    $stmt->close();
}

// Top 7 rated (by avg rating), top 7 sold (min 5), top 7 discount
$topRated = [];
$sqlRated = "SELECT p.id, p.name, p.image_url, p.price, p.discount_percent,
            COALESCE(AVG(r.rating),0) AS avg_rating, COUNT(r.rating) AS rating_count
            FROM products p LEFT JOIN product_ratings r ON r.product_id = p.id
            WHERE p.status='active' GROUP BY p.id ORDER BY avg_rating DESC, p.id DESC LIMIT 7";
if ($res = $mysqli->query($sqlRated)) { while ($row=$res->fetch_assoc()) { $topRated[]=$row; } }

$topSold = [];
if ($res = $mysqli->query("SELECT id, name, image_url, price, discount_percent, sold_count FROM products WHERE status='active' AND sold_count >= 5 ORDER BY sold_count DESC, id DESC LIMIT 7")) { while ($row=$res->fetch_assoc()) { $topSold[]=$row; } }

$topDiscount = [];
if ($res = $mysqli->query("SELECT id, name, image_url, price, discount_percent FROM products WHERE status='active' AND discount_percent>0 ORDER BY discount_percent DESC, id DESC LIMIT 7")) { while ($row=$res->fetch_assoc()) { $topDiscount[]=$row; } }

include __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section (Background Video) -->
<section class="relative h-[70vh] sm:h-[80vh] overflow-hidden">
  <video class="absolute inset-0 w-full h-full object-cover opacity-40" src="../assets/images/HATROX1.mp4" autoplay muted loop playsinline></video>
  <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
  <div class="relative z-10 h-full flex flex-col items-center justify-center text-center px-4">
    <h1 class="text-4xl sm:text-6xl font-bold tracking-widest inline-flex items-center leading-none">
    <span class="text-gold leading-none">HATRO</span>
    <img src="/hatrox-project/assets/images/icons/icon.png"
        alt="HATROX Logo"
        class="h-12 sm:h-16 w-auto object-contain -ml-1 m-0 p-0">
    </h1>
    <p class="mt-4 text-gray-300 max-w-2xl">Luxury jewelry reimagined. Discover rings, necklaces, bracelets, and earrings crafted to perfection.</p>
    <a href="/hatrox-project/public/shop.php" class="mt-8 inline-block bg-gold text-black px-6 py-3 rounded hover:bg-rose transition">Shop Collections</a>
  </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <h2 class="text-2xl font-semibold mt-10 mb-6">New Arrivals</h2>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
    <?php foreach ($featured as $p): $price=(float)$p['price']; $disc=(int)($p['discount_percent']??0); $new=$disc>0?$price*(1-$disc/100):$price; ?>
      <a href="/hatrox-project/public/product-detail.php?id=<?= (int)$p['id'] ?>" class="group block bg-neutral-900 border border-neutral-800 rounded overflow-hidden hover:shadow-xl hover:shadow-gold/10 transition">
        <div class="aspect-[4/3] overflow-hidden">
          <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
        </div>
        <div class="p-4">
          <div class="font-medium truncate" title="<?= e($p['name']) ?>"><?= e($p['name']) ?></div>
          <div class="mt-1 text-gold"><?php if($disc>0): ?><span class="line-through text-gray-500 mr-2">$<?= number_format($price,2) ?></span><?php endif; ?>$<?= number_format($new,2) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (!$featured): ?>
      <div class="text-gray-400">No featured products yet.</div>
    <?php endif; ?>
  </div>
</section>

<section class=" border-t border-neutral-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-10">
    <?php $sections = [ ['Top Rated',$topRated,true], ['Top Sellers',$topSold,false], ['Top Discounts',$topDiscount,false] ]; foreach ($sections as [$title,$list,$showStars]): ?>
      <div>
        <h3 class="text-xl font-semibold mb-4"><?= e($title) ?></h3>
        <div class="overflow-hidden group [mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)]">
          <div class="flex gap-4 animate-marquee group-hover:[animation-play-state:paused] will-change-transform">
            <?php for ($loop=0;$loop<2;$loop++): foreach ($list as $p): $price=(float)$p['price']; $disc=(int)($p['discount_percent']??0); $new=$disc>0?$price*(1-$disc/100):$price; ?>
              <a href="/hatrox-project/public/product-detail.php?id=<?= (int)$p['id'] ?>" class="min-w-[300px] max-w-[300px] bg-neutral-900 border border-neutral-800 rounded overflow-hidden hover:shadow-lg hover:shadow-gold/10 transition relative">
                <?php if ($disc>0): ?><div class="absolute top-2 left-2 bg-rose text-white text-xs px-2 py-0.5 rounded">-<?= $disc ?>%</div><?php endif; ?>
                <div class="aspect-[4/3] overflow-hidden">
                  <img src="<?= e($p['image_url']) ?>" class="w-full h-full object-cover" />
                </div>
                <div class="p-3">
                  <div class="truncate" title="<?= e($p['name']) ?>"><?= e($p['name']) ?></div>
                  <div class="text-gold text-sm mt-1"><?php if($disc>0): ?><span class="line-through text-gray-500 mr-1">$<?= number_format($price,2) ?></span><?php endif; ?>$<?= number_format($new,2) ?></div>
                  <?php if ($showStars): $avg=(float)($p['avg_rating']??0); $rc=(int)($p['rating_count']??0); ?>
                    <div class="mt-1 flex items-center space-x-2 text-xs text-gray-400">
                      <div class="flex">
                        <?php for ($i=1;$i<=5;$i++): $on = $i <= floor($avg); ?>
                          <svg class="w-4 h-4 <?= $on?'text-gold':'text-neutral-700' ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.197 3.6a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.99 20.537a.562.562 0 01-.84-.61l1.285-5.386a.563.563 0 00-.182-.557l-4.197-3.6a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                        <?php endfor; ?>
                      </div>
                      <span>(<?= number_format($avg,1) ?> · <?= $rc ?>)</span>
                    </div>
                  <?php endif; ?>
                </div>
              </a>
            <?php endforeach; endfor; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<style>
@keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
.animate-marquee { animation: marquee 40s linear infinite; }
</style>

<section class="bg-neutral-950 border-t border-neutral-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-2xl font-semibold mb-6">What our clients say</h2>
    <div id="testimonialSlider" class="relative overflow-hidden">
      <div id="testimonialTrack" class="flex transition-transform duration-700">
        <?php foreach ($testimonials as $t): ?>
          <div class="min-w-full px-2">
            <div class="bg-neutral-900 border border-neutral-800 rounded p-6">
              <div class="flex items-center mb-3">
                <?php for ($i=1; $i<=5; $i++): ?>
                  <svg class="w-5 h-5 <?= $i <= (int)$t['rating'] ? 'text-gold' : 'text-neutral-700' ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.197 3.6a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.99 20.537a.562.562 0 01-.84-.61l1.285-5.386a.563.563 0 00-.182-.557l-4.197-3.6a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                <?php endfor; ?>
              </div>
              <p class="text-gray-300 italic break-words overflow-hidden comment-display" style="word-break: break-word; max-width: 100%; overflow-wrap: break-word;" data-full="<?= e($t['comment_text']) ?>" data-short="<?= e(strlen($t['comment_text']) > 70 ? substr($t['comment_text'], 0, 70) . '...' : $t['comment_text']) ?>">"<span class="comment-text"><?= e(strlen($t['comment_text']) > 70 ? substr($t['comment_text'], 0, 70) . '...' : $t['comment_text']) ?></span>"</p>
              <?php if (strlen($t['comment_text']) > 70): ?><button class="text-xs text-gold hover:text-rose transition mt-1 expand-btn" onclick="toggleComment(event)">Read more</button><?php endif; ?>
              <div class="mt-3 text-sm text-gray-400">— <?= e($t['user_name']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$testimonials): ?>
          <div class="min-w-full px-2">
            <div class="bg-neutral-900 border border-neutral-800 rounded p-6 text-gray-400">No testimonials yet.</div>
          </div>
        <?php endif; ?>
      </div>
      <div class="flex justify-center space-x-4 mt-6">
        <button id="prevT" class="px-3 py-1 border border-neutral-700 rounded hover:border-gold">Prev</button>
        <button id="nextT" class="px-3 py-1 border border-neutral-700 rounded hover:border-gold">Next</button>
      </div>
    </div>
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

  (function(){
    const track = document.getElementById('testimonialTrack');
    const slides = track ? track.children.length : 0;
    let index = 0;
    function render(){
      if (!track) return;
      track.style.transform = `translateX(-${index * 100}%)`;
    }
    document.getElementById('prevT')?.addEventListener('click', ()=>{ index = (index - 1 + slides) % slides; render(); });
    document.getElementById('nextT')?.addEventListener('click', ()=>{ index = (index + 1) % slides; render(); });
    setInterval(()=>{ if (slides>0){ index = (index + 1) % slides; render(); } }, 5000);
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


