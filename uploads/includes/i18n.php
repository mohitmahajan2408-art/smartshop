<?php
/**
 * ShopSmart India - Internationalization (i18n) Handler
 * Supports: en (English), mr (मराठी), hi (हिंदी)
 */

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/session.php';
}

class I18n {
    private static string $currentLang = 'en';
    private static array $translations = [];
    private static array $supportedLangs = ['en', 'mr', 'hi'];

    public static function init(): void {
        // 1. Check GET parameter (?lang=mr)
        if (isset($_GET['lang']) && in_array($_GET['lang'], self::$supportedLangs, true)) {
            self::$currentLang = $_GET['lang'];
            $_SESSION['lang'] = self::$currentLang;
            setcookie('shopsmart_lang', self::$currentLang, time() + (86400 * 365), '/');
        }
        // 2. Check Session
        elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], self::$supportedLangs, true)) {
            self::$currentLang = $_SESSION['lang'];
        }
        // 3. Check Cookie
        elseif (isset($_COOKIE['shopsmart_lang']) && in_array($_COOKIE['shopsmart_lang'], self::$supportedLangs, true)) {
            self::$currentLang = $_COOKIE['shopsmart_lang'];
            $_SESSION['lang'] = self::$currentLang;
        } else {
            self::$currentLang = 'en';
        }

        // Load translations file
        $file = __DIR__ . '/../lang/' . self::$currentLang . '.php';
        if (file_exists($file)) {
            self::$translations = include $file;
        } else {
            self::$translations = include __DIR__ . '/../lang/en.php';
        }
    }

    public static function get(string $key, array $replace = []): string {
        $text = self::$translations[$key] ?? $key;
        if (!empty($replace)) {
            foreach ($replace as $placeholder => $value) {
                $text = str_replace(':' . $placeholder, $value, $text);
            }
        }
        return $text;
    }

    public static function getCurrentLang(): string {
        return self::$currentLang;
    }

    public static function getSupportedLanguages(): array {
        return [
            'en' => ['name' => 'English', 'native' => 'English', 'flag' => 'IN'],
            'mr' => ['name' => 'Marathi', 'native' => 'मराठी', 'flag' => 'MH'],
            'hi' => ['name' => 'Hindi', 'native' => 'हिंदी', 'flag' => 'IN'],
        ];
    }
}

// Initialize immediately
I18n::init();

function __(string $key, array $replace = []): string {
    return I18n::get($key, $replace);
}

function t(string $key, array $replace = []): string {
    return I18n::get($key, $replace);
}

function currentLang(): string {
    return I18n::getCurrentLang();
}
