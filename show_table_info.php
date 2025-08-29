<?php
require_once 'db.php';

echo "=== DATABASE TABLE INFORMATION ===\n\n";

try {
    $pdo = dm_db();
    
    // Get database name
    $stmt = $pdo->query("SELECT DB_NAME() as dbname");
    $dbInfo = $stmt->fetch();
    echo "Database: {$dbInfo['dbname']}\n";
    
    // Get table structure
    echo "\nTable: dm_settings\n";
    echo "Structure:\n";
    $stmt = $pdo->query("
        SELECT 
            COLUMN_NAME, 
            DATA_TYPE, 
            IS_NULLABLE, 
            CHARACTER_MAXIMUM_LENGTH,
            COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'dm_settings'
        ORDER BY ORDINAL_POSITION
    ");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $col) {
        $length = $col['CHARACTER_MAXIMUM_LENGTH'] ? "({$col['CHARACTER_MAXIMUM_LENGTH']})" : '';
        $nullable = $col['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $col['COLUMN_DEFAULT'] ? " DEFAULT {$col['COLUMN_DEFAULT']}" : '';
        echo "  {$col['COLUMN_NAME']}: {$col['DATA_TYPE']}$length $nullable$default\n";
    }
    
    // Get primary key info
    echo "\nPrimary Key:\n";
    $stmt = $pdo->query("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'dm_settings' 
        AND CONSTRAINT_NAME LIKE 'PK_%'
        ORDER BY ORDINAL_POSITION
    ");
    $pkColumns = $stmt->fetchAll();
    $pkList = array_map(function($col) { return $col['COLUMN_NAME']; }, $pkColumns);
    echo "  " . implode(', ', $pkList) . "\n";
    
    // Get current record count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM dm_settings");
    $count = $stmt->fetch();
    echo "\nCurrent Records: {$count['count']}\n";
    
    // Show sample data grouped by scope
    echo "\nSample Data by Platform:\n";
    $stmt = $pdo->query("
        SELECT scope, COUNT(*) as setting_count, 
               MAX(updated_at) as last_updated
        FROM dm_settings 
        GROUP BY scope
        ORDER BY scope
    ");
    $scopes = $stmt->fetchAll();
    
    foreach ($scopes as $scope) {
        $lastUpdated = $scope['last_updated'] ? date('Y-m-d H:i:s', $scope['last_updated']) : 'N/A';
        echo "  {$scope['scope']}: {$scope['setting_count']} settings (last updated: $lastUpdated)\n";
    }
    
    // Show detailed data for each platform
    echo "\nDetailed Settings:\n";
    $stmt = $pdo->query("SELECT scope, name, LEFT(value, 50) as value_preview FROM dm_settings ORDER BY scope, name");
    $settings = $stmt->fetchAll();
    
    $currentScope = '';
    foreach ($settings as $setting) {
        if ($setting['scope'] !== $currentScope) {
            $currentScope = $setting['scope'];
            echo "\n[$currentScope]\n";
        }
        $preview = strlen($setting['value_preview']) < 50 ? 
                  $setting['value_preview'] : 
                  $setting['value_preview'] . '...';
        echo "  {$setting['name']} = $preview\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Settings from settings.php are saved to:\n";
echo "- Database: realtime_marketplace\n";
echo "- Table: dm_settings\n";
echo "- Structure: scope (platform), name (setting key), value (setting value), updated_at (timestamp)\n";
echo "- Primary Key: scope + name (composite)\n";
?>
