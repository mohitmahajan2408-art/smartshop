<?php
/**
 * ShopSmart India - Interactive Order Tracking & Timeline
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$orderNumber = trim($_GET['order_number'] ?? '');
$db = Database::getConnection();
$userId = currentUserId();

$order = null;
$history = [];
$items = [];
$errorMsg = '';
$actionMsg = '';

// Handle Order Cancellation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    if (!verifyCsrf()) {
        $errorMsg = 'Security validation failed.';
    } elseif (!$userId) {
        $errorMsg = 'Please sign in to cancel your order.';
    } else {
        $oId = (int)$_POST['order_id'];
        $cancelReason = trim($_POST['cancel_reason'] ?? 'Customer requested cancellation');

        // Verify order status allows cancellation
        $chk = $db->prepare("SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?");
        $chk->execute([$oId, $userId]);
        $ordRow = $chk->fetch();

        if (!$ordRow) {
            $errorMsg = 'Order not found or permission denied.';
        } elseif (in_array($ordRow['order_status'], ['Shipped', 'Out for Delivery', 'Delivered', 'Cancelled', 'Returned'])) {
            $errorMsg = "Order cannot be cancelled in '{$ordRow['order_status']}' status.";
        } else {
            // Restore inventory
            $itemRows = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $itemRows->execute([$oId]);
            $oItems = $itemRows->fetchAll();

            $db->beginTransaction();
            try {
                $updOrder = $db->prepare("UPDATE orders SET order_status = 'Cancelled', updated_at = NOW() WHERE id = ?");
                $updOrder->execute([$oId]);

                $invUpd = $db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                $invLog = $db->prepare("INSERT INTO inventory_movements (product_id, change_qty, reason, reference_id, created_at) VALUES (?, ?, 'return_restock', ?, NOW())");

                foreach ($oItems as $it) {
                    $invUpd->execute([$it['quantity'], $it['product_id']]);
                    $invLog->execute([$it['product_id'], $it['quantity'], "CANCELLED_ORDER_" . $oId]);
                }

                $hist = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, created_at) VALUES (?, 'Cancelled', ?, NOW())");
                $hist->execute([$oId, "Customer cancelled: " . $cancelReason]);

                $db->commit();
                $actionMsg = 'Your order has been cancelled successfully. Any pre-paid demo balance is refunded.';
            } catch (Exception $e) {
                $db->rollBack();
                $errorMsg = 'Failed to cancel order: ' . $e->getMessage();
            }
        }
    }
}

// Handle Return Request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return_request') {
    if (!verifyCsrf()) {
        $errorMsg = 'Security validation failed.';
    } elseif (!$userId) {
        $errorMsg = 'Please sign in to request a return.';
    } else {
        $oId = (int)$_POST['order_id'];
        $returnReason = trim($_POST['return_reason'] ?? 'Defective Product');
        $returnDetails = trim($_POST['return_details'] ?? '');

        // Verify order is Delivered
        $chk = $db->prepare("SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?");
        $chk->execute([$oId, $userId]);
        $ordRow = $chk->fetch();

        if (!$ordRow || $ordRow['order_status'] !== 'Delivered') {
            $errorMsg = 'Return can only be requested for Delivered orders within 7 days.';
        } else {
            $retStmt = $db->prepare("
                INSERT INTO return_requests (order_id, user_id, return_reason, details, status, created_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $retStmt->execute([$oId, $userId, $returnReason, $returnDetails]);

            $updOrd = $db->prepare("UPDATE orders SET order_status = 'Return Requested', updated_at = NOW() WHERE id = ?");
            $updOrd->execute([$oId]);

            $hist = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, created_at) VALUES (?, 'Return Requested', ?, NOW())");
            $hist->execute([$oId, "Return requested. Reason: " . $returnReason]);

            $actionMsg = 'Return request registered! A ShopSmart representative will verify and arrange doorstep pickup.';
        }
    }
}

// Load order by order_number
if ($orderNumber !== '' && $db) {
    if (isAdmin()) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
    } else {
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$orderNumber, $userId]);
    }
    $order = $stmt->fetch();

    if ($order) {
        // Status History Timeline
        $hStmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC");
        $hStmt->execute([$order['id']]);
        $history = $hStmt->fetchAll();

        // Items
        $iStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $iStmt->execute([$order['id']]);
        $items = $iStmt->fetchAll();
    } else {
        $errorMsg = 'No order found matching "' . e($orderNumber) . '" under your account.';
    }
}

// Standard status progression list
$pipelineStages = ['Confirmed', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered'];
?>

<div class="container" style="max-width:920px;padding:36px 16px;">
  <div style="text-align:center;margin-bottom:28px;">
    <h1 style="font-size:26px;color:var(--primary-navy);margin-bottom:6px;">
      <i class="bi bi-truck" style="color:var(--primary-orange);"></i> <?= e(__('track_your_order')) ?>
    </h1>
    <p style="color:#64748b;font-size:14px;">
      Track real-time shipment updates, expected delivery and delivery milestones across India.
    </p>
  </div>

  <!-- Search by Order Number Form -->
  <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:var(--shadow-sm);margin-bottom:28px;">
    <form action="<?= APP_URL ?>/track_order.php" method="GET" style="display:flex;gap:12px;">
      <input type="text" name="order_number" value="<?= e($orderNumber) ?>" 
             placeholder="Enter Order Number (e.g. SS-20261005-A1B2C3)" 
             class="form-control" style="flex:1;" required>
      <button type="submit" class="btn-primary" style="white-space:nowrap;">
        <i class="bi bi-search"></i> Track Order
      </button>
    </form>
  </div>

  <?php if ($actionMsg): ?>
    <div style="background:#ecfdf5;border-left:4px solid #10b981;color:#065f46;padding:14px;border-radius:6px;margin-bottom:24px;font-weight:600;">
      ✓ <?= e($actionMsg) ?>
    </div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
    <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:14px;border-radius:6px;margin-bottom:24px;font-weight:600;">
      ✕ <?= e($errorMsg) ?>
    </div>
  <?php endif; ?>

  <?php if ($order): ?>
    <?php 
      $address = json_decode($order['delivery_address_json'], true) ?? [];
      $currentStatus = $order['order_status'];
    ?>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:var(--shadow-md);">
      
      <!-- Order Header Card -->
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;border-bottom:1px solid #f1f5f9;padding-bottom:20px;margin-bottom:24px;">
        <div>
          <span style="font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;">Order ID</span>
          <h2 style="font-size:22px;color:var(--primary-navy);margin:2px 0;"><?= e($order['order_number']) ?></h2>
          <div style="font-size:13px;color:#64748b;">Placed on <?= formatDateIndian($order['created_at']) ?></div>
        </div>

        <div style="text-align:right;">
          <span style="background:<?= in_array($currentStatus, ['Delivered']) ? 'var(--accent-green)' : (in_array($currentStatus, ['Cancelled', 'Returned']) ? 'var(--accent-red)' : 'var(--primary-orange)') ?>;color:#fff;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:700;">
            <?= e($currentStatus) ?>
          </span>
          <div style="font-size:13px;color:#64748b;margin-top:6px;">
            Courier: <strong><?= e($order['courier_name']) ?></strong> (Awb: <?= e($order['tracking_number'] ?: 'Processing') ?>)
          </div>
        </div>
      </div>

      <!-- Shipment Progress Bar (if not cancelled) -->
      <?php if (!in_array($currentStatus, ['Cancelled', 'Returned'])): ?>
        <div style="margin:32px 0;">
          <div style="display:flex;justify-content:space-between;position:relative;margin-bottom:12px;">
            <?php
              $currentIdx = array_search($currentStatus, $pipelineStages);
              if ($currentIdx === false) $currentIdx = 0;
            ?>
            <?php foreach ($pipelineStages as $idx => $stage): ?>
              <?php $isDone = $idx <= $currentIdx; ?>
              <div style="text-align:center;flex:1;position:relative;z-index:2;">
                <div style="width:36px;height:36px;border-radius:50%;margin:auto;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;<?= $isDone ? 'background:var(--accent-green);color:#fff;' : 'background:#e2e8f0;color:#94a3b8;' ?>">
                  <?= $isDone ? '✓' : ($idx + 1) ?>
                </div>
                <div style="font-size:12px;font-weight:600;margin-top:8px;color:<?= $isDone ? 'var(--primary-navy)' : '#94a3b8' ?>;">
                  <?= e($stage) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Timeline Audit Feed -->
      <div style="margin-top:28px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
        <h3 style="font-size:15px;font-weight:700;color:var(--primary-navy);margin-bottom:14px;">Shipment Activity Logs</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <?php foreach ($history as $h): ?>
            <div style="display:flex;gap:14px;font-size:13px;">
              <span style="color:var(--primary-orange);font-weight:700;min-width:80px;"><?= e($h['status']) ?></span>
              <span style="color:#0f172a;flex:1;"><?= e($h['notes'] ?: 'Status updated to ' . $h['status']) ?></span>
              <span style="color:#94a3b8;"><?= formatDateIndian($h['created_at']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Delivery Address & Items -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px;border-top:1px solid #f1f5f9;padding-top:20px;">
        <div>
          <h4 style="font-size:14px;color:var(--primary-navy);font-weight:700;margin-bottom:6px;">Delivery Address:</h4>
          <div style="font-size:13px;line-height:1.5;color:var(--text-secondary);">
            <strong><?= e($address['full_name'] ?? '') ?></strong><br>
            <?= e($address['flat_house'] ?? '') ?>, <?= e($address['street_area'] ?? '') ?><br>
            <?= e($address['city'] ?? '') ?>, <?= e($address['state'] ?? '') ?> — <?= e($address['pincode'] ?? '') ?><br>
            Mobile: +91 <?= e($address['phone'] ?? '') ?>
          </div>
        </div>

        <div>
          <h4 style="font-size:14px;color:var(--primary-navy);font-weight:700;margin-bottom:6px;">Items in Package:</h4>
          <div style="display:flex;flex-direction:column;gap:6px;font-size:13px;">
            <?php foreach ($items as $it): ?>
              <div style="display:flex;justify-content:space-between;">
                <span><?= (int)$it['quantity'] ?>x <?= e($it['product_name']) ?></span>
                <span style="font-weight:700;"><?= formatPriceINR($it['total_price']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Cancellation or Return Request Controls -->
      <div style="margin-top:28px;border-top:1px solid #e2e8f0;padding-top:20px;display:flex;gap:12px;justify-content:flex-end;">
        <?php if (in_array($currentStatus, ['Pending', 'Confirmed', 'Processing'])): ?>
          <button type="button" class="btn-secondary" style="color:#ef4444;border:1px solid #ef4444;" 
                  onclick="document.getElementById('cancel-modal').style.display='flex'">
            <i class="bi bi-x-circle"></i> Cancel Order
          </button>
        <?php elseif ($currentStatus === 'Delivered'): ?>
          <button type="button" class="btn-secondary" style="color:var(--primary-orange);border:1px solid var(--primary-orange);"
                  onclick="document.getElementById('return-modal').style.display='flex'">
            <i class="bi bi-arrow-counterclockwise"></i> Request 7-Day Return
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Cancel Modal -->
    <div id="cancel-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center;padding:16px;">
      <div style="background:#fff;border-radius:12px;max-width:440px;width:100%;padding:24px;">
        <h3 style="color:#0f233a;margin-bottom:12px;">Cancel Order Confirmation</h3>
        <p style="font-size:13px;color:#64748b;margin-bottom:16px;">Are you sure you want to cancel this order? Stock will be restored and any payment refunded.</p>
        <form action="<?= APP_URL ?>/track_order.php?order_number=<?= urlencode($order['order_number']) ?>" method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="cancel_order">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label">Reason for Cancellation</label>
            <select name="cancel_reason" class="form-control">
              <option value="Changed mind">Changed mind</option>
              <option value="Ordered by mistake">Ordered by mistake</option>
              <option value="Found better price elsewhere">Found better price elsewhere</option>
              <option value="Delivery address incorrect">Delivery address incorrect</option>
            </select>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" class="btn-secondary" onclick="document.getElementById('cancel-modal').style.display='none'">Back</button>
            <button type="submit" class="btn-primary" style="background:#ef4444;">Confirm Cancellation</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Return Modal -->
    <div id="return-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center;padding:16px;">
      <div style="background:#fff;border-radius:12px;max-width:460px;width:100%;padding:24px;">
        <h3 style="color:#0f233a;margin-bottom:12px;">Request 7-Day Return</h3>
        <form action="<?= APP_URL ?>/track_order.php?order_number=<?= urlencode($order['order_number']) ?>" method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="return_request">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <div class="form-group" style="margin-bottom:12px;">
            <label class="form-label">Reason for Return</label>
            <select name="return_reason" class="form-control">
              <option value="Defective Product">Defective or Damaged Product</option>
              <option value="Different from Description">Different from Description</option>
              <option value="Size/Fit Issue">Size or Fit Issue</option>
              <option value="Delayed Delivery">Delayed Delivery</option>
              <option value="Changed Mind">Changed Mind</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label">Additional Feedback (Optional)</label>
            <textarea name="return_details" class="form-control" rows="2" placeholder="Describe the issue..."></textarea>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" class="btn-secondary" onclick="document.getElementById('return-modal').style.display='none'">Back</button>
            <button type="submit" class="btn-primary">Submit Return Request</button>
          </div>
        </form>
      </div>
    </div>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
