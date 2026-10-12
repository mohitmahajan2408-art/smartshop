<?php
/**
 * ShopSmart India - Automated Database Setup & Seeder Script
 * BCA Semester V Field Project
 * Can be run via CLI: php setup_db.php
 * Or via browser: http://localhost/ShopSmart/setup_db.php
 */

require_once __DIR__ . '/config/config.php';

$isCli = (php_sapi_name() === 'cli');

function output(string $msg, bool $isCli): void {
    if ($isCli) {
        echo $msg . PHP_EOL;
    } else {
        echo '<p style="font-family:system-ui;margin:4px 0;">' . htmlspecialchars($msg) . '</p>';
    }
}

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><title>ShopSmart Database Setup</title>';
    echo '<style>body{font-family:system-ui;background:#f8fafc;color:#0f172a;padding:40px;line-height:1.6;} .box{max-width:600px;margin:auto;background:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);}</style>';
    echo '</head><body><div class="box">';
    echo '<h2 style="color:#1e3a8a;margin-top:0;">ShopSmart India — Database Setup</h2>';
}

output("Connecting to MySQL server at 127.0.0.1...", $isCli);

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    output("Connected successfully to MySQL server.", $isCli);
} catch (PDOException $e) {
    output("ERROR: Unable to connect to MySQL server: " . $e->getMessage(), $isCli);
    output("Please verify that XAMPP MySQL is started in XAMPP Control Panel.", $isCli);
    if (!$isCli) echo '</div></body></html>';
    exit(1);
}

output("Reading database.sql file...", $isCli);
$sqlFile = __DIR__ . '/database.sql';
if (!file_exists($sqlFile)) {
    output("ERROR: database.sql not found at $sqlFile", $isCli);
    if (!$isCli) echo '</div></body></html>';
    exit(1);
}

$sqlContent = file_get_contents($sqlFile);

output("Executing database schema and seed data...", $isCli);
try {
    $pdo->exec($sqlContent);
    output("SUCCESS: Database 'shopsmart_db' initialized and seeded successfully!", $isCli);
    output("Initial Admin Credentials:", $isCli);
    output("  Email: admin@shopsmart.in", $isCli);
    output("  Password: Admin@ShopSmart2026", $isCli);
    output("Initial Customer Credentials:", $isCli);
    output("  Email: customer@shopsmart.in", $isCli);
    output("  Password: Customer@123", $isCli);
    if (!$isCli) {
        echo '<div style="margin-top:20px;"><a href="' . APP_URL . '" style="background:#f97316;color:white;padding:10px 20px;text-decoration:none;border-radius:6px;font-weight:bold;">Launch ShopSmart India</a></div>';
    }
} catch (PDOException $e) {
    output("ERROR executing SQL: " . $e->getMessage(), $isCli);
    if (!$isCli) echo '</div></body></html>';
    exit(1);
}

if (!$isCli) echo '</div></body></html>';
