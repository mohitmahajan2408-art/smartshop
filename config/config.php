<?php
/**
 * ShopSmart India - Core Configuration
 * BCA Semester V Field Project
 */

// Error reporting (can be disabled or logged in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set default timezone to Indian Standard Time
date_default_timezone_set('Asia/Kolkata');

// Application Constants
define('APP_NAME', 'ShopSmart India');
define('APP_TAGLINE', 'Everything You Need, All in One Place.');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/ShopSmart');
define('CURRENCY_SYMBOL', '₹');
define('CURRENCY_CODE', 'INR');
define('FREE_SHIPPING_THRESHOLD', 499);
define('STANDARD_SHIPPING_FEE', 49);
define('EXPRESS_SHIPPING_FEE', 99);

// Base Paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('LANG_PATH', ROOT_PATH . '/lang');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// Ensure uploads directory exists
if (!file_exists(UPLOADS_PATH . '/products')) {
    @mkdir(UPLOADS_PATH . '/products', 0755, true);
}
