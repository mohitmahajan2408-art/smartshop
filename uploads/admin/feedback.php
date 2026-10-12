<?php
/**
 * ShopSmart India - Customer Happiness & Return Reason Analytics (Admin)
 * BCA Semester V Field Project
 */

$adminTitle = "Satisfaction & Returns Analytics";
require_once __DIR__ . '/admin_header.php';

$db = Database::getConnection();

// Calculate metrics from actual database records
$feedbackList = [];
$avgOverall = 0;
$avgQuality = 0;
$avgDelivery = 0;
$avgPricing = 0;
$avgUsability = 0;
$avgSupport = 0;
$totalResponses = 0;
$repeatCustomerPct = 0;
$returnReasons = [];

if ($db) {
    $feedStmt = $db->query("
        SELECT cf.*, u.name AS user_name, u.email AS user_email 
        FROM customer_feedback cf 
        LEFT JOIN users u ON cf.user_id = u.id 
        ORDER BY cf.created_at DESC
    ");
    $feedbackList = $feedStmt->fetchAll();
    $totalResponses = count($feedbackList);

    if ($totalResponses > 0) {
        $sumOverall = 0; $sumQual = 0; $sumDel = 0; $sumPrice = 0; $sumUse = 0; $sumSup = 0;
        foreach ($feedbackList as $fb) {
            $sumOverall += (int)$fb['overall_satisfaction'];
            $sumQual += (int)$fb['product_quality'];
            $sumDel += (int)$fb['delivery_experience'];
            $sumPrice += (int)$fb['pricing_satisfaction'];
            $sumUse += (int)$fb['website_usability'];
            $sumSup += (int)$fb['customer_support'];
        }
        $avgOverall = round($sumOverall / $totalResponses, 1);
        $avgQuality = round($sumQual / $totalResponses, 1);
        $avgDelivery = round($sumDel / $totalResponses, 1);
        $avgPricing = round($sumPrice / $totalResponses, 1);
        $avgUsability = round($sumUse / $totalResponses, 1);
        $avgSupport = round($sumSup / $totalResponses, 1);
    }

    // Repeat customer calculation: Users who placed > 1 order / total customers with orders
    $repeatQuery = $db->query("
        SELECT 
            COUNT(DISTINCT user_id) AS total_buyers,
            SUM(CASE WHEN order_count > 1 THEN 1 ELSE 0 END) AS repeat_buyers
        FROM (
            SELECT user_id, COUNT(*) AS order_count 
            FROM orders 
            WHERE order_status NOT IN ('Cancelled')
            GROUP BY user_id
        ) buyer_summary
    ")->fetch();

    $totalBuyers = (int)($repeatQuery['total_buyers'] ?? 0);
    $repeatBuyers = (int)($repeatQuery['repeat_buyers'] ?? 0);
    $repeatCustomerPct = $totalBuyers > 0 ? round(($repeatBuyers / $totalBuyers) * 100) : 0;

    // Return Reasons Aggregation from actual return_requests table
    $retStmt = $db->query("
        SELECT return_reason, COUNT(*) AS count 
        FROM return_requests 
        GROUP BY return_reason 
        ORDER BY count DESC
    ");
    $returnReasons = $retStmt->fetchAll();
}
?>

<!-- KPI Row -->
<div class="kpi-grid">
  <div class="kpi-card" style="border-left:4px solid var(--accent-green);">
    <div class="kpi-title">Overall Happiness Score</div>
    <div class="kpi-value"><?= $avgOverall > 0 ? number_format($avgOverall, 1) . ' / 5.0' : 'N/A' ?></div>
    <div style="font-size:12px;color:#64748b;margin-top:4px;">Based on <?= $totalResponses ?> customer responses</div>
  </div>

  <div class="kpi-card" style="border-left:4px solid var(--primary-orange);">
    <div class="kpi-title">Delivery Satisfaction</div>
    <div class="kpi-value"><?= $avgDelivery > 0 ? number_format($avgDelivery, 1) . ' / 5.0' : 'N/A' ?></div>
    <div style="font-size:12px;color:#64748b;margin-top:4px;">On-time courier courier score</div>
  </div>

  <div class="kpi-card" style="border-left:4px solid var(--primary-blue);">
    <div class="kpi-title">Repeat Buyer Rate</div>
    <div class="kpi-value"><?= $repeatCustomerPct ?>%</div>
    <div style="font-size:12px;color:#64748b;margin-top:4px;"><?= $repeatBuyers ?> of <?= $totalBuyers ?> active buyers</div>
  </div>

  <div class="kpi-card" style="border-left:4px solid #8b5cf6;">
    <div class="kpi-title">Website Usability</div>
    <div class="kpi-value"><?= $avgUsability > 0 ? number_format($avgUsability, 1) . ' / 5.0' : 'N/A' ?></div>
    <div style="font-size:12px;color:#64748b;margin-top:4px;">Speed & layout convenience</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
  <!-- Satisfaction Criteria Radar / Bars -->
  <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:var(--shadow-sm);">
    <h3 style="font-size:16px;color:var(--primary-navy);margin-bottom:18px;">Multi-Dimensional Satisfaction Breakdown</h3>
    <div style="display:flex;flex-direction:column;gap:14px;font-size:13.5px;">
      <div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span>Product Quality & Packaging:</span>
          <strong><?= number_format($avgQuality, 1) ?> / 5.0</strong>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
          <div style="width:<?= ($avgQuality / 5) * 100 ?>%;height:100%;background:var(--accent-green);"></div>
        </div>
      </div>

      <div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span>Delivery Speed & Courteousness:</span>
          <strong><?= number_format($avgDelivery, 1) ?> / 5.0</strong>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
          <div style="width:<?= ($avgDelivery / 5) * 100 ?>%;height:100%;background:var(--primary-orange);"></div>
        </div>
      </div>

      <div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span>Pricing & Value for Money:</span>
          <strong><?= number_format($avgPricing, 1) ?> / 5.0</strong>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
          <div style="width:<?= ($avgPricing / 5) * 100 ?>%;height:100%;background:var(--accent-amber);"></div>
        </div>
      </div>

      <div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span>Website Usability & Speed:</span>
          <strong><?= number_format($avgUsability, 1) ?> / 5.0</strong>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
          <div style="width:<?= ($avgUsability / 5) * 100 ?>%;height:100%;background:var(--primary-blue);"></div>
        </div>
      </div>

      <div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span>Customer Support & Assistance:</span>
          <strong><?= number_format($avgSupport, 1) ?> / 5.0</strong>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
          <div style="width:<?= ($avgSupport / 5) * 100 ?>%;height:100%;background:#8b5cf6;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Return Reason Analysis Table -->
  <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:var(--shadow-sm);">
    <h3 style="font-size:16px;color:var(--primary-navy);margin-bottom:8px;">
      <i class="bi bi-arrow-return-left"></i> Return Reason Analysis
    </h3>
    <p style="font-size:12.5px;color:#64748b;margin-bottom:16px;">
      Aggregated customer return requests to identify product defects or packaging shortcomings.
    </p>

    <?php if (!empty($returnReasons)): ?>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($returnReasons as $rr): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f8fafc;border-radius:8px;font-size:13px;">
            <span style="font-weight:600;color:var(--primary-navy);"><?= e($rr['return_reason']) ?></span>
            <span style="background:#fef2f2;color:#991b1b;font-weight:700;padding:2px 8px;border-radius:10px;">
              <?= $rr['count'] ?> incidents
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div style="padding:24px;text-align:center;background:#f8fafc;border-radius:8px;color:#64748b;font-size:13px;">
        No customer return requests on record. Product return rate is currently 0.0%.
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Raw Customer Feedback Feed -->
<div class="table-container">
  <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
    <h3 style="font-size:15px;margin:0;font-weight:700;color:var(--primary-navy);">Customer Written Feedback</h3>
  </div>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Customer</th>
        <th>Overall</th>
        <th>Comments / Suggestions</th>
        <th>Submitted Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($feedbackList)): ?>
        <?php foreach ($feedbackList as $fb): ?>
          <tr>
            <td>
              <strong><?= e($fb['user_name'] ?? 'Verified Shopper') ?></strong>
              <div style="font-size:11px;color:#64748b;"><?= e($fb['user_email'] ?? '') ?></div>
            </td>
            <td>
              <span class="rating-pill"><?= (int)$fb['overall_satisfaction'] ?> ★</span>
            </td>
            <td>
              <div style="font-size:13px;color:#334155;max-width:440px;line-height:1.4;">
                <?= e($fb['comments'] ?: 'No additional written comment.') ?>
              </div>
            </td>
            <td style="color:#64748b;font-size:12px;"><?= formatDateIndian($fb['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="4" style="text-align:center;padding:32px;color:#64748b;">No feedback responses recorded yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
