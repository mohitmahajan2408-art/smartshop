<?php
/**
 * ShopSmart India - Security Audit Logs (Admin)
 * BCA Semester V Field Project
 */

$adminTitle = "Security Audit Logs";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();

$logs = [];
if ($db) {
    $stmt = $db->query("
        SELECT a.*, u.name AS user_name, u.email AS user_email 
        FROM admin_audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.created_at DESC 
        LIMIT 100
    ");
    $logs = $stmt->fetchAll();
}
?>

<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;">
  <h3 style="font-size:16px;color:var(--primary-navy);margin:0 0 6px;">System Administrative Activity Stream</h3>
  <p style="font-size:13px;color:#64748b;margin:0;">
    Audit logging prevents unauthorized tampering by recording administrative operations with actor IDs, actions, details, and source IP addresses.
  </p>
</div>

<div class="table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Timestamp</th>
        <th>Administrator</th>
        <th>Action Code</th>
        <th>Event Details</th>
        <th>IP Address</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($logs)): ?>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td style="color:#64748b;font-size:12.5px;"><?= formatDateIndian($l['created_at']) ?></td>
            <td>
              <strong><?= e($l['user_name'] ?? 'System') ?></strong>
              <div style="font-size:11px;color:#64748b;"><?= e($l['user_email'] ?? '') ?></div>
            </td>
            <td>
              <span style="background:#f1f5f9;color:var(--primary-navy);font-weight:700;font-size:11.5px;padding:2px 8px;border-radius:4px;border:1px solid #cbd5e1;">
                <?= e($l['action']) ?>
              </span>
            </td>
            <td>
              <div style="max-width:380px;font-size:13px;color:#334155;"><?= e($l['details']) ?></div>
            </td>
            <td><code><?= e($l['ip_address']) ?></code></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5" style="text-align:center;padding:32px;color:#64748b;">No audit entries logged yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
