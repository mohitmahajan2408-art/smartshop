<?php
/**
 * ShopSmart India - Secure Customer Sign In
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to account
if (Auth::isLoggedIn()) {
    header('Location: ' . APP_URL . '/account.php');
    exit;
}

$errorMsg = '';
$identifier = trim($_POST['identifier'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errorMsg = 'Security validation failed. Please refresh the page.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateKey = 'login_' . md5($ip . '_' . $identifier);

        if (!RateLimiter::check($rateKey, 5, 300)) {
            $remaining = RateLimiter::getRemainingLockout($rateKey, 300);
            $errorMsg = "Too many failed attempts. For your security, please wait $remaining seconds before trying again.";
        } else {
            $password = $_POST['password'] ?? '';
            $rememberMe = !empty($_POST['remember_me']);

            $loginResult = Auth::login($identifier, $password, $rememberMe);

            if ($loginResult['success']) {
                RateLimiter::reset($rateKey);
                $redirect = $_SESSION['redirect_after_login'] ?? (APP_URL . '/index.php');
                unset($_SESSION['redirect_after_login']);
                // Prevent open redirect attacks
                if (!str_starts_with($redirect, APP_URL) && !str_starts_with($redirect, '/')) {
                    $redirect = APP_URL . '/index.php';
                }
                header("Location: $redirect");
                exit;
            } else {
                $errorMsg = $loginResult['message'];
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:440px;padding:60px 16px;">
  <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:var(--shadow-md);">
    <div style="text-align:center;margin-bottom:24px;">
      <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="ShopSmart" style="height:44px;margin:0 auto 12px;">
      <h1 style="font-size:22px;color:var(--primary-navy);margin:0;"><?= e(__('nav_login')) ?></h1>
      <p style="font-size:13px;color:#64748b;margin-top:4px;"><?= e(__('login_prompt')) ?></p>
    </div>

    <?php if ($errorMsg): ?>
      <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;font-size:13px;margin-bottom:20px;font-weight:600;">
        ✕ <?= e($errorMsg) ?>
      </div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/login.php" method="POST">
      <?= csrfField() ?>

      <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label" style="font-weight:600;">Email Address or 10-Digit Mobile</label>
        <input type="text" name="identifier" class="form-control" 
               placeholder="customer@shopsmart.in or 9123456789" 
               value="<?= e($identifier) ?>" required autofocus>
      </div>

      <div class="form-group" style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
          <label class="form-label" style="font-weight:600;">Password</label>
          <a href="<?= APP_URL ?>/forgot_password.php" style="font-size:12px;color:var(--primary-orange);font-weight:600;">Forgot Password?</a>
        </div>
        <div style="position:relative;">
          <input type="password" id="login-pass" name="password" class="form-control" placeholder="••••••••" required>
          <button type="button" onclick="togglePassVisibility('login-pass', this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;">
            Show
          </button>
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;font-size:13px;">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
          <input type="checkbox" name="remember_me" value="1"> Remember Me (30 Days)
        </label>
      </div>

      <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:12px;font-size:15px;margin-bottom:20px;">
        <?= e(__('nav_login')) ?>
      </button>

      <div style="text-align:center;font-size:13px;color:#64748b;">
        <?= e(__('dont_have_account')) ?> 
        <a href="<?= APP_URL ?>/register.php" style="color:var(--primary-orange);font-weight:700;"><?= e(__('create_account')) ?></a>
      </div>

      <!-- Demo Credentials Helper Callout -->
      <div style="margin-top:24px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:12px;line-height:1.5;color:#475569;">
        <strong>Demo Customer Account:</strong><br>
        Email: <code>customer@shopsmart.in</code> | Password: <code>Customer@123</code>
      </div>
    </form>
  </div>
</div>

<script>
function togglePassVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = 'Hide';
  } else {
    input.type = 'password';
    btn.textContent = 'Show';
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
