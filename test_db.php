<?php
require_once 'db.php';

echo "Testing database connection...\n";
try {
    $pdo = dm_db();
    echo "Database connected successfully\n";
    
    // Check if dm_settings table exists and has data
    echo "\nChecking dm_settings table:\n";
    $stmt = $pdo->query('SELECT * FROM dm_settings ORDER BY scope, name');
    $rows = $stmt->fetchAll();
    
    if (empty($rows)) {
        echo "No data found in dm_settings table\n";
    } else {
        echo "Found " . count($rows) . " records:\n";
        foreach($rows as $row) {
            echo "Scope: {$row['scope']}, Name: {$row['name']}, Value: {$row['value']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
