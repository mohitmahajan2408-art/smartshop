<?php
/**
 * ShopSmart India - Review Moderation & Trust Analytics (Admin)
 * BCA Semester V Field Project
 */

$adminTitle = "Reviews Moderation";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf()) {
        $error = 'Security validation failed.';
    } elseif ($_POST['action'] === 'moderate_review') {
        $revId = (int)$_POST['review_id'];
        $newStatus = $_POST['status'] === 'approved' ? 'approved' : 'rejected';
        $upd = $db->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $revId]);
        logAdminAudit(currentUserId(), 'MODERATE_REVIEW', "Set review ID $revId status to $newStatus");
        $msg = "Review status updated to $newStatus.";
    } elseif ($_POST['action'] === 'delete_review') {
        $revId = (int)$_POST['review_id'];
        $del = $db->prepare("DELETE FROM reviews WHERE id = ?");
        $del->execute([$revId]);
        logAdminAudit(currentUserId(), 'DELETE_REVIEW', "Deleted review ID $revId");
        $msg = 'Review permanently deleted.';
    }
}

$reviews = $db->query("
    SELECT r.*, p.name AS product_name, p.slug AS product_slug, u.name AS user_name 
    FROM reviews r 
    JOIN products p ON r.product_id = p.id 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC
")->fetchAll();
?>

<?php if ($msg): ?>
  <div style="background:#ecfdf5;border-left:4px solid #10b981;color:#065f46;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-weight:600;">
    ✓ <?= e($msg) ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-weight:600;">
    ✕ <?= e($error) ?>
  </div>
<?php endif; ?>

<div class="table-container">
  <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
    <h3 style="font-size:15px;margin:0;font-weight:700;color:var(--primary-navy);">Customer Reviews Feed (<?= count($reviews) ?>)</h3>
    <span style="font-size:12.5px;color:#64748b;">Review Trust Algorithm: Verified purchases marked with <i class="bi bi-patch-check-fill" style="color:var(--accent-green);"></i></span>
  </div>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Product</th>
        <th>Customer</th>
        <th>Rating</th>
        <th>Review Details</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $r): ?>
          <tr>
            <td>
              <a href="<?= APP_URL ?>/product.php?slug=<?= urlencode($r['product_slug']) ?>" target="_blank" style="font-weight:700;color:var(--primary-navy);" title="<?= e($r['product_name']) ?>">
                <?= e(substr($r['product_name'], 0, 32)) ?>...
              </a>
            </td>
            <td>
              <div style="font-weight:600;"><?= e($r['user_name']) ?></div>
              <?php if (!empty($r['is_verified_purchase'])): ?>
                <span style="font-size:11px;color:var(--accent-green);font-weight:700;">
                  <i class="bi bi-patch-check-fill"></i> Verified Purchase
                </span>
              <?php else: ?>
                <span style="font-size:11px;color:#94a3b8;">Unverified</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="rating-pill">
                <?= (int)$r['rating'] ?> ★
              </span>
            </td>
            <td>
              <div style="font-weight:700;font-size:13px;"><?= e($r['headline']) ?></div>
              <div style="font-size:12px;color:#475569;margin-top:2px;max-width:320px;"><?= e($r['comment']) ?></div>
              <div style="font-size:11px;color:#94a3b8;margin-top:4px;"><?= formatDateIndian($r['created_at']) ?></div>
            </td>
            <td>
              <span style="font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:12px;color:#fff;background:<?= $r['status'] === 'approved' ? 'var(--accent-green)' : ($r['status'] === 'pending' ? 'var(--accent-amber)' : 'var(--accent-red)') ?>;">
                <?= ucfirst($r['status']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:6px;">
                <?php if ($r['status'] !== 'approved'): ?>
                  <form action="<?= APP_URL ?>/admin/reviews.php" method="POST" style="margin:0;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="moderate_review">
                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn-secondary" style="padding:3px 8px;font-size:11.5px;color:var(--accent-green);">Approve</button>
                  </form>
                <?php endif; ?>

                <?php if ($r['status'] !== 'rejected'): ?>
                  <form action="<?= APP_URL ?>/admin/reviews.php" method="POST" style="margin:0;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="moderate_review">
                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="status" value="rejected">
                    <button type="submit" class="btn-secondary" style="padding:3px 8px;font-size:11.5px;color:var(--accent-red);">Reject</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6" style="text-align:center;padding:32px;color:#64748b;">No reviews submitted yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
