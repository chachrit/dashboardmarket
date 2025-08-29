<?php
// cPanel Environment Debug Script
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "=== cPANEL ENVIRONMENT DEBUG ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Basic PHP Environment
echo "1. PHP ENVIRONMENT:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Script Path: " . __FILE__ . "\n";
echo "Current Working Directory: " . getcwd() . "\n";
echo "Script Owner: " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner(__FILE__))['name'] ?? 'Unknown' : 'Unknown') . "\n";

// 2. Required Extensions
echo "\n2. PHP EXTENSIONS:\n";
$required_extensions = ['pdo', 'pdo_mysql', 'pdo_sqlite', 'curl', 'json', 'openssl'];
foreach ($required_extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✓ LOADED" : "✗ NOT LOADED") . "\n";
}

// Check for SQL Server extensions
$sqlsrv_extensions = ['pdo_sqlsrv', 'sqlsrv'];
echo "\nSQL Server Extensions:\n";
foreach ($sqlsrv_extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✓ LOADED" : "✗ NOT LOADED") . "\n";
}

// 3. File System Permissions
echo "\n3. FILE SYSTEM:\n";
$check_paths = [
    __DIR__,
    __DIR__ . '/db.php',
    __DIR__ . '/api.php',
    __DIR__ . '/data',
    __DIR__ . '/data/dashboardmarket.sqlite'
];

foreach ($check_paths as $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $readable = is_readable($path) ? "R" : "-";
        $writable = is_writable($path) ? "W" : "-";
        $type = is_dir($path) ? "DIR" : "FILE";
        echo "$type $path: $perms ($readable$writable)\n";
    } else {
        echo "MISSING $path\n";
    }
}

// 4. Environment Variables
echo "\n4. DATABASE ENVIRONMENT VARIABLES:\n";
$db_vars = ['DM_DB_DSN', 'DM_DB_SERVER', 'DM_DB_NAME', 'DM_DB_USER', 'DM_DB_PASS'];
foreach ($db_vars as $var) {
    $value = getenv($var);
    if ($value !== false) {
        // Mask password
        if (strpos($var, 'PASS') !== false) {
            $value = str_repeat('*', strlen($value));
        }
        echo "$var: $value\n";
    } else {
        echo "$var: NOT SET\n";
    }
}

// 5. Database Connection Test
echo "\n5. DATABASE CONNECTION TEST:\n";
try {
    require_once __DIR__ . '/db.php';
    $pdo = dm_db();
    
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "✓ Database connected\n";
    echo "Driver: $driver\n";
    
    if ($driver === 'sqlite') {
        echo "Database file: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n";
        $dbPath = __DIR__ . '/data/dashboardmarket.sqlite';
        if (file_exists($dbPath)) {
            echo "SQLite file size: " . filesize($dbPath) . " bytes\n";
            echo "SQLite file writable: " . (is_writable($dbPath) ? "YES" : "NO") . "\n";
        }
    } elseif ($driver === 'sqlsrv') {
        $stmt = $pdo->query("SELECT DB_NAME() as dbname");
        $dbInfo = $stmt->fetch();
        echo "SQL Server Database: " . $dbInfo['dbname'] . "\n";
    }
    
    // Test table existence
    if ($driver === 'sqlite') {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='dm_settings'");
        $tableExists = $stmt->fetchColumn();
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'dm_settings'");
        $tableExists = $stmt->fetchColumn() > 0;
    }
    
    echo "dm_settings table: " . ($tableExists ? "EXISTS" : "NOT FOUND") . "\n";
    
    if ($tableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM dm_settings");
        $count = $stmt->fetchColumn();
        echo "Records in dm_settings: $count\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// 6. Write Test
echo "\n6. WRITE PERMISSION TEST:\n";
$testFile = __DIR__ . '/cpanel_write_test_' . time() . '.tmp';
try {
    if (file_put_contents($testFile, 'cPanel write test: ' . date('Y-m-d H:i:s'))) {
        echo "✓ Write test successful\n";
        unlink($testFile);
    } else {
        echo "✗ Write test failed\n";
    }
} catch (Exception $e) {
    echo "✗ Write test error: " . $e->getMessage() . "\n";
}

// 7. Data Directory Test
echo "\n7. DATA DIRECTORY TEST:\n";
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    echo "Creating data directory...\n";
    try {
        mkdir($dataDir, 0755, true);
        echo "✓ Data directory created\n";
    } catch (Exception $e) {
        echo "✗ Failed to create data directory: " . $e->getMessage() . "\n";
    }
}

if (is_dir($dataDir)) {
    echo "Data directory exists\n";
    echo "Data directory writable: " . (is_writable($dataDir) ? "YES" : "NO") . "\n";
    echo "Data directory permissions: " . substr(sprintf('%o', fileperms($dataDir)), -4) . "\n";
}

// 8. PHP Configuration
echo "\n8. PHP CONFIGURATION:\n";
$php_settings = [
    'memory_limit',
    'max_execution_time',
    'upload_max_filesize',
    'post_max_size',
    'allow_url_fopen',
    'display_errors',
    'log_errors',
    'error_log'
];

foreach ($php_settings as $setting) {
    echo "$setting: " . ini_get($setting) . "\n";
}

// 9. Request Information (if this is called via HTTP)
if (isset($_SERVER['REQUEST_METHOD'])) {
    echo "\n9. REQUEST INFORMATION:\n";
    echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
    echo "Query String: " . ($_SERVER['QUERY_STRING'] ?? '') . "\n";
    echo "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? '') . "\n";
    echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\n";
    echo "Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "Save this output and compare with localhost environment.\n";
?>
