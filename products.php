<?php
/**
 * ShopSmart India - Product Catalog & Search Listing
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();

// Filters from Query Parameters
$searchQuery = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$brandSlug = trim($_GET['brand'] ?? '');
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$minRating = isset($_GET['rating']) && is_numeric($_GET['rating']) ? (float)$_GET['rating'] : null;
$inStockOnly = !empty($_GET['in_stock']);
$ecoOnly = !empty($_GET['eco']);
$dealOnly = !empty($_GET['deal']);
$sortBy = $_GET['sort'] ?? 'relevance';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Base query
$conditions = ["p.is_active = 1"];
$params = [];

if ($searchQuery !== '') {
    $conditions[] = "(p.name LIKE ? OR p.short_description LIKE ? OR p.description LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categorySlug !== '') {
    $conditions[] = "c.slug = ?";
    $params[] = $categorySlug;
}

if ($brandSlug !== '') {
    $conditions[] = "b.slug = ?";
    $params[] = $brandSlug;
}

if ($minPrice !== null) {
    $conditions[] = "p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== null) {
    $conditions[] = "p.price <= ?";
    $params[] = $maxPrice;
}

if ($inStockOnly) {
    $conditions[] = "p.stock_quantity > 0";
}

if ($ecoOnly) {
    $conditions[] = "p.eco_friendly_score >= 80";
}

if ($dealOnly) {
    $conditions[] = "p.is_deal = 1";
}

// Sorting logic
$orderSql = "p.id DESC";
if ($sortBy === 'price_asc') {
    $orderSql = "p.price ASC";
} elseif ($sortBy === 'price_desc') {
    $orderSql = "p.price DESC";
} elseif ($sortBy === 'best_value') {
    $orderSql = "p.best_value_score DESC, p.price ASC";
} elseif ($sortBy === 'rating') {
    $orderSql = "p.best_value_score DESC";
}

$whereClause = implode(" AND ", $conditions);

$products = [];
$totalProducts = 0;
$totalPages = 1;
$allCategories = [];
$allBrands = [];

if ($db) {
    // Total count for pagination
    $cntStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE $whereClause
    ");
    $cntStmt->execute($params);
    $totalProducts = (int)$cntStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalProducts / $perPage));

    // Fetch paginated products
    $sql = "
        SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE $whereClause 
        ORDER BY $orderSql 
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Fetch categories and brands for filter sidebar
    $allCategories = $db->query("SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
    $allBrands = $db->query("SELECT name, slug FROM brands WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
}
?>

<div class="container" style="padding: 24px 16px;">
  <!-- Breadcrumbs & Result Count -->
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <div style="font-size:13px;color:#64748b;margin-bottom:4px;">
        <a href="<?= APP_URL ?>" style="color:var(--primary-orange);">Home</a> &gt; 
        <span>Catalog</span>
        <?php if ($categorySlug): ?> &gt; <strong><?= e($categorySlug) ?></strong><?php endif; ?>
      </div>
      <h1 style="font-size:22px;color:var(--primary-navy);margin:0;">
        <?= $searchQuery ? 'Search: "' . e($searchQuery) . '"' : ($categorySlug ? 'Category Products' : 'All Marketplace Products') ?>
        <span style="font-size:14px;color:#64748b;font-weight:400;">(<?= $totalProducts ?> items found)</span>
      </h1>
    </div>

    <!-- Sort Selector -->
    <div style="display:flex;align-items:center;gap:10px;">
      <label style="font-size:13px;font-weight:600;color:var(--text-secondary);"><?= e(__('sort_by')) ?>:</label>
      <select onchange="location = this.value;" class="form-control" style="padding:6px 12px;font-size:13px;border-radius:6px;">
        <?php
          $sortUrl = function($s) {
            $q = $_GET;
            $q['sort'] = $s;
            return '?' . http_build_query($q);
          };
        ?>
        <option value="<?= $sortUrl('relevance') ?>" <?= $sortBy === 'relevance' ? 'selected' : '' ?>><?= e(__('sort_relevance')) ?></option>
        <option value="<?= $sortUrl('price_asc') ?>" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>><?= e(__('sort_price_low')) ?></option>
        <option value="<?= $sortUrl('price_desc') ?>" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>><?= e(__('sort_price_high')) ?></option>
        <option value="<?= $sortUrl('best_value') ?>" <?= $sortBy === 'best_value' ? 'selected' : '' ?>><?= e(__('sort_best_value')) ?></option>
      </select>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:260px 1fr;gap:28px;">
    <!-- Filter Sidebar -->
    <aside style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;height:fit-content;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="font-size:16px;font-weight:700;color:var(--primary-navy);margin:0;"><?= e(__('filters')) ?></h3>
        <a href="<?= APP_URL ?>/products.php" style="font-size:12px;color:var(--primary-orange);font-weight:600;"><?= e(__('clear_filters')) ?></a>
      </div>

      <form action="<?= APP_URL ?>/products.php" method="GET">
        <?php if ($searchQuery): ?><input type="hidden" name="q" value="<?= e($searchQuery) ?>"><?php endif; ?>
        <?php if ($sortBy): ?><input type="hidden" name="sort" value="<?= e($sortBy) ?>"><?php endif; ?>

        <!-- Categories -->
        <div style="margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:16px;">
          <h4 style="font-size:13px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;margin-bottom:10px;">Categories</h4>
          <div style="max-height:180px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;font-size:13px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="radio" name="category" value="" <?= $categorySlug === '' ? 'checked' : '' ?> onchange="this.form.submit()"> All Categories
            </label>
            <?php foreach ($allCategories as $cat): ?>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="radio" name="category" value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'checked' : '' ?> onchange="this.form.submit()">
                <span><?= e($cat['name']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Price Range -->
        <div style="margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:16px;">
          <h4 style="font-size:13px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;margin-bottom:10px;">Price (₹)</h4>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
            <input type="number" name="min_price" placeholder="Min ₹" value="<?= e($_GET['min_price'] ?? '') ?>" class="form-control" style="padding:6px;font-size:12px;">
            <input type="number" name="max_price" placeholder="Max ₹" value="<?= e($_GET['max_price'] ?? '') ?>" class="form-control" style="padding:6px;font-size:12px;">
          </div>
          <button type="submit" class="btn-primary" style="width:100%;padding:6px;font-size:12px;justify-content:center;">Apply Price</button>
        </div>

        <!-- Brands -->
        <div style="margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:16px;">
          <h4 style="font-size:13px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;margin-bottom:10px;">Brand</h4>
          <select name="brand" class="form-control" style="font-size:13px;padding:6px;" onchange="this.form.submit()">
            <option value="">All Brands</option>
            <?php foreach ($allBrands as $b): ?>
              <option value="<?= e($b['slug']) ?>" <?= $brandSlug === $b['slug'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Quick Checkboxes -->
        <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="in_stock" value="1" <?= $inStockOnly ? 'checked' : '' ?> onchange="this.form.submit()">
            <span>In Stock Items Only</span>
          </label>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="eco" value="1" <?= $ecoOnly ? 'checked' : '' ?> onchange="this.form.submit()">
            <span>🌿 Eco-Friendly Picks</span>
          </label>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="deal" value="1" <?= $dealOnly ? 'checked' : '' ?> onchange="this.form.submit()">
            <span>⚡ Today's Special Deals</span>
          </label>
        </div>
      </form>
    </aside>

    <!-- Product Grid or Empty State -->
    <div>
      <?php if (!empty($products)): ?>
        <div class="products-grid" style="margin-top:0;">
          <?php foreach ($products as $product): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
          <?php endforeach; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
          <div style="display:flex;justify-content:center;gap:6px;margin-top:36px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <?php
                $pageParams = $_GET;
                $pageParams['page'] = $i;
                $pageUrl = '?' . http_build_query($pageParams);
              ?>
              <a href="<?= $pageUrl ?>" 
                 style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:6px;border:1px solid #cbd5e1;font-weight:600;font-size:14px;<?= $page === $i ? 'background:var(--primary-orange);color:#ffffff;border-color:var(--primary-orange);' : 'background:#ffffff;color:#0f172a;' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- Empty State -->
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:60px 24px;text-align:center;">
          <div style="font-size:48px;margin-bottom:12px;">🔍</div>
          <h3 style="font-size:20px;color:var(--primary-navy);margin-bottom:8px;"><?= e(__('no_products_found')) ?></h3>
          <p style="color:#64748b;font-size:14px;max-width:440px;margin:0 auto 20px;">
            <?= e(__('try_different_search')) ?>
          </p>
          <a href="<?= APP_URL ?>/products.php" class="btn-primary" style="display:inline-flex;">
            Reset All Filters & View Catalog
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
