<?php
require_once 'db.php';

echo "=== FINAL VERIFICATION ===\n\n";

$pdo = dm_db();
$stmt = $pdo->query("SELECT scope, name, value, updated_at FROM dm_settings WHERE scope IN ('shopee', 'lazada') ORDER BY scope, name");
$settings = $stmt->fetchAll();

if (empty($settings)) {
    echo "No settings found for shopee or lazada\n";
} else {
    echo "Found settings in real database:\n\n";
    $currentScope = '';
    foreach ($settings as $setting) {
        if ($setting['scope'] !== $currentScope) {
            $currentScope = $setting['scope'];
            echo "[$currentScope]\n";
        }
        $updatedTime = $setting['updated_at'] ? date('Y-m-d H:i:s', $setting['updated_at']) : 'N/A';
        echo "  {$setting['name']} = {$setting['value']} (updated: $updatedTime)\n";
    }
}

echo "\nDatabase driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
echo "Total records: " . count($settings) . "\n";
?>
