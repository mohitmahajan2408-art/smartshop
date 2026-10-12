<?php
/**
 * ShopSmart India - Dedicated Administrator Login
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in as admin, redirect directly
if (Auth::isAdmin()) {
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

$errorMsg = '';
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errorMsg = 'Security validation failed. Please refresh the page.';
    } else {
        $password = $_POST['password'] ?? '';
        $db = Database::getConnection();

        if ($db) {
            $stmt = $db->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errorMsg = 'Invalid administrator credentials.';
            } elseif ($user['role'] !== 'admin') {
                $errorMsg = 'Access Denied: Administrator role required.';
            } elseif ($user['status'] !== 'active') {
                $errorMsg = 'This administrator account is currently suspended.';
            } else {
                // Legitimate admin login
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_role'] = 'admin';
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                logAdminAudit((int)$user['id'], 'ADMIN_LOGIN', 'Administrator signed into portal');
                header('Location: ' . APP_URL . '/admin/index.php');
                exit;
            }
        } else {
            $errorMsg = 'Database connection unavailable.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Sign In | ShopSmart India</title>
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body style="background:linear-gradient(135deg, #0a192f 0%, #0f233a 50%, #1e3a8a 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;">

<div style="background:#ffffff;border-radius:16px;max-width:420px;width:100%;padding:36px;box-shadow:0 20px 40px rgba(0,0,0,0.25);">
  <div style="text-align:center;margin-bottom:28px;">
    <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="ShopSmart" style="height:44px;margin-bottom:12px;">
    <h1 style="font-size:22px;color:#0f233a;margin:0;">Administrator Portal</h1>
    <p style="font-size:13px;color:#64748b;margin-top:4px;">Restricted system access &bull; Authorized personnel only</p>
  </div>

  <?php if ($errorMsg): ?>
    <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;font-size:13px;margin-bottom:20px;font-weight:600;">
      ✕ <?= e($errorMsg) ?>
    </div>
  <?php endif; ?>

  <form action="<?= APP_URL ?>/admin/login.php" method="POST">
    <?= csrfField() ?>

    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label" style="font-weight:600;color:#0f233a;">Administrator Email</label>
      <input type="email" name="email" class="form-control" placeholder="admin@shopsmart.in" value="<?= e($email) ?>" required autofocus>
    </div>

    <div class="form-group" style="margin-bottom:24px;">
      <label class="form-label" style="font-weight:600;color:#0f233a;">Admin Password</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>

    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:12px;font-size:15px;margin-bottom:16px;">
      Sign In to Admin Panel
    </button>

    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:12px;line-height:1.5;color:#475569;">
      <strong>Default Admin Credentials:</strong><br>
      Email: <code>admin@shopsmart.in</code><br>
      Password: <code>Admin@ShopSmart2026</code>
    </div>

    <div style="text-align:center;margin-top:16px;font-size:13px;">
      <a href="<?= APP_URL ?>/index.php" style="color:var(--primary-orange);text-decoration:none;">&larr; Return to Storefront</a>
    </div>
  </form>
</div>

</body>
</html>
