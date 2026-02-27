<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) { header('Location: /hatrox-project/admin/login.php'); exit; }

$ALLOWED_CATEGORIES = [
  'Necklace', 'Jhumka', 'Rings', 'Bracelet', 'Earrings', 'Pendant', 'Chains'
];

$errors = [];$notice = isset($_GET['notice']) ? $_GET['notice'] : '';

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $category = trim((string)($_POST['category'] ?? ''));
        if (!in_array($category, $ALLOWED_CATEGORIES, true)) {
          $errors[] = 'Invalid category selected.';
        }
        $stock = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $rawStatus = trim((string)($_POST['status'] ?? 'pending'));
        $status = ($rawStatus === 'active') ? 'active' : 'pending';
        $discount = max(0, min(90, (int)($_POST['discount_percent'] ?? 0)));
        $image_url = trim((string)($_POST['image_url'] ?? ''));

        if (!empty($_FILES['image_file']['name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
            $f = $_FILES['image_file'];
            if ($f['size'] > 2 * 1024 * 1024) { $errors[] = 'Image too large (max 2MB).'; }
            $mime = @mime_content_type($f['tmp_name']);
            $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png'];
            if (!isset($allowed[$mime])) { $errors[] = 'Only JPG/PNG allowed.'; }
            if (!$errors) {
                $ext = $allowed[$mime];
                $safeName = bin2hex(random_bytes(8)) . $ext;
                $dest = __DIR__ . '/../assets/uploads/' . $safeName;
                if (move_uploaded_file($f['tmp_name'], $dest)) {
                    $image_url = '/hatrox-project/assets/uploads/' . $safeName;
                } else {
                    $errors[] = 'Failed to save uploaded file.';
                }
            }
        }

        if ($name===''||$description===''||$price<=0||$category===''||$image_url==='') {
            $errors[] = 'Please fill all required fields correctly.';
        } else {
            //  check for duplicates by (name, category) case-insensitively
            if ($st = $mysqli->prepare('SELECT id FROM products WHERE LOWER(name) = LOWER(?) AND LOWER(category) = LOWER(?) LIMIT 1')) {
                $st->bind_param('ss', $name, $category);
                $st->execute();
                $rs = $st->get_result();
                if ($rs && $rs->fetch_row()) { $errors[] = 'A product with the same name and category already exists.'; }
                $st->close();
            }
            if ($errors) { goto skip_create_insert; }
            if ($stmt = $mysqli->prepare('INSERT INTO products (name, description, price, discount_percent, image_url, category, stock_quantity, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')) {
                $uid = (int)$_SESSION['user_id'];
                $stmt->bind_param('ssdissiis', $name, $description, $price, $discount, $image_url, $category, $stock, $uid, $status);
                if ($stmt->execute()) {
                    header('Location: /hatrox-project/admin/products.php?notice=Product%20created.');
                    exit;
                } else { $errors[]='Unable to create product.'; }
                $stmt->close();
            }
            skip_create_insert:
        }
    } elseif ($action==='delete') {
        $pid = (int)($_POST['id'] ?? 0);
        $mysqli->begin_transaction();
        try {
            if ($stmt = $mysqli->prepare('DELETE FROM order_items WHERE product_id = ?')) { $stmt->bind_param('i',$pid); $stmt->execute(); $stmt->close(); }
            if ($stmt = $mysqli->prepare('DELETE FROM cart WHERE product_id = ?')) { $stmt->bind_param('i',$pid); $stmt->execute(); $stmt->close(); }
            if ($stmt = $mysqli->prepare('DELETE FROM products WHERE id = ?')) { $stmt->bind_param('i',$pid); $stmt->execute(); $stmt->close(); }
            $mysqli->commit();
        } catch (Throwable $e) {
            $mysqli->rollback();
        }
        header('Location: /hatrox-project/admin/products.php?notice=Product%20deleted.');
        exit;
    } elseif ($action==='approve') {
        $pid = (int)($_POST['id'] ?? 0);
        if ($stmt = $mysqli->prepare("UPDATE products SET status='active' WHERE id=?")) { $stmt->bind_param('i',$pid); $stmt->execute(); $stmt->close(); header('Location: /hatrox-project/admin/products.php?notice=Product%20approved.'); exit; }
    } elseif ($action==='update') {
        $pid = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $category = trim((string)($_POST['category'] ?? ''));
        if (!in_array($category, $ALLOWED_CATEGORIES, true)) {
          $errors[] = 'Invalid category selected.';
        }
        $stock = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $rawStatus = trim((string)($_POST['status'] ?? 'pending'));
        $status = ($rawStatus === 'active') ? 'active' : 'pending';
        $discount = max(0, min(90, (int)($_POST['discount_percent'] ?? 0)));
        $image_url = trim((string)($_POST['image_url'] ?? ''));

        if ($image_url === '') {
            if ($st = $mysqli->prepare('SELECT image_url FROM products WHERE id = ?')) {
                $st->bind_param('i', $pid);
                $st->execute();
                $st->bind_result($existing_url);
                if ($st->fetch()) { $image_url = (string)$existing_url; }
                $st->close();
            }
        }

        if (!empty($_FILES['image_file']['name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
            $f = $_FILES['image_file'];
            if ($f['size'] > 2 * 1024 * 1024) { $errors[] = 'Image too large (max 2MB).'; }
            $mime = @mime_content_type($f['tmp_name']);
            $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png'];
            if (!isset($allowed[$mime])) { $errors[] = 'Only JPG/PNG allowed.'; }
            if (!$errors) {
                $ext = $allowed[$mime];
                $safeName = bin2hex(random_bytes(8)) . $ext;
                $dest = __DIR__ . '/../assets/uploads/' . $safeName;
                if (move_uploaded_file($f['tmp_name'], $dest)) {
                    $image_url = '/hatrox-project/assets/uploads/' . $safeName;
                } else {
                    $errors[] = 'Failed to save uploaded file.';
                }
            }
        }

        if ($pid<=0 || $name===''||$description===''||$price<=0||$category===''||$image_url==='') {
            $errors[] = 'Please fill all required fields correctly.';
        } else {
            // check for duplicates on update by (name, category) 
            if ($st = $mysqli->prepare('SELECT id FROM products WHERE LOWER(name) = LOWER(?) AND LOWER(category) = LOWER(?) AND id <> ? LIMIT 1')) {
                $st->bind_param('ssi', $name, $category, $pid);
                $st->execute();
                $rs = $st->get_result();
                if ($rs && $rs->fetch_row()) { $errors[] = 'Another product with the same name and category already exists.'; }
                $st->close();
            }
        }
        if (!$errors && ($stmt = $mysqli->prepare('UPDATE products SET name = ?, description = ?, price = ?, discount_percent = ?, image_url = ?, category = ?, stock_quantity = ?, status = ? WHERE id = ?'))) {
            $stmt->bind_param('ssdissisi', $name, $description, $price, $discount, $image_url, $category, $stock, $status, $pid);
            if ($stmt->execute()) { header('Location: /hatrox-project/admin/products.php?notice=Product%20updated.'); exit; } else { $errors[] = 'Unable to update product.'; }
            $stmt->close();
        }
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 7; $offset = ($page - 1) * $perPage; $total=0; $totalPages=1;
$products = [];
$base = 'FROM products WHERE 1=1';
$where = '';
$whereParams = []; $whereTypes = '';
if ($search !== '') { $where .= ' AND (name LIKE ? OR category LIKE ?)'; $like = "%$search%"; $whereParams[]=&$like; $whereParams[]=&$like; $whereTypes.='ss'; }

$sql = 'SELECT id, name, price, category, status, stock_quantity, image_url ' . $base . $where . ' ORDER BY id DESC LIMIT ? OFFSET ?';
if ($stmt = $mysqli->prepare($sql)) {
    if ($whereParams) {
        $listParams = $whereParams; $types2=$whereTypes.'ii'; $listParams[]=&$perPage; $listParams[]=&$offset; $stmt->bind_param($types2, ...$listParams);
    } else {
        $stmt->bind_param('ii', $perPage, $offset);
    }
    $stmt->execute(); $res=$stmt->get_result(); while ($row=$res->fetch_assoc()){$products[]=$row;} $stmt->close();
}

$countSql = 'SELECT COUNT(*) AS c ' . $base . $where;
if ($stmt = $mysqli->prepare($countSql)) { if ($whereParams) { $stmt->bind_param($whereTypes, ...$whereParams); } $stmt->execute(); $stmt->bind_result($total); $stmt->fetch(); $stmt->close(); $totalPages = max(1, (int)ceil($total/$perPage)); }

include __DIR__ . '/../includes/header.php';
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <h1 class="text-2xl font-semibold mb-6">Products</h1>
  <form id="adminSearchForm" method="get" class="mb-6 grid md:grid-cols-3 gap-3 bg-neutral-900 border border-neutral-800 p-4 rounded">
    <div class="relative">
      <i class="ri-search-2-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"></i>
      <input id="adminSearchInput" name="q" value="<?= e($search) ?>" placeholder="Search by name or category" class="pl-10 w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2">
    </div>
  </form>
  <?php if ($errors): ?><div class="mb-4 p-3 bg-rose-900/40 border border-rose-800 text-rose-200 rounded"><?= e(implode("\n", $errors)) ?></div><?php endif; ?>
  <?php if ($notice): ?><div id="adminFlash" class="mb-4 p-3 bg-emerald-900/40 border border-emerald-800 text-emerald-200 rounded"><?= e($notice) ?></div><?php endif; ?>

  <details class="mb-6 bg-neutral-900 border border-neutral-800 rounded">
    <summary class="cursor-pointer px-4 py-3">Add Product</summary>
    <form method="post" enctype="multipart/form-data" class="p-4 grid md:grid-cols-2 gap-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="create">
      <div>
        <label class="block text-sm mb-1">Name</label>
        <input name="name" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">Category</label>
        <select name="category" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2">
          <?php foreach ($ALLOWED_CATEGORIES as $cat): ?>
            <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm mb-1">Description</label>
        <textarea name="description" rows="4" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2"></textarea>
      </div>
      <div>
        <label class="block text-sm mb-1">Price</label>
        <input name="price" type="number" step="0.01" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">Stock</label>
        <input name="stock_quantity" type="number" step="1" min="0" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">Discount %</label>
        <input name="discount_percent" type="number" step="1" min="0" max="90" value="0" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">Upload Image (JPG/PNG, max 2MB)</label>
        <input name="image_file" type="file" accept="image/jpeg,image/png" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">Or Image URL</label>
        <input name="image_url" placeholder="/hatrox-project/assets/images/yourimage.jpg" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">Status</label>
        <select name="status" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2">
          <option value="pending">Pending</option>
          <option value="active">Active</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <button class="bg-gold text-black font-medium px-6 py-2 rounded hover:bg-rose transition">Create</button>
      </div>
    </form>
  </details>

  <?php if (isset($_GET['edit'])): ?>
    <?php $editId = (int)$_GET['edit']; $edit = null; if ($st = $mysqli->prepare('SELECT id, name, description, price, image_url, category, stock_quantity, status FROM products WHERE id = ?')) { $st->bind_param('i',$editId); $st->execute(); $rs=$st->get_result(); $edit=$rs->fetch_assoc(); $st->close(); } ?>
    <?php if ($edit): ?>
    <div class="mb-6 bg-neutral-900 border border-neutral-800 rounded">
      <div class="px-4 py-3 font-semibold">Edit Product #<?= (int)$edit['id'] ?></div>
      <form method="post" enctype="multipart/form-data" class="p-4 grid md:grid-cols-2 gap-4">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <div>
          <label class="block text-sm mb-1">Name</label>
          <input name="name" value="<?= e($edit['name']) ?>" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">Category</label>
          <select name="category" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2">
            <?php foreach ($ALLOWED_CATEGORIES as $cat): ?>
              <option value="<?= e($cat) ?>" <?= $edit['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm mb-1">Description</label>
          <textarea name="description" rows="4" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2"><?= e($edit['description']) ?></textarea>
        </div>
        <div>
          <label class="block text-sm mb-1">Price</label>
          <input name="price" type="number" step="0.01" value="<?= e((string)$edit['price']) ?>" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">Stock</label>
          <input name="stock_quantity" type="number" step="1" min="0" value="<?= (int)$edit['stock_quantity'] ?>" required class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">Discount %</label>
          <input name="discount_percent" type="number" step="1" min="0" max="90" value="<?= isset($edit['discount_percent']) ? (int)$edit['discount_percent'] : 0 ?>" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">Upload New Image (optional)</label>
          <input name="image_file" type="file" accept="image/jpeg,image/png" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
          <div class="text-xs text-gray-500 mt-1">Current: <?= e($edit['image_url']) ?></div>
        </div>
        <div>
          <label class="block text-sm mb-1">Or Image URL (optional)</label>
          <input name="image_url" placeholder="/hatrox-project/assets/images/yourimage.jpg" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">Status</label>
          <select name="status" class="w-full bg-neutral-800 border border-neutral-700 rounded px-3 py-2">
            <option value="pending" <?= $edit['status']==='pending'?'selected':'' ?>>Pending</option>
            <option value="active" <?= $edit['status']==='active'?'selected':'' ?>>Active</option>
          </select>
        </div>
        <div class="md:col-span-2 flex items-center space-x-3">
          <button class="bg-gold text-black font-medium px-6 py-2 rounded hover:bg-rose transition">Save Changes</button>
          <a class="px-6 py-2 border border-neutral-700 rounded hover:border-gold" href="/hatrox-project/admin/products.php">Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="overflow-x-auto bg-neutral-900 border border-neutral-800 rounded">
    <table class="min-w-full text-sm">
      <thead class="bg-neutral-950">
        <tr>
          <th class="text-left p-3">ID</th>
          <th class="text-left p-3">Name</th>
          <th class="text-left p-3">Category</th>
          <th class="text-left p-3">Price</th>
          <th class="text-left p-3">Stock</th>
          <th class="text-left p-3">Status</th>
          <th class="p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr class="border-t border-neutral-800">
            <td class="p-3"><?= (int)$p['id'] ?></td>
            <td class="p-3"><div class="flex items-center space-x-3"><img src="<?= e($p['image_url']) ?>" class="w-10 h-10 object-cover rounded border border-neutral-800"><span><?= e($p['name']) ?></span></div></td>
            <td class="p-3"><?= e($p['category']) ?></td>
            <td class="p-3">$<?= number_format((float)$p['price'],2) ?></td>
            <td class="p-3"><?= (int)$p['stock_quantity'] ?></td>
            <td class="p-3 uppercase text-xs <?= $p['status']==='active'?'text-emerald-400':'text-rose-400' ?>"><?= e($p['status']) ?></td>
            <td class="p-3 space-x-2">
              <?php if ($p['status']!=='active'): ?>
                <form class="inline" method="post" style="margin-right:6px;">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="approve">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button aria-label="Approve product" title="Approve" class="inline-flex items-center gap-2 px-3 py-1 text-sm rounded font-medium border border-neutral-700 hover:border-gold transition">
                    <i class="ri-check-line"></i>
                    <span>Approve</span>
                  </button>
                </form>
              <?php endif; ?>

              <a class="inline-flex items-center gap-2 px-3 py-1 text-sm rounded font-medium border border-neutral-700 hover:border-gold transition" href="/hatrox-project/admin/products.php?edit=<?= (int)$p['id'] ?>" aria-label="Edit product" title="Edit">
                <i class="ri-edit-line"></i>
                <span>Edit</span>
              </a>

              <form class="inline" method="post" onsubmit="return confirm('Delete this product?');" style="margin-left:6px;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button aria-label="Delete product" title="Delete" class="inline-flex items-center gap-2 px-3 py-1 text-sm rounded font-medium border border-neutral-700 hover:border-gold transition">
                  <i class="ri-delete-bin-6-line"></i>
                  <span>Delete</span>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-6 flex items-center justify-between">
    <?php $prev = max(1, $page-1); $next = min($totalPages, $page+1); ?>
    <a class="px-3 py-1 border border-neutral-700 rounded hover:border-gold <?= $page<=1?'pointer-events-none opacity-50':'' ?>" href="?<?= http_build_query(array_merge($_GET, ['page'=>$prev])) ?>">Prev</a>
    <div class="text-xs text-gray-400">Page <?= (int)$page ?> of <?= (int)$totalPages ?></div>
    <a class="px-3 py-1 border border-neutral-700 rounded hover:border-gold <?= $page>=$totalPages?'pointer-events-none opacity-50':'' ?>" href="?<?= http_build_query(array_merge($_GET, ['page'=>$next])) ?>">Next</a>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>



<script>
(function(){
  const form = document.getElementById('adminSearchForm');
  const input = document.getElementById('adminSearchInput');
  if (!form || !input) return;
  let t = null;
  function submitSoon(){
    if (t) clearTimeout(t);
    t = setTimeout(()=>{ form.submit(); }, 350);
  }
  input.addEventListener('input', submitSoon);
  input.addEventListener('change', submitSoon);
})();
</script>
<script>
(function(){
  const flash = document.getElementById('adminFlash');
  if (!flash) return;
  setTimeout(()=>{
    flash.style.transition = 'opacity 300ms ease';
    flash.style.opacity = '0';
    setTimeout(()=>{ flash.remove(); }, 320);
  }, 3000);
})();
</script>