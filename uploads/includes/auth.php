<?php
/**
 * ShopSmart India - Authentication & Authorization (RBAC)
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

class Auth {
    public static function isLoggedIn(): bool {
        return !empty($_SESSION['user_id']);
    }

    public static function isAdmin(): bool {
        return !empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public static function getCurrentUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) {
            return null;
        }

        $db = Database::getConnection();
        if (!$db) return null;

        $stmt = $db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    public static function login(string $identifier, string $password, bool $rememberMe = false): array {
        $db = Database::getConnection();
        if (!$db) {
            return ['success' => false, 'message' => 'Database connection unavailable. Please check XAMPP MySQL.'];
        }

        // Generic error message to prevent account enumeration
        $genericError = 'Invalid email/mobile or password.';

        // Identifier could be email or 10-digit phone
        $stmt = $db->prepare("SELECT id, name, email, phone, password_hash, role, status FROM users WHERE (email = ? OR phone = ?) LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => $genericError];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'This account has been deactivated. Please contact support.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => $genericError];
        }

        // Authentication successful: Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        // Merge guest cart & wishlist if exists
        self::mergeGuestCartAndWishlist((int)$user['id']);

        // Remember Me token
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days

            $remStmt = $db->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
            $remStmt->execute([$user['id'], $tokenHash, $expires]);

            $cookieValue = base64_encode($user['id'] . ':' . $token);
            setcookie('shopsmart_remember', $cookieValue, time() + (86400 * 30), '/', '', false, true);
        }

        return ['success' => true, 'user' => $user];
    }

    public static function logout(): void {
        $db = Database::getConnection();
        if ($db && !empty($_COOKIE['shopsmart_remember'])) {
            $parts = explode(':', base64_decode($_COOKIE['shopsmart_remember']), 2);
            if (count($parts) === 2) {
                $remStmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                $remStmt->execute([(int)$parts[0]]);
            }
            setcookie('shopsmart_remember', '', time() - 3600, '/');
        }

        // Unset session variables
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();
    }

    public static function requireAuth(string $redirect = 'login.php'): void {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: " . APP_URL . "/$redirect");
            exit;
        }
    }

    public static function requireAdmin(): void {
        if (!self::isAdmin()) {
            header("Location: " . APP_URL . "/admin/login.php?error=unauthorized");
            exit;
        }
    }

    private static function mergeGuestCartAndWishlist(int $userId): void {
        $db = Database::getConnection();
        if (!$db) return;

        // If guest cart session exists, transfer to database
        if (!empty($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
            foreach ($_SESSION['guest_cart'] as $productId => $qty) {
                $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                $stmt->execute([$userId, (int)$productId, (int)$qty, (int)$qty]);
            }
            unset($_SESSION['guest_cart']);
        }

        // If guest wishlist exists, transfer to database
        if (!empty($_SESSION['guest_wishlist']) && is_array($_SESSION['guest_wishlist'])) {
            foreach ($_SESSION['guest_wishlist'] as $productId) {
                $stmt = $db->prepare("INSERT IGNORE INTO wishlist_items (user_id, product_id) VALUES (?, ?)");
                $stmt->execute([$userId, (int)$productId]);
            }
            unset($_SESSION['guest_wishlist']);
        }
    }
}

function isLoggedIn(): bool {
    return Auth::isLoggedIn();
}

function isAdmin(): bool {
    return Auth::isAdmin();
}

function currentUser(): ?array {
    return Auth::getCurrentUser();
}

function currentUserId(): ?int {
    return Auth::getCurrentUserId();
}
