<?php
/**
 * ShopSmart India - Customer Happiness & Feedback
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();
$userId = currentUserId();

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errorMsg = 'Security validation failed. Please refresh and try again.';
    } else {
        $overall = max(1, min(5, (int)($_POST['overall_satisfaction'] ?? 5)));
        $quality = max(1, min(5, (int)($_POST['product_quality'] ?? 5)));
        $delivery = max(1, min(5, (int)($_POST['delivery_experience'] ?? 5)));
        $pricing = max(1, min(5, (int)($_POST['pricing_satisfaction'] ?? 5)));
        $usability = max(1, min(5, (int)($_POST['website_usability'] ?? 5)));
        $support = max(1, min(5, (int)($_POST['customer_support'] ?? 5)));
        $comments = trim($_POST['comments'] ?? '');

        if ($db) {
            $stmt = $db->prepare("
                INSERT INTO customer_feedback (user_id, overall_satisfaction, product_quality, delivery_experience, pricing_satisfaction, website_usability, customer_support, comments, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $overall, $quality, $delivery, $pricing, $usability, $support, $comments]);
            $successMsg = __('feedback_success');
        } else {
            $errorMsg = 'Database connection error.';
        }
    }
}
?>

<div class="container" style="max-width:760px;padding:36px 16px;">
  <div style="text-align:center;margin-bottom:32px;">
    <span style="background:var(--primary-orange);color:#fff;font-size:11px;font-weight:800;padding:3px 10px;border-radius:4px;text-transform:uppercase;">
      Customer Voice
    </span>
    <h1 style="font-size:28px;color:var(--primary-navy);margin:10px 0 8px;">
      <i class="bi bi-chat-heart-fill" style="color:var(--primary-orange);"></i> <?= e(__('feedback_title')) ?>
    </h1>
    <p style="color:#64748b;font-size:14.5px;">
      <?= e(__('feedback_subtitle')) ?>
    </p>
  </div>

  <?php if ($successMsg): ?>
    <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-left:4px solid #10b981;border-radius:12px;padding:24px;text-align:center;margin-bottom:32px;">
      <div style="font-size:42px;margin-bottom:8px;">🎉</div>
      <h3 style="color:#065f46;margin-bottom:6px;"><?= e($successMsg) ?></h3>
      <p style="color:#047857;font-size:14px;margin-bottom:16px;">Your valuable response helps our engineering and delivery teams serve India better.</p>
      <a href="<?= APP_URL ?>/index.php" class="btn-primary" style="display:inline-flex;">Return to Homepage</a>
    </div>
  <?php else: ?>
    <?php if ($errorMsg): ?>
      <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-weight:600;">
        ✕ <?= e($errorMsg) ?>
      </div>
    <?php endif; ?>

    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:var(--shadow-md);">
      <form action="<?= APP_URL ?>/feedback.php" method="POST">
        <?= csrfField() ?>

        <div style="display:flex;flex-direction:column;gap:22px;">
          <!-- 1. Overall -->
          <div>
            <label style="font-weight:700;font-size:14px;color:var(--primary-navy);display:block;margin-bottom:8px;">
              1. <?= e(__('overall_satisfaction')) ?>
            </label>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;border:1px solid #cbd5e1;padding:8px 14px;border-radius:8px;cursor:pointer;">
                  <input type="radio" name="overall_satisfaction" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                  <span><?= $i ?> ★ (<?= $i === 5 ? 'Excellent' : ($i === 4 ? 'Good' : ($i === 3 ? 'Average' : 'Poor')) ?>)</span>
                </label>
              <?php endfor; ?>
            </div>
          </div>

          <!-- 2. Product Quality -->
          <div>
            <label style="font-weight:700;font-size:14px;color:var(--primary-navy);display:block;margin-bottom:8px;">
              2. <?= e(__('product_quality')) ?>
            </label>
            <select name="product_quality" class="form-control">
              <option value="5">5 ★ — Genuine authentic products & strong packaging</option>
              <option value="4">4 ★ — Good quality, satisfied with items</option>
              <option value="3">3 ★ — Average quality</option>
              <option value="2">2 ★ — Below expectations</option>
              <option value="1">1 ★ — Poor / Damaged packaging</option>
            </select>
          </div>

          <!-- 3. Delivery Experience -->
          <div>
            <label style="font-weight:700;font-size:14px;color:var(--primary-navy);display:block;margin-bottom:8px;">
              3. <?= e(__('delivery_experience')) ?>
            </label>
            <select name="delivery_experience" class="form-control">
              <option value="5">5 ★ — Superfast delivery & polite courier staff</option>
              <option value="4">4 ★ — On-time delivery as promised</option>
              <option value="3">3 ★ — Minor delay but delivered safely</option>
              <option value="2">2 ★ — Late delivery</option>
              <option value="1">1 ★ — Significant delay or delivery issue</option>
            </select>
          </div>

          <!-- 4. Price & Value -->
          <div>
            <label style="font-weight:700;font-size:14px;color:var(--primary-navy);display:block;margin-bottom:8px;">
              4. <?= e(__('pricing_satisfaction')) ?>
            </label>
            <select name="pricing_satisfaction" class="form-control">
              <option value="5">5 ★ — Unbeatable discounts & high value</option>
              <option value="4">4 ★ — Fair pricing across categories</option>
              <option value="3">3 ★ — Comparable to offline stores</option>
              <option value="2">2 ★ — Expensive on certain items</option>
            </select>
          </div>

          <!-- 5. Website Usability -->
          <div>
            <label style="font-weight:700;font-size:14px;color:var(--primary-navy);display:block;margin-bottom:8px;">
              5. <?= e(__('website_usability')) ?>
            </label>
            <select name="website_usability" class="form-control">
              <option value="5">5 ★ — Extremely fast, responsive & easy search</option>
              <option value="4">4 ★ — Smooth checkout & good mobile view</option>
              <option value="3">3 ★ — Standard browsing experience</option>
            </select>
          </div>

          <!-- 6. Customer Support -->
          <div>
            <label style="font-weight:700;font-size:14px;color:var(--primary-navy);display:block;margin-bottom:8px;">
              6. <?= e(__('customer_support')) ?>
            </label>
            <select name="customer_support" class="form-control">
              <option value="5">5 ★ — Helpful, prompt & resolved inquiry</option>
              <option value="4">4 ★ — Satisfactory support</option>
              <option value="3">3 ★ — Not required / Did not contact</option>
            </select>
          </div>

          <!-- Comments -->
          <div class="form-group">
            <label class="form-label" style="font-weight:700;color:var(--primary-navy);"><?= e(__('feedback_comments')) ?></label>
            <textarea name="comments" class="form-control" rows="4" placeholder="Tell us what you liked most about ShopSmart India, or suggestions for new features..."></textarea>
          </div>

          <button type="submit" class="btn-primary" style="padding:14px;justify-content:center;font-size:16px;">
            <i class="bi bi-send-fill"></i> <?= e(__('submit_feedback')) ?>
          </button>
        </div>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
