<?php
// Debug API test for troubleshooting 500 errors

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>API Debug Test</h1>";

echo "<h2>1. Basic PHP Check</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current working directory: " . getcwd() . "<br>";

echo "<h2>2. File Existence Check</h2>";
$files = ['config.php', 'db.php', 'api.php'];
foreach ($files as $file) {
    echo "$file: " . (file_exists($file) ? '✅ EXISTS' : '❌ NOT FOUND') . "<br>";
}

echo "<h2>3. Directory Check</h2>";
$dirs = ['data', 'logs'];
foreach ($dirs as $dir) {
    echo "$dir: " . (is_dir($dir) ? '✅ EXISTS' : '❌ NOT FOUND');
    if (is_dir($dir)) {
        echo " (writable: " . (is_writable($dir) ? '✅ YES' : '❌ NO') . ")";
    }
    echo "<br>";
}

echo "<h2>4. Extensions Check</h2>";
$extensions = ['pdo', 'pdo_sqlite', 'curl', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? '✅ LOADED' : '❌ NOT LOADED') . "<br>";
}

echo "<h2>5. Config Load Test</h2>";
try {
    if (file_exists('config.php')) {
        require_once 'config.php';
        echo "Config loaded successfully ✅<br>";
        echo "Environment: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'NOT DEFINED') . "<br>";
        echo "Debug Mode: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'TRUE' : 'FALSE') : 'NOT DEFINED') . "<br>";
    } else {
        echo "❌ config.php not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Database Test</h2>";
try {
    require_once 'db.php';
    $pdo = dm_db();
    echo "Database connection: ✅ SUCCESS<br>";
    echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";
    
    // Test settings table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM dm_settings");
    $result = $stmt->fetch();
    echo "Settings table: ✅ " . $result['count'] . " records<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>7. API Functions Test</h2>";
try {
    require_once 'api.php';
    echo "API file loaded: ✅ SUCCESS<br>";
    
    // Test getAPIConfig function
    if (function_exists('getAPIConfig')) {
        $config = getAPIConfig();
        echo "getAPIConfig function: ✅ SUCCESS<br>";
        echo "Platforms configured: " . implode(', ', array_keys($config)) . "<br>";
    } else {
        echo "❌ getAPIConfig function not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ API error: " . $e->getMessage() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
}

echo "<h2>8. Direct API Test</h2>";
try {
    // Simulate API call
    $_GET['action'] = 'test_connection';
    $_GET['platform'] = 'shopee';
    
    if (function_exists('handle_request')) {
        ob_start();
        $result = handle_request();
        ob_end_clean();
        
        echo "API handle_request: ✅ SUCCESS<br>";
        echo "Result: <pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "❌ handle_request function not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ API test error: " . $e->getMessage() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Stack trace:<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Done</h2>";
?>
