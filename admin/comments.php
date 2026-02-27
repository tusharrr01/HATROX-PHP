<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) { header('Location: /hatrox-project/admin/login.php'); exit; }

$notice='';
if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf()) {
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    if ($id>0) {
        if ($action==='approve') {
            if ($stmt = $mysqli->prepare('UPDATE comments SET is_approved = 1 WHERE id = ?')) { $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close(); $notice='Comment approved.'; }
        } elseif ($action==='hide') {
            if ($stmt = $mysqli->prepare('UPDATE comments SET is_hidden = 1 WHERE id = ?')) { $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close(); $notice='Comment hidden.'; }
        } elseif ($action==='unhide') {
            if ($stmt = $mysqli->prepare('UPDATE comments SET is_hidden = 0 WHERE id = ?')) { $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close(); $notice='Comment unhidden.'; }
        } elseif ($action==='delete') {
            if ($stmt = $mysqli->prepare('DELETE FROM comments WHERE id = ?')) { $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close(); $notice='Comment deleted.'; }
        }
    }
}

$comments = [];
if ($res = $mysqli->query('SELECT id, user_name, email, comment_text, rating, is_approved, is_hidden, created_at FROM comments ORDER BY id DESC')) { while ($row=$res->fetch_assoc()){$comments[]=$row;} }

include __DIR__ . '/../includes/header.php';
?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <h1 class="text-2xl font-semibold mb-6">Comments</h1>
  <?php if ($notice): ?><div class="mb-4 p-3 bg-emerald-900/40 border border-emerald-800 text-emerald-200 rounded"><?= e($notice) ?></div><?php endif; ?>
  <div class="overflow-x-auto bg-neutral-900 border border-neutral-800 rounded">
    <table class="min-w-full text-sm">
      <thead class="bg-neutral-950"><tr><th class="text-left p-3">User</th><th class="text-left p-3">Email</th><th class="text-left p-3">Rating</th><th class="text-left p-3">Comment</th><th class="text-left p-3">Approved</th><th class="text-left p-3">Status</th><th class="p-3">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($comments as $c): ?>
          <tr class="border-t border-neutral-800">
            <td class="p-3"><?= e($c['user_name']) ?></td>
            <td class="p-3"><?= e($c['email']) ?></td>
            <td class="p-3"><?= (int)$c['rating'] ?>/5</td>
            <td class="p-3" style="max-width: 300px;">
              <div class="comment-display break-words overflow-hidden" style="word-break: break-word; max-width: 100%; overflow-wrap: break-word; display: block;" data-full="<?= e($c['comment_text']) ?>" data-short="<?= e(strlen($c['comment_text']) > 50 ? substr($c['comment_text'], 0, 50) . '...' : $c['comment_text']) ?>">
                <span class="comment-text" style="display: block; word-break: break-word;"><?= e(strlen($c['comment_text']) > 50 ? substr($c['comment_text'], 0, 50) . '...' : $c['comment_text']) ?></span>
                <?php if (strlen($c['comment_text']) > 50): ?><button type="button" class="text-xs text-gold hover:text-rose transition mt-1 expand-btn" onclick="toggleComment(event)" style="display: block;">Read more</button><?php endif; ?>
              </div>
            </td>
            <td class="p-3"><?= $c['is_approved'] ? 'Yes' : 'No' ?></td>
            <td class="p-3"><?= $c['is_hidden'] ? '<span class="text-sm text-rose">Hidden</span>' : '<span class="text-sm text-green-400">Visible</span>' ?></td>
            <td class="p-3 space-x-2">
              <?php if (!$c['is_approved']): ?>
              <form class="inline" method="post" style="margin-right:4px;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <button class="inline-flex items-center gap-2 px-3 py-1 text-sm rounded font-medium border border-neutral-700 hover:border-gold transition"><i class="ri-check-line"></i><span>Approve</span></button>
              </form>
              <?php endif; ?>
              <?php if ((int)$c['is_hidden'] === 1): ?>
              <form class="inline" method="post" style="margin-right:4px;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="action" value="unhide">
                <button class="inline-flex items-center gap-2 px-3 py-1 text-sm rounded font-medium border border-neutral-700 hover:border-gold transition" title="Show"><i class="ri-eye-line"></i><span>Show</span></button>
              </form>
              <?php else: ?>
              <form class="inline" method="post" style="margin-right:4px;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="action" value="hide">
                <button class="inline-flex items-center gap-2 px-3 py-1 text-sm rounded font-medium border border-neutral-700 hover:border-gold transition" title="Hide"><i class="ri-eye-off-line"></i><span>Hide</span></button>
              </form>
              <?php endif; ?>
              <form class="inline" method="post" onsubmit="return confirm('Delete this comment?');">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="inline-flex items-center gap-2 px-3 py-1 text-sm rounded font-medium border border-neutral-700 hover:border-gold transition" title="Delete"><i class="ri-delete-bin-6-line"></i><span>Delete</span></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  function toggleComment(e) {
    e.preventDefault();
    const btn = e.target;
    const commentDiv = btn.parentElement;
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


