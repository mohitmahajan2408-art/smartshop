<?php
/**
 * ShopSmart India - Smart Product Comparison Matrix
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();

if (!isset($_SESSION['compare_list'])) {
    $_SESSION['compare_list'] = [];
}

// Handle Add to Compare
if (isset($_GET['add'])) {
    $addId = (int)$_GET['add'];
    if ($addId > 0 && !in_array($addId, $_SESSION['compare_list'])) {
        if (count($_SESSION['compare_list']) >= 4) {
            array_shift($_SESSION['compare_list']); // Max 4 items
        }
        $_SESSION['compare_list'][] = $addId;
    }
}

// Handle Remove from Compare
if (isset($_GET['remove'])) {
    $remId = (int)$_GET['remove'];
    $_SESSION['compare_list'] = array_values(array_diff($_SESSION['compare_list'], [$remId]));
}

// Default items if empty: load 2 electronics for immediate demonstration
if (empty($_SESSION['compare_list']) && $db) {
    $_SESSION['compare_list'] = [1, 2];
}

$comparedProducts = [];
if (!empty($_SESSION['compare_list']) && $db) {
    $ids = $_SESSION['compare_list'];
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $db->prepare("
        SELECT p.*, c.name AS category_name, b.name AS brand_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE p.id IN ($placeholders)
    ");
    $stmt->execute($ids);
    $comparedProducts = $stmt->fetchAll();
}

// Find algorithmic top recommended winner among compared items
$winnerId = null;
$highestScore = -1;
foreach ($comparedProducts as $cp) {
    $composite = ($cp['best_value_score'] * 0.6) + ($cp['eco_friendly_score'] * 0.4);
    if ($composite > $highestScore) {
        $highestScore = $composite;
        $winnerId = $cp['id'];
    }
}
?>

<div class="container" style="padding: 36px 16px;">
  <div style="margin-bottom:28px;">
    <h1 style="font-size:26px;color:var(--primary-navy);margin-bottom:6px;">
      <i class="bi bi-arrow-left-right" style="color:var(--primary-orange);"></i> <?= e(__('compare_title')) ?>
    </h1>
    <p style="color:#64748b;font-size:14px;">
      <?= e(__('compare_subtitle')) ?>
    </p>
  </div>

  <?php if (!empty($comparedProducts)): ?>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow-x:auto;box-shadow:var(--shadow-md);">
      <table style="width:100%;border-collapse:collapse;min-width:680px;font-size:14px;">
        <thead>
          <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
            <th style="padding:20px;text-align:left;width:200px;color:var(--text-secondary);font-size:13px;text-transform:uppercase;">
              Features & Metrics
            </th>
            <?php foreach ($comparedProducts as $p): ?>
              <th style="padding:20px;text-align:center;width:260px;position:relative;">
                <a href="<?= APP_URL ?>/compare.php?remove=<?= $p['id'] ?>" style="position:absolute;top:10px;right:10px;color:#94a3b8;font-size:18px;" title="Remove">&times;</a>
                
                <?php if ($p['id'] === $winnerId && count($comparedProducts) > 1): ?>
                  <div style="background:var(--accent-amber);color:#fff;font-size:11px;font-weight:800;padding:2px 8px;border-radius:4px;display:inline-block;margin-bottom:8px;">
                    🏆 SMART RECOMMENDED CHOICE
                  </div>
                <?php endif; ?>

                <div style="width:120px;height:120px;margin:0 auto 12px;background:#f8fafc;border-radius:8px;padding:8px;">
                  <img src="<?= e(getProductImageUrl($p['image_url'], $p['name'])) ?>" alt="<?= e($p['name']) ?>" style="width:100%;height:100%;object-fit:contain;">
                </div>
                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;"><?= e($p['brand_name'] ?? $p['category_name']) ?></div>
                <a href="<?= APP_URL ?>/product.php?slug=<?= urlencode($p['slug']) ?>" style="font-size:14.5px;font-weight:700;color:var(--primary-navy);line-height:1.3;display:block;margin:4px 0 8px;">
                  <?= e($p['name']) ?>
                </a>
                <div style="font-size:20px;font-weight:800;color:var(--primary-orange);">
                  <?= formatPriceINR($p['price']) ?>
                </div>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <!-- Best Value Score -->
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:14px 20px;font-weight:700;color:var(--primary-navy);">
              <i class="bi bi-award-fill" style="color:var(--primary-orange);"></i> Best Value Score
            </td>
            <?php foreach ($comparedProducts as $p): ?>
              <td style="padding:14px 20px;text-align:center;">
                <span class="badge-score-value" style="font-size:13px;padding:4px 10px;">
                  <?= e($p['best_value_score']) ?> / 100
                </span>
              </td>
            <?php endforeach; ?>
          </tr>

          <!-- Eco-Friendly Score -->
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:14px 20px;font-weight:700;color:var(--primary-navy);">
              <i class="bi bi-leaf-fill" style="color:var(--accent-green);"></i> Eco-Friendly Score
            </td>
            <?php foreach ($comparedProducts as $p): ?>
              <td style="padding:14px 20px;text-align:center;">
                <span class="badge-score-eco" style="font-size:13px;padding:4px 10px;">
                  <?= e($p['eco_friendly_score']) ?> / 100
                </span>
              </td>
            <?php endforeach; ?>
          </tr>

          <!-- Warranty -->
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:14px 20px;font-weight:700;color:var(--primary-navy);">
              <i class="bi bi-shield-check"></i> Manufacturer Warranty
            </td>
            <?php foreach ($comparedProducts as $p): ?>
              <td style="padding:14px 20px;text-align:center;font-weight:600;">
                <?= $p['warranty_months'] > 0 ? "{$p['warranty_months']} Months" : 'Standard Warranty' ?>
              </td>
            <?php endforeach; ?>
          </tr>

          <!-- Availability -->
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:14px 20px;font-weight:700;color:var(--primary-navy);">
              <i class="bi bi-box-seam"></i> Availability
            </td>
            <?php foreach ($comparedProducts as $p): ?>
              <td style="padding:14px 20px;text-align:center;">
                <?= $p['stock_quantity'] > 0 ? '<span style="color:var(--accent-green);font-weight:700;">In Stock</span>' : '<span style="color:var(--accent-red);font-weight:700;">Out of Stock</span>' ?>
              </td>
            <?php endforeach; ?>
          </tr>

          <!-- Actions -->
          <tr>
            <td style="padding:20px;font-weight:700;">Action</td>
            <?php foreach ($comparedProducts as $p): ?>
              <td style="padding:20px;text-align:center;">
                <button type="button" class="btn-primary js-add-to-cart" data-product-id="<?= $p['id'] ?>" data-quantity="1" style="width:100%;justify-content:center;padding:10px;">
                  <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
              </td>
            <?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>

    <div style="margin-top:20px;font-size:12px;color:#94a3b8;line-height:1.5;">
      * Disclaimer: Best Value Scores and Eco-Friendly Scores are computed estimates derived transparently from ratings, savings percentages, warranty periods, and packaging material data.
    </div>

  <?php else: ?>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:64px 24px;text-align:center;max-width:540px;margin:auto;">
      <div style="font-size:48px;margin-bottom:12px;">⚖️</div>
      <h2 style="font-size:20px;color:var(--primary-navy);margin-bottom:8px;">No Products in Comparison</h2>
      <p style="color:#64748b;font-size:14px;margin-bottom:20px;">
        Click the compare icon (<i class="bi bi-arrow-left-right"></i>) on any product card to see side-by-side metrics.
      </p>
      <a href="<?= APP_URL ?>/products.php" class="btn-primary">Browse Products to Compare</a>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
