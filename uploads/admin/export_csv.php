<?php
/**
 * ShopSmart India - CSV Data Export Streamer (Admin)
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Authorization check
Auth::requireAdmin();

$type = $_GET['type'] ?? 'orders';
$db = Database::getConnection();

if (!$db) {
    die("Database unavailable.");
}

$filename = 'shopsmart_' . $type . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

if ($type === 'products') {
    fputcsv($output, ['Product ID', 'Name', 'SKU', 'Category', 'Brand', 'Price (INR)', 'Original MRP (INR)', 'Stock', 'Best Value Score', 'Eco Score', 'Status', 'Created At']);
    
    $stmt = $db->query("
        SELECT p.id, p.name, p.sku, c.name AS category, b.name AS brand, p.price, p.original_price, p.stock_quantity, p.best_value_score, p.eco_friendly_score, p.is_active, p.created_at
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        ORDER BY p.id ASC
    ");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['sku'],
            $row['category'],
            $row['brand'] ?? 'N/A',
            $row['price'],
            $row['original_price'],
            $row['stock_quantity'],
            $row['best_value_score'],
            $row['eco_friendly_score'],
            $row['is_active'] ? 'Active' : 'Inactive',
            $row['created_at']
        ]);
    }
} else {
    // Default: Orders export
    fputcsv($output, ['Order ID', 'Order Number', 'Customer Name', 'Customer Email', 'Subtotal', 'Delivery Fee', 'Total Amount', 'Payment Method', 'Payment Status', 'Order Status', 'Tracking Number', 'Order Date']);
    
    $stmt = $db->query("
        SELECT o.id, o.order_number, u.name AS customer_name, u.email AS customer_email, o.subtotal, o.delivery_fee, o.total_amount, o.payment_method, o.payment_status, o.order_status, o.tracking_number, o.created_at
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.id DESC
    ");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['order_number'],
            $row['customer_name'],
            $row['customer_email'],
            $row['subtotal'],
            $row['delivery_fee'],
            $row['total_amount'],
            $row['payment_method'],
            $row['payment_status'],
            $row['order_status'],
            $row['tracking_number'] ?? 'N/A',
            $row['created_at']
        ]);
    }
}

logAdminAudit(currentUserId(), 'EXPORT_CSV', "Exported {$type} CSV report ($filename)");
fclose($output);
exit;
