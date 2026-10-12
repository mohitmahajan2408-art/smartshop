<?php
/**
 * ShopSmart India - Database Connection via PDO
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    
    // Default XAMPP settings
    private static string $host = '127.0.0.1';
    private static string $dbName = 'shopsmart_db';
    private static string $username = 'root';
    private static string $password = '';
    private static string $charset = 'utf8mb4';

    public static function getConnection(): ?PDO {
        if (self::$instance === null) {
            // Load custom env/config if exists
            $customConfigFile = __DIR__ . '/local.config.php';
            if (file_exists($customConfigFile)) {
                $customConfig = include $customConfigFile;
                if (is_array($customConfig)) {
                    self::$host = $customConfig['db_host'] ?? self::$host;
                    self::$dbName = $customConfig['db_name'] ?? self::$dbName;
                    self::$username = $customConfig['db_user'] ?? self::$username;
                    self::$password = $customConfig['db_pass'] ?? self::$password;
                }
            }

            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbName . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // If database doesn't exist, try connecting to MySQL server directly
                if ($e->getCode() === 1049) {
                    try {
                        $dsnNoDb = "mysql:host=" . self::$host . ";charset=" . self::$charset;
                        $pdoNoDb = new PDO($dsnNoDb, self::$username, self::$password, $options);
                        // Auto-create database if requested
                        $pdoNoDb->exec("CREATE DATABASE IF NOT EXISTS `" . self::$dbName . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        self::$instance = new PDO($dsn, self::$username, self::$password, $options);
                    } catch (PDOException $ex) {
                        error_log("Database connection failed: " . $ex->getMessage());
                        return null;
                    }
                } else {
                    error_log("Database connection failed: " . $e->getMessage());
                    return null;
                }
            }
        }
        return self::$instance;
    }

    public static function isConnected(): bool {
        return self::getConnection() !== null;
    }

    public static function getCredentials(): array {
        return [
            'host' => self::$host,
            'database' => self::$dbName,
            'user' => self::$username
        ];
    }
}
