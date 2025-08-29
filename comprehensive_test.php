<?php
// Comprehensive test for settings save functionality
echo "=== COMPREHENSIVE SETTINGS SAVE TEST ===\n\n";

// 1. Test database connection and functions
echo "1. Testing Database Functions:\n";
try {
    require_once 'db.php';
    
    $testPlatform = 'test_platform';
    $testKey = 'test_key';
    $testValue = 'test_value_' . time();
    
    // Test dm_settings_set
    dm_settings_set($testPlatform, $testKey, $testValue);
    echo "✓ dm_settings_set works\n";
    
    // Test dm_settings_get
    $retrieved = dm_settings_get($testPlatform, $testKey);
    if ($retrieved === $testValue) {
        echo "✓ dm_settings_get works\n";
    } else {
        echo "✗ dm_settings_get failed - expected: $testValue, got: $retrieved\n";
    }
    
    // Test dm_settings_get_all
    $allSettings = dm_settings_get_all($testPlatform);
    if (isset($allSettings[$testKey]) && $allSettings[$testKey] === $testValue) {
        echo "✓ dm_settings_get_all works\n";
    } else {
        echo "✗ dm_settings_get_all failed\n";
    }
    
    // Clean up test data
    $pdo = dm_db();
    $stmt = $pdo->prepare('DELETE FROM dm_settings WHERE scope = ?');
    $stmt->execute([$testPlatform]);
    
} catch (Exception $e) {
    echo "✗ Database test failed: " . $e->getMessage() . "\n";
}

// 2. Test API endpoint directly
echo "\n2. Testing API Save Settings Function:\n";
try {
    // Simulate the save_settings API call
    $_GET['action'] = 'save_settings';
    $_GET['platform'] = 'test_api';
    
    $testData = [
        'partner_id' => 'API_TEST_' . time(),
        'partner_key' => 'API_KEY_' . time(),
        'enabled' => true,
        'test_field' => 'api_value'
    ];
    
    // Simulate JSON input
    $jsonInput = json_encode($testData);
    
    // Test the logic from api.php save_settings case
    $platform = $_GET['platform'];
    $data = json_decode($jsonInput, true);
    
    if (!$platform) {
        echo "✗ Platform required\n";
    } elseif (!$data) {
        echo "✗ Invalid data\n";
    } else {
        // Convert boolean 'enabled' to string 'true'/'false' before saving
        if (isset($data['enabled'])) {
            $data['enabled'] = $data['enabled'] ? 'true' : 'false';
        }
        
        foreach($data as $key => $value){
            dm_settings_set($platform, $key, is_bool($value) ? ($value ? 'true' : 'false') : $value);
            echo "Saved: $platform.$key = " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
        }
        
        echo "✓ API save_settings logic works\n";
        
        // Verify saved data
        $savedData = dm_settings_get_all($platform);
        echo "Verification:\n";
        foreach ($data as $key => $expectedValue) {
            $expected = is_bool($expectedValue) ? ($expectedValue ? 'true' : 'false') : $expectedValue;
            $actual = $savedData[$key] ?? 'NOT_FOUND';
            $status = ($actual === $expected) ? "✓" : "✗";
            echo "  $status $key: expected='$expected', actual='$actual'\n";
        }
    }
    
    // Clean up
    $pdo = dm_db();
    $stmt = $pdo->prepare('DELETE FROM dm_settings WHERE scope = ?');
    $stmt->execute(['test_api']);
    
} catch (Exception $e) {
    echo "✗ API test failed: " . $e->getMessage() . "\n";
}

// 3. Test real HTTP API call
echo "\n3. Testing Real HTTP API Call:\n";
try {
    $testDataHttp = [
        'partner_id' => 'HTTP_TEST_' . time(),
        'partner_key' => 'HTTP_KEY_' . time(),
        'enabled' => true
    ];
    
    $postData = json_encode($testDataHttp);
    $url = 'http://localhost/dashboardmarket/api.php?action=save_settings&platform=http_test';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "✗ cURL error: $curlError\n";
    } elseif ($httpCode !== 200) {
        echo "✗ HTTP error: $httpCode\n";
        echo "Response: $response\n";
    } else {
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            echo "✓ HTTP API call successful\n";
            echo "Response: " . json_encode($result) . "\n";
            
            // Verify in database
            $httpSavedData = dm_settings_get_all('http_test');
            if (count($httpSavedData) > 0) {
                echo "✓ Data saved to database via HTTP API\n";
                echo "Saved data: " . json_encode($httpSavedData) . "\n";
            } else {
                echo "✗ No data found in database after HTTP API call\n";
            }
        } else {
            echo "✗ API call failed\n";
            echo "Response: $response\n";
        }
    }
    
    // Clean up
    $pdo = dm_db();
    $stmt = $pdo->prepare('DELETE FROM dm_settings WHERE scope = ?');
    $stmt->execute(['http_test']);
    
} catch (Exception $e) {
    echo "✗ HTTP test failed: " . $e->getMessage() . "\n";
}

// 4. Check current settings data
echo "\n4. Current Settings in Database:\n";
try {
    $pdo = dm_db();
    $stmt = $pdo->query('SELECT scope, name, value FROM dm_settings ORDER BY scope, name');
    $allSettings = $stmt->fetchAll();
    
    if (empty($allSettings)) {
        echo "No settings found in database\n";
    } else {
        echo "Found " . count($allSettings) . " settings:\n";
        $currentScope = '';
        foreach ($allSettings as $setting) {
            if ($setting['scope'] !== $currentScope) {
                $currentScope = $setting['scope'];
                echo "\n[$currentScope]\n";
            }
            echo "  {$setting['name']} = {$setting['value']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error reading settings: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>
