<?php
/**
 * ShopSmart India - User Account Portal & Expense Tracker
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Access Control: Require Login
Auth::requireAuth();

$db = Database::getConnection();
$userId = currentUserId();
$user = Auth::getCurrentUser();
$tab = $_GET['tab'] ?? 'profile';

$statusMsg = '';
$errorMsg = '';

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf()) {
        $errorMsg = 'Security validation failed.';
    } elseif ($_POST['action'] === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (strlen($name) < 2) {
            $errorMsg = 'Please provide a valid full name.';
        } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
            $errorMsg = 'Please provide a valid 10-digit Indian mobile number.';
        } else {
            $upd = $db->prepare("UPDATE users SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$name, $phone, $userId]);
            $_SESSION['user_name'] = $name;
            $statusMsg = 'Profile updated successfully!';
            $user = Auth::getCurrentUser();
        }
    } elseif ($_POST['action'] === 'change_password') {
        $currPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $chk = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $chk->execute([$userId]);
        $hash = $chk->fetchColumn();

        if (!password_verify($currPass, $hash)) {
            $errorMsg = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $errorMsg = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $errorMsg = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $updPass = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updPass->execute([$newHash, $userId]);
            $statusMsg = 'Password changed successfully!';
        }
    } elseif ($_POST['action'] === 'update_budget') {
        $newBudget = (float)($_POST['monthly_budget'] ?? 15000);
        $prefUpd = $db->prepare("
            INSERT INTO user_preferences (user_id, monthly_budget, updated_at) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE monthly_budget = ?, updated_at = NOW()
        ");
        $prefUpd->execute([$userId, $newBudget, $newBudget]);
        $statusMsg = 'Monthly budget target updated!';
    } elseif ($_POST['action'] === 'add_address') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $flat = trim($_POST['flat_house'] ?? '');
        $street = trim($_POST['street_area'] ?? '');
        $landmark = trim($_POST['landmark'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');

        if ($fullName && $phone && $flat && $street && $city && $state && $pincode) {
            $insA = $db->prepare("
                INSERT INTO addresses (user_id, full_name, phone, flat_house, street_area, landmark, city, state, pincode, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $insA->execute([$userId, $fullName, $phone, $flat, $street, $landmark, $city, $state, $pincode]);
            $statusMsg = 'Address added successfully!';
        } else {
            $errorMsg = 'Please fill all required address fields.';
        }
    }
}

// Fetch user data for various tabs
// 1. Orders
$userOrders = [];
$completedSpend = 0;
$refundedAmount = 0;

if ($db) {
    $ordStmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $ordStmt->execute([$userId]);
    $userOrders = $ordStmt->fetchAll();

    foreach ($userOrders as $uo) {
        if (in_array($uo['order_status'], ['Confirmed', 'Processing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered'])) {
            $completedSpend += (float)$uo['total_amount'];
        } elseif (in_array($uo['order_status'], ['Cancelled', 'Returned', 'Refunded'])) {
            $refundedAmount += (float)$uo['total_amount'];
        }
    }

    // 2. Addresses
    $addresses = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $addresses->execute([$userId]);
    $userAddresses = $addresses->fetchAll();

    // 3. Preferences & Budget
    $prefStmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ? LIMIT 1");
    $prefStmt->execute([$userId]);
    $userPrefs = $prefStmt->fetch() ?: ['monthly_budget' => 15000.00];

    // 4. Price Alerts
    $alertStmt = $db->prepare("
        SELECT pa.*, p.name AS product_name, p.price AS current_price, p.slug
        FROM price_alerts pa
        JOIN products p ON pa.product_id = p.id
        WHERE pa.user_id = ?
    ");
    $alertStmt->execute([$userId]);
    $userAlerts = $alertStmt->fetchAll();
}

$monthlyBudget = (float)$userPrefs['monthly_budget'];
$budgetPercentage = $monthlyBudget > 0 ? min(100, round(($completedSpend / $monthlyBudget) * 100)) : 0;
?>

<div class="container" style="padding: 36px 16px;">
  <div style="display:grid;grid-template-columns:260px 1fr;gap:32px;align-items:flex-start;">
    
    <!-- Sidebar Navigation -->
    <aside style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;box-shadow:var(--shadow-sm);">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;">
        <div style="width:48px;height:48px;border-radius:50%;background:var(--primary-orange);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">
          <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div>
          <div style="font-weight:700;color:var(--primary-navy);font-size:15px;"><?= e($user['name'] ?? '') ?></div>
          <div style="font-size:12px;color:#64748b;"><?= e($user['email'] ?? '') ?></div>
        </div>
      </div>

      <nav style="display:flex;flex-direction:column;gap:6px;font-size:13.5px;">
        <a href="?tab=profile" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-weight:600;<?= $tab === 'profile' ? 'background:var(--primary-orange-light);color:var(--primary-orange);' : 'color:#475569;' ?>">
          <i class="bi bi-person"></i> Profile Info
        </a>
        <a href="?tab=orders" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-weight:600;<?= $tab === 'orders' ? 'background:var(--primary-orange-light);color:var(--primary-orange);' : 'color:#475569;' ?>">
          <i class="bi bi-box-seam"></i> <?= e(__('nav_my_orders')) ?>
        </a>
        <a href="?tab=expense" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-weight:600;<?= $tab === 'expense' ? 'background:var(--primary-orange-light);color:var(--primary-orange);' : 'color:#475569;' ?>">
          <i class="bi bi-wallet2"></i> <?= e(__('expense_tracker')) ?>
        </a>
        <a href="?tab=addresses" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-weight:600;<?= $tab === 'addresses' ? 'background:var(--primary-orange-light);color:var(--primary-orange);' : 'color:#475569;' ?>">
          <i class="bi bi-geo-alt"></i> <?= e(__('saved_addresses')) ?>
        </a>
        <a href="?tab=alerts" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-weight:600;<?= $tab === 'alerts' ? 'background:var(--primary-orange-light);color:var(--primary-orange);' : 'color:#475569;' ?>">
          <i class="bi bi-bell"></i> <?= e(__('price_alert_title')) ?>
        </a>
        <a href="?tab=password" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-weight:600;<?= $tab === 'password' ? 'background:var(--primary-orange-light);color:var(--primary-orange);' : 'color:#475569;' ?>">
          <i class="bi bi-key"></i> <?= e(__('change_password')) ?>
        </a>
        
        <hr style="border:none;border-top:1px solid #f1f5f9;margin:8px 0;">

        <form action="<?= APP_URL ?>/logout.php" method="POST">
          <?= csrfField() ?>
          <button type="submit" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-weight:600;color:#ef4444;width:100%;text-align:left;">
            <i class="bi bi-box-arrow-right"></i> <?= e(__('nav_logout')) ?>
          </button>
        </form>
      </nav>
    </aside>

    <!-- Main Tab Content Area -->
    <main style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:var(--shadow-sm);">
      
      <?php if ($statusMsg): ?>
        <div style="background:#ecfdf5;border-left:4px solid #10b981;color:#065f46;padding:12px 16px;border-radius:6px;margin-bottom:24px;font-weight:600;">
          ✓ <?= e($statusMsg) ?>
        </div>
      <?php endif; ?>

      <?php if ($errorMsg): ?>
        <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:24px;font-weight:600;">
          ✕ <?= e($errorMsg) ?>
        </div>
      <?php endif; ?>

      <!-- 1. Profile Tab -->
      <?php if ($tab === 'profile'): ?>
        <h2 style="font-size:20px;color:var(--primary-navy);margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">
          <?= e(__('personal_info')) ?>
        </h2>
        <form action="?tab=profile" method="POST" style="max-width:480px;">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="update_profile">
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label"><?= e(__('full_name')) ?></label>
            <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
          </div>
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label"><?= e(__('email_address')) ?></label>
            <input type="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" disabled style="background:#f8fafc;cursor:not-allowed;">
            <span style="font-size:11.5px;color:#94a3b8;">Email address cannot be changed directly for security.</span>
          </div>
          <div class="form-group" style="margin-bottom:20px;">
            <label class="form-label"><?= e(__('mobile_number')) ?></label>
            <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" required maxlength="10">
          </div>
          <button type="submit" class="btn-primary"><?= e(__('save_changes')) ?></button>
        </form>

      <!-- 2. Orders Tab -->
      <?php elseif ($tab === 'orders'): ?>
        <h2 style="font-size:20px;color:var(--primary-navy);margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">
          <?= e(__('nav_my_orders')) ?> (<?= count($userOrders) ?> total)
        </h2>
        <?php if (!empty($userOrders)): ?>
          <div style="display:flex;flex-direction:column;gap:18px;">
            <?php foreach ($userOrders as $ord): ?>
              <div style="border:1px solid #e2e8f0;border-radius:12px;padding:20px;background:#f8fafc;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                  <div>
                    <div style="font-weight:800;color:var(--primary-navy);font-size:15px;"><?= e($ord['order_number']) ?></div>
                    <div style="font-size:12px;color:#64748b;">Placed on <?= formatDateIndian($ord['created_at']) ?></div>
                  </div>
                  <div>
                    <span style="background:<?= in_array($ord['order_status'], ['Delivered']) ? 'var(--accent-green)' : (in_array($ord['order_status'], ['Cancelled', 'Returned']) ? 'var(--accent-red)' : 'var(--primary-orange)') ?>;color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                      <?= e($ord['order_status']) ?>
                    </span>
                  </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e2e8f0;padding-top:12px;margin-top:8px;">
                  <div>
                    <span style="font-size:12px;color:#64748b;">Total Amount:</span>
                    <strong style="font-size:16px;color:var(--primary-navy);margin-left:6px;"><?= formatPriceINR($ord['total_amount']) ?></strong>
                  </div>
                  <div style="display:flex;gap:10px;">
                    <a href="<?= APP_URL ?>/track_order.php?order_number=<?= urlencode($ord['order_number']) ?>" class="btn-primary" style="padding:6px 14px;font-size:12.5px;">
                      <i class="bi bi-truck"></i> Track Package
                    </a>
                    <a href="<?= APP_URL ?>/order_success.php?id=<?= $ord['id'] ?>" class="btn-secondary" style="padding:6px 14px;font-size:12.5px;">
                      View Details
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="color:#64748b;padding:20px 0;">You have not placed any orders yet.</p>
          <a href="<?= APP_URL ?>/products.php" class="btn-primary">Start Shopping</a>
        <?php endif; ?>

      <!-- 3. Expense Tracker Tab -->
      <?php elseif ($tab === 'expense'): ?>
        <h2 style="font-size:20px;color:var(--primary-navy);margin-bottom:8px;">
          <i class="bi bi-wallet2" style="color:var(--primary-orange);"></i> <?= e(__('expense_tracker')) ?>
        </h2>
        <p style="color:#64748b;font-size:13.5px;margin-bottom:24px;">
          Authoritative financial tracker calculated directly from your delivered and active orders. Cancelled and refunded orders are separated automatically.
        </p>

        <!-- KPI Metrics -->
        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin-bottom:28px;">
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px;">
            <div style="font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;">Confirmed Spend</div>
            <div style="font-size:24px;font-weight:800;color:var(--primary-navy);margin-top:4px;">
              <?= formatPriceINR($completedSpend) ?>
            </div>
          </div>
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px;">
            <div style="font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;">Monthly Budget Target</div>
            <div style="font-size:24px;font-weight:800;color:var(--primary-orange);margin-top:4px;">
              <?= formatPriceINR($monthlyBudget) ?>
            </div>
          </div>
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px;">
            <div style="font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;">Refunded / Cancelled</div>
            <div style="font-size:24px;font-weight:800;color:var(--accent-red);margin-top:4px;">
              <?= formatPriceINR($refundedAmount) ?>
            </div>
          </div>
        </div>

        <!-- Budget Progress Bar -->
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:28px;">
          <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;margin-bottom:8px;">
            <span>Budget Utilization: <?= $budgetPercentage ?>%</span>
            <span style="color:<?= $completedSpend > $monthlyBudget ? '#ef4444' : '#10b981' ?>;">
              <?= $completedSpend > $monthlyBudget ? 'Budget Exceeded!' : formatPriceINR($monthlyBudget - $completedSpend) . ' Remaining' ?>
            </span>
          </div>
          <div style="height:12px;background:#f1f5f9;border-radius:6px;overflow:hidden;">
            <div style="height:100%;width:<?= $budgetPercentage ?>%;background:<?= $completedSpend > $monthlyBudget ? '#ef4444' : 'var(--primary-orange)' ?>;"></div>
          </div>
        </div>

        <!-- Update Target Form -->
        <form action="?tab=expense" method="POST" style="max-width:400px;">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="update_budget">
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label" style="font-weight:700;">Set Monthly Budget (₹)</label>
            <input type="number" name="monthly_budget" class="form-control" value="<?= (int)$monthlyBudget ?>" min="500" step="500">
          </div>
          <button type="submit" class="btn-primary" style="padding:8px 18px;font-size:13.5px;">Update Target</button>
        </form>

      <!-- 4. Addresses Tab -->
      <?php elseif ($tab === 'addresses'): ?>
        <h2 style="font-size:20px;color:var(--primary-navy);margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">
          <?= e(__('saved_addresses')) ?>
        </h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px;margin-bottom:28px;">
          <?php foreach ($userAddresses as $ad): ?>
            <div style="border:1.5px solid #e2e8f0;border-radius:12px;padding:18px;background:#f8fafc;position:relative;">
              <?php if ($ad['is_default']): ?>
                <span style="background:var(--primary-navy);color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;position:absolute;top:12px;right:12px;">DEFAULT</span>
              <?php endif; ?>
              <div style="font-weight:700;font-size:15px;color:var(--primary-navy);"><?= e($ad['full_name']) ?></div>
              <div style="font-size:13px;color:#475569;margin:6px 0 10px;line-height:1.4;">
                <?= e($ad['flat_house']) ?>, <?= e($ad['street_area']) ?><br>
                <?= e($ad['city']) ?>, <?= e($ad['state']) ?> — <strong><?= e($ad['pincode']) ?></strong><br>
                Phone: +91 <?= e($ad['phone']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <h3 style="font-size:16px;color:var(--primary-navy);margin-bottom:14px;">Add New Delivery Address</h3>
        <form action="?tab=addresses" method="POST" style="max-width:540px;">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="add_address">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
            <input type="text" name="phone" class="form-control" placeholder="10-digit mobile" maxlength="10" required>
          </div>
          <div class="form-group" style="margin-bottom:12px;">
            <input type="text" name="flat_house" class="form-control" placeholder="House / Flat No." required>
          </div>
          <div class="form-group" style="margin-bottom:12px;">
            <input type="text" name="street_area" class="form-control" placeholder="Street / Area" required>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px;">
            <input type="text" name="city" class="form-control" placeholder="City" required>
            <input type="text" name="state" class="form-control" placeholder="State" value="Maharashtra" required>
            <input type="text" name="pincode" class="form-control" placeholder="PIN Code" maxlength="6" required>
          </div>
          <button type="submit" class="btn-primary" style="padding:10px 20px;">Save Address</button>
        </form>

      <!-- 5. Price Alerts Tab -->
      <?php elseif ($tab === 'alerts'): ?>
        <h2 style="font-size:20px;color:var(--primary-navy);margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">
          <?= e(__('price_alert_title')) ?>
        </h2>
        <?php if (!empty($userAlerts)): ?>
          <div style="display:flex;flex-direction:column;gap:14px;">
            <?php foreach ($userAlerts as $alt): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;border:1px solid #e2e8f0;border-radius:10px;padding:14px;background:#f8fafc;">
                <div>
                  <a href="<?= APP_URL ?>/product.php?slug=<?= urlencode($alt['slug']) ?>" style="font-weight:700;color:var(--primary-navy);font-size:14px;">
                    <?= e($alt['product_name']) ?>
                  </a>
                  <div style="font-size:12px;color:#64748b;margin-top:2px;">
                    Current Price: <strong><?= formatPriceINR($alt['current_price']) ?></strong> &bull; Target Alert: <span style="color:var(--accent-green);font-weight:700;"><?= formatPriceINR($alt['target_price']) ?></span>
                  </div>
                </div>
                <div>
                  <span style="font-size:12px;padding:3px 8px;border-radius:4px;font-weight:600;background:<?= $alt['current_price'] <= $alt['target_price'] ? '#ecfdf5;color:#065f46;' : '#f1f5f9;color:#64748b;' ?>">
                    <?= $alt['current_price'] <= $alt['target_price'] ? '🎯 Target Reached!' : 'Tracking Price' ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="color:#64748b;">No price drop alerts set. Visit any product page to set a target price alert!</p>
        <?php endif; ?>

      <!-- 6. Password Tab -->
      <?php elseif ($tab === 'password'): ?>
        <h2 style="font-size:20px;color:var(--primary-navy);margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">
          <?= e(__('change_password')) ?>
        </h2>
        <form action="?tab=password" method="POST" style="max-width:440px;">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="change_password">
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label"><?= e(__('current_password')) ?></label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label"><?= e(__('new_password')) ?></label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
          </div>
          <div class="form-group" style="margin-bottom:20px;">
            <label class="form-label"><?= e(__('confirm_password')) ?></label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6">
          </div>
          <button type="submit" class="btn-primary"><?= e(__('change_password')) ?></button>
        </form>

      <?php endif; ?>
    </main>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
