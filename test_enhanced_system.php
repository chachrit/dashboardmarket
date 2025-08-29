<?php
/**
 * Test Enhanced System - ทดสอบระบบ Pagination และ Database Caching
 */

require_once 'pagination_manager.php';

echo "🧪 Testing Enhanced Dashboard Market System\n";
echo str_repeat("=", 60) . "\n";

$platforms = ['shopee', 'lazada'];
$testResults = [];

foreach ($platforms as $platform) {
    echo "\n🔍 Testing $platform:\n";
    echo str_repeat("-", 30) . "\n";
    
    try {
        // ตรวจสอบการตั้งค่า
        $settings = dm_settings_get_all($platform);
        $enabled = ($settings['enabled'] ?? 'false') === 'true';
        
        if (!$enabled) {
            echo "⚠️ $platform disabled - skipping\n";
            continue;
        }
        
        $config = getAPIConfig();
        $api = null;
        
        switch ($platform) {
            case 'shopee':
                $api = new ShopeeAPI('shopee', $config['shopee']);
                break;
            case 'lazada':
                $api = new LazadaAPI('lazada', $config['lazada']);
                break;
        }
        
        if (!$api) {
            echo "❌ Could not create API instance for $platform\n";
            continue;
        }
        
        // Test 1: Database Functions
        echo "1️⃣ Testing database functions...\n";
        
        $isFresh = isDatabaseDataFresh($platform, 30);
        echo "   - Data freshness: " . ($isFresh ? "Fresh (< 30 min)" : "Stale (> 30 min)") . "\n";
        
        $stats = getDatabaseStats($platform);
        echo "   - DB Orders: " . number_format($stats['totalOrders']) . "\n";
        echo "   - DB Sales: ₿" . number_format($stats['totalSales'], 2) . "\n";
        echo "   - Last fetch: " . ($stats['lastFetchTime'] ?: 'Never') . "\n";
        
        // Test 2: Cache vs API Performance
        echo "\n2️⃣ Testing cache vs API performance...\n";
        
        // Cache test
        $startTime = microtime(true);
        $cacheData = getOrdersFromDatabase($platform, date('Y-m-d'), date('Y-m-d'), 10);
        $cacheTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "   - Cache query: {$cacheTime}ms ({$cacheData['total_orders']} orders)\n";
        
        // API test (only if data is stale or no data)
        if (!$isFresh || $stats['totalOrders'] == 0) {
            try {
                echo "   - Testing API connection...\n";
                $connTest = $api->testConnection();
                if ($connTest['success']) {
                    echo "   - API Status: ✅ " . $connTest['message'] . "\n";
                    
                    $startTime = microtime(true);
                    $apiData = $api->getOrders(date('Y-m-d'), date('Y-m-d'), 10);
                    $apiTime = round((microtime(true) - $startTime) * 1000, 2);
                    echo "   - API query: {$apiTime}ms ({$apiData['total_orders']} orders)\n";
                    
                    $speedup = $cacheTime > 0 ? round($apiTime / $cacheTime, 1) : 'N/A';
                    echo "   - Speed improvement: {$speedup}x faster with cache\n";
                } else {
                    echo "   - API Status: ❌ " . $connTest['message'] . "\n";
                }
            } catch (Exception $e) {
                echo "   - API Error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   - Skipping API test (data is fresh)\n";
        }
        
        // Test 3: Pagination Manager
        echo "\n3️⃣ Testing Pagination Manager...\n";
        
        $manager = new PaginationManager($platform, $api);
        $managerStats = $manager->getLastFetchStats();
        
        echo "   - Manager stats:\n";
        echo "     * Total orders: " . number_format($managerStats['total_orders']) . "\n";
        echo "     * Total sales: ₿" . number_format($managerStats['total_sales'], 2) . "\n";
        echo "     * Date range: {$managerStats['oldest_order']} to {$managerStats['newest_order']}\n";
        
        // Test 4: API Endpoint Integration
        echo "\n4️⃣ Testing API endpoints...\n";
        
        $endpoints = [
            'getSummary' => "api.php?action=getSummary&platform=$platform",
            'getOrders' => "api.php?action=getOrders&platform=$platform&limit=5"
        ];
        
        foreach ($endpoints as $name => $url) {
            $startTime = microtime(true);
            $response = @file_get_contents($url);
            $loadTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && $data['success']) {
                    $source = $data['data']['source'] ?? 'unknown';
                    echo "   - $name: ✅ {$loadTime}ms (source: $source)\n";
                } else {
                    echo "   - $name: ❌ " . ($data['error'] ?? 'Unknown error') . "\n";
                }
            } else {
                echo "   - $name: ❌ No response\n";
            }
        }
        
        $testResults[$platform] = [
            'enabled' => true,
            'db_orders' => $stats['totalOrders'],
            'db_sales' => $stats['totalSales'],
            'cache_time' => $cacheTime,
            'data_fresh' => $isFresh
        ];
        
    } catch (Exception $e) {
        echo "❌ Error testing $platform: " . $e->getMessage() . "\n";
        $testResults[$platform] = ['enabled' => false, 'error' => $e->getMessage()];
    }
}

// Summary
echo "\n\n📊 Test Summary:\n";
echo str_repeat("=", 60) . "\n";

foreach ($testResults as $platform => $result) {
    if ($result['enabled']) {
        echo "$platform: ✅ Working\n";
        echo "  - DB Orders: " . number_format($result['db_orders']) . "\n";
        echo "  - DB Sales: ₿" . number_format($result['db_sales'], 2) . "\n";
        echo "  - Cache Speed: {$result['cache_time']}ms\n";
        echo "  - Data: " . ($result['data_fresh'] ? "Fresh" : "Stale") . "\n";
    } else {
        echo "$platform: ❌ " . ($result['error'] ?? 'Disabled') . "\n";
    }
    echo "\n";
}

// Recommendations
echo "💡 Recommendations:\n";
echo str_repeat("-", 30) . "\n";

$totalDbOrders = array_sum(array_column(array_filter($testResults, fn($r) => $r['enabled']), 'db_orders'));

if ($totalDbOrders == 0) {
    echo "🚨 No orders in database. Run: php fetch_orders.php all\n";
} elseif ($totalDbOrders < 100) {
    echo "📈 Few orders in database. Consider fetching historical data:\n";
    echo "   php fetch_orders.php all --from=2024-08-01 --to=" . date('Y-m-d') . "\n";
}

$staleCount = count(array_filter($testResults, fn($r) => isset($r['data_fresh']) && !$r['data_fresh']));
if ($staleCount > 0) {
    echo "⏰ Some data is stale. Set up cron job:\n";
    echo "   0 * * * * /path/to/dashboardmarket/cron_fetch_orders.sh\n";
}

echo "\n✅ System test completed!\n";
echo "📁 Check order_management.php for web interface\n";
echo "🌐 Check index_enhanced.php for improved dashboard\n";

?>
