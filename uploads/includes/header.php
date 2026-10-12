<?php
/**
 * ShopSmart India - Universal Site Header
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/auth.php';

// Calculate live cart & wishlist counts
$cartCount = 0;
$wishlistCount = 0;

if (isLoggedIn()) {
    $db = Database::getConnection();
    if ($db) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?");
        $stmt->execute([currentUserId()]);
        $cartCount = (int)$stmt->fetchColumn();

        $wstmt = $db->prepare("SELECT COUNT(*) FROM wishlist_items WHERE user_id = ?");
        $wstmt->execute([currentUserId()]);
        $wishlistCount = (int)$wstmt->fetchColumn();
    }
} else {
    if (!empty($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
        $cartCount = (int)array_sum($_SESSION['guest_cart']);
    }
    if (!empty($_SESSION['guest_wishlist']) && is_array($_SESSION['guest_wishlist'])) {
        $wishlistCount = (int)count($_SESSION['guest_wishlist']);
    }
}

// Fetch categories for search bar and navigation
$headerCategories = [];
$db = Database::getConnection();
if ($db) {
    try {
        $catStmt = $db->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC LIMIT 14");
        $headerCategories = $catStmt->fetchAll();
    } catch (Exception $e) {
        $headerCategories = [];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' . e(__('app_name')) : e(__('app_name')) . ' — ' . e(__('tagline')) ?></title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">
  
  <!-- Master Stylesheet -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
  
  <!-- Bootstrap Icons CDN for high-fidelity UI icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<!-- Splash Screen (shown briefly once per session) -->
<div id="shopsmart-splash">
  <div class="splash-content">
    <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="ShopSmart India" class="splash-logo">
    <div style="color:#ffffff;font-size:18px;font-weight:700;margin-top:12px;">ShopSmart India</div>
    <div style="color:#94a3b8;font-size:13px;margin-top:4px;"><?= e(__('tagline')) ?></div>
    <div class="splash-spinner"></div>
  </div>
</div>

<header class="site-header">
  <!-- Top Utility Bar -->
  <div class="top-bar">
    <div class="container">
      <div class="top-bar-left">
        <span class="top-link" id="btn-change-pin" style="cursor:pointer;" title="Click to change delivery PIN code">
          <i class="bi bi-geo-alt-fill" style="color:var(--primary-orange);"></i>
          <span><?= e(__('deliver_to')) ?>: <strong><span id="header-delivery-pin">411004</span></strong></span>
          <i class="bi bi-chevron-down" style="font-size:10px;"></i>
        </span>
        <span class="top-link" style="color:#94a3b8;">|</span>
        <span class="top-link">
          <i class="bi bi-shield-check" style="color:var(--accent-green);"></i>
          <span>100% Original Products & Safe Payments</span>
        </span>
      </div>

      <div class="top-bar-right">
        <a href="<?= APP_URL ?>/portal.php" class="top-link" style="background:#f97316;color:#ffffff;padding:2px 10px;border-radius:12px;font-weight:700;display:inline-flex;align-items:center;gap:5px;">
          <i class="bi bi-grid-3x3-gap-fill"></i>
          <span>All-in-One Portal</span>
        </a>
        <!-- Language Selector -->
        <div style="position:relative;display:inline-block;">
          <button type="button" class="lang-selector-btn" onclick="document.getElementById('lang-dropdown').classList.toggle('active')">
            <i class="bi bi-translate"></i>
            <span><?= e(I18n::getSupportedLanguages()[currentLang()]['native']) ?></span>
            <i class="bi bi-chevron-down" style="font-size:10px;"></i>
          </button>
          <div id="lang-dropdown" style="display:none;position:absolute;top:100%;right:0;background:#ffffff;border:1px solid #e2e8f0;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,0.15);padding:6px;min-width:140px;z-index:9999;">
            <a href="?lang=en" style="display:block;padding:6px 12px;font-size:13px;color:#0f172a;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#fff7ed'" onmouseout="this.style.background='transparent'">English</a>
            <a href="?lang=mr" style="display:block;padding:6px 12px;font-size:13px;color:#0f172a;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#fff7ed'" onmouseout="this.style.background='transparent'">मराठी (Marathi)</a>
            <a href="?lang=hi" style="display:block;padding:6px 12px;font-size:13px;color:#0f172a;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#fff7ed'" onmouseout="this.style.background='transparent'">हिंदी (Hindi)</a>
          </div>
        </div>

        <a href="<?= APP_URL ?>/feedback.php" class="top-link">
          <i class="bi bi-emoji-smile"></i>
          <span><?= e(__('feedback_title')) ?></span>
        </a>

        <a href="<?= APP_URL ?>/account.php?tab=orders" class="top-link">
          <i class="bi bi-box-seam"></i>
          <span><?= e(__('nav_my_orders')) ?></span>
        </a>

        <?php if (isAdmin()): ?>
          <a href="<?= APP_URL ?>/admin/index.php" class="top-link" style="color:var(--primary-orange);font-weight:700;">
            <i class="bi bi-speedometer2"></i>
            <span><?= e(__('nav_admin')) ?></span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Main Search & Action Bar -->
  <div class="main-header">
    <div class="container">
      <!-- Logo -->
      <a href="<?= APP_URL ?>/index.php" class="brand-link" title="<?= e(__('app_name')) ?>">
        <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="ShopSmart India" class="brand-logo">
      </a>

      <!-- Search Omnibar -->
      <div class="search-container">
        <form action="<?= APP_URL ?>/products.php" method="GET" class="search-form" id="header-search-form">
          <select name="category" class="search-category-select">
            <option value=""><?= e(__('nav_categories')) ?></option>
            <?php foreach ($headerCategories as $cat): ?>
              <option value="<?= e($cat['slug']) ?>" <?= (($_GET['category'] ?? '') === $cat['slug']) ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <input type="text" name="q" id="header-search-input" class="search-input" 
                 placeholder="<?= e(__('search_placeholder')) ?>" 
                 value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">

          <!-- Voice Search Trigger -->
          <button type="button" class="search-tools-btn" id="btn-voice-search" title="Voice Search (Web Speech API)">
            <i class="bi bi-mic-fill"></i>
          </button>

          <!-- Image Search Trigger -->
          <button type="button" class="search-tools-btn" id="btn-image-search" title="Image Search Demonstration">
            <i class="bi bi-camera-fill"></i>
          </button>

          <!-- Submit Button -->
          <button type="submit" class="search-submit-btn" title="Search">
            <i class="bi bi-search" style="font-size:16px;"></i>
          </button>
        </form>

        <!-- Live Search Suggestions Dropdown -->
        <div id="header-search-suggestions" class="search-suggestions-dropdown"></div>
      </div>

      <!-- User Actions Right -->
      <div class="header-actions">
        <!-- Account / Login Menu -->
        <div style="position:relative;display:inline-block;" id="user-menu-container">
          <?php if (isLoggedIn()): ?>
            <a href="<?= APP_URL ?>/account.php" class="header-action-item" onclick="event.preventDefault(); document.getElementById('account-dropdown').classList.toggle('active')">
              <i class="bi bi-person-circle" style="font-size:22px;color:var(--primary-orange);"></i>
              <div class="header-action-text">
                <span class="subtext">Hello, <?= e(explode(' ', currentUser()['name'])[0]) ?></span>
                <span class="maintext"><?= e(__('nav_account')) ?> <i class="bi bi-chevron-down" style="font-size:10px;"></i></span>
              </div>
            </a>
            <div id="account-dropdown" style="display:none;position:absolute;top:100%;right:0;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.15);padding:8px;min-width:200px;z-index:9999;">
              <a href="<?= APP_URL ?>/account.php" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13.5px;color:#0f172a;border-radius:4px;" onmouseover="this.style.background='#fff7ed'" onmouseout="this.style.background='transparent'"><i class="bi bi-person"></i> My Profile</a>
              <a href="<?= APP_URL ?>/account.php?tab=orders" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13.5px;color:#0f172a;border-radius:4px;" onmouseover="this.style.background='#fff7ed'" onmouseout="this.style.background='transparent'"><i class="bi bi-box"></i> My Orders</a>
              <a href="<?= APP_URL ?>/wishlist.php" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13.5px;color:#0f172a;border-radius:4px;" onmouseover="this.style.background='#fff7ed'" onmouseout="this.style.background='transparent'"><i class="bi bi-heart"></i> My Wishlist</a>
              <a href="<?= APP_URL ?>/account.php?tab=expense" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13.5px;color:#0f172a;border-radius:4px;" onmouseover="this.style.background='#fff7ed'" onmouseout="this.style.background='transparent'"><i class="bi bi-wallet2"></i> Expense Tracker</a>
              <?php if (isAdmin()): ?>
                <a href="<?= APP_URL ?>/admin/index.php" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13.5px;color:var(--primary-orange);font-weight:700;border-radius:4px;" onmouseover="this.style.background='#fff7ed'" onmouseout="this.style.background='transparent'"><i class="bi bi-shield-lock"></i> Admin Portal</a>
              <?php endif; ?>
              <hr style="border:none;border-top:1px solid #f1f5f9;margin:6px 0;">
              <form action="<?= APP_URL ?>/logout.php" method="POST" style="margin:0;">
                <?= csrfField() ?>
                <button type="submit" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13.5px;color:#ef4444;width:100%;text-align:left;border-radius:4px;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                  <i class="bi bi-box-arrow-right"></i> <?= e(__('nav_logout')) ?>
                </button>
              </form>
            </div>
          <?php else: ?>
            <a href="<?= APP_URL ?>/login.php" class="header-action-item">
              <i class="bi bi-person" style="font-size:22px;"></i>
              <div class="header-action-text">
                <span class="subtext">Sign In</span>
                <span class="maintext"><?= e(__('nav_account')) ?></span>
              </div>
            </a>
          <?php endif; ?>
        </div>

        <!-- Wishlist -->
        <a href="<?= APP_URL ?>/wishlist.php" class="header-action-item" title="<?= e(__('nav_wishlist')) ?>">
          <i class="bi bi-heart" style="font-size:22px;"></i>
          <span class="badge-count" id="header-wishlist-count" style="<?= $wishlistCount > 0 ? '' : 'display:none;' ?>"><?= $wishlistCount ?></span>
          <div class="header-action-text">
            <span class="subtext"><?= e(__('nav_wishlist')) ?></span>
            <span class="maintext">Saved</span>
          </div>
        </a>

        <!-- Cart -->
        <a href="<?= APP_URL ?>/cart.php" class="header-action-item" title="<?= e(__('nav_my_cart')) ?>">
          <i class="bi bi-cart3" style="font-size:24px;color:var(--primary-orange);"></i>
          <span class="badge-count" id="header-cart-count" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
          <div class="header-action-text">
            <span class="subtext"><?= e(__('nav_my_cart')) ?></span>
            <span class="maintext" style="color:var(--primary-orange);">View Cart</span>
          </div>
        </a>
      </div>
    </div>
  </div>

  <!-- Secondary Categories & Smart Navigation Bar -->
  <nav class="nav-categories-bar">
    <div class="container">
      <ul class="nav-categories-list">
        <li class="nav-cat-item" style="background:#ffedd5;border-radius:6px;">
          <a href="<?= APP_URL ?>/portal.php" style="color:#ea580c;font-weight:700;">
            <i class="bi bi-rocket-takeoff-fill"></i>
            <span>All-in-One Hub</span>
          </a>
        </li>
        <li class="nav-cat-item">
          <a href="<?= APP_URL ?>/products.php">
            <i class="bi bi-grid-fill"></i>
            <span><?= e(__('nav_categories')) ?></span>
          </a>
        </li>
        <li class="nav-cat-item highlight">
          <a href="<?= APP_URL ?>/budget.php">
            <i class="bi bi-lightning-charge-fill"></i>
            <span><?= e(__('nav_budget_mode')) ?></span>
          </a>
        </li>
        <li class="nav-cat-item">
          <a href="<?= APP_URL ?>/compare.php">
            <i class="bi bi-arrow-left-right"></i>
            <span><?= e(__('nav_compare')) ?></span>
          </a>
        </li>
        <li class="nav-cat-item">
          <a href="<?= APP_URL ?>/products.php?deal=1">
            <i class="bi bi-fire" style="color:#ef4444;"></i>
            <span><?= e(__('nav_deals')) ?></span>
          </a>
        </li>

        <?php foreach ($headerCategories as $cat): ?>
          <li class="nav-cat-item">
            <a href="<?= APP_URL ?>/products.php?category=<?= e($cat['slug']) ?>">
              <?= e($cat['name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </nav>
</header>

<!-- Image Search Demonstration Modal -->
<div id="image-search-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99999;align-items:center;justify-content:center;padding:16px;">
  <div style="background:#ffffff;border-radius:12px;max-width:480px;width:100%;padding:24px;position:relative;box-shadow:0 12px 32px rgba(0,0,0,0.2);">
    <button type="button" class="modal-close" style="position:absolute;top:16px;right:16px;font-size:20px;color:#64748b;cursor:pointer;">&times;</button>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
      <i class="bi bi-camera-fill" style="font-size:24px;color:var(--primary-orange);"></i>
      <h3 style="margin:0;font-size:18px;color:#0f233a;">Image-Based Product Search</h3>
    </div>
    <p style="font-size:13px;color:#64748b;line-height:1.5;margin-bottom:16px;">
      <strong>Note (BCA Demonstration):</strong> Drag & drop a product photo or select a sample image below to find matching items across our catalog.
    </p>
    <div style="border:2px dashed #cbd5e1;border-radius:8px;padding:24px;text-align:center;background:#f8fafc;margin-bottom:16px;cursor:pointer;">
      <i class="bi bi-cloud-arrow-up" style="font-size:36px;color:#94a3b8;"></i>
      <div style="font-size:14px;font-weight:600;margin-top:8px;">Drop photo here or browse</div>
      <div style="font-size:12px;color:#94a3b8;">Supports JPG, PNG, WEBP (Max 5MB)</div>
    </div>
    <div style="font-size:12px;font-weight:700;color:#0f233a;margin-bottom:8px;">Try sample image categories:</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/products.php?q=Headphones" class="btn-secondary" style="font-size:12px;padding:6px 12px;">🎧 Headphones</a>
      <a href="<?= APP_URL ?>/products.php?q=Kurta" class="btn-secondary" style="font-size:12px;padding:6px 12px;">👔 Kurta</a>
      <a href="<?= APP_URL ?>/products.php?q=Cooker" class="btn-secondary" style="font-size:12px;padding:6px 12px;">🍳 Cooker</a>
      <a href="<?= APP_URL ?>/products.php?q=Smartwatch" class="btn-secondary" style="font-size:12px;padding:6px 12px;">⌚ Smartwatch</a>
    </div>
  </div>
</div>
<script>
// Toggle language & account dropdowns cleanly
window.addEventListener('click', function(e) {
  const langDrop = document.getElementById('lang-dropdown');
  if (langDrop && !e.target.closest('.lang-selector-btn')) {
    langDrop.style.display = 'none';
  }
  const accDrop = document.getElementById('account-dropdown');
  if (accDrop && !e.target.closest('#user-menu-container')) {
    accDrop.style.display = 'none';
  }
});
</script>
