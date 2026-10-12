<?php
/**
 * ShopSmart India - Universal Site Footer
 * BCA Semester V Field Project
 */
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <!-- Col 1: About & Branding -->
      <div class="footer-col">
        <div style="margin-bottom:16px;">
          <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="ShopSmart India" style="height:44px;filter:brightness(0) invert(1);">
        </div>
        <p style="font-size:13.5px;line-height:1.6;margin-bottom:14px;color:#cbd5e1;">
          <strong>ShopSmart India</strong> — <?= e(__('tagline')) ?> India's premier multi-category smart shopping destination empowering conscious, budget-friendly consumers.
        </p>
        <div style="font-size:12px;color:#94a3b8;background:rgba(255,255,255,0.05);padding:10px 14px;border-radius:6px;border:1px solid rgba(255,255,255,0.1);">
          🎓 <strong>BCA Semester V Field Project</strong><br>
          Full-Stack PHP, MySQL, Apache & Modern JavaScript.
        </div>
      </div>

      <!-- Col 2: Unique Smart Features -->
      <div class="footer-col">
        <h4>Smart Features</h4>
        <ul class="footer-links">
          <li><a href="<?= APP_URL ?>/budget.php"><i class="bi bi-calculator"></i> <?= e(__('smart_budget_title')) ?></a></li>
          <li><a href="<?= APP_URL ?>/compare.php"><i class="bi bi-arrow-left-right"></i> <?= e(__('compare_title')) ?></a></li>
          <li><a href="<?= APP_URL ?>/products.php?eco=1"><i class="bi bi-leaf"></i> Eco-Friendly Sustainable Picks</a></li>
          <li><a href="<?= APP_URL ?>/products.php?sort=best_value"><i class="bi bi-award"></i> Best Value Score Leaders</a></li>
          <li><a href="<?= APP_URL ?>/account.php?tab=expense"><i class="bi bi-wallet2"></i> Monthly Expense Tracker</a></li>
          <li><a href="<?= APP_URL ?>/feedback.php"><i class="bi bi-chat-heart"></i> Customer Happiness Feedback</a></li>
        </ul>
      </div>

      <!-- Col 3: Customer Care & Policies -->
      <div class="footer-col">
        <h4>Customer Care</h4>
        <ul class="footer-links">
          <li><a href="<?= APP_URL ?>/account.php?tab=orders"><i class="bi bi-box"></i> <?= e(__('nav_my_orders')) ?></a></li>
          <li><a href="<?= APP_URL ?>/track_order.php"><i class="bi bi-truck"></i> <?= e(__('track_your_order')) ?></a></li>
          <li><a href="<?= APP_URL ?>/wishlist.php"><i class="bi bi-heart"></i> <?= e(__('nav_wishlist')) ?></a></li>
          <li><a href="<?= APP_URL ?>/feedback.php"><i class="bi bi-headset"></i> Help Center & Grievance</a></li>
          <li><a href="#!"><i class="bi bi-arrow-counterclockwise"></i> Easy 7-Day Returns Policy</a></li>
          <li><a href="#!"><i class="bi bi-shield-check"></i> Safe & Secure Payments Policy</a></li>
        </ul>
      </div>

      <!-- Col 4: Top Indian Categories -->
      <div class="footer-col">
        <h4>Popular Categories</h4>
        <ul class="footer-links">
          <li><a href="<?= APP_URL ?>/products.php?category=electronics"><?= e(__('cat_electronics')) ?></a></li>
          <li><a href="<?= APP_URL ?>/products.php?category=mens-fashion"><?= e(__('cat_mens_fashion')) ?></a></li>
          <li><a href="<?= APP_URL ?>/products.php?category=womens-fashion"><?= e(__('cat_womens_fashion')) ?></a></li>
          <li><a href="<?= APP_URL ?>/products.php?category=grocery-daily-needs"><?= e(__('cat_grocery')) ?></a></li>
          <li><a href="<?= APP_URL ?>/products.php?category=home-kitchen"><?= e(__('cat_home_kitchen')) ?></a></li>
          <li><a href="<?= APP_URL ?>/products.php?category=health-wellness"><?= e(__('cat_health')) ?></a></li>
        </ul>
      </div>
    </div>

    <!-- Payment Badges & Copyright -->
    <div class="footer-bottom">
      <div>
        &copy; <?= date('Y') ?> <strong>ShopSmart India</strong>. All Rights Reserved. Built with pride for Indian consumers.
      </div>
      <div style="display:flex;align-items:center;gap:12px;font-size:16px;">
        <span style="font-size:12px;color:#cbd5e1;">Accepted Payments:</span>
        <span style="background:#ffffff;color:#0f233a;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">UPI</span>
        <span style="background:#ffffff;color:#0f233a;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">RuPay</span>
        <span style="background:#ffffff;color:#0f233a;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">VISA</span>
        <span style="background:#ffffff;color:#0f233a;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">Mastercard</span>
        <span style="background:#ffffff;color:#0f233a;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">COD</span>
      </div>
    </div>
  </div>
</footer>

<!-- Interactive Frontend Script -->
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
