<?php
/**
 * ShopSmart India - Categories & Brands Management (Admin)
 * BCA Semester V Field Project
 */

$adminTitle = "Categories & Brands";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf()) {
        $error = 'Security validation failed.';
    } elseif ($_POST['action'] === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'bi-tag');

        if (empty($name)) {
            $error = 'Category name cannot be empty.';
        } else {
            $slug = slugify($name);
            $ins = $db->prepare("INSERT INTO categories (name, slug, description, icon, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
            $ins->execute([$name, $slug, $desc, $icon]);
            logAdminAudit(currentUserId(), 'ADD_CATEGORY', "Added category '$name'");
            $msg = 'Category added successfully!';
        }
    } elseif ($_POST['action'] === 'add_brand') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $error = 'Brand name cannot be empty.';
        } else {
            $slug = slugify($name);
            $ins = $db->prepare("INSERT INTO brands (name, slug, is_active, created_at) VALUES (?, ?, 1, NOW())");
            $ins->execute([$name, $slug]);
            logAdminAudit(currentUserId(), 'ADD_BRAND', "Added brand '$name'");
            $msg = 'Brand added successfully!';
        }
    }
}

$categories = $db->query("
    SELECT c.*, COUNT(p.id) AS product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.display_order ASC
")->fetchAll();

$brands = $db->query("
    SELECT b.*, COUNT(p.id) AS product_count 
    FROM brands b 
    LEFT JOIN products p ON b.id = p.brand_id 
    GROUP BY b.id 
    ORDER BY b.name ASC
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

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:28px;">
  <!-- Categories Table & Form -->
  <div>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;">
      <h3 style="font-size:16px;color:var(--primary-navy);margin-bottom:14px;">Add New Category</h3>
      <form action="<?= APP_URL ?>/admin/categories.php" method="POST" style="display:flex;gap:12px;flex-wrap:wrap;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_category">
        <input type="text" name="name" class="form-control" placeholder="Category Name" style="flex:1;min-width:180px;" required>
        <input type="text" name="icon" class="form-control" placeholder="Icon (e.g. bi-camera)" style="width:160px;" value="bi-tag">
        <button type="submit" class="btn-primary" style="padding:8px 18px;font-size:13px;">Add Category</button>
      </form>
    </div>

    <div class="table-container">
      <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
        <h3 style="font-size:15px;margin:0;font-weight:700;color:var(--primary-navy);">All Marketplace Categories (<?= count($categories) ?>)</h3>
      </div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Icon</th>
            <th>Name</th>
            <th>Slug</th>
            <th>Products</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td><i class="bi <?= e($cat['icon']) ?>" style="font-size:18px;color:var(--primary-orange);"></i></td>
              <td><strong><?= e($cat['name']) ?></strong></td>
              <td><code><?= e($cat['slug']) ?></code></td>
              <td><span style="background:#f1f5f9;padding:2px 8px;border-radius:10px;font-weight:700;font-size:12px;"><?= $cat['product_count'] ?></span></td>
              <td>
                <span style="color:var(--accent-green);font-weight:600;font-size:12px;">Active</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Brands Management -->
  <div>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;">
      <h3 style="font-size:16px;color:var(--primary-navy);margin-bottom:14px;">Add Brand</h3>
      <form action="<?= APP_URL ?>/admin/categories.php" method="POST" style="display:flex;gap:12px;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_brand">
        <input type="text" name="name" class="form-control" placeholder="Brand Name" required>
        <button type="submit" class="btn-primary" style="padding:8px 18px;font-size:13px;">Add Brand</button>
      </form>
    </div>

    <div class="table-container">
      <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
        <h3 style="font-size:15px;margin:0;font-weight:700;color:var(--primary-navy);">Marketplace Brands (<?= count($brands) ?>)</h3>
      </div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Brand</th>
            <th>Catalog Products</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($brands as $b): ?>
            <tr>
              <td><strong><?= e($b['name']) ?></strong></td>
              <td><?= $b['product_count'] ?> products</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
