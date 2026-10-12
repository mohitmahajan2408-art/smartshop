<?php
/**
 * ShopSmart India - Reusable Product Card Component
 * BCA Semester V Field Project
 */

$cardPrice = (float)$product['price'];
$cardOriginalPrice = (float)$product['original_price'];
$cardDiscount = 0;
if ($cardOriginalPrice > $cardPrice && $cardOriginalPrice > 0) {
    $cardDiscount = round((($cardOriginalPrice - $cardPrice) / $cardOriginalPrice) * 100);
}

$cardRating = 4.5; // default fallback
$cardReviewCount = 12;
if (!empty($product['rating'])) {
    $cardRating = (float)$product['rating'];
}
if (!empty($product['review_count'])) {
    $cardReviewCount = (int)$product['review_count'];
}

$imageUrl = getProductImageUrl($product['image_url'] ?? null, $product['name']);
$inStock = ($product['stock_quantity'] > 0);
?>
<div class="product-card">
  <div class="card-image-wrap">
    <!-- Badges -->
    <div class="card-badges">
      <?php if ($cardDiscount > 0): ?>
        <span class="badge-discount"><?= $cardDiscount ?>% <?= e(__('off')) ?></span>
      <?php endif; ?>
      <?php if (!empty($product['best_value_score']) && $product['best_value_score'] >= 85): ?>
        <span class="badge-score-value" title="Best Value Score: Calculated based on ratings, savings and warranty">
          <i class="bi bi-award-fill" style="color:#f59e0b;"></i> Value <?= e($product['best_value_score']) ?>
        </span>
      <?php endif; ?>
      <?php if (!empty($product['eco_friendly_score']) && $product['eco_friendly_score'] >= 80): ?>
        <span class="badge-score-eco" title="Eco-Friendly Score: Certified packaging and sustainable materials">
          <i class="bi bi-leaf-fill"></i> Eco <?= e($product['eco_friendly_score']) ?>
        </span>
      <?php endif; ?>
    </div>

    <!-- Wishlist Button -->
    <button type="button" class="card-wishlist-btn js-toggle-wishlist" 
            data-product-id="<?= (int)$product['id'] ?>" title="<?= e(__('nav_wishlist')) ?>">
      🤍
    </button>

    <!-- Product Image linking to detail page -->
    <a href="<?= APP_URL ?>/product.php?slug=<?= urlencode($product['slug']) ?>">
      <img src="<?= e($imageUrl) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
    </a>
  </div>

  <div class="card-body">
    <!-- Brand -->
    <div class="card-brand">
      <?= e($product['brand_name'] ?? $product['category_name'] ?? 'ShopSmart') ?>
    </div>

    <!-- Title -->
    <a href="<?= APP_URL ?>/product.php?slug=<?= urlencode($product['slug']) ?>" class="card-title" title="<?= e($product['name']) ?>">
      <?= e($product['name']) ?>
    </a>

    <!-- Rating & Reviews -->
    <div class="card-rating">
      <span class="rating-pill">
        <?= number_format($cardRating, 1) ?> <i class="bi bi-star-fill"></i>
      </span>
      <span class="rating-count">(<?= $cardReviewCount ?>)</span>
      
      <?php if ($inStock): ?>
        <span style="margin-left:auto;font-size:11px;color:var(--accent-green);font-weight:600;">
          <i class="bi bi-check-circle-fill"></i> <?= e(__('in_stock')) ?>
        </span>
      <?php else: ?>
        <span style="margin-left:auto;font-size:11px;color:var(--accent-red);font-weight:600;">
          <?= e(__('out_of_stock')) ?>
        </span>
      <?php endif; ?>
    </div>

    <!-- Price Row -->
    <div class="card-price-row">
      <span class="price-current"><?= formatPriceINR($cardPrice) ?></span>
      <?php if ($cardDiscount > 0): ?>
        <span class="price-mrp"><?= formatPriceINR($cardOriginalPrice) ?></span>
      <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="card-actions">
      <?php if ($inStock): ?>
        <button type="button" class="btn-add-cart js-add-to-cart" 
                data-product-id="<?= (int)$product['id'] ?>" data-quantity="1">
          <i class="bi bi-cart-plus"></i> <?= e(__('add_to_cart')) ?>
        </button>
      <?php else: ?>
        <button type="button" class="btn-add-cart" disabled style="background:#cbd5e1;cursor:not-allowed;">
          <?= e(__('out_of_stock')) ?>
        </button>
      <?php endif; ?>

      <a href="<?= APP_URL ?>/compare.php?add=<?= (int)$product['id'] ?>" class="btn-compare" title="Compare this product">
        <i class="bi bi-arrow-left-right"></i>
      </a>
    </div>
  </div>
</div>
