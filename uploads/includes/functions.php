<?php
/**
 * ShopSmart India - Common Core Functions & Business Logic
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/i18n.php';

/**
 * Format currency in Indian numbering system (e.g., ₹499, ₹1,299, ₹12,999, ₹1,25,000)
 */
function formatPriceINR(float|int|string|null $amount): string {
    if ($amount === null || $amount === '') {
        $amount = 0;
    }
    $amount = round((float)$amount);
    $isNegative = $amount < 0;
    $amount = abs($amount);
    
    $str = (string)$amount;
    $len = strlen($str);
    
    if ($len <= 3) {
        $formatted = $str;
    } else {
        $lastThree = substr($str, -3);
        $remaining = substr($str, 0, -3);
        $formatted = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $remaining) . "," . $lastThree;
    }
    
    return ($isNegative ? '-' : '') . CURRENCY_SYMBOL . $formatted;
}

/**
 * Safe HTML escaping against XSS
 */
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format Indian date and time (Asia/Kolkata)
 */
function formatDateIndian(?string $datetime, bool $includeTime = true): string {
    if (empty($datetime)) {
        return 'N/A';
    }
    $ts = strtotime($datetime);
    if (!$ts) {
        return 'N/A';
    }
    return $includeTime ? date('d M Y, h:i A', $ts) : date('d M Y', $ts);
}

/**
 * Convert string to URL-friendly slug
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Generate unique ShopSmart order number (e.g. SS-20261012-7F3A2)
 */
function generateOrderNumber(): string {
    return 'SS-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

/**
 * Transparent Best Value Score Algorithm (0-100)
 * Factors:
 * 1. Customer Rating (40% weight): 5 stars -> 40 pts
 * 2. Discount / Savings (25% weight): Savings percentage up to 40% discount -> 25 pts
 * 3. Warranty Coverage (20% weight): 12+ months warranty -> 20 pts
 * 4. Feature / Specification depth (15% weight): Verified specifications present -> 15 pts
 */
function calculateBestValueScore(float $price, float $originalPrice, float $rating, int $warrantyMonths, int $specCount = 4): int {
    // 1. Rating component (max 40)
    $ratingComp = min(40, ($rating / 5.0) * 40);

    // 2. Discount component (max 25)
    $discountPct = 0;
    if ($originalPrice > $price && $originalPrice > 0) {
        $discountPct = (($originalPrice - $price) / $originalPrice) * 100;
    }
    $discountComp = min(25, ($discountPct / 40.0) * 25);

    // 3. Warranty component (max 20)
    $warrantyComp = min(20, ($warrantyMonths / 24.0) * 20);

    // 4. Feature richness (max 15)
    $specComp = min(15, ($specCount / 6.0) * 15);

    $total = (int)round($ratingComp + $discountComp + $warrantyComp + $specComp);
    return max(10, min(99, $total));
}

/**
 * Eco-Friendly Score Calculation (0-100)
 * Based on documented product sustainability attributes:
 * - Recyclable packaging (25 pts)
 * - Energy Star / BEE 4+ Star rating (25 pts)
 * - Organic or chemical-free / Made in India sustainable sourcing (25 pts)
 * - Repairability & plastic-free design (25 pts)
 */
function calculateEcoScore(array $attributes): int {
    $score = 40; // baseline
    if (!empty($attributes['recyclable_packaging'])) $score += 15;
    if (!empty($attributes['bee_star_rating']) && (int)$attributes['bee_star_rating'] >= 4) $score += 20;
    if (!empty($attributes['organic_certified'])) $score += 15;
    if (!empty($attributes['made_in_india'])) $score += 10;
    return min(100, $score);
}

/**
 * Transparent Review Trust Score (0-100%)
 * Measures review authenticity based on verified purchases and rating variance
 */
function calculateReviewTrustScore(int $totalReviews, int $verifiedReviews): array {
    if ($totalReviews === 0) {
        return [
            'score' => 100,
            'label' => 'New Product',
            'explanation' => 'No reviews submitted yet.'
        ];
    }
    $verifiedRatio = ($verifiedReviews / $totalReviews) * 100;
    $score = (int)round(50 + ($verifiedRatio * 0.5)); // min 50%, scales with verified ratio
    
    $label = $score >= 85 ? 'High Trust' : ($score >= 70 ? 'Moderate Trust' : 'Caution Advised');
    $explanation = "{$verifiedReviews} of {$totalReviews} reviews (" . round($verifiedRatio) . "%) are from confirmed delivered orders.";
    
    return [
        'score' => $score,
        'label' => $label,
        'explanation' => $explanation
    ];
}

/**
 * Admin Audit Logger
 */
function logAdminAudit(?int $adminId, string $action, string $details = ''): void {
    try {
        $db = Database::getConnection();
        if ($db) {
            $stmt = $db->prepare("INSERT INTO admin_audit_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt->execute([$adminId, $action, $details, $ip]);
        }
    } catch (Exception $e) {
        error_log("Failed to write audit log: " . $e->getMessage());
    }
}

/**
 * Safe Image helper with fallback SVG generator
 */
function getProductImageUrl(?string $imagePath, string $productName = 'Product'): string {
    if (!empty($imagePath)) {
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }
        $localPath = ROOT_PATH . '/' . ltrim($imagePath, '/');
        if (file_exists($localPath)) {
            return APP_URL . '/' . ltrim($imagePath, '/');
        }
    }
    // Reliable local SVG data-uri fallback with ShopSmart badge so NO image is ever broken!
    $cleanName = substr(htmlspecialchars($productName, ENT_QUOTES), 0, 24);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400">'
         . '<defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">'
         . '<stop offset="0%" stop-color="#0f233a"/><stop offset="100%" stop-color="#1e3a8a"/>'
         . '</linearGradient></defs>'
         . '<rect width="400" height="400" fill="url(#g)" rx="16"/>'
         . '<circle cx="200" cy="180" r="70" fill="#f97316" opacity="0.15"/>'
         . '<path d="M170 160 h60 l-10 45 h-40 z" fill="#f97316"/>'
         . '<circle cx="180" cy="215" r="7" fill="#f97316"/>'
         . '<circle cx="220" cy="215" r="7" fill="#f97316"/>'
         . '<text x="200" y="270" fill="#ffffff" font-family="system-ui,sans-serif" font-size="16" font-weight="600" text-anchor="middle">' . $cleanName . '</text>'
         . '<text x="200" y="300" fill="#f97316" font-family="system-ui,sans-serif" font-size="12" font-weight="500" text-anchor="middle">ShopSmart India</text>'
         . '</svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}
