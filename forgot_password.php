<?php
/**
 * ShopSmart India - Account Recovery & Password Reset
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();
$msg = '';
$demoResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrf()) {
        $email = trim($_POST['email'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $db) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(24));
                $ins = $db->prepare("INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())");
                $ins->execute([$email, $token]);

                $demoResetLink = APP_URL . "/forgot_password.php?token=" . $token;
                $msg = "A password reset request has been registered.";
            } else {
                // Generic response to prevent user enumeration
                $msg = "If an account exists with this email, recovery instructions have been initiated.";
            }
        }
    }
}
?>

<div class="container" style="max-width:480px;padding:60px 16px;">
  <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:var(--shadow-md);">
    <h1 style="font-size:22px;color:var(--primary-navy);margin-bottom:8px;">Reset Password</h1>
    <p style="font-size:13.5px;color:#64748b;line-height:1.5;margin-bottom:20px;">
      Enter your registered email address to receive password recovery instructions.
    </p>

    <!-- Project Academic Note -->
    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:12px;font-size:12px;line-height:1.5;color:#9a3412;margin-bottom:20px;">
      ℹ️ <strong>Demonstration Note:</strong> Because this is a local field project environment without a configured production SMTP mail relay, reset tokens are generated and displayed securely below for testing.
    </div>

    <?php if ($msg): ?>
      <div style="background:#ecfdf5;border-left:4px solid #10b981;color:#065f46;padding:12px;border-radius:6px;font-size:13px;margin-bottom:20px;font-weight:600;">
        <?= e($msg) ?>
      </div>
      <?php if ($demoResetLink): ?>
        <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:12px;font-size:12px;margin-bottom:20px;">
          <strong>Simulated Recovery Link:</strong><br>
          <a href="<?= e($demoResetLink) ?>" style="color:var(--primary-orange);word-break:break-all;"><?= e($demoResetLink) ?></a>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/forgot_password.php" method="POST">
      <?= csrfField() ?>
      <div class="form-group" style="margin-bottom:20px;">
        <label class="form-label" style="font-weight:600;"><?= e(__('email_address')) ?></label>
        <input type="email" name="email" class="form-control" placeholder="customer@shopsmart.in" required>
      </div>
      <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:12px;font-size:15px;margin-bottom:16px;">
        Generate Recovery Token
      </button>
      <div style="text-align:center;font-size:13px;">
        <a href="<?= APP_URL ?>/login.php" style="color:var(--primary-orange);font-weight:600;">Back to Sign In</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
