<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

// Ensure admin is logged in
if (empty($_SESSION['is_admin'])) {
    header('Location: /hatrox-project/admin/login.php');
    exit;
}


$comments = [];
if ($stmt = $mysqli->prepare('SELECT user_name, rating, comment_text, created_at FROM comments WHERE is_approved = 1 AND is_hidden = 0 ORDER BY created_at DESC LIMIT 10')) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $comments[] = $row; }
    $stmt->close();
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
  <p class="mt-4 text-gray-300 leading-relaxed"><span class="text-gold">HATRO:X</span> is a dynamic jewelry e-commerce platform designed and developed using PHP, MySQL, and Tailwind CSS. The name HATROX represents the collaboration of its creators — Harsh (<span class="text-gold">HA</span>), Tushar (<span class="text-gold">T</span>), and Ronak (<span class="text-gold">RO</span>) — with the letter ‘<span class="text-gold">:X</span>’ symbolizing the brand’s unique identity and elegance.</p>
  <p class="mt-4 text-gray-300 leading-relaxed">This <span class="text-gold">HATRO:X</span> project is a web-based platform designed to simplify and automate the entire business workflow.HATROX aims to digitalize jewellery shopping by offering convenience, better accessibility, and a user-friendly online platform.The platform allows users to browse a wide collection of jewellery items, view product details, and make purchases with ease. The main purpose is to offer a digital solution to improve accessibility, productivity, and efficiency.</p>

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

<section class="max-w-5xl mx-auto px-4 pb-16">
  <h2 class="text-2xl font-semibold mb-4">Testimonials</h2>
  <div id="testimonialsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
    const isExpanded = card.classList.contains('expanded');

    if (isExpanded) {
      card.classList.remove('expanded');
      span.textContent = commentDiv.getAttribute('data-short');
      btn.textContent = 'Read more';
    } else {
      card.classList.add('expanded');
      span.textContent = commentDiv.getAttribute('data-full');
      btn.textContent = 'Read less';
      if (card && card.scrollIntoView) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  (function(){
    const grid = document.getElementById('testimonialsGrid');
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.bg-neutral-900'));
    const prev = document.getElementById('tPrev');
    const next = document.getElementById('tNext');
    // choose items per page based on viewport width
    function calcPerDefault(){
      const w = window.innerWidth;
      if (w >= 1280) return 4; // xl
      if (w >= 1024) return 3; // lg
      if (w >= 768) return 2;  // md
      return 1;                // sm/xs
    }
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

    prev?.addEventListener('click', ()=>{ if (page > 0) { page--; render(); } });
    next?.addEventListener('click', ()=>{ const pages = Math.max(1, Math.ceil(cards.length / per)); if (page < pages-1) { page++; render(); } });
    // recalc on resize to remain responsive
    window.addEventListener('resize', ()=>{ per = calcPerDefault(); render(); });
    render();
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
