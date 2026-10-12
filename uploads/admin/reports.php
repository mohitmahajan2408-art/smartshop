<?php
/**
 * ShopSmart India - Sales Reports & Analytics (Admin)
 * BCA Semester V Field Project
 */

$adminTitle = "Sales & Inventory Reports";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();

// Top Selling Products
$topProducts = [];
$catPerformance = [];
$totalRevenue = 0;
$totalOrdersCount = 0;
$avgOrderValue = 0;

if ($db) {
    // Overall Sales Stats
    $overallStats = $db->query("
        SELECT 
            COUNT(*) AS order_count,
            COALESCE(SUM(total_amount), 0) AS revenue,
            COALESCE(AVG(total_amount), 0) AS aov
        FROM orders
        WHERE order_status NOT IN ('Cancelled', 'Returned')
    ")->fetch();

    $totalOrdersCount = (int)($overallStats['order_count'] ?? 0);
    $totalRevenue = (float)($overallStats['revenue'] ?? 0);
    $avgOrderValue = (float)($overallStats['aov'] ?? 0);

    // Top Selling Products by Quantity Sold
    $topProducts = $db->query("
        SELECT oi.product_id, oi.product_name, SUM(oi.quantity) AS units_sold, SUM(oi.total_price) AS total_revenue
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_status NOT IN ('Cancelled', 'Returned')
        GROUP BY oi.product_id, oi.product_name
        ORDER BY units_sold DESC
        LIMIT 6
    ")->fetchAll();

    // Category Sales Performance
    $catPerformance = $db->query("
        SELECT c.name AS category_name, COUNT(DISTINCT o.id) AS order_count, SUM(oi.total_price) AS total_sales
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_status NOT IN ('Cancelled', 'Returned')
        GROUP BY c.id, c.name
        ORDER BY total_sales DESC
    ")->fetchAll();
}
?>

<!-- KPI Row -->
<div class="kpi-grid">
  <div class="kpi-card" style="border-left:4px solid var(--primary-orange);">
    <div class="kpi-title">Total Realized Revenue</div>
    <div class="kpi-value" style="color:var(--primary-orange);"><?= formatPriceINR($totalRevenue) ?></div>
  </div>

  <div class="kpi-card" style="border-left:4px solid var(--primary-blue);">
    <div class="kpi-title">Completed Orders</div>
    <div class="kpi-value"><?= number_format($totalOrdersCount) ?></div>
  </div>

  <div class="kpi-card" style="border-left:4px solid var(--accent-green);">
    <div class="kpi-title">Average Order Value (AOV)</div>
    <div class="kpi-value"><?= formatPriceINR($avgOrderValue) ?></div>
  </div>
</div>

<!-- Export Controls -->
<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:28px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
  <div>
    <h3 style="font-size:16px;color:var(--primary-navy);margin:0 0 4px;">Data Export Utilities</h3>
    <p style="font-size:13px;color:#64748b;margin:0;">Download raw database tables into formatted CSV files for spreadsheet auditing.</p>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="<?= APP_URL ?>/admin/export_csv.php?type=orders" class="btn-primary" style="font-size:13px;padding:8px 16px;">
      <i class="bi bi-download"></i> Export Orders CSV
    </a>
    <a href="<?= APP_URL ?>/admin/export_csv.php?type=products" class="btn-secondary" style="font-size:13px;padding:8px 16px;border:1px solid #cbd5e1;">
      <i class="bi bi-download"></i> Export Products CSV
    </a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
  <!-- Top Selling Products Table -->
  <div class="table-container">
    <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
      <h3 style="font-size:15px;margin:0;font-weight:700;color:var(--primary-navy);">Top Selling Products</h3>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Product</th>
          <th>Units Sold</th>
          <th>Revenue</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($topProducts)): ?>
          <?php foreach ($topProducts as $tp): ?>
            <tr>
              <td><strong><?= e($tp['product_name']) ?></strong></td>
              <td><span style="background:#f1f5f9;padding:2px 8px;border-radius:10px;font-weight:700;"><?= $tp['units_sold'] ?> units</span></td>
              <td style="font-weight:700;color:var(--primary-navy);"><?= formatPriceINR($tp['total_revenue']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3" style="text-align:center;padding:24px;color:#64748b;">No sales records available yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Category Performance Table -->
  <div class="table-container">
    <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
      <h3 style="font-size:15px;margin:0;font-weight:700;color:var(--primary-navy);">Sales by Category</h3>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Category</th>
          <th>Orders</th>
          <th>Total Sales</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($catPerformance)): ?>
          <?php foreach ($catPerformance as $cp): ?>
            <tr>
              <td><strong><?= e($cp['category_name']) ?></strong></td>
              <td><?= $cp['order_count'] ?> orders</td>
              <td style="font-weight:700;color:var(--primary-orange);"><?= formatPriceINR($cp['total_sales']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3" style="text-align:center;padding:24px;color:#64748b;">No category sales data yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
