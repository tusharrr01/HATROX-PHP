<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';


$q = trim((string)($_GET['q'] ?? ''));
$cat = trim((string)($_GET['category'] ?? ''));
$ALLOWED_CATEGORIES = [
  'Necklace', 'Jhumka', 'Rings', 'Bracelet', 'Earrings', 'Pendant', 'Chains'
];
$isPartial = isset($_GET['partial']) && $_GET['partial'] === '1';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12; $offset = ($page - 1) * $perPage;
$total = 0; $totalPages = 1;

$products = [];
$sql = "SELECT id, name, price, discount_percent, image_url, category FROM products WHERE status = 'active'";
$params = [];
$types = '';
if ($q !== '') { $sql .= " AND (name LIKE ? OR description LIKE ?)"; $like = "%$q%"; $params[] = &$like; $params[] = &$like; $types .= 'ss'; }
if ($cat !== '') { $sql .= " AND category = ?"; $params[] = &$cat; $types .= 's'; }
$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

if ($stmt = $mysqli->prepare($sql)) {
    if ($params) { $types2 = $types.'ii'; $params[] = &$perPage; $params[] = &$offset; $stmt->bind_param($types2, ...$params); }
    else { $stmt->bind_param('ii', $perPage, $offset); }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $products[] = $row; }
    $stmt->close();
}

$countSql = "SELECT COUNT(*) AS c FROM products WHERE status = 'active'";
$countParams = []; $countTypes = '';
if ($q !== '') { $countSql .= " AND (name LIKE ? OR description LIKE ?)"; $countParams[] = &$like; $countParams[] = &$like; $countTypes .= 'ss'; }
if ($cat !== '') { $countSql .= " AND category = ?"; $countParams[] = &$cat; $countTypes .= 's'; }
if ($stmt = $mysqli->prepare($countSql)) {
    if ($countParams) { $stmt->bind_param($countTypes, ...$countParams); }
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
}
$totalPages = max(1, (int)ceil($total / $perPage));

if (!$isPartial) {
include __DIR__ . '/../includes/header.php';
}
?>

<?php if (!$isPartial): ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
  <form id="filtersForm" method="get" class="bg-neutral-900 border border-neutral-800 p-3 rounded">
    <div class="grid md:grid-cols-2 gap-3">
      <div class="relative">
        <i class="ri-search-2-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"></i>
        <input id="searchInput" name="q" value="<?= e($q) ?>" placeholder="Search products..." class="pl-10 w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
      </div>
      <div class="relative">
        <i class="ri-filter-3-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"></i>
        <select id="categoryInput" name="category" class="pl-10 w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2">
          <option value="">All categories</option>
          <?php foreach ($ALLOWED_CATEGORIES as $c): ?>
            <option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
    </div>
    <noscript>
      <div class="mt-3">
        <button class="bg-gold text-black rounded px-4 py-2 hover:bg-rose transition">Apply</button>
      </div>
    </noscript>
  </form>
</section>
<?php endif; ?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" id="productsContainer">
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
    <?php foreach ($products as $p): ?>
      <a href="/hatrox-project/public/product-detail.php?id=<?= (int)$p['id'] ?>" class="group block bg-neutral-900 border border-neutral-800 rounded overflow-hidden hover:shadow-xl hover:shadow-gold/10 transition relative">
        <?php $disc=(int)($p['discount_percent']??0); if($disc>0): ?>
          <div class="absolute top-2 left-2 z-10 bg-rose text-white text-xs px-2 py-1 rounded">-<?= $disc ?>%</div>
        <?php endif; ?>
        <div class="aspect-[4/3] overflow-hidden">
          <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
        </div>
        <div class="p-4">
          <div class="font-medium truncate" title="<?= e($p['name']) ?>"><?= e($p['name']) ?></div>
          <div class="text-xs text-gray-400 mt-0.5 uppercase"><?= e($p['category']) ?></div>
          <div class="mt-2 text-gold">
            <?php $price=(float)$p['price']; $disc=(int)$p['discount_percent']; if($disc>0){ $new=$price*(1-$disc/100); ?>
              <span class="line-through text-gray-500 mr-2">$<?= number_format($price,2) ?></span>
              <span>$<?= number_format($new,2) ?></span>
            <?php } else { ?>
              $<?= number_format($price,2) ?>
            <?php } ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (!$products): ?>
      <div class="text-gray-400">No products available.</div>
    <?php endif; ?>
  </div>
  <div class="mt-8 flex items-center justify-between">
    <?php $prev = max(1, $page-1); $next = min($totalPages, $page+1); ?>
    <a class="px-3 py-1 border border-neutral-700 rounded hover:border-gold <?= $page<=1?'pointer-events-none opacity-50':'' ?> ajax-page" href="?<?= http_build_query(array_merge($_GET, ['page'=>$prev])) ?>">Prev</a>
    <div class="text-xs text-gray-400">Page <?= (int)$page ?> of <?= (int)$totalPages ?></div>
    <a class="px-3 py-1 border border-neutral-700 rounded hover:border-gold <?= $page>=$totalPages?'pointer-events-none opacity-50':'' ?> ajax-page" href="?<?= http_build_query(array_merge($_GET, ['page'=>$next])) ?>">Next</a>
  </div>
</section>


<?php if ($isPartial) { exit; } ?>

  <script>
  (function(){
    const form = document.getElementById('filtersForm');
    let container = document.getElementById('productsContainer');
    if (!form || !container) return;
    const inputs = [document.getElementById('searchInput'), document.getElementById('categoryInput')].filter(Boolean);
    let t = null;

    function buildQuery(){
      const params = new URLSearchParams(new FormData(form));
      params.set('page','1');
      params.set('partial','1');
      return params.toString();
    }

    function attachPaginationHandlers(){
      container.querySelectorAll('a.ajax-page').forEach(a=>{
        a.addEventListener('click', (e)=>{
          e.preventDefault();
          const url = new URL(a.href, window.location.origin);
          url.searchParams.set('partial','1');
          fetchAndReplace(url);
        });
      });
    }

    async function fetchAndReplace(url){
      container.classList.add('opacity-50');
      try {
        const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();
        container.outerHTML = html; 
        const newContainer = document.getElementById('productsContainer');
        if (newContainer) {
          container = newContainer;
        }
        history.replaceState(null, '', url.toString().replace(/([&?])partial=1(&|$)/,'$1').replace(/[&?]$/,''));
        setTimeout(()=>{
          attachPaginationHandlers();
        },0);
      } finally {
        setTimeout(()=>{ document.getElementById('productsContainer')?.classList.remove('opacity-50'); }, 50);
      }
    }

    function submitSoon(){
      if (t) clearTimeout(t);
      t = setTimeout(()=>{
        const qs = buildQuery();
        const url = new URL(window.location.href);
        url.search = '?' + qs;
        fetchAndReplace(url);
      }, 400);
    }

    inputs.forEach(el=>{
      el.addEventListener('input', submitSoon);
      el.addEventListener('change', submitSoon);
    });

    attachPaginationHandlers();
  })();
  </script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


