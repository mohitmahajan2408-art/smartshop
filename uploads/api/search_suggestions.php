<?php
/**
 * ShopSmart India - Live Search Suggestions API
 * BCA Semester V Field Project
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$db = Database::getConnection();
if (!$db) {
    echo json_encode([]);
    exit;
}

$stmt = $db->prepare("
    SELECT p.id, p.name, p.slug, p.price, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1 AND (p.name LIKE ? OR p.short_description LIKE ?)
    ORDER BY p.best_value_score DESC
    LIMIT 6
");

$searchParam = '%' . $query . '%';
$stmt->execute([$searchParam, $searchParam]);
$results = $stmt->fetchAll();

echo json_encode($results ?: []);
