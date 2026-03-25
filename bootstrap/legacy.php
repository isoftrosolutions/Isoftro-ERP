<?php

/**
 * Legacy Bootstrap Logic
 * Contains custom db() and App singleton for legacy compatibility
 */

// Load database constants
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
}

// Load main configuration
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}

// Load legacy authentication functions (lockout, pass verify, etc.)
if (file_exists(__DIR__ . '/../app/Http/Middleware/auth.php')) {
    require_once __DIR__ . '/../app/Http/Middleware/auth.php';
}


// Database connection function
if (!function_exists('db')) {
    function db() {
        static $pdo = null;
        if ($pdo === null) {
            try {
                $host = defined('DB_HOST') ? DB_HOST : (env('DB_HOST') ?: '127.0.0.1');
                $name = defined('DB_NAME') ? DB_NAME : (env('DB_DATABASE') ?: 'erp_db');
                $user = defined('DB_USER') ? DB_USER : (env('DB_USERNAME') ?: 'root');
                $pass = defined('DB_PASS') ? DB_PASS : (env('DB_PASSWORD') ?: '');
                
                $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                error_log("Legacy DB connection failed: " . $e->getMessage());
                return null;
            }
        }
        return $pdo;
    }
}

if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        return db();
    }
}

// Legacy App Singleton
class LegacyApp {
    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
