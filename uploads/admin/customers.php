<?php
/**
 * ShopSmart India - Customer Directory (Admin)
 * BCA Semester V Field Project
 */

$adminTitle = "Customer Directory";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_customer') {
    if (!verifyCsrf()) {
        $error = 'Security validation failed.';
    } else {
        $cId = (int)$_POST['customer_id'];
        $upd = $db->prepare("UPDATE users SET status = IF(status='active', 'inactive', 'active'), updated_at = NOW() WHERE id = ? AND role = 'customer'");
        $upd->execute([$cId]);
        logAdminAudit(currentUserId(), 'TOGGLE_CUSTOMER_STATUS', "Toggled status for customer ID $cId");
        $msg = 'Customer status updated successfully.';
    }
}

$search = trim($_GET['q'] ?? '');
$query = "
    SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at,
           COUNT(o.id) AS order_count,
           COALESCE(SUM(o.total_amount), 0) AS lifetime_spend
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.order_status NOT IN ('Cancelled', 'Returned')
    WHERE u.role = 'customer'
";
$params = [];

if ($search) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$query .= " GROUP BY u.id ORDER BY u.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();
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

<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;">
  <form action="<?= APP_URL ?>/admin/customers.php" method="GET" style="display:flex;gap:10px;">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search Name, Email, or Phone..." class="form-control" style="width:280px;padding:6px 12px;font-size:13px;">
    <button type="submit" class="btn-primary" style="padding:6px 14px;font-size:13px;">Search</button>
  </form>
  <div style="font-size:13px;color:#64748b;">
    Total Customers: <strong><?= count($customers) ?></strong>
  </div>
</div>

<div class="table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Customer Name</th>
        <th>Email Address</th>
        <th>Phone (+91)</th>
        <th>Registered Date</th>
        <th>Orders Placed</th>
        <th>Lifetime Spend</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($customers)): ?>
        <?php foreach ($customers as $c): ?>
          <tr>
            <td><strong><?= e($c['name']) ?></strong></td>
            <td><?= e($c['email']) ?></td>
            <td>+91 <?= e($c['phone']) ?></td>
            <td style="color:#64748b;"><?= formatDateIndian($c['created_at'], false) ?></td>
            <td>
              <span style="background:#f1f5f9;padding:2px 8px;border-radius:10px;font-weight:700;font-size:12px;">
                <?= $c['order_count'] ?> orders
              </span>
            </td>
            <td style="font-weight:700;color:var(--primary-navy);"><?= formatPriceINR($c['lifetime_spend']) ?></td>
            <td>
              <form action="<?= APP_URL ?>/admin/customers.php" method="POST" style="margin:0;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_customer">
                <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
                <button type="submit" style="font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:12px;color:#fff;background:<?= $c['status'] === 'active' ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
                  <?= ucfirst($c['status']) ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center;padding:32px;color:#64748b;">No customers found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
