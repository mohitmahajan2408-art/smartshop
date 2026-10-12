<?php
/**
 * ShopSmart India - Order Confirmation & Receipt
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$orderId = (int)($_GET['id'] ?? 0);
$userId = currentUserId();

$db = Database::getConnection();
$order = null;
$orderItems = [];

if ($db && $orderId > 0 && $userId) {
    // IDOR protection: Verify order belongs to this customer
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$orderId]);
        $orderItems = $itemStmt->fetchAll();
    }
}

if (!$order) {
    echo '<div class="container" style="padding:60px 16px;text-align:center;">';
    echo '<h2>Order Not Found</h2>';
    echo '<p style="color:#64748b;margin:12px 0 24px;">The specified order does not exist or you do not have permission to view it.</p>';
    echo '<a href="' . APP_URL . '/account.php?tab=orders" class="btn-primary">View My Orders</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$address = json_decode($order['delivery_address_json'], true) ?? [];
?>

<div class="container" style="max-width:860px;padding:40px 16px;">
  <!-- Success Banner -->
  <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:36px;box-shadow:var(--shadow-md);text-align:center;margin-bottom:28px;">
    <div style="width:68px;height:68px;background:#ecfdf5;color:var(--accent-green);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 16px;">
      <i class="bi bi-check-lg"></i>
    </div>
    <h1 style="font-size:26px;color:var(--primary-navy);margin-bottom:8px;"><?= e(__('order_confirmed')) ?></h1>
    <p style="color:#64748b;font-size:15px;max-width:540px;margin:auto;">
      Thank you for shopping with ShopSmart India! Your order has been placed successfully and is being packed.
    </p>

    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 20px;display:inline-flex;align-items:center;gap:16px;margin-top:20px;">
      <div>
        <span style="font-size:12px;color:#64748b;text-transform:uppercase;font-weight:700;">Order ID</span>
        <div style="font-size:18px;font-weight:800;color:var(--primary-navy);"><?= e($order['order_number']) ?></div>
      </div>
      <div style="border-left:1px solid #cbd5e1;height:32px;"></div>
      <div>
        <span style="font-size:12px;color:#64748b;text-transform:uppercase;font-weight:700;">Est. Delivery</span>
        <div style="font-size:15px;font-weight:700;color:var(--accent-green);"><?= formatDateIndian($order['estimated_delivery'], false) ?></div>
      </div>
    </div>
  </div>

  <!-- Order Items & Invoice Details -->
  <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;margin-bottom:28px;">
    <h3 style="font-size:18px;color:var(--primary-navy);margin-bottom:18px;border-bottom:1px solid #f1f5f9;padding-bottom:10px;">
      Order Items (<?= count($orderItems) ?> items)
    </h3>

    <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:24px;">
      <?php foreach ($orderItems as $item): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px;border-bottom:1px solid #f8fafc;padding-bottom:10px;">
          <div>
            <div style="font-weight:700;color:var(--primary-navy);"><?= e($item['product_name']) ?></div>
            <div style="font-size:12px;color:#64748b;">Quantity: <?= (int)$item['quantity'] ?> &times; <?= formatPriceINR($item['price']) ?></div>
          </div>
          <div style="font-weight:800;color:var(--primary-navy);">
            <?= formatPriceINR($item['total_price']) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Breakdown Grid -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;border-top:1px solid #e2e8f0;padding-top:20px;">
      <!-- Delivery Address -->
      <div>
        <h4 style="font-size:14px;font-weight:700;color:var(--primary-navy);margin-bottom:8px;">Shipping Address:</h4>
        <div style="font-size:13px;line-height:1.5;color:var(--text-secondary);">
          <strong><?= e($address['full_name'] ?? '') ?></strong><br>
          <?= e($address['flat_house'] ?? '') ?>, <?= e($address['street_area'] ?? '') ?><br>
          <?= e($address['city'] ?? '') ?>, <?= e($address['state'] ?? '') ?> — <strong><?= e($address['pincode'] ?? '') ?></strong><br>
          Mobile: +91 <?= e($address['phone'] ?? '') ?>
        </div>
      </div>

      <!-- Payment Summary -->
      <div style="text-align:right;">
        <h4 style="font-size:14px;font-weight:700;color:var(--primary-navy);margin-bottom:8px;">Payment Breakdown:</h4>
        <div style="display:flex;flex-direction:column;gap:6px;font-size:13px;">
          <div style="display:flex;justify-content:space-between;">
            <span style="color:#64748b;">Subtotal:</span>
            <span><?= formatPriceINR($order['subtotal']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:#64748b;">Delivery Fee:</span>
            <span><?= $order['delivery_fee'] == 0 ? 'FREE' : formatPriceINR($order['delivery_fee']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:#64748b;">Payment Mode:</span>
            <span style="text-transform:uppercase;font-weight:600;"><?= e($order['payment_method']) ?> (Demo)</span>
          </div>
          <hr style="border:none;border-top:1px solid #e2e8f0;margin:4px 0;">
          <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;color:var(--primary-orange);">
            <span>Total Paid:</span>
            <span><?= formatPriceINR($order['total_amount']) ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div style="display:flex;gap:14px;justify-content:center;">
    <a href="<?= APP_URL ?>/track_order.php?order_number=<?= urlencode($order['order_number']) ?>" class="btn-primary">
      <i class="bi bi-truck"></i> Track Order Status
    </a>
    <button type="button" onclick="window.print()" class="btn-secondary" style="border:1px solid #cbd5e1;">
      <i class="bi bi-printer"></i> Print Invoice
    </button>
    <a href="<?= APP_URL ?>/products.php" class="btn-secondary">
      <i class="bi bi-shop"></i> Continue Shopping
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
