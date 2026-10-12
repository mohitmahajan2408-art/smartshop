<?php
/**
 * ShopSmart India - CSRF Protection Helper
 * BCA Semester V Field Project
 */

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/session.php';
}

class CSRF {
    public static function getToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function getField(): string {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token = null): bool {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }

        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function regenerate(): string {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}

function csrfField(): string {
    return CSRF::getField();
}

function csrfToken(): string {
    return CSRF::getToken();
}

function verifyCsrf(?string $token = null): bool {
    return CSRF::verify($token);
}
