<?php
/**
 * ShopSmart India - Shopping Cart API Endpoint
 * Handles: add, update, remove, get_count
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Read raw JSON input or POST form
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// Verify CSRF
$token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verifyCsrf($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh the page.']);
    exit;
}

$action = $input['action'] ?? 'add';
$productId = (int)($input['product_id'] ?? 0);
$quantity = max(1, (int)($input['quantity'] ?? 1));

$db = Database::getConnection();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

// Verify product exists and check stock
$prodStmt = $db->prepare("SELECT id, name, price, stock_quantity, is_active FROM products WHERE id = ? LIMIT 1");
$prodStmt->execute([$productId]);
$product = $prodStmt->fetch();

if (!$product || !$product['is_active']) {
    echo json_encode(['success' => false, 'message' => 'Product is not available.']);
    exit;
}

$userId = currentUserId();

if ($action === 'add') {
    if ($userId) {
        // Logged-in user: database cart
        $checkStmt = $db->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$userId, $productId]);
        $existing = $checkStmt->fetch();

        $newQty = $existing ? ($existing['quantity'] + $quantity) : $quantity;

        if ($newQty > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => "Only {$product['stock_quantity']} units in stock."]);
            exit;
        }

        if ($existing) {
            $updateStmt = $db->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $updateStmt->execute([$newQty, $userId, $productId]);
        } else {
            $insertStmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insertStmt->execute([$userId, $productId, $newQty]);
        }
    } else {
        // Guest user: session cart
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        $current = $_SESSION['guest_cart'][$productId] ?? 0;
        $newQty = $current + $quantity;

        if ($newQty > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => "Only {$product['stock_quantity']} units in stock."]);
            exit;
        }

        $_SESSION['guest_cart'][$productId] = $newQty;
    }

    $cartCount = getCartCount($db, $userId);
    echo json_encode([
        'success' => true,
        'message' => 'Added to Cart successfully!',
        'cart_count' => $cartCount
    ]);
    exit;
}

if ($action === 'update') {
    if ($quantity > $product['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => "Only {$product['stock_quantity']} units in stock."]);
        exit;
    }

    if ($userId) {
        $updateStmt = $db->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
        $updateStmt->execute([$quantity, $userId, $productId]);
    } else {
        $_SESSION['guest_cart'][$productId] = $quantity;
    }

    $cartCount = getCartCount($db, $userId);
    echo json_encode([
        'success' => true,
        'message' => 'Quantity updated',
        'cart_count' => $cartCount
    ]);
    exit;
}

if ($action === 'remove') {
    if ($userId) {
        $delStmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
        $delStmt->execute([$userId, $productId]);
    } else {
        unset($_SESSION['guest_cart'][$productId]);
    }

    $cartCount = getCartCount($db, $userId);
    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart',
        'cart_count' => $cartCount
    ]);
    exit;
}

function getCartCount(?PDO $db, ?int $userId): int {
    if ($userId && $db) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    return !empty($_SESSION['guest_cart']) ? (int)array_sum($_SESSION['guest_cart']) : 0;
}
