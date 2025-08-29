<?php
require_once 'db.php';

echo "=== DATABASE STRUCTURE ANALYSIS ===\n\n";

try {
    $pdo = dm_db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Database driver: $driver\n";
    
    // Check current table structure
    if ($driver === 'sqlite') {
        $stmt = $pdo->query("PRAGMA table_info(dm_settings)");
        $columns = $stmt->fetchAll();
        
        echo "\nCurrent dm_settings table structure:\n";
        foreach ($columns as $col) {
            echo "- {$col['name']} ({$col['type']})\n";
        }
        
        // Check if we have the old structure
        $hasOldStructure = false;
        $hasNewStructure = false;
        
        foreach ($columns as $col) {
            if ($col['name'] === 'platform') $hasOldStructure = true;
            if ($col['name'] === 'scope') $hasNewStructure = true;
        }
        
        echo "\nStructure analysis:\n";
        echo "- Has old structure (platform, setting_key, setting_value): " . ($hasOldStructure ? "YES" : "NO") . "\n";
        echo "- Has new structure (scope, name, value): " . ($hasNewStructure ? "YES" : "NO") . "\n";
        
        if ($hasOldStructure && !$hasNewStructure) {
            echo "\n=== MIGRATION NEEDED ===\n";
            
            // Show current data
            echo "\nCurrent data in old format:\n";
            $stmt = $pdo->query("SELECT * FROM dm_settings ORDER BY platform, setting_key");
            $oldData = $stmt->fetchAll();
            
            foreach ($oldData as $row) {
                echo "Platform: {$row['platform']}, Key: {$row['setting_key']}, Value: {$row['setting_value']}\n";
            }
            
            // Ask for migration
            echo "\nDo you want to migrate to new structure? (y/n): ";
            $handle = fopen("php://stdin", "r");
            $input = trim(fgets($handle));
            fclose($handle);
            
            if (strtolower($input) === 'y') {
                echo "\nStarting migration...\n";
                
                // Create backup
                $backupTable = 'dm_settings_backup_' . date('Ymd_His');
                $pdo->exec("CREATE TABLE $backupTable AS SELECT * FROM dm_settings");
                echo "✓ Backup created: $backupTable\n";
                
                // Store old data
                $migrateData = [];
                foreach ($oldData as $row) {
                    $migrateData[] = [
                        'scope' => $row['platform'],
                        'name' => $row['setting_key'], 
                        'value' => $row['setting_value'],
                        'updated_at' => strtotime($row['updated_at'] ?? 'now')
                    ];
                }
                
                // Drop old table and create new one
                $pdo->exec("DROP TABLE dm_settings");
                echo "✓ Old table dropped\n";
                
                // Create new table structure
                $pdo->exec('CREATE TABLE dm_settings (
                    scope TEXT NOT NULL,
                    name TEXT NOT NULL,
                    value TEXT,
                    updated_at INTEGER,
                    PRIMARY KEY (scope, name)
                )');
                echo "✓ New table structure created\n";
                
                // Insert migrated data
                $stmt = $pdo->prepare('INSERT INTO dm_settings (scope, name, value, updated_at) VALUES (?,?,?,?)');
                foreach ($migrateData as $data) {
                    $stmt->execute([$data['scope'], $data['name'], $data['value'], $data['updated_at']]);
                }
                echo "✓ Data migrated: " . count($migrateData) . " records\n";
                
                echo "\nMigration completed successfully!\n";
            } else {
                echo "\nMigration skipped.\n";
            }
        } elseif ($hasNewStructure) {
            echo "\n=== CORRECT STRUCTURE DETECTED ===\n";
            
            // Show current data
            echo "\nCurrent data:\n";
            $stmt = $pdo->query("SELECT * FROM dm_settings ORDER BY scope, name");
            $data = $stmt->fetchAll();
            
            foreach ($data as $row) {
                echo "Scope: {$row['scope']}, Name: {$row['name']}, Value: {$row['value']}\n";
            }
        }
        
    } else {
        echo "Non-SQLite database detected. Manual structure check needed.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
