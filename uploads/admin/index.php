<?php
/**
 * ShopSmart India - Admin Overview Dashboard
 * BCA Semester V Field Project
 */

$adminTitle = "Dashboard Overview";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();

$totalProducts = 0;
$totalCustomers = 0;
$totalOrders = 0;
$totalSales = 0;
$pendingOrders = 0;
$avgRating = 0;
$customerSatisfaction = 0;
$lowStockProducts = [];
$recentOrders = [];

if ($db) {
    // Total Products
    $totalProducts = (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();

    // Total Customers
    $totalCustomers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

    // Orders & Sales metrics
    $ordMetrics = $db->query("
        SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(CASE WHEN order_status NOT IN ('Cancelled', 'Returned') THEN total_amount ELSE 0 END), 0) AS total_sales,
            SUM(CASE WHEN order_status IN ('Pending', 'Processing') THEN 1 ELSE 0 END) AS pending_count
        FROM orders
    ")->fetch();

    $totalOrders = (int)($ordMetrics['total_count'] ?? 0);
    $totalSales = (float)($ordMetrics['total_sales'] ?? 0);
    $pendingOrders = (int)($ordMetrics['pending_count'] ?? 0);

    // Average Product Rating
    $avgRating = (float)$db->query("SELECT COALESCE(AVG(rating), 4.5) FROM reviews WHERE status = 'approved'")->fetchColumn();

    // Customer Satisfaction
    $satMetrics = $db->query("SELECT COALESCE(AVG(overall_satisfaction), 4.8) FROM customer_feedback")->fetchColumn();
    $customerSatisfaction = round(((float)$satMetrics / 5) * 100);

    // Low stock items (stock <= 15)
    $lowStockProducts = $db->query("
        SELECT id, name, sku, stock_quantity, price 
        FROM products 
        WHERE stock_quantity <= 15 AND is_active = 1 
        ORDER BY stock_quantity ASC LIMIT 5
    ")->fetchAll();

    // Recent 5 orders
    $recentOrders = $db->query("
        SELECT o.*, u.name AS customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC LIMIT 5
    ")->fetchAll();
}
?>

<!-- KPI Cards -->
<div class="kpi-grid">
  <div class="kpi-card" style="border-left:4px solid var(--primary-orange);">
    <div class="kpi-title">Gross Sales (INR)</div>
    <div class="kpi-value" style="color:var(--primary-orange);"><?= formatPriceINR($totalSales) ?></div>
  </div>

  <div class="kpi-card" style="border-left:4px solid var(--primary-blue);">
    <div class="kpi-title">Total Orders</div>
    <div class="kpi-value"><?= number_format($totalOrders) ?></div>
    <div style="font-size:12px;color:#64748b;margin-top:4px;"><?= $pendingOrders ?> pending processing</div>
  </div>

  <div class="kpi-card" style="border-left:4px solid var(--accent-green);">
    <div class="kpi-title">Active Products</div>
    <div class="kpi-value"><?= number_format($totalProducts) ?></div>
  </div>

  <div class="kpi-card" style="border-left:4px solid #8b5cf6;">
    <div class="kpi-title">Registered Customers</div>
    <div class="kpi-value"><?= number_format($totalCustomers) ?></div>
  </div>

  <div class="kpi-card" style="border-left:4px solid var(--accent-amber);">
    <div class="kpi-title">Customer Satisfaction</div>
    <div class="kpi-value"><?= $customerSatisfaction ?>%</div>
    <div style="font-size:12px;color:#64748b;margin-top:4px;">Avg Rating: <?= number_format($avgRating, 1) ?> ★</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px;margin-bottom:28px;">
  <!-- Recent Orders Table -->
  <div class="table-container">
    <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
      <h3 style="font-size:15px;margin:0;font-weight:700;color:var(--primary-navy);">Recent Orders</h3>
      <a href="<?= APP_URL ?>/admin/orders.php" style="font-size:12.5px;color:var(--primary-orange);font-weight:600;">View All Orders &rarr;</a>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Total</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recentOrders)): ?>
          <?php foreach ($recentOrders as $ro): ?>
            <tr>
              <td><strong><?= e($ro['order_number']) ?></strong></td>
              <td><?= e($ro['customer_name']) ?></td>
              <td style="font-weight:700;"><?= formatPriceINR($ro['total_amount']) ?></td>
              <td>
                <span style="font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:12px;color:#fff;background:<?= in_array($ro['order_status'], ['Delivered']) ? 'var(--accent-green)' : (in_array($ro['order_status'], ['Cancelled', 'Returned']) ? 'var(--accent-red)' : 'var(--primary-orange)') ?>;">
                  <?= e($ro['order_status']) ?>
                </span>
              </td>
              <td>
                <a href="<?= APP_URL ?>/admin/order_detail.php?id=<?= $ro['id'] ?>" style="color:var(--primary-orange);font-weight:600;font-size:13px;">Manage</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center;padding:20px;color:#64748b;">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Inventory Stock Alerts -->
  <div class="table-container">
    <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
      <h3 style="font-size:15px;margin:0;font-weight:700;color:var(--primary-navy);display:flex;align-items:center;gap:6px;">
        <i class="bi bi-exclamation-triangle-fill" style="color:var(--accent-amber);"></i> Low Stock Alerts
      </h3>
      <a href="<?= APP_URL ?>/admin/products.php" style="font-size:12.5px;color:var(--primary-orange);font-weight:600;">Inventory &rarr;</a>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Product</th>
          <th>Remaining</th>
          <th>Price</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($lowStockProducts)): ?>
          <?php foreach ($lowStockProducts as $lp): ?>
            <tr>
              <td>
                <div style="font-weight:600;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($lp['name']) ?>">
                  <?= e($lp['name']) ?>
                </div>
                <div style="font-size:11px;color:#64748b;">SKU: <?= e($lp['sku']) ?></div>
              </td>
              <td>
                <span style="color:var(--accent-red);font-weight:700;font-size:13px;">
                  <?= (int)$lp['stock_quantity'] ?> left
                </span>
              </td>
              <td style="font-weight:600;"><?= formatPriceINR($lp['price']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3" style="text-align:center;padding:20px;color:#64748b;">All products well-stocked!</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
