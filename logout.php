<?php
/**
 * ShopSmart India - Secure Logout Handler
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrf()) {
        Auth::logout();
    }
}

header('Location: ' . APP_URL . '/index.php');
exit;
