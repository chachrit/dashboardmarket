<?php
// Test database connection to real SQL Server database
echo "=== REAL DATABASE CONNECTION TEST ===\n\n";

// 1. Test database connection
echo "1. Testing Database Connection:\n";
try {
    require_once 'db.php';
    $pdo = dm_db();
    
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "✓ Database connected successfully\n";
    echo "Driver: $driver\n";
    
    if ($driver === 'sqlsrv') {
        echo "✓ Using SQL Server (production database)\n";
        
        // Get SQL Server version info
        $stmt = $pdo->query("SELECT @@VERSION as version");
        $version = $stmt->fetch();
        echo "SQL Server Version: " . substr($version['version'], 0, 100) . "...\n";
        
        // Get current database name
        $stmt = $pdo->query("SELECT DB_NAME() as dbname");
        $dbInfo = $stmt->fetch();
        echo "Current Database: " . $dbInfo['dbname'] . "\n";
        
    } else {
        echo "⚠ Warning: Still using $driver (not SQL Server)\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check table structure
echo "\n2. Checking Table Structure:\n";
try {
    if ($driver === 'sqlsrv') {
        $stmt = $pdo->query("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'dm_settings'
            ORDER BY ORDINAL_POSITION
        ");
        $columns = $stmt->fetchAll();
        
        if (empty($columns)) {
            echo "⚠ Table dm_settings not found. Creating...\n";
            
            // Create the table
            $pdo->exec("
                CREATE TABLE [dbo].[dm_settings] (
                    [scope] NVARCHAR(50) NOT NULL,
                    [name] NVARCHAR(100) NOT NULL,
                    [value] NVARCHAR(MAX) NULL,
                    [updated_at] BIGINT NULL,
                    CONSTRAINT [PK_dm_settings] PRIMARY KEY ([scope],[name])
                )
            ");
            echo "✓ Table dm_settings created\n";
        } else {
            echo "✓ Table dm_settings exists\n";
            echo "Columns:\n";
            foreach ($columns as $col) {
                echo "- {$col['COLUMN_NAME']} ({$col['DATA_TYPE']}" . 
                     ($col['CHARACTER_MAXIMUM_LENGTH'] ? "({$col['CHARACTER_MAXIMUM_LENGTH']})" : '') . 
                     ", " . ($col['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL') . ")\n";
            }
        }
    }
} catch (Exception $e) {
    echo "✗ Table structure check failed: " . $e->getMessage() . "\n";
}

// 3. Test data operations
echo "\n3. Testing Data Operations:\n";
try {
    $testPlatform = 'test_sql_' . time();
    $testData = [
        'partner_id' => 'SQL_TEST_' . time(),
        'partner_key' => 'SQL_KEY_' . time(),
        'enabled' => 'true',
        'test_timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo "Testing with platform: $testPlatform\n";
    
    // Test individual saves
    foreach ($testData as $key => $value) {
        dm_settings_set($testPlatform, $key, $value);
        echo "✓ Saved: $key = $value\n";
    }
    
    // Test retrieval
    echo "\nTesting retrieval:\n";
    $retrieved = dm_settings_get_all($testPlatform);
    foreach ($testData as $key => $expectedValue) {
        $actualValue = $retrieved[$key] ?? 'NOT_FOUND';
        $status = ($actualValue === $expectedValue) ? "✓" : "✗";
        echo "$status $key: expected='$expectedValue', actual='$actualValue'\n";
    }
    
    // Clean up test data
    $pdo = dm_db();
    $stmt = $pdo->prepare("DELETE FROM dm_settings WHERE scope = ?");
    $stmt->execute([$testPlatform]);
    echo "✓ Test data cleaned up\n";
    
} catch (Exception $e) {
    echo "✗ Data operations failed: " . $e->getMessage() . "\n";
}

// 4. Test API save_settings with real database
echo "\n4. Testing API save_settings with Real Database:\n";
try {
    $testPlatform = 'shopee_test';
    $testApiData = [
        'partner_id' => '1183136',
        'shop_id' => '225729332',
        'partner_key' => 'test_key_' . time(),
        'access_token' => 'test_token_' . time(),
        'enabled' => true
    ];
    
    // Simulate the API save_settings logic
    $data = $testApiData;
    
    // Convert boolean 'enabled' to string 'true'/'false'
    if (isset($data['enabled'])) {
        $data['enabled'] = $data['enabled'] ? 'true' : 'false';
    }
    
    foreach($data as $key => $value){
        dm_settings_set($testPlatform, $key, is_bool($value) ? ($value ? 'true' : 'false') : $value);
        echo "API Saved: $testPlatform.$key = " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }
    
    // Verify
    $apiSaved = dm_settings_get_all($testPlatform);
    echo "\nAPI Verification:\n";
    foreach ($data as $key => $expectedValue) {
        $expected = is_bool($expectedValue) ? ($expectedValue ? 'true' : 'false') : $expectedValue;
        $actual = $apiSaved[$key] ?? 'NOT_FOUND';
        $status = ($actual === $expected) ? "✓" : "✗";
        echo "$status $key: expected='$expected', actual='$actual'\n";
    }
    
} catch (Exception $e) {
    echo "✗ API test failed: " . $e->getMessage() . "\n";
}

// 5. Show all current settings in real database
echo "\n5. Current Settings in Real Database:\n";
try {
    $pdo = dm_db();
    $stmt = $pdo->query("SELECT scope, name, value, updated_at FROM dm_settings ORDER BY scope, name");
    $allSettings = $stmt->fetchAll();
    
    if (empty($allSettings)) {
        echo "No settings found in database\n";
    } else {
        echo "Found " . count($allSettings) . " settings in real database:\n";
        $currentScope = '';
        foreach ($allSettings as $setting) {
            if ($setting['scope'] !== $currentScope) {
                $currentScope = $setting['scope'];
                echo "\n[$currentScope]\n";
            }
            $updatedTime = $setting['updated_at'] ? date('Y-m-d H:i:s', $setting['updated_at']) : 'N/A';
            echo "  {$setting['name']} = {$setting['value']} (updated: $updatedTime)\n";
        }
    }
} catch (Exception $e) {
    echo "Error reading current settings: " . $e->getMessage() . "\n";
}

echo "\n=== REAL DATABASE TEST COMPLETED ===\n";
?>
