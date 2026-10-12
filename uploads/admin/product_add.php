<?php
/**
 * ShopSmart India - Add New Product (Admin)
 * BCA Semester V Field Project
 */

$adminTitle = "Add New Product";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Security validation failed.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $sku = strtoupper(trim($_POST['sku'] ?? ''));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $brandId = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
        $price = (float)($_POST['price'] ?? 0);
        $originalPrice = (float)($_POST['original_price'] ?? $price);
        $stock = max(0, (int)($_POST['stock_quantity'] ?? 10));
        $warranty = max(0, (int)($_POST['warranty_months'] ?? 12));
        $bestVal = max(10, min(99, (int)($_POST['best_value_score'] ?? 85)));
        $ecoScore = max(10, min(99, (int)($_POST['eco_friendly_score'] ?? 70)));
        $shortDesc = trim($_POST['short_description'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (empty($name) || empty($sku) || $categoryId <= 0 || $price <= 0) {
            $error = 'Please fill all required fields (Name, SKU, Category, and valid Price).';
        } else {
            // Check SKU uniqueness
            $chkSku = $db->prepare("SELECT id FROM products WHERE sku = ? LIMIT 1");
            $chkSku->execute([$sku]);
            if ($chkSku->fetch()) {
                $error = "SKU '$sku' is already taken. Please provide a unique SKU.";
            } else {
                // Handle Image Upload safely
                $imageUrl = null;
                if (!empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $fileTmp = $_FILES['product_image']['tmp_name'];
                    $fileSize = $_FILES['product_image']['size'];
                    $fileMime = mime_content_type($fileTmp);
                    $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));

                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                    if (!in_array($ext, $allowedExts) || !in_array($fileMime, ALLOWED_IMAGE_TYPES)) {
                        $error = 'Invalid image format. Allowed: JPG, PNG, WEBP.';
                    } elseif ($fileSize > MAX_FILE_SIZE) {
                        $error = 'Image file exceeds maximum allowed size (5 MB).';
                    } else {
                        $newFilename = 'prod_' . bin2hex(random_bytes(10)) . '.' . $ext;
                        $destPath = UPLOADS_PATH . '/products/' . $newFilename;
                        if (move_uploaded_file($fileTmp, $destPath)) {
                            $imageUrl = 'uploads/products/' . $newFilename;
                        }
                    }
                }

                if (empty($error)) {
                    $slug = slugify($name);
                    // Ensure unique slug
                    $chkSlug = $db->prepare("SELECT id FROM products WHERE slug = ?");
                    $chkSlug->execute([$slug]);
                    if ($chkSlug->fetch()) {
                        $slug .= '-' . rand(100, 999);
                    }

                    $insStmt = $db->prepare("
                        INSERT INTO products (name, slug, sku, short_description, description, category_id, brand_id, price, original_price, stock_quantity, image_url, is_active, warranty_months, best_value_score, eco_friendly_score, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW())
                    ");
                    $insStmt->execute([
                        $name, $slug, $sku, $shortDesc, $desc, $categoryId, $brandId,
                        $price, $originalPrice, $stock, $imageUrl, $warranty, $bestVal, $ecoScore
                    ]);
                    $newProdId = (int)$db->lastInsertId();

                    logAdminAudit(currentUserId(), 'ADD_PRODUCT', "Added new product '$name' (SKU: $sku, ID: $newProdId)");
                    $success = "Product '$name' added to catalog successfully!";
                }
            }
        }
    }
}

$categories = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
$brands = $db->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
?>

<div style="max-width:800px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:var(--shadow-sm);">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;border-bottom:1px solid #f1f5f9;padding-bottom:16px;">
    <h2 style="font-size:20px;color:var(--primary-navy);margin:0;">Create New Product Listing</h2>
    <a href="<?= APP_URL ?>/admin/products.php" class="btn-secondary" style="font-size:13px;">&larr; Back to Catalog</a>
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

  <form action="<?= APP_URL ?>/admin/product_add.php" method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>

    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label" style="font-weight:700;">Product Title *</label>
      <input type="text" name="name" class="form-control" placeholder="e.g. boAt Wave Call 2 Smartwatch with Bluetooth Calling" required>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Unique SKU *</label>
        <input type="text" name="sku" class="form-control" placeholder="e.g. SKU-ELEC-099" required>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Category *</label>
        <select name="category_id" class="form-control" required>
          <option value="">Select Category</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Brand (Optional)</label>
        <select name="brand_id" class="form-control">
          <option value="">Select Brand</option>
          <?php foreach ($brands as $b): ?>
            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Selling Price (₹) *</label>
        <input type="number" name="price" class="form-control" placeholder="e.g. 1499" step="0.5" required>
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Original MRP (₹)</label>
        <input type="number" name="original_price" class="form-control" placeholder="e.g. 4999" step="0.5">
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Initial Stock Qty</label>
        <input type="number" name="stock_quantity" class="form-control" value="25" min="0">
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Best Value Score (10-99)</label>
        <input type="number" name="best_value_score" class="form-control" value="88" min="10" max="99">
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Eco Score (10-99)</label>
        <input type="number" name="eco_friendly_score" class="form-control" value="75" min="10" max="99">
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Warranty (Months)</label>
        <input type="number" name="warranty_months" class="form-control" value="12" min="0">
      </div>
    </div>

    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label" style="font-weight:700;">Product Image File</label>
      <input type="file" name="product_image" class="form-control" accept="image/jpeg,image/png,image/webp">
      <span style="font-size:12px;color:#64748b;">Supported formats: JPG, PNG, WEBP. Max size: 5MB. Automatic fallback SVG will be used if omitted.</span>
    </div>

    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label" style="font-weight:700;">Short Summary / Bullet Points</label>
      <input type="text" name="short_description" class="form-control" placeholder="Key highlights in one line...">
    </div>

    <div class="form-group" style="margin-bottom:24px;">
      <label class="form-label" style="font-weight:700;">Full Description</label>
      <textarea name="description" class="form-control" rows="4" placeholder="Detailed product specifications, usage notes, and packaging details..."></textarea>
    </div>

    <button type="submit" class="btn-primary" style="padding:12px 28px;font-size:15px;">
      <i class="bi bi-check2-circle"></i> Save Product Listing
    </button>
  </form>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
