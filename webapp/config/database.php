<?php
/**
 * TransKwanza Web App - Database Configuration
 */

// Database configuration
define('DB_PATH', __DIR__ . '/../database/transkwanza.db');
define('DB_TYPE', 'sqlite'); // sqlite or mysql

// MySQL Configuration (opcional - se preferir MySQL)
define('DB_HOST', 'localhost');
define('DB_NAME', 'transkwanza');
define('DB_USER', 'root');
define('DB_PASS', '');

// App Configuration
define('APP_NAME', 'TransKwanza');
define('APP_URL', 'http://localhost/webapp');
define('API_URL', APP_URL . '/api');

// Security
define('JWT_SECRET', 'your-secret-key-change-this-in-production');
define('SESSION_LIFETIME', 86400); // 24 hours

// Platform Settings
define('PLATFORM_FEE', 0.03); // 3%
define('PROPOSAL_EXPIRY_HOURS', 24);
define('EXCHANGE_CACHE_MINUTES', 30);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

/**
 * Get Database Connection
 */
function getDB() {
    static $db = null;

    if ($db === null) {
        try {
            if (DB_TYPE === 'sqlite') {
                $db = new PDO('sqlite:' . DB_PATH);
            } else {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $db = new PDO($dsn, DB_USER, DB_PASS);
            }

            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $db;
}

/**
 * Initialize Database
 */
function initDatabase() {
    $db = getDB();
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');

    try {
        $db->exec($schema);
        return true;
    } catch (PDOException $e) {
        error_log('Database init error: ' . $e->getMessage());
        return false;
    }
}

// Auto-initialize database if not exists
if (DB_TYPE === 'sqlite' && !file_exists(DB_PATH)) {
    initDatabase();
}
