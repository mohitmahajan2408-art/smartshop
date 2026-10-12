<?php
/**
 * ShopSmart India - Admin Order Detail & Status Management
 * BCA Semester V Field Project
 */

$adminTitle = "Order Details";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();
$orderId = (int)($_GET['id'] ?? 0);
$msg = '';
$error = '';

$order = null;
$items = [];
$history = [];

if ($db && $orderId > 0) {
    // Handle Status Update POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
        if (!verifyCsrf()) {
            $error = 'Security validation failed.';
        } else {
            $newStatus = trim($_POST['order_status'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            $validStatuses = ['Pending', 'Confirmed', 'Processing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled', 'Return Requested', 'Returned', 'Refunded'];
            if (!in_array($newStatus, $validStatuses, true)) {
                $error = 'Invalid status selected.';
            } else {
                // Fetch current order status
                $curr = $db->prepare("SELECT order_status, order_number FROM orders WHERE id = ?");
                $curr->execute([$orderId]);
                $currRow = $curr->fetch();

                if ($currRow) {
                    $oldStatus = $currRow['order_status'];

                    $db->beginTransaction();
                    try {
                        // If transitioning to Cancelled or Returned, restore inventory if not already restored
                        if (in_array($newStatus, ['Cancelled', 'Returned', 'Refunded']) && !in_array($oldStatus, ['Cancelled', 'Returned', 'Refunded'])) {
                            $itemRows = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                            $itemRows->execute([$orderId]);
                            $prodItems = $itemRows->fetchAll();

                            $invUpd = $db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                            $invLog = $db->prepare("INSERT INTO inventory_movements (product_id, change_qty, reason, reference_id, created_at) VALUES (?, ?, 'return_restock', ?, NOW())");

                            foreach ($prodItems as $pi) {
                                $invUpd->execute([$pi['quantity'], $pi['product_id']]);
                                $invLog->execute([$pi['product_id'], $pi['quantity'], $currRow['order_number']]);
                            }
                        }

                        // Update order record
                        $updOrd = $db->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
                        $updOrd->execute([$newStatus, $orderId]);

                        // Add history log
                        $hist = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $hist->execute([$orderId, $newStatus, $notes ?: "Status updated by admin to $newStatus", currentUserId()]);

                        $db->commit();
                        logAdminAudit(currentUserId(), 'ORDER_STATUS_UPDATE', "Changed order #{$currRow['order_number']} status from $oldStatus to $newStatus");
                        $msg = "Order status updated to '$newStatus' successfully!";
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error = 'Failed to update order status: ' . $e->getMessage();
                    }
                }
            }
        }
    }

    // Load Order
    $stmt = $db->prepare("
        SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? LIMIT 1
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if ($order) {
        $iStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $iStmt->execute([$orderId]);
        $items = $iStmt->fetchAll();

        $hStmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC");
        $hStmt->execute([$orderId]);
        $history = $hStmt->fetchAll();
    }
}

if (!$order) {
    echo '<div style="background:#fff;padding:40px;border-radius:12px;text-align:center;">Order not found. <a href="' . APP_URL . '/admin/orders.php">Back to orders</a></div>';
    require_once __DIR__ . '/admin_footer.php';
    exit;
}

$address = json_decode($order['delivery_address_json'], true) ?? [];
?>

<?php if ($msg): ?>
  <div style="background:#ecfdf5;border-left:4px solid #10b981;color:#065f46;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-weight:600;">
    ✓ <?= e($msg) ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-weight:600;">
    ✕ <?= e($error) ?>
  </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:28px;">
  <!-- Left Column: Order Items & Customer -->
  <div style="display:flex;flex-direction:column;gap:24px;">
    <!-- Order Card -->
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;">
      <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9;padding-bottom:14px;margin-bottom:18px;">
        <div>
          <span style="font-size:12px;color:#64748b;text-transform:uppercase;font-weight:700;">Order Details</span>
          <h2 style="font-size:22px;color:var(--primary-navy);margin:2px 0;"><?= e($order['order_number']) ?></h2>
          <div style="font-size:13px;color:#64748b;">Placed on <?= formatDateIndian($order['created_at']) ?></div>
        </div>
        <div>
          <span style="font-size:13px;font-weight:700;padding:4px 12px;border-radius:12px;color:#fff;background:<?= in_array($order['order_status'], ['Delivered']) ? 'var(--accent-green)' : (in_array($order['order_status'], ['Cancelled', 'Returned']) ? 'var(--accent-red)' : 'var(--primary-orange)') ?>;">
            <?= e($order['order_status']) ?>
          </span>
        </div>
      </div>

      <!-- Items List -->
      <table class="admin-table">
        <thead>
          <tr>
            <th>Product Name</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><strong><?= e($it['product_name']) ?></strong></td>
              <td><?= formatPriceINR($it['price']) ?></td>
              <td><?= (int)$it['quantity'] ?></td>
              <td style="font-weight:700;"><?= formatPriceINR($it['total_price']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Totals -->
      <div style="margin-top:16px;border-top:1px solid #f1f5f9;padding-top:14px;display:flex;flex-direction:column;gap:6px;align-items:flex-end;font-size:13.5px;">
        <div>Subtotal: <strong><?= formatPriceINR($order['subtotal']) ?></strong></div>
        <div>Delivery Fee: <strong><?= $order['delivery_fee'] == 0 ? 'FREE' : formatPriceINR($order['delivery_fee']) ?></strong></div>
        <div style="font-size:17px;font-weight:800;color:var(--primary-orange);margin-top:4px;">
          Grand Total: <?= formatPriceINR($order['total_amount']) ?>
        </div>
      </div>
    </div>

    <!-- Timeline History -->
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;">
      <h3 style="font-size:16px;color:var(--primary-navy);margin-bottom:14px;">Status Transition History</h3>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($history as $h): ?>
          <div style="display:flex;justify-content:space-between;border-bottom:1px solid #f8fafc;padding-bottom:8px;font-size:13px;">
            <div>
              <span style="font-weight:700;color:var(--primary-orange);"><?= e($h['status']) ?></span> — <?= e($h['notes']) ?>
            </div>
            <div style="color:#94a3b8;"><?= formatDateIndian($h['created_at']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Right Column: Customer Info & Status Updater -->
  <div style="display:flex;flex-direction:column;gap:24px;">
    <!-- Update Status Card -->
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;box-shadow:var(--shadow-sm);">
      <h3 style="font-size:16px;color:var(--primary-navy);margin-bottom:14px;">Update Status</h3>
      <form action="<?= APP_URL ?>/admin/order_detail.php?id=<?= $orderId ?>" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_status">

        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label">New Status</label>
          <select name="order_status" class="form-control">
            <?php
              $allSt = ['Pending', 'Confirmed', 'Processing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled', 'Return Requested', 'Returned', 'Refunded'];
              foreach ($allSt as $st):
            ?>
              <option value="<?= e($st) ?>" <?= $st === $order['order_status'] ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label">Admin Notes</label>
          <input type="text" name="notes" class="form-control" placeholder="e.g. Dispatched via Bluedart Awb 91029">
        </div>

        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:10px;">
          Apply Status Update
        </button>
      </form>
    </div>

    <!-- Customer & Delivery Address Card -->
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;">
      <h3 style="font-size:16px;color:var(--primary-navy);margin-bottom:12px;">Shipping Address</h3>
      <div style="font-size:13px;line-height:1.5;color:#334155;">
        <strong><?= e($address['full_name'] ?? $order['customer_name']) ?></strong><br>
        <?= e($address['flat_house'] ?? '') ?>, <?= e($address['street_area'] ?? '') ?><br>
        <?= e($address['city'] ?? '') ?>, <?= e($address['state'] ?? '') ?> — <strong><?= e($address['pincode'] ?? '') ?></strong><br>
        Mobile: +91 <?= e($address['phone'] ?? $order['customer_phone']) ?><br>
        Email: <?= e($order['customer_email']) ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
