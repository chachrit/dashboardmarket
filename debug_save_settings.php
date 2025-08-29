<?php
// Debug script for save_settings functionality on cPanel
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Save Settings - cPanel Environment</h2>\n";
echo "<pre>\n";

// Check if we're debugging via GET or testing actual save
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';
$test_save = isset($_GET['test_save']) && $_GET['test_save'] === '1';

if ($debug_mode) {
    echo "=== ENVIRONMENT DEBUG MODE ===\n\n";
    
    // 1. Check PHP version and extensions
    echo "1. PHP Environment:\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
    echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
    echo "Script Path: " . __FILE__ . "\n";
    
    // 2. Check if required files exist
    echo "\n2. File Checks:\n";
    $files = ['db.php', 'api.php', 'data/dashboardmarket.sqlite'];
    foreach ($files as $file) {
        $path = __DIR__ . '/' . $file;
        echo "$file: " . (file_exists($path) ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
        if (file_exists($path)) {
            echo "  - Readable: " . (is_readable($path) ? "✓" : "✗") . "\n";
            if ($file !== 'data/dashboardmarket.sqlite') {
                echo "  - Writable: " . (is_writable($path) ? "✓" : "✗") . "\n";
            }
        }
    }
    
    // 3. Check data directory
    echo "\n3. Data Directory:\n";
    $dataDir = __DIR__ . '/data';
    echo "Data dir exists: " . (is_dir($dataDir) ? "✓" : "✗") . "\n";
    if (is_dir($dataDir)) {
        echo "Data dir writable: " . (is_writable($dataDir) ? "✓" : "✗") . "\n";
        echo "Data dir permissions: " . substr(sprintf('%o', fileperms($dataDir)), -4) . "\n";
    }
    
    // 4. Test database connection
    echo "\n4. Database Connection Test:\n";
    try {
        require_once __DIR__ . '/db.php';
        $pdo = dm_db();
        echo "Database connection: ✓ SUCCESS\n";
        echo "Database driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
        
        // Check if table exists
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='dm_settings'");
        $tableExists = $stmt->fetchColumn();
        echo "dm_settings table: " . ($tableExists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
        
        if ($tableExists) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM dm_settings");
            $count = $stmt->fetchColumn();
            echo "Records in dm_settings: $count\n";
        }
        
    } catch (Exception $e) {
        echo "Database connection: ✗ FAILED\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    // 5. Test write permissions
    echo "\n5. Write Permission Test:\n";
    $testFile = __DIR__ . '/test_write.tmp';
    if (file_put_contents($testFile, 'test')) {
        echo "Write test: ✓ SUCCESS\n";
        unlink($testFile);
    } else {
        echo "Write test: ✗ FAILED\n";
    }
    
} elseif ($test_save) {
    echo "=== TEST SAVE FUNCTIONALITY ===\n\n";
    
    try {
        require_once __DIR__ . '/db.php';
        
        // Test data
        $platform = 'shopee';
        $testData = [
            'partner_id' => 'TEST_' . time(),
            'partner_key' => 'KEY_' . time(),
            'enabled' => 'true',
            'test_field' => 'test_value_' . time()
        ];
        
        echo "1. Testing direct database save:\n";
        foreach ($testData as $key => $value) {
            dm_settings_set($platform, $key, $value);
            echo "  Saved: $platform.$key = $value\n";
        }
        
        echo "\n2. Verifying saved data:\n";
        $savedData = dm_settings_get_all($platform);
        foreach ($testData as $key => $expectedValue) {
            $actualValue = $savedData[$key] ?? 'NOT_FOUND';
            $status = ($actualValue === $expectedValue) ? "✓" : "✗";
            echo "  $status $key: expected='$expectedValue', actual='$actualValue'\n";
        }
        
        echo "\n3. Testing API simulation:\n";
        // Simulate the API save_settings logic
        $_GET['action'] = 'save_settings';
        $_GET['platform'] = $platform;
        
        // Convert boolean to string as API does
        if (isset($testData['enabled'])) {
            $testData['enabled'] = $testData['enabled'] ? 'true' : 'false';
        }
        
        foreach($testData as $key => $value){
            dm_settings_set($platform, $key, is_bool($value) ? ($value ? 'true' : 'false') : $value);
        }
        
        echo "API simulation completed successfully\n";
        
        // Final verification
        echo "\n4. Final verification:\n";
        $finalData = dm_settings_get_all($platform);
        echo "Total records for $platform: " . count($finalData) . "\n";
        foreach ($finalData as $key => $value) {
            echo "  $key = $value\n";
        }
        
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} else {
    // Show usage
    echo "=== SAVE SETTINGS DEBUGGER ===\n\n";
    echo "This script helps debug save_settings issues on cPanel.\n\n";
    echo "Usage:\n";
    echo "1. Environment Check: ?debug=1\n";
    echo "2. Test Save Function: ?test_save=1\n\n";
    
    $currentUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $baseUrl = dirname($currentUrl) . '/' . basename(__FILE__);
    
    echo "Links:\n";
    echo "<a href='{$baseUrl}?debug=1'>Debug Environment</a>\n";
    echo "<a href='{$baseUrl}?test_save=1'>Test Save Function</a>\n";
}

echo "</pre>\n";
?>
