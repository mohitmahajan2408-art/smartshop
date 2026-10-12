<?php
/**
 * ShopSmart India - Edit Product Listing (Admin)
 * BCA Semester V Field Project
 */

$adminTitle = "Edit Product";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();
$productId = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

$product = null;
if ($db && $productId > 0) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
}

if (!$product) {
    echo '<div style="background:#fff;padding:40px;border-radius:12px;text-align:center;">Product not found. <a href="' . APP_URL . '/admin/products.php">Back to list</a></div>';
    require_once __DIR__ . '/admin_footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Security validation failed.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $originalPrice = (float)($_POST['original_price'] ?? $price);
        $stock = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $brandId = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
        $bestVal = max(10, min(99, (int)($_POST['best_value_score'] ?? 85)));
        $ecoScore = max(10, min(99, (int)($_POST['eco_friendly_score'] ?? 70)));
        $warranty = max(0, (int)($_POST['warranty_months'] ?? 12));
        $shortDesc = trim($_POST['short_description'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

        if (empty($name) || $price <= 0 || $categoryId <= 0) {
            $error = 'Name, Category, and a valid Price are required.';
        } else {
            $imageUrl = $product['image_url'];
            // Handle image replacement if provided
            if (!empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['product_image']['tmp_name'];
                $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $newFilename = 'prod_' . bin2hex(random_bytes(10)) . '.' . $ext;
                $destPath = UPLOADS_PATH . '/products/' . $newFilename;
                if (move_uploaded_file($fileTmp, $destPath)) {
                    $imageUrl = 'uploads/products/' . $newFilename;
                }
            }

            $updStmt = $db->prepare("
                UPDATE products 
                SET name = ?, price = ?, original_price = ?, stock_quantity = ?, category_id = ?, brand_id = ?, 
                    best_value_score = ?, eco_friendly_score = ?, warranty_months = ?, short_description = ?, description = ?, 
                    image_url = ?, is_active = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $updStmt->execute([
                $name, $price, $originalPrice, $stock, $categoryId, $brandId,
                $bestVal, $ecoScore, $warranty, $shortDesc, $desc, $imageUrl, $isActive, $productId
            ]);

            logAdminAudit(currentUserId(), 'UPDATE_PRODUCT', "Updated product '$name' (ID: $productId)");
            $success = "Product updated successfully!";

            // Refresh product
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
        }
    }
}

$categories = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
$brands = $db->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
?>

<div style="max-width:800px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:var(--shadow-sm);">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;border-bottom:1px solid #f1f5f9;padding-bottom:16px;">
    <h2 style="font-size:20px;color:var(--primary-navy);margin:0;">Edit Product: <?= e($product['name']) ?></h2>
    <a href="<?= APP_URL ?>/admin/products.php" class="btn-secondary" style="font-size:13px;">&larr; Back to Products</a>
  </div>

  <?php if ($success): ?>
    <div style="background:#ecfdf5;border-left:4px solid #10b981;color:#065f46;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-weight:600;">
      ✓ <?= e($success) ?>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-weight:600;">
      ✕ <?= e($error) ?>
    </div>
  <?php endif; ?>

  <form action="<?= APP_URL ?>/admin/product_edit.php?id=<?= $productId ?>" method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>

    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label" style="font-weight:700;">Product Title *</label>
      <input type="text" name="name" class="form-control" value="<?= e($product['name']) ?>" required>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">SKU (Read Only)</label>
        <input type="text" class="form-control" value="<?= e($product['sku']) ?>" disabled style="background:#f8fafc;">
      </div>

      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Category *</label>
        <select name="category_id" class="form-control" required>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id'] == $product['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Brand</label>
        <select name="brand_id" class="form-control">
          <option value="">None / Generic</option>
          <?php foreach ($brands as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $b['id'] == $product['brand_id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Selling Price (₹) *</label>
        <input type="number" name="price" class="form-control" value="<?= (float)$product['price'] ?>" step="0.5" required>
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Original MRP (₹)</label>
        <input type="number" name="original_price" class="form-control" value="<?= (float)$product['original_price'] ?>" step="0.5">
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Stock Quantity *</label>
        <input type="number" name="stock_quantity" class="form-control" value="<?= (int)$product['stock_quantity'] ?>" min="0" required>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Best Value Score</label>
        <input type="number" name="best_value_score" class="form-control" value="<?= (int)$product['best_value_score'] ?>" min="10" max="99">
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Eco-Friendly Score</label>
        <input type="number" name="eco_friendly_score" class="form-control" value="<?= (int)$product['eco_friendly_score'] ?>" min="10" max="99">
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Warranty (Months)</label>
        <input type="number" name="warranty_months" class="form-control" value="<?= (int)$product['warranty_months'] ?>" min="0">
      </div>
    </div>

    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label" style="font-weight:700;">Replace Image (Optional)</label>
      <input type="file" name="product_image" class="form-control" accept="image/jpeg,image/png,image/webp">
      <?php if (!empty($product['image_url'])): ?>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">Current image: <code><?= e($product['image_url']) ?></code></div>
      <?php endif; ?>
    </div>

    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label" style="font-weight:700;">Short Summary</label>
      <input type="text" name="short_description" class="form-control" value="<?= e($product['short_description'] ?? '') ?>">
    </div>

    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label" style="font-weight:700;">Full Description</label>
      <textarea name="description" class="form-control" rows="4"><?= e($product['description'] ?? '') ?></textarea>
    </div>

    <div style="margin-bottom:24px;">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:700;">
        <input type="checkbox" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?>>
        Product is Active and visible in storefront catalog
      </label>
    </div>

    <button type="submit" class="btn-primary" style="padding:12px 28px;font-size:15px;">
      <i class="bi bi-save2"></i> Update Product
    </button>
  </form>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
