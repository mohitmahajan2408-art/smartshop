<?php
/**
 * ShopSmart India - Customer Registration
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (Auth::isLoggedIn()) {
    header('Location: ' . APP_URL . '/account.php');
    exit;
}

$errorMsg = '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errorMsg = 'Security token validation failed. Please refresh and try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $terms = !empty($_POST['terms']);

        if (strlen($name) < 2) {
            $errorMsg = 'Please enter your full name.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Please enter a valid email address.';
        } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
            $errorMsg = 'Please enter a valid 10-digit Indian mobile number.';
        } elseif (strlen($password) < 6) {
            $errorMsg = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $errorMsg = 'Passwords do not match.';
        } elseif (!$terms) {
            $errorMsg = 'You must accept the ShopSmart Terms & Privacy Policy.';
        } else {
            $db = Database::getConnection();
            if ($db) {
                // Check duplicate email or phone
                $checkStmt = $db->prepare("SELECT id, email, phone FROM users WHERE email = ? OR phone = ? LIMIT 1");
                $checkStmt->execute([$email, $phone]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    if ($existing['email'] === $email) {
                        $errorMsg = 'An account with this email address already exists.';
                    } else {
                        $errorMsg = 'An account with this mobile number already exists.';
                    }
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $db->prepare("
                        INSERT INTO users (name, email, phone, password_hash, role, status, created_at)
                        VALUES (?, ?, ?, ?, 'customer', 'active', NOW())
                    ");
                    $ins->execute([$name, $email, $phone, $hash]);

                    // Automatically sign in
                    Auth::login($email, $password);
                    header('Location: ' . APP_URL . '/index.php?registered=1');
                    exit;
                }
            } else {
                $errorMsg = 'Database connection unavailable.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:480px;padding:50px 16px;">
  <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:var(--shadow-md);">
    <div style="text-align:center;margin-bottom:24px;">
      <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="ShopSmart" style="height:44px;margin:0 auto 12px;">
      <h1 style="font-size:22px;color:var(--primary-navy);margin:0;"><?= e(__('create_account')) ?></h1>
      <p style="font-size:13px;color:#64748b;margin-top:4px;">Join thousands of smart shoppers across India today</p>
    </div>

    <?php if ($errorMsg): ?>
      <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;font-size:13px;margin-bottom:20px;font-weight:600;">
        ✕ <?= e($errorMsg) ?>
      </div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/register.php" method="POST">
      <?= csrfField() ?>

      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label" style="font-weight:600;"><?= e(__('full_name')) ?> *</label>
        <input type="text" name="name" class="form-control" value="<?= e($name) ?>" placeholder="e.g. Ananya Sen" required>
      </div>

      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label" style="font-weight:600;"><?= e(__('email_address')) ?> *</label>
        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" placeholder="name@example.com" required>
      </div>

      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label" style="font-weight:600;"><?= e(__('mobile_number')) ?> *</label>
        <div style="position:relative;">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-weight:600;color:#64748b;font-size:13px;">+91</span>
          <input type="text" name="phone" class="form-control" style="padding-left:44px;" value="<?= e($phone) ?>" placeholder="9876543210" maxlength="10" required>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label" style="font-weight:600;">Create Password *</label>
        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
      </div>

      <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label" style="font-weight:600;"><?= e(__('confirm_password')) ?> *</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required minlength="6">
      </div>

      <div style="margin-bottom:20px;font-size:13px;">
        <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;">
          <input type="checkbox" name="terms" value="1" required style="margin-top:3px;">
          <span style="line-height:1.4;"><?= e(__('terms_agree')) ?></span>
        </label>
      </div>

      <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:12px;font-size:15px;margin-bottom:20px;">
        <?= e(__('create_account')) ?>
      </button>

      <div style="text-align:center;font-size:13px;color:#64748b;">
        <?= e(__('already_have_account')) ?> 
        <a href="<?= APP_URL ?>/login.php" style="color:var(--primary-orange);font-weight:700;"><?= e(__('nav_login')) ?></a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
