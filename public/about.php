<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

$comments = [];
if ($stmt = $mysqli->prepare('SELECT user_name, rating, comment_text, created_at FROM comments WHERE is_approved = 1 AND is_hidden = 0 ORDER BY created_at DESC LIMIT 10')) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $comments[] = $row; }
    $stmt->close();
}

$draft = $_SESSION['testimonial_draft'] ?? [];
$draftComment = isset($draft['comment']) ? (string)$draft['comment'] : '';
$draftRating = isset($draft['rating']) ? (int)$draft['rating'] : 5;
if ($draftRating < 1 || $draftRating > 5) { $draftRating = 5; }

$userLoggedIn = !empty($_SESSION['user_id']);
$userName = $userLoggedIn ? (string)($_SESSION['full_name'] ?? '') : '';
$userEmail = $userLoggedIn ? (string)($_SESSION['email'] ?? '') : '';

$userHasCompletedOrder = false;
if ($userLoggedIn) {
    if ($stmt = $mysqli->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = "completed"')) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($cnt);
        if ($stmt->fetch() && (int)$cnt > 0) {
            $userHasCompletedOrder = true;
        }
        $stmt->close();
    }
}

if (isset($_GET['submitted'])) {
    unset($_SESSION['testimonial_draft']);
    $draftComment = '';
    $draftRating = 5;
}

include __DIR__ . '/../includes/header.php';
?>

<style>
  @media (min-width: 768px) {
    #testimonialsGrid .bg-neutral-900.expanded { grid-column: span 2; }
  }
  #testimonialsGrid .bg-neutral-900 { transition: all .18s ease; }
</style>

<section class="max-w-5xl mx-auto px-4 py-12">
    <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold tracking-widest inline-flex items-center leading-none">
  <span class="text-silver pr-2">About</span>
  <span class="text-gold">HATRO</span>
  <img src="/hatrox-project/assets/images/icons/icon.png"
    alt="HATROX Logo"
    class="h-6 sm:h-8 md:h-10 w-auto object-contain -ml-1 transition group-hover:opacity-80">
  </h1>
  <p class="mt-4 text-gray-300 leading-relaxed"><span class="text-gold">HATRO:X</span> is a dynamic jewelry e-commerce platform designed and developed using PHP, MySQL, and Tailwind CSS. The name HATROX represents the collaboration of its creators — Harsh (<span class="text-gold">HA</span>), Tushar (<span class="text-gold">T</span>), and Ronak (<span class="text-gold">RO</span>) — with the letter ‘<span class="text-gold">X</span>’ symbolizing the brand’s unique identity and elegance.</p>
  <p class="mt-4 text-gray-300 leading-relaxed">This <span class="text-gold">HATRO:X</span> project is a web-based platform designed to simplify and automate the entire business workflow.HATROX aims to digitalize jewellery shopping by offering convenience, better accessibility, and a user-friendly online platform.The platform allows users to browse a wide collection of jewellery items, view product details, and make purchases with ease. The main purpose is to offer a digital solution to improve accessibility, productivity, and efficiency.

  <?php
$adminDir = __DIR__ . '/../assets/images/Admins';
$imgs = [];
if (is_dir($adminDir)) {
    $files = glob($adminDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
    foreach ($files as $f) {
        $imgs[] = '../assets/images/Admins/' . basename($f);
    }
}
?>
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mt-8">
  <?php if ($imgs): ?>
    <?php foreach ($imgs as $src): ?>
      <div class="w-full aspect-square bg-cover bg-center rounded border border-neutral-800" style="background-image: url('<?= e($src) ?>')"></div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="w-full aspect-square bg-neutral-800 rounded border border-neutral-700"></div>
    <div class="w-full aspect-square bg-neutral-800 rounded border border-neutral-700"></div>
    <div class="w-full aspect-square bg-neutral-800 rounded border border-neutral-700"></div>
  <?php endif; ?>
</div>
</section>

<section class="max-w-3xl mx-auto px-4 pb-16" id="testimonial-section">
  <h2 class="text-2xl font-semibold mb-4">Leave a Testimonial</h2>
  <?php if (isset($_GET['submitted'])): ?>
    <div class="mb-4 p-3 bg-emerald-900/40 border border-emerald-800 text-emerald-200 rounded">Thank you! Your comment awaits approval.</div>
  <?php elseif (isset($_GET['err'])): ?>
    <?php if ($_GET['err'] === 'purchase'): ?>
      <div class="mb-4 p-3 bg-rose-900/40 border border-rose-800 text-rose-200 rounded">Only customers with a completed order can leave a testimonial.</div>
    <?php else: ?>
      <div class="mb-4 p-3 bg-rose-900/40 border border-rose-800 text-rose-200 rounded">Please fill all fields correctly.</div>
    <?php endif; ?>
  <?php endif; ?>
  <form id="testimonialForm" method="post" action="/hatrox-project/utilities/post_comment.php" class="space-y-4 bg-neutral-900 border border-neutral-800 p-6 rounded">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php if ($userLoggedIn): ?>
      <div class="text-sm text-gray-400">Logged in as <span class="text-gold"><?= e($userName ?: $userEmail) ?></span></div>
      <?php if (!$userHasCompletedOrder): ?>
        <div class="text-sm text-rose-300 bg-rose-900/30 border border-rose-800 rounded px-3 py-2">You need at least one completed order before leaving a testimonial.</div>
      <?php endif; ?>
    <?php else: ?>
      <div class="text-sm text-gray-400 bg-neutral-950/70 border border-neutral-800 rounded px-3 py-2">Please log in before submitting. Your draft will be saved.</div>
    <?php endif; ?>
    <div>
      <label class="block text-sm mb-1">Comment</label>
      <textarea name="comment" rows="4" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2 focus:outline-none focus:border-gold"><?= e($draftComment) ?></textarea>
    </div>
    <div>
      <label class="block text-sm mb-1">Rating</label>
      <div id="stars" class="flex space-x-2 cursor-pointer">
        <?php for ($i=1; $i<=5; $i++): ?>
          <svg data-rate="<?= $i ?>" class="w-7 h-7 text-neutral-600 hover:text-gold" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.197 3.6a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.99 20.537a.562.562 0 01-.84-.61l1.285-5.386a.563.563 0 00-.182-.557l-4.197-3.6a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
        <?php endfor; ?>
      </div>
      <input type="hidden" name="rating" id="rating" value="<?= (int)$draftRating ?>" />
    </div>
    <button class="w-full bg-gold text-black font-medium py-2 rounded hover:bg-rose transition <?= ($userLoggedIn && !$userHasCompletedOrder) ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= ($userLoggedIn && !$userHasCompletedOrder) ? 'disabled' : '' ?>>Submit</button>
  </form>
</section>

<section class="max-w-5xl mx-auto px-4 pb-16">
  <h2 class="text-2xl font-semibold mb-4">Testimonials</h2>
  <div id="testimonialsGrid" class="grid md:grid-cols-2 gap-6">
    <?php foreach ($comments as $c): ?>
      <div class="bg-neutral-900 border border-neutral-800 rounded p-5">
        <div class="flex items-center mb-2">
          <?php for ($i=1; $i<=5; $i++): ?>
            <svg class="w-5 h-5 <?= $i <= (int)$c['rating'] ? 'text-gold' : 'text-neutral-700' ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.197 3.6a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.562 0 00-.586 0L6.99 20.537a.562.562 0 01-.84-.61l1.285-5.386a.563.563 0 00-.182-.557l-4.197-3.6a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
          <?php endfor; ?>
        </div>
        <div class="text-gray-300 break-words overflow-hidden comment-display" style="word-break: break-word; max-width: 100%; overflow-wrap: break-word;" data-full="<?= e($c['comment_text']) ?>" data-short="<?= e(strlen($c['comment_text']) > 70 ? substr($c['comment_text'], 0, 70) . '...' : $c['comment_text']) ?>">"<span class="comment-text"><?= e(strlen($c['comment_text']) > 70 ? substr($c['comment_text'], 0, 70) . '...' : $c['comment_text']) ?></span>"</div>
        <?php if (strlen($c['comment_text']) > 70): ?><button class="text-xs text-gold hover:text-rose transition mt-1 expand-btn" onclick="toggleComment(event)">Read more</button><?php endif; ?>
        <div class="text-sm text-gray-500 mt-2">— <?= e($c['user_name']) ?> · <?= e(date('M j, Y', strtotime($c['created_at']))) ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$comments): ?>
      <div class="text-gray-400">No testimonials yet.</div>
    <?php endif; ?>
  </div>
  <div class="flex justify-center space-x-4 mt-6">
    <button id="tPrev" class="px-3 py-1 border border-neutral-700 rounded hover:border-gold">Prev</button>
    <button id="tNext" class="px-3 py-1 border border-neutral-700 rounded hover:border-gold">Next</button>
  </div>
</section>

<script>
  function toggleComment(e) {
    e.preventDefault();
    const btn = e.target;
    const card = btn.closest('.bg-neutral-900');
    if (!card) return;
    const commentDiv = card.querySelector('.comment-display');
    const span = commentDiv.querySelector('.comment-text');
    const grid = document.getElementById('testimonialsGrid');
    const isExpanded = card.classList.contains('expanded');

    if (isExpanded) {
      card.classList.remove('expanded');
      span.textContent = commentDiv.getAttribute('data-short');
      btn.textContent = 'Read more';
      if (window._testimonials && typeof window._testimonials.resetPer === 'function') window._testimonials.resetPer();
    } else {
      if (grid) {
        const others = grid.querySelectorAll('.bg-neutral-900.expanded');
        others.forEach(o => {
          o.classList.remove('expanded');
          const d = o.querySelector('.comment-display');
          const b = o.querySelector('.expand-btn');
          if (d) d.querySelector('.comment-text').textContent = d.getAttribute('data-short');
          if (b) b.textContent = 'Read more';
        });
      }
      card.classList.add('expanded');
      span.textContent = commentDiv.getAttribute('data-full');
      btn.textContent = 'Read less';
      if (grid && window._testimonials && typeof window._testimonials.setPerAndShowIndex === 'function') {
        const all = Array.from(grid.querySelectorAll('.bg-neutral-900'));
        const idx = all.indexOf(card);
        if (idx >= 0) window._testimonials.setPerAndShowIndex(idx, 3);
      }
      if (card && card.scrollIntoView) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  (function(){
    const stars = Array.from(document.querySelectorAll('#stars svg'));
    const input = document.getElementById('rating');
    function paint(n){
      stars.forEach((s,i)=>{
        const on = i < n;
        if (on) { s.classList.add('text-gold'); s.classList.remove('text-neutral-600'); }
        else { s.classList.remove('text-gold'); s.classList.add('text-neutral-600'); }
      });
    }
    stars.forEach(s=> s.addEventListener('click', ()=>{ const n = parseInt(s.getAttribute('data-rate')); input.value = String(n); paint(n); }));
    stars.forEach(s=> s.addEventListener('mouseenter', ()=>{ const n = parseInt(s.getAttribute('data-rate')); paint(n); }));
    document.getElementById('stars')?.addEventListener('mouseleave', ()=>{ paint(parseInt(input.value||'5')); });
    paint(parseInt(input.value||'5'));
  })();

  (function(){
    const grid = document.getElementById('testimonialsGrid');
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.bg-neutral-900'));
    const prev = document.getElementById('tPrev');
    const next = document.getElementById('tNext');
    function calcPerDefault(){
      const w = window.innerWidth;
      if (w >= 1280) return 4;
      if (w >= 1024) return 3;
      if (w >= 768) return 2;
      return 1;
    }
    const perExpanded = 3;
    let per = calcPerDefault();
    let page = 0;

    function render(){
      const pages = Math.max(1, Math.ceil(cards.length / per));
      if (page < 0) page = 0;
      if (page > pages - 1) page = pages - 1;
      cards.forEach((c,i)=>{ const show = i >= page*per && i < (page+1)*per; c.style.display = show ? '' : 'none'; });
      if (prev) { prev.disabled = page<=0; prev.classList.toggle('opacity-50', prev.disabled); }
      if (next) { next.disabled = page>=pages-1; next.classList.toggle('opacity-50', next.disabled); }
    }

    window._testimonials = { cards, setPerAndShowIndex(idx, newPer){ per = newPer; const pages = Math.max(1, Math.ceil(cards.length / per)); page = Math.max(0, Math.min(pages-1, Math.floor(idx / per))); render(); }, resetPer(){ per = calcPerDefault(); const pages = Math.max(1, Math.ceil(cards.length / per)); if (page > pages-1) page = pages-1; render(); }, next(){ const pages = Math.max(1, Math.ceil(cards.length / per)); if (page < pages-1) { page++; render(); } }, prev(){ if (page > 0) { page--; render(); } } };

    prev?.addEventListener('click', ()=> window._testimonials.prev());
    next?.addEventListener('click', ()=> window._testimonials.next());
    window.addEventListener('resize', ()=>{ per = calcPerDefault(); render(); });
    render();
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


