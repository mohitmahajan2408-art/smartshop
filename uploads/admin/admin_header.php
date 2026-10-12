<?php
/**
 * ShopSmart India - Admin Portal Master Header
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Strict Role-Based Access Control
Auth::requireAdmin();

$adminUser = Auth::getCurrentUser();
$currentAdminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
  <title><?= isset($adminTitle) ? e($adminTitle) . ' | ShopSmart Admin' : 'ShopSmart Admin Portal' ?></title>
  
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <style>
    body { background-color: #f1f5f9; display: flex; min-height: 100vh; flex-direction: column; }
    .admin-layout { display: grid; grid-template-columns: 260px 1fr; flex: 1; }
    .admin-sidebar { background: #0a192f; color: #cbd5e1; display: flex; flex-direction: column; }
    .admin-sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 12px; }
    .admin-nav { padding: 16px 12px; display: flex; flex-direction: column; gap: 4px; flex: 1; }
    .admin-nav a { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; color: #94a3b8; font-size: 13.5px; font-weight: 500; transition: all 0.15s; }
    .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.08); color: #ffffff; }
    .admin-nav a.active { background: var(--primary-orange); color: #ffffff; font-weight: 700; }
    .admin-main { padding: 32px; overflow-y: auto; }
    .admin-top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
    .table-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; box-shadow: var(--shadow-sm); }
    table.admin-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    table.admin-table th { background: #f8fafc; padding: 12px 16px; text-align: left; font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0; font-size: 12.5px; text-transform: uppercase; }
    table.admin-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #1e293b; }
    table.admin-table tr:hover { background: #f8fafc; }
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 28px; }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; }
    .kpi-title { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; }
    .kpi-value { font-size: 26px; font-weight: 800; color: #0f233a; margin-top: 6px; }
  </style>
</head>
<body>

<div class="admin-layout">
  <!-- Left Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-sidebar-header">
      <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="ShopSmart" style="height:36px;filter:brightness(0) invert(1);">
      <span style="background:var(--primary-orange);color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:4px;">ADMIN</span>
    </div>

    <nav class="admin-nav">
      <a href="<?= APP_URL ?>/admin/index.php" class="<?= $currentAdminPage === 'index' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard Overview
      </a>
      <a href="<?= APP_URL ?>/admin/products.php" class="<?= in_array($currentAdminPage, ['products', 'product_add', 'product_edit']) ? 'active' : '' ?>">
        <i class="bi bi-box-seam"></i> Manage Products
      </a>
      <a href="<?= APP_URL ?>/admin/categories.php" class="<?= $currentAdminPage === 'categories' ? 'active' : '' ?>">
        <i class="bi bi-tags"></i> Categories & Brands
      </a>
      <a href="<?= APP_URL ?>/admin/orders.php" class="<?= in_array($currentAdminPage, ['orders', 'order_detail']) ? 'active' : '' ?>">
        <i class="bi bi-receipt"></i> Orders & Logistics
      </a>
      <a href="<?= APP_URL ?>/admin/customers.php" class="<?= $currentAdminPage === 'customers' ? 'active' : '' ?>">
        <i class="bi bi-people"></i> Customers Directory
      </a>
      <a href="<?= APP_URL ?>/admin/reviews.php" class="<?= $currentAdminPage === 'reviews' ? 'active' : '' ?>">
        <i class="bi bi-star"></i> Reviews Moderation
      </a>
      <a href="<?= APP_URL ?>/admin/feedback.php" class="<?= $currentAdminPage === 'feedback' ? 'active' : '' ?>">
        <i class="bi bi-chat-heart"></i> Customer Satisfaction
      </a>
      <a href="<?= APP_URL ?>/admin/reports.php" class="<?= $currentAdminPage === 'reports' ? 'active' : '' ?>">
        <i class="bi bi-bar-chart-line"></i> Sales Reports & CSV
      </a>
      <a href="<?= APP_URL ?>/admin/audit_logs.php" class="<?= $currentAdminPage === 'audit_logs' ? 'active' : '' ?>">
        <i class="bi bi-shield-check"></i> Security Audit Logs
      </a>

      <div style="margin-top:auto;padding-top:20px;border-top:1px solid rgba(255,255,255,0.08);">
        <a href="<?= APP_URL ?>/index.php" target="_blank" style="color:#38bdf8;">
          <i class="bi bi-box-arrow-up-right"></i> View Live Storefront
        </a>
      </div>
    </nav>
  </aside>

  <!-- Main Workspace -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 style="font-size:22px;color:var(--primary-navy);margin:0;"><?= isset($adminTitle) ? e($adminTitle) : 'Admin Dashboard' ?></h1>
        <div style="font-size:12.5px;color:#64748b;margin-top:2px;">Logged in as: <strong><?= e($adminUser['name'] ?? 'Administrator') ?></strong> (<?= e($adminUser['email'] ?? '') ?>)</div>
      </div>
      <div style="display:flex;gap:12px;align-items:center;">
        <form action="<?= APP_URL ?>/logout.php" method="POST" style="margin:0;">
          <?= csrfField() ?>
          <button type="submit" class="btn-secondary" style="font-size:12.5px;padding:8px 16px;color:#ef4444;border:1px solid #fca5a5;">
            <i class="bi bi-box-arrow-right"></i> Sign Out
          </button>
        </form>
      </div>
    </div>
