<?php
/**
 * ShopSmart India - Smart Budget Optimizer Backend Algorithm
 * BCA Semester V Field Project
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$budget = (float)($_POST['budget'] ?? $_GET['budget'] ?? 0);
$persona = trim($_POST['persona'] ?? $_GET['persona'] ?? 'student');
$categorySlug = trim($_POST['category'] ?? $_GET['category'] ?? '');

if ($budget < 100) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a realistic shopping budget of at least ₹100.'
    ]);
    exit;
}

$db = Database::getConnection();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Map persona to relevant search keywords and category hints
$personaKeywords = [
    'student' => ['Classmate', 'Wings of Fire', 'Rockerz', 'Power Bank', 'Backpack', 'Notebook'],
    'professional' => ['Formal', 'Smartwatch', 'Power Bank', 'Plug', 'Derby'],
    'home' => ['Cooker', 'Mixer', 'Ghee', 'Atta', 'Safe', 'Soil', 'Watering Can'],
    'gaming' => ['Headphones', 'Smartwatch', 'Fastrack', 'LED'],
    'travel' => ['Power Bank', 'Headphones', 'Backpack', 'Watch', 'Holder']
];

// Fetch matching products
$query = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1";
$params = [];

if (!empty($categorySlug)) {
    $query .= " AND c.slug = ?";
    $params[] = $categorySlug;
}

$query .= " ORDER BY p.best_value_score DESC, p.price ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$allProducts = $stmt->fetchAll();

if (empty($allProducts)) {
    echo json_encode([
        'success' => false,
        'message' => 'No active products found for the selected category.'
    ]);
    exit;
}

// Check minimum product price in selected pool
$minPrice = min(array_column($allProducts, 'price'));
if ($budget < $minPrice) {
    echo json_encode([
        'success' => false,
        'message' => "Your entered budget of " . formatPriceINR($budget) . " is below the minimum available item in this category (" . formatPriceINR($minPrice) . "). Please increase your budget to view recommendations.",
        'suggested_min_budget' => $minPrice
    ]);
    exit;
}

// Algorithmic Selection: Prioritize persona-matching items first, then complementary items
$selectedItems = [];
$runningTotal = 0;
$personaTerms = $personaKeywords[$persona] ?? [];

// 1. Prioritize persona matches
foreach ($allProducts as $p) {
    $isPersonaMatch = false;
    foreach ($personaTerms as $term) {
        if (stripos($p['name'], $term) !== false || stripos($p['short_description'], $term) !== false) {
            $isPersonaMatch = true;
            break;
        }
    }

    if ($isPersonaMatch && ($runningTotal + $p['price'] <= $budget)) {
        $selectedItems[] = $p;
        $runningTotal += $p['price'];
    }
}

// 2. If remaining budget allows, fill in highest value-for-money items
if ($runningTotal < $budget * 0.85) {
    foreach ($allProducts as $p) {
        if (!in_array($p['id'], array_column($selectedItems, 'id'))) {
            if ($runningTotal + $p['price'] <= $budget) {
                $selectedItems[] = $p;
                $runningTotal += $p['price'];
            }
        }
    }
}

// Fallback: If no persona items fit, take the single best item that fits
if (empty($selectedItems)) {
    foreach ($allProducts as $p) {
        if ($p['price'] <= $budget) {
            $selectedItems[] = $p;
            $runningTotal += $p['price'];
            break;
        }
    }
}

$moneySaved = 0;
$totalBestVal = 0;
$totalEco = 0;

foreach ($selectedItems as $item) {
    $orig = (float)$item['original_price'];
    $cur = (float)$item['price'];
    if ($orig > $cur) {
        $moneySaved += ($orig - $cur);
    }
    $totalBestVal += (int)$item['best_value_score'];
    $totalEco += (int)$item['eco_friendly_score'];
}

$count = count($selectedItems);
$avgBestVal = $count > 0 ? round($totalBestVal / $count) : 0;
$avgEco = $count > 0 ? round($totalEco / $count) : 0;
$remaining = $budget - $runningTotal;

// Prepare response
$formattedItems = array_map(function($item) {
    return [
        'id' => $item['id'],
        'name' => $item['name'],
        'slug' => $item['slug'],
        'price' => (float)$item['price'],
        'formatted_price' => formatPriceINR($item['price']),
        'original_price' => (float)$item['original_price'],
        'formatted_mrp' => formatPriceINR($item['original_price']),
        'best_value_score' => $item['best_value_score'],
        'eco_friendly_score' => $item['eco_friendly_score'],
        'category_name' => $item['category_name'],
        'image_url' => getProductImageUrl($item['image_url'], $item['name'])
    ];
}, $selectedItems);

echo json_encode([
    'success' => true,
    'budget' => $budget,
    'formatted_budget' => formatPriceINR($budget),
    'total_cost' => $runningTotal,
    'formatted_total' => formatPriceINR($runningTotal),
    'money_remaining' => $remaining,
    'formatted_remaining' => formatPriceINR($remaining),
    'money_saved' => $moneySaved,
    'formatted_saved' => formatPriceINR($moneySaved),
    'avg_best_value_score' => $avgBestVal,
    'avg_eco_score' => $avgEco,
    'item_count' => $count,
    'items' => $formattedItems
]);
