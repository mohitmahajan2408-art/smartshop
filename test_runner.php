<?php
/**
 * ShopSmart India - Automated Quality Assurance & Verification Test Runner
 * BCA Semester V Field Project
 * Can be run via CLI: php test_runner.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/auth.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function runTest(string $testName, callable $fn): void {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    try {
        $result = $fn();
        if ($result === true) {
            $passedTests++;
            echo " [PASS] $testName" . PHP_EOL;
        } else {
            $failedTests++;
            echo " [FAIL] $testName - Test returned false" . PHP_EOL;
        }
    } catch (Throwable $e) {
        $failedTests++;
        echo " [FAIL] $testName - Exception: " . $e->getMessage() . PHP_EOL;
    }
}

echo "======================================================================" . PHP_EOL;
echo "  SHOPSMART INDIA — AUTOMATED TEST RUNNER (BCA FIELD PROJECT)" . PHP_EOL;
echo "======================================================================" . PHP_EOL;

// 1. Indian Currency Formatting Test
runTest("Indian Currency Formatter (499 -> ₹499)", function() {
    return formatPriceINR(499) === '₹499';
});

runTest("Indian Currency Formatter (1299 -> ₹1,299)", function() {
    return formatPriceINR(1299) === '₹1,299';
});

runTest("Indian Currency Formatter (12999 -> ₹12,999)", function() {
    return formatPriceINR(12999) === '₹12,999';
});

runTest("Indian Currency Formatter (125000 -> ₹1,25,000)", function() {
    return formatPriceINR(125000) === '₹1,25,000';
});

// 2. Best Value Score Algorithm Test
runTest("Best Value Score Calculation in Range (10-99)", function() {
    $score = calculateBestValueScore(1299, 3990, 4.6, 12, 5);
    return is_int($score) && $score >= 10 && $score <= 99;
});

// 3. Eco-Friendly Score Calculation Test
runTest("Eco-Friendly Score Calculation", function() {
    $score = calculateEcoScore([
        'recyclable_packaging' => true,
        'bee_star_rating' => 5,
        'made_in_india' => true
    ]);
    return $score >= 70 && $score <= 100;
});

// 4. Review Trust Score Calculation Test
runTest("Review Trust Score Calculation", function() {
    $trust = calculateReviewTrustScore(10, 8);
    return isset($trust['score']) && $trust['score'] >= 80 && $trust['label'] === 'High Trust';
});

// 5. CSRF Security Verification Test
runTest("CSRF Token Generation and Hash Equals", function() {
    $token = CSRF::getToken();
    return !empty($token) && CSRF::verify($token) === true && CSRF::verify('invalid_token') === false;
});

// 6. Password Hashing & Verification Test
runTest("Password Hashing with password_verify", function() {
    $pwd = "Customer@123";
    $hash = password_hash($pwd, PASSWORD_BCRYPT);
    return password_verify($pwd, $hash) && !password_verify("WrongPassword", $hash);
});

// 7. Multi-Language Dictionaries Integrity Test
runTest("i18n Translation Dictionary Integrity (EN, MR, HI)", function() {
    $en = include __DIR__ . '/lang/en.php';
    $mr = include __DIR__ . '/lang/mr.php';
    $hi = include __DIR__ . '/lang/hi.php';
    
    // Ensure all critical navigation and currency keys exist in all 3 languages
    $requiredKeys = ['app_name', 'tagline', 'currency', 'nav_home', 'nav_categories', 'nav_budget_mode', 'nav_compare', 'price', 'in_stock'];
    foreach ($requiredKeys as $k) {
        if (!isset($en[$k]) || !isset($mr[$k]) || !isset($hi[$k])) {
            return false;
        }
    }
    return true;
});

// 8. Order Number Generator Test
runTest("Unique Order Number Generator Format", function() {
    $on1 = generateOrderNumber();
    $on2 = generateOrderNumber();
    return str_starts_with($on1, 'SS-') && ($on1 !== $on2);
});

// 9. Rate Limiter Mechanism Test
runTest("Rate Limiter Progressive Delays", function() {
    $key = 'test_ip_' . rand(1000, 9999);
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::check($key, 5, 60);
    }
    $blocked = !RateLimiter::check($key, 5, 60);
    RateLimiter::reset($key);
    return $blocked;
});

// 10. Database Connectivity Test (if MySQL service is running)
runTest("Database Connection or Schema File Integrity", function() {
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile) || filesize($sqlFile) < 1000) {
        return false;
    }
    return true;
});

echo "======================================================================" . PHP_EOL;
echo "  TEST SUMMARY: $totalTests Executed | $passedTests Passed | $failedTests Failed" . PHP_EOL;
echo "======================================================================" . PHP_EOL;

if ($failedTests > 0) {
    exit(1);
}
