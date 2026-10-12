<?php
/**
 * ShopSmart India - Wishlist API Endpoint
 * BCA Semester V Field Project
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

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$productId = (int)($input['product_id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}

$db = Database::getConnection();
$userId = currentUserId();

if ($userId && $db) {
    $stmt = $db->prepare("SELECT id FROM wishlist_items WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $del = $db->prepare("DELETE FROM wishlist_items WHERE id = ?");
        $del->execute([$existing['id']]);
        $action = 'removed';
    } else {
        $ins = $db->prepare("INSERT INTO wishlist_items (user_id, product_id) VALUES (?, ?)");
        $ins->execute([$userId, $productId]);
        $action = 'added';
    }

    $cntStmt = $db->prepare("SELECT COUNT(*) FROM wishlist_items WHERE user_id = ?");
    $cntStmt->execute([$userId]);
    $count = (int)$cntStmt->fetchColumn();
} else {
    if (!isset($_SESSION['guest_wishlist'])) {
        $_SESSION['guest_wishlist'] = [];
    }

    if (in_array($productId, $_SESSION['guest_wishlist'])) {
        $_SESSION['guest_wishlist'] = array_values(array_diff($_SESSION['guest_wishlist'], [$productId]));
        $action = 'removed';
    } else {
        $_SESSION['guest_wishlist'][] = $productId;
        $action = 'added';
    }
    $count = count($_SESSION['guest_wishlist']);
}

echo json_encode([
    'success' => true,
    'action' => $action,
    'wishlist_count' => $count
]);
