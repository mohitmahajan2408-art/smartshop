<?php
/**
 * ShopSmart India - Homepage
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();

// Fetch Today's Deals
$deals = [];
$bestSellers = [];
$ecoProducts = [];
$bestValueProducts = [];
$categories = [];

if ($db) {
    try {
        // Today's Deals
        $dealsStmt = $db->query("
            SELECT p.*, c.name AS category_name, b.name AS brand_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.is_active = 1 AND p.is_deal = 1 
            ORDER BY p.price ASC LIMIT 6
        ");
        $deals = $dealsStmt->fetchAll();

        // Best Sellers
        $bestStmt = $db->query("
            SELECT p.*, c.name AS category_name, b.name AS brand_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.is_active = 1 AND p.is_featured = 1 
            ORDER BY p.best_value_score DESC LIMIT 6
        ");
        $bestSellers = $bestStmt->fetchAll();

        // Eco-Friendly Products
        $ecoStmt = $db->query("
            SELECT p.*, c.name AS category_name, b.name AS brand_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.is_active = 1 AND p.eco_friendly_score >= 85 
            ORDER BY p.eco_friendly_score DESC LIMIT 6
        ");
        $ecoProducts = $ecoStmt->fetchAll();

        // Best Value Score Leaders
        $bvStmt = $db->query("
            SELECT p.*, c.name AS category_name, b.name AS brand_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.is_active = 1 
            ORDER BY p.best_value_score DESC LIMIT 6
        ");
        $bestValueProducts = $bvStmt->fetchAll();

        // Categories with icons
        $catStmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC");
        $categories = $catStmt->fetchAll();
    } catch (Exception $e) {
        // Safe empty fallbacks
    }
}
?>

<!-- Hero Banner -->
<section class="hero-section">
  <div class="container">
    <div class="hero-grid">
      <div>
        <div class="hero-badges">
          <span class="hero-badge-item" style="background:rgba(249,115,22,0.25);color:#fed7aa;border:1px solid #f97316;">
            <i class="bi bi-stars"></i> <?= e(__('hero_title')) ?>
          </span>
          <span class="hero-badge-item">
            <i class="bi bi-truck"></i> Free Delivery on orders over ₹499
          </span>
        </div>
        <h1 class="hero-title">
          Everything You Need, <span>All in One Place.</span>
        </h1>
        <p class="hero-subtitle">
          <?= e(__('hero_subtitle')) ?> Discover authentic electronics, fashion, groceries, kitchen essentials, and wellness products with transparent Best Value & Eco-Friendly scores.
        </p>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
          <a href="<?= APP_URL ?>/products.php" class="btn-primary">
            <i class="bi bi-bag-check-fill"></i> <?= e(__('shop_now')) ?>
          </a>
          <a href="<?= APP_URL ?>/budget.php" class="btn-secondary">
            <i class="bi bi-lightning-charge-fill" style="color:var(--primary-orange);"></i> <?= e(__('nav_budget_mode')) ?>
          </a>
        </div>
      </div>

      <div style="text-align:center;">
        <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.15);border-radius:var(--radius-lg);padding:24px;backdrop-filter:blur(10px);">
          <div style="font-size:13px;font-weight:700;color:#fed7aa;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">
            ⚡ Deal of the Day
          </div>
          <h3 style="font-size:20px;color:#ffffff;margin-bottom:12px;">boAt Rockerz 450 Wireless</h3>
          <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:16px;">
            <span style="font-size:28px;font-weight:800;color:var(--primary-orange);">₹1,299</span>
            <span style="font-size:16px;color:#94a3b8;text-decoration:line-through;">₹3,990</span>
            <span class="badge-discount">67% OFF</span>
          </div>
          <a href="<?= APP_URL ?>/product.php?slug=boat-rockerz-450-wireless-headphones" class="btn-primary" style="width:100%;justify-content:center;">
            Grab Deal Now
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Trust Strip -->
<div class="container">
  <div class="trust-strip">
    <div class="trust-item">
      <div class="trust-icon"><i class="bi bi-shield-check"></i></div>
      <div>
        <div class="trust-title">100% Original Products</div>
        <div class="trust-desc">Direct from verified Indian brands</div>
      </div>
    </div>
    <div class="trust-item">
      <div class="trust-icon"><i class="bi bi-truck"></i></div>
      <div>
        <div class="trust-title">Fast Pan-India Delivery</div>
        <div class="trust-desc">Real-time status updates</div>
      </div>
    </div>
    <div class="trust-item">
      <div class="trust-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
      <div>
        <div class="trust-title">Easy 7-Day Returns</div>
        <div class="trust-desc">Hassle-free doorstep pickup</div>
      </div>
    </div>
    <div class="trust-item">
      <div class="trust-icon"><i class="bi bi-award"></i></div>
      <div>
        <div class="trust-title">Best Value Guarantee</div>
        <div class="trust-desc">Data-backed scoring system</div>
      </div>
    </div>
  </div>
</div>

<main class="container">
  <!-- Categories Showcase Grid -->
  <section style="margin-bottom:48px;">
    <div class="section-header">
      <h2 class="section-title"><?= e(__('nav_categories')) ?></h2>
      <a href="<?= APP_URL ?>/products.php" class="view-all-link">View All Categories <i class="bi bi-arrow-right"></i></a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(135px, 1fr));gap:14px;">
      <?php foreach ($categories as $c): ?>
        <a href="<?= APP_URL ?>/products.php?category=<?= e($c['slug']) ?>" 
           style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 10px;text-align:center;transition:all 0.2s ease;display:flex;flex-direction:column;align-items:center;gap:10px;"
           onmouseover="this.style.borderColor='var(--primary-orange)';this.style.transform='translateY(-3px)'"
           onmouseout="this.style.borderColor='#e2e8f0';this.style.transform='none'">
          <div style="width:48px;height:48px;border-radius:50%;background:#fff7ed;color:var(--primary-orange);display:flex;align-items:center;justify-content:center;font-size:22px;">
            <i class="bi <?= e($c['icon'] ?: 'bi-tag') ?>"></i>
          </div>
          <span style="font-size:12.5px;font-weight:600;color:#0f172a;line-height:1.3;"><?= e($c['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Interactive Smart Budget Optimizer Quick Widget -->
  <section class="budget-widget-card">
    <div class="budget-widget-header">
      <span style="background:var(--primary-orange);color:#ffffff;font-size:11px;font-weight:800;padding:2px 8px;border-radius:4px;text-transform:uppercase;">
        Exclusive Feature
      </span>
      <h3 style="margin-top:6px;"><i class="bi bi-lightning-charge-fill" style="color:var(--primary-orange);"></i> <?= e(__('smart_budget_title')) ?></h3>
      <p style="font-size:13.5px;color:#64748b;"><?= e(__('smart_budget_subtitle')) ?></p>
    </div>
    <form action="<?= APP_URL ?>/budget.php" method="GET" class="budget-inputs-row">
      <div class="form-group">
        <label class="form-label"><?= e(__('your_budget')) ?></label>
        <input type="number" name="budget" class="form-control" placeholder="e.g. 5000" min="100" step="50" value="5000" required>
      </div>
      <div class="form-group">
        <label class="form-label"><?= e(__('intended_use')) ?></label>
        <select name="persona" class="form-control">
          <option value="student"><?= e(__('use_student')) ?></option>
          <option value="professional"><?= e(__('use_professional')) ?></option>
          <option value="home"><?= e(__('use_home')) ?></option>
          <option value="gaming"><?= e(__('use_gaming')) ?></option>
          <option value="travel"><?= e(__('use_travel')) ?></option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Category Filter (Optional)</label>
        <select name="category" class="form-control">
          <option value="">All Marketplace Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-primary" style="height:44px;white-space:nowrap;">
        <i class="bi bi-cpu"></i> <?= e(__('find_bundle')) ?>
      </button>
    </form>
  </section>

  <!-- Today's Deals -->
  <?php if (!empty($deals)): ?>
  <section style="margin-bottom:48px;">
    <div class="section-header">
      <h2 class="section-title"><i class="bi bi-fire" style="color:#ef4444;"></i> <?= e(__('todays_deals')) ?></h2>
      <a href="<?= APP_URL ?>/products.php?deal=1" class="view-all-link">View All Deals <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="products-grid">
      <?php foreach ($deals as $product): ?>
        <?php include __DIR__ . '/includes/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Best Sellers -->
  <?php if (!empty($bestSellers)): ?>
  <section style="margin-bottom:48px;">
    <div class="section-header">
      <h2 class="section-title"><i class="bi bi-trophy-fill" style="color:var(--accent-amber);"></i> <?= e(__('best_sellers')) ?></h2>
      <a href="<?= APP_URL ?>/products.php?sort=best_value" class="view-all-link">Explore Best Sellers <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="products-grid">
      <?php foreach ($bestSellers as $product): ?>
        <?php include __DIR__ . '/includes/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Eco-Friendly Sustainable Picks -->
  <?php if (!empty($ecoProducts)): ?>
  <section style="margin-bottom:48px;">
    <div class="section-header">
      <h2 class="section-title"><i class="bi bi-leaf-fill" style="color:var(--accent-green);"></i> <?= e(__('eco_friendly_picks')) ?></h2>
      <a href="<?= APP_URL ?>/products.php?eco=1" class="view-all-link">View All Eco Picks <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="products-grid">
      <?php foreach ($ecoProducts as $product): ?>
        <?php include __DIR__ . '/includes/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Best Value Score Leaders -->
  <?php if (!empty($bestValueProducts)): ?>
  <section style="margin-bottom:48px;">
    <div class="section-header">
      <h2 class="section-title"><i class="bi bi-award-fill" style="color:var(--primary-navy);"></i> <?= e(__('best_value_picks')) ?></h2>
      <a href="<?= APP_URL ?>/products.php?sort=best_value" class="view-all-link">View All Ranked Items <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="products-grid">
      <?php foreach ($bestValueProducts as $product): ?>
        <?php include __DIR__ . '/includes/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
