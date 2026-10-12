<?php
/**
 * ShopSmart India - Admin Product Management
 * BCA Semester V Field Project
 */

$adminTitle = "Manage Products";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();
$msg = '';
$error = '';

// Handle Toggle Active/Inactive or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf()) {
        $error = 'Security validation failed.';
    } elseif ($_POST['action'] === 'toggle_status') {
        $pId = (int)$_POST['product_id'];
        $upd = $db->prepare("UPDATE products SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?");
        $upd->execute([$pId]);
        logAdminAudit(currentUserId(), 'TOGGLE_PRODUCT_STATUS', "Toggled active state for product ID $pId");
        $msg = 'Product status updated successfully.';
    } elseif ($_POST['action'] === 'delete_product') {
        $pId = (int)$_POST['product_id'];
        // Soft delete/deactivate to preserve order history integrity
        $upd = $db->prepare("UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $upd->execute([$pId]);
        logAdminAudit(currentUserId(), 'DEACTIVATE_PRODUCT', "Deactivated product ID $pId");
        $msg = 'Product deactivated successfully.';
    }
}

// Fetch products with filters
$categoryFilter = $_GET['category'] ?? '';
$searchFilter = trim($_GET['q'] ?? '');

$query = "
    SELECT p.*, c.name AS category_name, b.name AS brand_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE 1=1
";
$params = [];

if ($categoryFilter) {
    $query .= " AND c.slug = ?";
    $params[] = $categoryFilter;
}

if ($searchFilter) {
    $query .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $like = "%$searchFilter%";
    $params[] = $like;
    $params[] = $like;
}

$query .= " ORDER BY p.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $db->query("SELECT name, slug FROM categories ORDER BY display_order ASC")->fetchAll();
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

<!-- Action Header & Filters -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <form action="<?= APP_URL ?>/admin/products.php" method="GET" style="display:flex;gap:10px;">
    <input type="text" name="q" value="<?= e($searchFilter) ?>" placeholder="Search name or SKU..." class="form-control" style="width:220px;padding:6px 12px;font-size:13px;">
    <select name="category" class="form-control" style="font-size:13px;padding:6px 12px;" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= e($cat['slug']) ?>" <?= $categoryFilter === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-primary" style="padding:6px 14px;font-size:13px;">Filter</button>
  </form>

  <a href="<?= APP_URL ?>/admin/product_add.php" class="btn-primary" style="padding:8px 18px;font-size:13.5px;">
    <i class="bi bi-plus-lg"></i> Add New Product
  </a>
</div>

<!-- Product Table -->
<div class="table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Product</th>
        <th>SKU</th>
        <th>Category</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Best Val</th>
        <th>Eco</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($products)): ?>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:12px;">
                <img src="<?= e(getProductImageUrl($p['image_url'], $p['name'])) ?>" alt="thumb" style="width:40px;height:40px;object-fit:contain;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0;">
                <div>
                  <a href="<?= APP_URL ?>/product.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" style="font-weight:700;color:var(--primary-navy);" title="<?= e($p['name']) ?>">
                    <?= e(substr($p['name'], 0, 36)) ?><?= strlen($p['name']) > 36 ? '...' : '' ?>
                  </a>
                  <div style="font-size:11px;color:#64748b;"><?= e($p['brand_name'] ?? 'Generic') ?></div>
                </div>
              </div>
            </td>
            <td><code><?= e($p['sku']) ?></code></td>
            <td><?= e($p['category_name']) ?></td>
            <td style="font-weight:700;color:var(--primary-orange);"><?= formatPriceINR($p['price']) ?></td>
            <td>
              <span style="font-weight:700;color:<?= $p['stock_quantity'] <= 15 ? 'var(--accent-red)' : 'var(--accent-green)' ?>;">
                <?= (int)$p['stock_quantity'] ?>
              </span>
            </td>
            <td><span class="badge-score-value" style="font-size:11px;"><?= $p['best_value_score'] ?></span></td>
            <td><span class="badge-score-eco" style="font-size:11px;"><?= $p['eco_friendly_score'] ?></span></td>
            <td>
              <form action="<?= APP_URL ?>/admin/products.php" method="POST" style="margin:0;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <button type="submit" style="font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:12px;color:#fff;background:<?= $p['is_active'] ? 'var(--accent-green)' : '#94a3b8' ?>;">
                  <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                </button>
              </form>
            </td>
            <td>
              <div style="display:flex;gap:8px;">
                <a href="<?= APP_URL ?>/admin/product_edit.php?id=<?= $p['id'] ?>" style="color:var(--primary-navy);font-weight:600;font-size:13px;" title="Edit">
                  <i class="bi bi-pencil-square"></i> Edit
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="9" style="text-align:center;padding:32px;color:#64748b;">No products found matching filters.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
