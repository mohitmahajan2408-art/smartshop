<?php
/**
 * ShopSmart India - Secure Session Management
 * BCA Semester V Field Project
 */

if (session_status() === PHP_SESSION_NONE) {
    // Cookie security flags
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 days
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Session timeout check (30 minutes of inactivity for sensitive sessions)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600 * 2)) {
    // If inactive for more than 2 hours, clear sensitive data
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
    }
}
$_SESSION['last_activity'] = time();

// Rate limiting helper for login attempts to thwart brute force
class RateLimiter {
    public static function check(string $key, int $maxAttempts = 5, int $decaySeconds = 300): bool {
        $now = time();
        $rateKey = 'rate_limit_' . $key;
        
        if (!isset($_SESSION[$rateKey])) {
            $_SESSION[$rateKey] = [
                'attempts' => 1,
                'first_attempt' => $now
            ];
            return true;
        }

        $data = &$_SESSION[$rateKey];
        if ($now - $data['first_attempt'] > $decaySeconds) {
            $data = [
                'attempts' => 1,
                'first_attempt' => $now
            ];
            return true;
        }

        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }

        $data['attempts']++;
        return true;
    }

    public static function getRemainingLockout(string $key, int $decaySeconds = 300): int {
        $rateKey = 'rate_limit_' . $key;
        if (!isset($_SESSION[$rateKey])) {
            return 0;
        }
        $diff = time() - $_SESSION[$rateKey]['first_attempt'];
        return max(0, $decaySeconds - $diff);
    }

    public static function reset(string $key): void {
        $rateKey = 'rate_limit_' . $key;
        unset($_SESSION[$rateKey]);
    }
}
