<?php
/**
 * Configuration file for Dashboard Market
 * Copy this to config.php and modify for your environment
 */

// Environment settings
define('ENVIRONMENT', 'production'); // 'development' or 'production'
define('DEBUG_MODE', false); // Set to false in production

// Database settings
define('DB_PATH', __DIR__ . '/data/dashboardmarket.sqlite');
define('DB_BACKUP_PATH', __DIR__ . '/data/backups/');

// API Settings
define('API_TIMEOUT', 30); // seconds
define('API_MAX_RETRIES', 3);

// Security settings
define('SECURE_COOKIES', true); // Set to true if using HTTPS
define('COOKIE_LIFETIME', 86400 * 365); // 1 year in seconds

// Logging
define('LOG_PATH', __DIR__ . '/logs/');
define('LOG_LEVEL', 'error'); // 'debug', 'info', 'warning', 'error'

// SSL/TLS settings
define('SSL_VERIFY_PEER', true); // Set to false only for development
define('SSL_VERIFY_HOST', true);

// Rate limiting (requests per minute per IP)
define('RATE_LIMIT_REQUESTS', 60);
define('RATE_LIMIT_WINDOW', 60);

// Timezone
define('TIMEZONE', 'Asia/Bangkok');

return [
    'environment' => ENVIRONMENT,
    'debug' => DEBUG_MODE,
    'database' => [
        'path' => DB_PATH,
        'backup_path' => DB_BACKUP_PATH
    ],
    'api' => [
        'timeout' => API_TIMEOUT,
        'max_retries' => API_MAX_RETRIES
    ],
    'security' => [
        'secure_cookies' => SECURE_COOKIES,
        'cookie_lifetime' => COOKIE_LIFETIME
    ],
    'logging' => [
        'path' => LOG_PATH,
        'level' => LOG_LEVEL
    ],
    'ssl' => [
        'verify_peer' => SSL_VERIFY_PEER,
        'verify_host' => SSL_VERIFY_HOST
    ],
    'rate_limit' => [
        'requests' => RATE_LIMIT_REQUESTS,
        'window' => RATE_LIMIT_WINDOW
    ],
    'timezone' => TIMEZONE
];
