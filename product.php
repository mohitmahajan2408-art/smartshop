<?php
/**
 * ShopSmart India - Product Detail & Reviews Page
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$slug = trim($_GET['slug'] ?? '');
$productId = (int)($_GET['id'] ?? 0);

$db = Database::getConnection();
$product = null;

if ($db) {
    if ($slug !== '') {
        $stmt = $db->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, b.slug AS brand_slug 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.slug = ? LIMIT 1
        ");
        $stmt->execute([$slug]);
        $product = $stmt->fetch();
    } elseif ($productId > 0) {
        $stmt = $db->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, b.slug AS brand_slug 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.id = ? LIMIT 1
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
    }
}

if (!$product) {
    echo '<div class="container" style="padding:60px 16px;text-align:center;">';
    echo '<h2>Product Not Found</h2>';
    echo '<p style="color:#64748b;margin:12px 0 24px;">The product you are looking for may have been moved or deactivated.</p>';
    echo '<a href="' . APP_URL . '/products.php" class="btn-primary">Browse All Products</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$prodId = (int)$product['id'];

// Track recently viewed products in session
if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}
if (!in_array($prodId, $_SESSION['recently_viewed'])) {
    array_unshift($_SESSION['recently_viewed'], $prodId);
    $_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 6);
}

// Handle Customer Review Submission
$reviewSuccessMsg = '';
$reviewErrorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    if (!verifyCsrf()) {
        $reviewErrorMsg = 'Invalid security token. Please refresh and try again.';
    } elseif (!isLoggedIn()) {
        $reviewErrorMsg = 'Please sign in to post a product review.';
    } else {
        $userId = currentUserId();
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $headline = trim($_POST['headline'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        if (strlen($headline) < 3 || strlen($comment) < 10) {
            $reviewErrorMsg = 'Please provide a descriptive title (at least 3 chars) and detailed review (at least 10 chars).';
        } else {
            // Check if user already submitted a review for this product
            $chkReview = $db->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
            $chkReview->execute([$prodId, $userId]);
            if ($chkReview->fetch()) {
                $reviewErrorMsg = 'You have already submitted a review for this product.';
            } else {
                // Check if verified purchase (has a delivered order containing this product)
                $verStmt = $db->prepare("
                    SELECT oi.id 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'Delivered'
                    LIMIT 1
                ");
                $verStmt->execute([$userId, $prodId]);
                $isVerified = $verStmt->fetch() ? 1 : 0;

                $insStmt = $db->prepare("
                    INSERT INTO reviews (product_id, user_id, rating, headline, comment, is_verified_purchase, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW())
                ");
                $insStmt->execute([$prodId, $userId, $rating, $headline, $comment, $isVerified]);
                $reviewSuccessMsg = 'Thank you! Your verified review has been published.';
            }
        }
    }
}

// Fetch Reviews
$revStmt = $db->prepare("
    SELECT r.*, u.name AS user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.status = 'approved' 
    ORDER BY r.created_at DESC
");
$revStmt->execute([$prodId]);
$reviews = $revStmt->fetchAll();

$totalReviews = count($reviews);
$avgRating = 0;
$verifiedCount = 0;
$ratingBreakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

if ($totalReviews > 0) {
    $sum = 0;
    foreach ($reviews as $r) {
        $sum += (int)$r['rating'];
        $ratingBreakdown[(int)$r['rating']]++;
        if (!empty($r['is_verified_purchase'])) {
            $verifiedCount++;
        }
    }
    $avgRating = round($sum / $totalReviews, 1);
} else {
    $avgRating = 4.5; // Catalog baseline for new items
}

$trustScoreData = calculateReviewTrustScore($totalReviews, $verifiedCount);

// Specifications JSON
$specs = !empty($product['specifications']) ? json_decode($product['specifications'], true) : [];

// Similar Products in same category
$simStmt = $db->prepare("
    SELECT p.*, c.name AS category_name, b.name AS brand_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 
    ORDER BY p.best_value_score DESC LIMIT 4
");
$simStmt->execute([$product['category_id'], $prodId]);
$similarProducts = $simStmt->fetchAll();

$price = (float)$product['price'];
$mrp = (float)$product['original_price'];
$discount = ($mrp > $price && $mrp > 0) ? round((($mrp - $price) / $mrp) * 100) : 0;
$savings = $mrp - $price;
$imageUrl = getProductImageUrl($product['image_url'], $product['name']);

// Fetch multi-image gallery
$imgStmt = $db->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC");
$imgStmt->execute([$prodId]);
$galleryImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($galleryImages)) {
    $galleryImages = [$imageUrl];
}
?>

<div class="container" style="padding:24px 16px;">
  <!-- Breadcrumbs -->
  <div style="font-size:13px;color:#64748b;margin-bottom:18px;">
    <a href="<?= APP_URL ?>" style="color:var(--primary-orange);">Home</a> &gt; 
    <a href="<?= APP_URL ?>/products.php?category=<?= e($product['category_slug']) ?>" style="color:var(--primary-orange);"><?= e($product['category_name']) ?></a> &gt; 
    <span><?= e($product['name']) ?></span>
  </div>

  <?php if ($reviewSuccessMsg): ?>
    <div style="background:#ecfdf5;border-left:4px solid #10b981;color:#065f46;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-weight:600;">
      ✓ <?= e($reviewSuccessMsg) ?>
    </div>
  <?php endif; ?>

  <?php if ($reviewErrorMsg): ?>
    <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-weight:600;">
      ✕ <?= e($reviewErrorMsg) ?>
    </div>
  <?php endif; ?>

  <!-- Main Product Detail Layout -->
  <div style="display:grid;grid-template-columns:460px 1fr 320px;gap:32px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;margin-bottom:36px;">
    <!-- Column 1: Image Gallery -->
    <div>
      <div style="border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center;background:#f8fafc;position:relative;overflow:hidden;height:380px;display:flex;align-items:center;justify-content:center;">
        <img id="main-product-img" src="<?= e($galleryImages[0]) ?>" alt="<?= e($product['name']) ?>" style="max-height:100%;max-width:100%;object-fit:cover;border-radius:8px;transition:transform 0.3s ease;">
        <button type="button" class="card-wishlist-btn js-toggle-wishlist" data-product-id="<?= $prodId ?>" style="top:16px;right:16px;">
          🤍
        </button>
      </div>
      <!-- Thumbnails -->
      <div style="display:flex;gap:12px;margin-top:14px;overflow-x:auto;">
        <?php foreach ($galleryImages as $idx => $gImg): ?>
          <div onclick="document.getElementById('main-product-img').src='<?= e($gImg) ?>'; document.querySelectorAll('.gallery-thumb').forEach(t => t.style.borderColor='#e2e8f0'); this.style.borderColor='var(--primary-orange)';" 
               class="gallery-thumb" 
               style="width:70px;height:70px;border:2px solid <?= $idx === 0 ? 'var(--primary-orange)' : '#e2e8f0' ?>;border-radius:8px;padding:2px;background:#fff;cursor:pointer;flex-shrink:0;">
            <img src="<?= e($gImg) ?>" alt="thumb" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Column 2: Product Info & Scores -->
    <div>
      <div style="font-size:13px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">
        <?= e($product['brand_name'] ?? 'ShopSmart India') ?> &bull; SKU: <?= e($product['sku']) ?>
      </div>
      <h1 style="font-size:24px;color:var(--primary-navy);line-height:1.3;margin-bottom:12px;">
        <?= e($product['name']) ?>
      </h1>

      <!-- Rating summary -->
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <span class="rating-pill" style="font-size:13px;padding:3px 8px;">
          <?= number_format($avgRating, 1) ?> <i class="bi bi-star-fill"></i>
        </span>
        <span style="font-size:13px;color:#64748b;">(<?= $totalReviews ?> customer ratings & reviews)</span>
        <span style="color:#cbd5e1;">|</span>
        <span style="font-size:13px;color:var(--accent-green);font-weight:600;"><i class="bi bi-shield-check"></i> 100% Authentic</span>
      </div>

      <!-- Pricing Block -->
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:20px;">
        <div style="display:flex;align-items:baseline;gap:12px;">
          <span style="font-size:32px;font-weight:800;color:var(--primary-navy);"><?= formatPriceINR($price) ?></span>
          <?php if ($discount > 0): ?>
            <span style="font-size:16px;color:#94a3b8;text-decoration:line-through;"><?= formatPriceINR($mrp) ?></span>
            <span class="badge-discount" style="font-size:13px;"><?= $discount ?>% OFF</span>
          <?php endif; ?>
        </div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;"><?= e(__('inclusive_taxes')) ?></div>
        <?php if ($savings > 0): ?>
          <div style="font-size:13px;color:var(--accent-green);font-weight:700;margin-top:6px;">
            🎉 You save <?= formatPriceINR($savings) ?> on this order!
          </div>
        <?php endif; ?>
      </div>

      <!-- ShopSmart Data Metrics (Best Value, Eco, Trust) -->
      <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:12px;margin-bottom:20px;">
        <!-- Best Value Score -->
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:10px;text-align:center;">
          <div style="font-size:11px;color:#9a3412;font-weight:700;text-transform:uppercase;">Best Value Score</div>
          <div style="font-size:22px;font-weight:800;color:var(--primary-orange);margin:2px 0;">
            <?= e($product['best_value_score']) ?>/100
          </div>
          <div style="font-size:11px;color:#64748b;">Calculated savings & specs</div>
        </div>

        <!-- Eco-Friendly Score -->
        <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:10px;text-align:center;">
          <div style="font-size:11px;color:#065f46;font-weight:700;text-transform:uppercase;">Eco Score</div>
          <div style="font-size:22px;font-weight:800;color:var(--accent-green);margin:2px 0;">
            <?= e($product['eco_friendly_score']) ?>/100
          </div>
          <div style="font-size:11px;color:#64748b;">Sustainable packaging</div>
        </div>

        <!-- Review Trust Score -->
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px;text-align:center;">
          <div style="font-size:11px;color:#166534;font-weight:700;text-transform:uppercase;">Trust Score</div>
          <div style="font-size:22px;font-weight:800;color:#16a34a;margin:2px 0;">
            <?= $trustScoreData['score'] ?>%
          </div>
          <div style="font-size:11px;color:#64748b;"><?= e($trustScoreData['label']) ?></div>
        </div>
      </div>

      <!-- Short Description -->
      <p style="font-size:14px;color:var(--text-secondary);line-height:1.6;margin-bottom:20px;">
        <?= nl2br(e($product['short_description'])) ?>
      </p>

      <!-- Key Specifications Table -->
      <?php if (!empty($specs)): ?>
        <div style="margin-top:16px;">
          <h4 style="font-size:14px;font-weight:700;color:var(--primary-navy);margin-bottom:10px;">Key Specifications</h4>
          <table style="width:100%;font-size:13px;border-collapse:collapse;">
            <?php foreach ($specs as $key => $val): ?>
              <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:6px 0;color:#64748b;width:40%;"><?= e($key) ?></td>
                <td style="padding:6px 0;font-weight:600;color:#0f172a;"><?= e($val) ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Column 3: Buy Box & Delivery Estimator -->
    <div style="border-left:1px solid #f1f5f9;padding-left:24px;">
      <div style="font-size:24px;font-weight:800;color:var(--primary-navy);margin-bottom:12px;">
        <?= formatPriceINR($price) ?>
      </div>

      <!-- Stock availability -->
      <div style="margin-bottom:16px;">
        <?php if ($product['stock_quantity'] > 0): ?>
          <span style="color:var(--accent-green);font-weight:700;font-size:14px;">
            <i class="bi bi-check-circle-fill"></i> In Stock (<?= $product['stock_quantity'] ?> units left)
          </span>
        <?php else: ?>
          <span style="color:var(--accent-red);font-weight:700;font-size:14px;">
            <i class="bi bi-x-circle-fill"></i> Currently Out of Stock
          </span>
        <?php endif; ?>
      </div>

      <!-- Delivery PIN check -->
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:20px;font-size:12.5px;">
        <div style="font-weight:600;color:#0f172a;margin-bottom:4px;">
          <i class="bi bi-geo-alt-fill" style="color:var(--primary-orange);"></i> Deliver to PIN: <span id="pincode-display">411004</span>
        </div>
        <div style="color:var(--accent-green);font-weight:600;">
          <i class="bi bi-lightning-fill"></i> Estimated delivery by <?= date('d M, l', strtotime('+2 days')) ?>
        </div>
        <div style="color:#64748b;font-size:11.5px;margin-top:2px;">Free Delivery on orders above ₹499</div>
      </div>

      <!-- Quantity Selector -->
      <?php if ($product['stock_quantity'] > 0): ?>
        <div style="margin-bottom:16px;">
          <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">Quantity:</label>
          <select id="product-qty-select" class="form-control" style="width:100%;font-size:14px;">
            <?php for ($i = 1; $i <= min(5, (int)$product['stock_quantity']); $i++): ?>
              <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <!-- Add to Cart & Buy Now Buttons -->
        <button type="button" class="btn-primary js-add-to-cart" 
                data-product-id="<?= $prodId ?>" data-quantity="1"
                onclick="this.dataset.quantity = document.getElementById('product-qty-select').value"
                style="width:100%;justify-content:center;margin-bottom:10px;padding:12px;">
          <i class="bi bi-cart-plus-fill"></i> Add to Cart
        </button>

        <button type="button" class="btn-secondary" 
                onclick="ShopSmart.addToCart(<?= $prodId ?>, document.getElementById('product-qty-select').value).then(() => { window.location.href = '<?= APP_URL ?>/checkout.php'; });"
                style="width:100%;justify-content:center;background:#0f233a;color:#ffffff;padding:12px;font-weight:700;">
          <i class="bi bi-bag-check-fill"></i> Buy Now
        </button>
      <?php endif; ?>

      <!-- Secondary Links -->
      <div style="display:flex;flex-direction:column;gap:8px;margin-top:16px;font-size:13px;">
        <a href="<?= APP_URL ?>/compare.php?add=<?= $prodId ?>" style="color:#64748b;text-decoration:none;display:flex;align-items:center;gap:6px;">
          <i class="bi bi-arrow-left-right"></i> Add to Compare
        </a>
        <a href="<?= APP_URL ?>/account.php?tab=alerts&product_id=<?= $prodId ?>" style="color:#64748b;text-decoration:none;display:flex;align-items:center;gap:6px;">
          <i class="bi bi-bell"></i> Set Price Drop Alert
        </a>
      </div>
    </div>
  </div>

  <!-- Detailed Reviews & Review Form Section -->
  <section style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;margin-bottom:36px;">
    <div style="display:grid;grid-template-columns:360px 1fr;gap:40px;">
      <!-- Ratings Summary -->
      <div>
        <h3 style="font-size:18px;font-weight:700;color:var(--primary-navy);margin-bottom:12px;">Customer Ratings</h3>
        <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:16px;">
          <span style="font-size:42px;font-weight:800;color:var(--primary-navy);"><?= number_format($avgRating, 1) ?></span>
          <span style="color:#f59e0b;font-size:22px;">★★★★☆</span>
          <span style="font-size:13px;color:#64748b;">(<?= $totalReviews ?> total)</span>
        </div>

        <!-- Rating distribution bars -->
        <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;margin-bottom:24px;">
          <?php for ($star = 5; $star >= 1; $star--): ?>
            <?php 
              $pct = $totalReviews > 0 ? round(($ratingBreakdown[$star] / $totalReviews) * 100) : 0; 
            ?>
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="width:50px;"><?= $star ?> star</span>
              <div style="flex:1;height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
                <div style="width:<?= $pct ?>%;height:100%;background:var(--accent-amber);"></div>
              </div>
              <span style="width:36px;color:#64748b;text-align:right;"><?= $pct ?>%</span>
            </div>
          <?php endfor; ?>
        </div>

        <!-- Write a Review Form -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
          <h4 style="font-size:15px;font-weight:700;color:var(--primary-navy);margin-bottom:8px;">Review this product</h4>
          <p style="font-size:12.5px;color:#64748b;margin-bottom:14px;">Share your thoughts with other Indian shoppers.</p>

          <?php if (isLoggedIn()): ?>
            <form action="<?= APP_URL ?>/product.php?slug=<?= urlencode($product['slug']) ?>" method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="submit_review">
              
              <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-control" style="font-size:13px;">
                  <option value="5">★★★★★ (5 Stars - Outstanding)</option>
                  <option value="4">★★★★☆ (4 Stars - Very Good)</option>
                  <option value="3">★★★☆☆ (3 Stars - Average)</option>
                  <option value="2">★★☆☆☆ (2 Stars - Disappointing)</option>
                  <option value="1">★☆☆☆☆ (1 Star - Poor)</option>
                </select>
              </div>

              <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Headline / Title</label>
                <input type="text" name="headline" class="form-control" placeholder="e.g. Excellent build quality and fast shipping" required>
              </div>

              <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Your Experience</label>
                <textarea name="comment" class="form-control" rows="3" placeholder="What did you like or dislike about this product?" required></textarea>
              </div>

              <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:10px;">
                Submit Verified Review
              </button>
            </form>
          <?php else: ?>
            <p style="font-size:13px;color:#64748b;margin-bottom:12px;">Please sign in to submit a review.</p>
            <a href="<?= APP_URL ?>/login.php" class="btn-secondary" style="width:100%;justify-content:center;">Sign In to Review</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Reviews Feed -->
      <div>
        <h3 style="font-size:18px;font-weight:700;color:var(--primary-navy);margin-bottom:16px;">Verified Customer Reviews</h3>
        <?php if (!empty($reviews)): ?>
          <div style="display:flex;flex-direction:column;gap:20px;">
            <?php foreach ($reviews as $rev): ?>
              <div style="border-bottom:1px solid #f1f5f9;padding-bottom:16px;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                  <span class="rating-pill">
                    <?= (int)$rev['rating'] ?> ★
                  </span>
                  <span style="font-weight:700;font-size:14px;color:#0f172a;"><?= e($rev['headline']) ?></span>
                </div>

                <div style="font-size:12px;color:#64748b;margin-bottom:8px;">
                  By <strong><?= e($rev['user_name']) ?></strong> on <?= formatDateIndian($rev['created_at'], false) ?>
                  <?php if (!empty($rev['is_verified_purchase'])): ?>
                    <span style="color:var(--accent-green);font-weight:600;margin-left:8px;">
                      <i class="bi bi-patch-check-fill"></i> Verified Purchase
                    </span>
                  <?php endif; ?>
                </div>

                <p style="font-size:13.5px;color:var(--text-secondary);line-height:1.5;">
                  <?= nl2br(e($rev['comment'])) ?>
                </p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="background:#f8fafc;padding:32px;text-align:center;border-radius:12px;color:#64748b;">
            <p>No customer reviews yet. Be the first to share your experience!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Similar Products -->
  <?php if (!empty($similarProducts)): ?>
    <section style="margin-top:36px;">
      <h3 class="section-title">Similar Products You May Like</h3>
      <div class="products-grid">
        <?php foreach ($similarProducts as $product): ?>
          <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
