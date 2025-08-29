<?php
require_once 'db.php';

echo "Testing save_settings functionality...\n\n";

// Test data
$testData = [
    'partner_id' => '123456',
    'partner_key' => 'test_key',
    'shop_id' => '789',
    'access_token' => 'test_token',
    'enabled' => 'true'
];

echo "1. Testing dm_settings_set function directly:\n";
try {
    dm_settings_set('shopee', 'partner_id', '123456');
    dm_settings_set('shopee', 'partner_key', 'test_key');
    dm_settings_set('shopee', 'enabled', 'true');
    echo "✓ Settings saved directly via dm_settings_set\n";
    
    // Check if data was saved
    $partnerId = dm_settings_get('shopee', 'partner_id');
    $enabled = dm_settings_get('shopee', 'enabled');
    echo "✓ Retrieved partner_id: $partnerId\n";
    echo "✓ Retrieved enabled: $enabled\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing save_settings via simulated API call:\n";

// Simulate POST data
$_POST = [];
$_GET['action'] = 'save_settings';
$_GET['platform'] = 'shopee';

// Simulate JSON input
$jsonData = json_encode($testData);
file_put_contents('php://temp', $jsonData);

// Test the API handler
ob_start();
try {
    // Include api.php logic for save_settings
    $platform = $_GET['platform'] ?? '';
    $data = $testData; // Use test data directly
    
    if ($platform && $data) {
        // Convert boolean 'enabled' to string 'true'/'false' before saving
        if (isset($data['enabled'])) {
            $data['enabled'] = $data['enabled'] ? 'true' : 'false';
        }
        
        foreach($data as $key => $value){
            dm_settings_set($platform, $key, is_bool($value) ? ($value ? 'true' : 'false') : $value);
            echo "Saved: $platform.$key = $value\n";
        }
        
        echo "✓ API save_settings simulation completed\n";
    }
    
} catch (Exception $e) {
    echo "✗ API Error: " . $e->getMessage() . "\n";
}
ob_end_flush();

echo "\n3. Verifying all saved data:\n";
$allSettings = dm_settings_get_all('shopee');
if (empty($allSettings)) {
    echo "✗ No settings found for 'shopee' platform\n";
} else {
    echo "✓ Found settings:\n";
    foreach ($allSettings as $key => $value) {
        echo "  $key = $value\n";
    }
}
?>
