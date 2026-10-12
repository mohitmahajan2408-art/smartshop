<?php
/**
 * ShopSmart India - Admin Order Management & Tracking
 * BCA Semester V Field Project
 */

$adminTitle = "Orders & Logistics";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$query = "
    SELECT o.*, u.name AS customer_name, u.email AS customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE 1=1
";
$params = [];

if ($statusFilter) {
    $query .= " AND o.order_status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $query .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statuses = ['Pending', 'Confirmed', 'Processing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled', 'Return Requested', 'Returned', 'Refunded'];
?>

<!-- Filter Bar -->
<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
  <form action="<?= APP_URL ?>/admin/orders.php" method="GET" style="display:flex;gap:10px;align-items:center;">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search Order # or Customer..." class="form-control" style="width:240px;padding:6px 12px;font-size:13px;">
    
    <select name="status" class="form-control" style="font-size:13px;padding:6px 12px;" onchange="this.form.submit()">
      <option value="">All Statuses (<?= count($orders) ?>)</option>
      <?php foreach ($statuses as $st): ?>
        <option value="<?= e($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e($st) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn-primary" style="padding:6px 14px;font-size:13px;">Search</button>
  </form>

  <a href="<?= APP_URL ?>/admin/export_csv.php?type=orders" class="btn-secondary" style="border:1px solid #cbd5e1;font-size:13px;padding:8px 16px;">
    <i class="bi bi-file-earmark-spreadsheet"></i> Export Orders to CSV
  </a>
</div>

<!-- Orders Table -->
<div class="table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Order Number</th>
        <th>Customer</th>
        <th>Date</th>
        <th>Total Amount</th>
        <th>Payment</th>
        <th>Current Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $ord): ?>
          <tr>
            <td>
              <a href="<?= APP_URL ?>/admin/order_detail.php?id=<?= $ord['id'] ?>" style="font-weight:700;color:var(--primary-orange);">
                <?= e($ord['order_number']) ?>
              </a>
            </td>
            <td>
              <div style="font-weight:600;"><?= e($ord['customer_name']) ?></div>
              <div style="font-size:11px;color:#64748b;"><?= e($ord['customer_email']) ?></div>
            </td>
            <td style="font-size:12.5px;color:#64748b;"><?= formatDateIndian($ord['created_at']) ?></td>
            <td style="font-weight:800;color:var(--primary-navy);"><?= formatPriceINR($ord['total_amount']) ?></td>
            <td>
              <span style="font-size:11px;font-weight:700;text-transform:uppercase;padding:2px 6px;border-radius:4px;background:#f1f5f9;">
                <?= e($ord['payment_method']) ?> (<?= e($ord['payment_status']) ?>)
              </span>
            </td>
            <td>
              <span style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:12px;color:#fff;background:<?= in_array($ord['order_status'], ['Delivered']) ? 'var(--accent-green)' : (in_array($ord['order_status'], ['Cancelled', 'Returned']) ? 'var(--accent-red)' : 'var(--primary-orange)') ?>;">
                <?= e($ord['order_status']) ?>
              </span>
            </td>
            <td>
              <a href="<?= APP_URL ?>/admin/order_detail.php?id=<?= $ord['id'] ?>" class="btn-secondary" style="padding:4px 10px;font-size:12px;border:1px solid #cbd5e1;">
                Manage &rarr;
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center;padding:32px;color:#64748b;">No orders found matching filter.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
