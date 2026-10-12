<?php
/**
 * ShopSmart India - Smart Budget Optimizer (Exclusive Feature)
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();

// Default values
$inputBudget = isset($_GET['budget']) ? (float)$_GET['budget'] : 5000;
$inputPersona = $_GET['persona'] ?? 'student';
$inputCategory = $_GET['category'] ?? '';

// Fetch all categories for filter dropdown
$categories = [];
if ($db) {
    $categories = $db->query("SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
}
?>

<div class="container" style="padding: 36px 16px;">
  <!-- Header -->
  <div style="text-align:center;max-width:760px;margin:0 auto 32px;">
    <span style="background:var(--primary-orange);color:#fff;font-size:11px;font-weight:800;padding:3px 10px;border-radius:4px;text-transform:uppercase;letter-spacing:0.5px;">
      Proprietary Algorithm
    </span>
    <h1 style="font-size:30px;color:var(--primary-navy);margin:10px 0 8px;">
      <i class="bi bi-lightning-charge-fill" style="color:var(--primary-orange);"></i> <?= e(__('smart_budget_title')) ?>
    </h1>
    <p style="color:#64748b;font-size:15px;line-height:1.6;">
      <?= e(__('smart_budget_subtitle')) ?> Our intelligent recommendation engine automatically selects complementary, top-rated products that strictly maximize value without exceeding your specified budget.
    </p>
  </div>

  <!-- Calculator Control Card -->
  <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;box-shadow:var(--shadow-md);margin-bottom:36px;">
    <form id="budget-calc-form" style="display:grid;grid-template-columns:1.2fr 1fr 1fr auto;gap:18px;align-items:flex-end;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;color:var(--primary-navy);"><?= e(__('your_budget')) ?></label>
        <div style="position:relative;">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-weight:700;color:#64748b;">₹</span>
          <input type="number" id="budget-input" name="budget" class="form-control" 
                 style="padding-left:28px;font-size:16px;font-weight:700;" 
                 value="<?= $inputBudget ?>" min="100" step="50" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-weight:700;color:var(--primary-navy);"><?= e(__('intended_use')) ?></label>
        <select id="persona-select" name="persona" class="form-control" style="font-size:14px;">
          <option value="student" <?= $inputPersona === 'student' ? 'selected' : '' ?>><?= e(__('use_student')) ?></option>
          <option value="professional" <?= $inputPersona === 'professional' ? 'selected' : '' ?>><?= e(__('use_professional')) ?></option>
          <option value="home" <?= $inputPersona === 'home' ? 'selected' : '' ?>><?= e(__('use_home')) ?></option>
          <option value="gaming" <?= $inputPersona === 'gaming' ? 'selected' : '' ?>><?= e(__('use_gaming')) ?></option>
          <option value="travel" <?= $inputPersona === 'travel' ? 'selected' : '' ?>><?= e(__('use_travel')) ?></option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-weight:700;color:var(--primary-navy);">Category Filter</label>
        <select id="category-select" name="category" class="form-control" style="font-size:14px;">
          <option value="">All Marketplace Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat['slug']) ?>" <?= $inputCategory === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn-primary" style="height:44px;font-size:15px;white-space:nowrap;">
        <i class="bi bi-cpu-fill"></i> <?= e(__('find_bundle')) ?>
      </button>
    </form>
  </div>

  <!-- Results Container -->
  <div id="budget-results-container" style="display:none;">
    <!-- Summary Metrics Strip -->
    <div style="background:linear-gradient(135deg, #0a192f 0%, #0f233a 50%, #1e3a8a 100%);border-radius:16px;padding:24px;color:#fff;margin-bottom:32px;display:grid;grid-template-columns:repeat(4, 1fr);gap:20px;text-align:center;">
      <div>
        <div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:700;">Entered Budget</div>
        <div style="font-size:24px;font-weight:800;color:#ffffff;" id="res-budget">₹0</div>
      </div>
      <div>
        <div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:700;">Total Setup Cost</div>
        <div style="font-size:24px;font-weight:800;color:var(--primary-orange);" id="res-total">₹0</div>
      </div>
      <div>
        <div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:700;">Money Remaining</div>
        <div style="font-size:24px;font-weight:800;color:var(--accent-green);" id="res-remaining">₹0</div>
      </div>
      <div>
        <div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:700;">Total Money Saved</div>
        <div style="font-size:24px;font-weight:800;color:#fcd34d;" id="res-saved">₹0</div>
      </div>
    </div>

    <!-- Recommended Setup Items Grid -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h2 style="font-size:20px;color:var(--primary-navy);margin:0;">
        Recommended Setup Bundle (<span id="res-item-count">0</span> Items)
      </h2>
      <button type="button" id="btn-add-all-setup" class="btn-primary" style="padding:10px 20px;">
        <i class="bi bi-cart-check-fill"></i> <?= e(__('add_setup_to_cart')) ?>
      </button>
    </div>

    <div id="budget-items-grid" class="products-grid"></div>
  </div>

  <!-- Loading State -->
  <div id="budget-loading" style="display:none;text-align:center;padding:60px 0;">
    <div class="splash-spinner" style="border-top-color:var(--primary-orange);width:44px;height:44px;margin-bottom:16px;"></div>
    <div style="font-size:16px;font-weight:700;color:var(--primary-navy);">Analyzing prices, specs and scores across marketplace...</div>
  </div>

  <!-- Error / Insufficient Budget Container -->
  <div id="budget-error" style="display:none;background:#ffffff;border:1px solid #fed7aa;border-radius:16px;padding:48px 24px;text-align:center;max-width:640px;margin:auto;">
    <div style="font-size:48px;margin-bottom:12px;">💡</div>
    <h3 style="color:#9a3412;margin-bottom:8px;" id="budget-error-title">Budget Notice</h3>
    <p style="color:#64748b;font-size:14px;line-height:1.5;margin-bottom:20px;" id="budget-error-msg"></p>
    <button type="button" class="btn-primary" onclick="document.getElementById('budget-input').focus();">Adjust Budget</button>
  </div>
</div>

<script>
document.getElementById('budget-calc-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const budget = document.getElementById('budget-input').value;
  const persona = document.getElementById('persona-select').value;
  const category = document.getElementById('category-select').value;

  const resultsDiv = document.getElementById('budget-results-container');
  const loadingDiv = document.getElementById('budget-loading');
  const errorDiv = document.getElementById('budget-error');
  const itemsGrid = document.getElementById('budget-items-grid');

  resultsDiv.style.display = 'none';
  errorDiv.style.display = 'none';
  loadingDiv.style.display = 'block';

  try {
    const res = await fetch(`<?= APP_URL ?>/api/budget_recommend.php?budget=${budget}&persona=${persona}&category=${category}`);
    const data = await res.json();

    loadingDiv.style.display = 'none';

    if (data.success && data.items.length > 0) {
      document.getElementById('res-budget').textContent = data.formatted_budget;
      document.getElementById('res-total').textContent = data.formatted_total;
      document.getElementById('res-remaining').textContent = data.formatted_remaining;
      document.getElementById('res-saved').textContent = data.formatted_saved;
      document.getElementById('res-item-count').textContent = data.item_count;

      // Populate Items Grid
      itemsGrid.innerHTML = data.items.map(item => `
        <div class="product-card">
          <div class="card-image-wrap">
            <div class="card-badges">
              <span class="badge-score-value"><i class="bi bi-award-fill"></i> Val ${item.best_value_score}</span>
              <span class="badge-score-eco"><i class="bi bi-leaf-fill"></i> Eco ${item.eco_friendly_score}</span>
            </div>
            <a href="<?= APP_URL ?>/product.php?slug=${encodeURIComponent(item.slug)}">
              <img src="${item.image_url}" alt="${item.name}">
            </a>
          </div>
          <div class="card-body">
            <div class="card-brand">${item.category_name}</div>
            <a href="<?= APP_URL ?>/product.php?slug=${encodeURIComponent(item.slug)}" class="card-title">${item.name}</a>
            <div class="card-price-row">
              <span class="price-current">${item.formatted_price}</span>
              ${item.original_price > item.price ? `<span class="price-mrp">${item.formatted_mrp}</span>` : ''}
            </div>
            <div class="card-actions">
              <button type="button" class="btn-add-cart js-add-to-cart" data-product-id="${item.id}" data-quantity="1">
                <i class="bi bi-cart-plus"></i> Add to Cart
              </button>
            </div>
          </div>
        </div>
      `).join('');

      // Add entire setup button handler
      document.getElementById('btn-add-all-setup').onclick = async () => {
        for (const item of data.items) {
          await ShopSmart.addToCart(item.id, 1);
        }
        ShopSmart.toast('All recommended items added to your cart!', 'success');
        setTimeout(() => { window.location.href = '<?= APP_URL ?>/cart.php'; }, 1000);
      };

      resultsDiv.style.display = 'block';
    } else {
      document.getElementById('budget-error-msg').textContent = data.message || 'No products could be bundled within this budget.';
      errorDiv.style.display = 'block';
    }
  } catch (err) {
    loadingDiv.style.display = 'none';
    document.getElementById('budget-error-msg').textContent = 'Unable to compute budget bundle. Please try again.';
    errorDiv.style.display = 'block';
  }
});

// Auto-run on page load if budget is present
window.addEventListener('DOMContentLoaded', () => {
  document.getElementById('budget-calc-form').dispatchEvent(new Event('submit'));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
