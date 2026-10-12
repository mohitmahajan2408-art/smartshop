<?php
/**
 * ShopSmart India - Customer Wishlist
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();
$userId = currentUserId();

$wishlistItems = [];

if ($db) {
    if ($userId) {
        $stmt = $db->prepare("
            SELECT wi.id AS wishlist_id, p.*, c.name AS category_name, b.name AS brand_name
            FROM wishlist_items wi
            JOIN products p ON wi.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE wi.user_id = ? AND p.is_active = 1
            ORDER BY wi.created_at DESC
        ");
        $stmt->execute([$userId]);
        $wishlistItems = $stmt->fetchAll();
    } else {
        if (!empty($_SESSION['guest_wishlist'])) {
            $ids = array_values($_SESSION['guest_wishlist']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $db->prepare("
                SELECT p.*, c.name AS category_name, b.name AS brand_name
                FROM products p
                JOIN categories c ON p.category_id = c.id
                LEFT JOIN brands b ON p.brand_id = b.id
                WHERE p.id IN ($placeholders) AND p.is_active = 1
            ");
            $stmt->execute($ids);
            $wishlistItems = $stmt->fetchAll();
        }
    }
}
?>

<div class="container" style="padding: 32px 16px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <h1 style="font-size:24px;color:var(--primary-navy);margin:0;display:flex;align-items:center;gap:10px;">
      <i class="bi bi-heart-fill" style="color:var(--accent-red);"></i> <?= e(__('nav_wishlist')) ?>
      <span style="font-size:15px;color:#64748b;font-weight:400;">(<?= count($wishlistItems) ?> saved items)</span>
    </h1>
    <a href="<?= APP_URL ?>/products.php" class="view-all-link">Browse More Products <i class="bi bi-arrow-right"></i></a>
  </div>

  <?php if (!empty($wishlistItems)): ?>
    <div class="products-grid">
      <?php foreach ($wishlistItems as $product): ?>
        <?php include __DIR__ . '/includes/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:64px 24px;text-align:center;max-width:540px;margin:auto;">
      <div style="font-size:52px;margin-bottom:14px;">🤍</div>
      <h2 style="font-size:20px;color:var(--primary-navy);margin-bottom:8px;">Your Wishlist is Empty</h2>
      <p style="color:#64748b;font-size:14px;margin-bottom:20px;">
        Explore your favorite products and tap the heart icon to save them for later!
      </p>
      <a href="<?= APP_URL ?>/products.php" class="btn-primary" style="display:inline-flex;">
        Explore Marketplace
      </a>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
