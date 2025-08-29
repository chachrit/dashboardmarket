#!/usr/bin/env php
<?php
/**
 * Order Fetcher CLI Tool
 * เครื่องมือดึงข้อมูล Orders ทั้งหมดจาก API โดยใช้ Pagination
 * 
 * Usage:
 *   php fetch_orders.php shopee
 *   php fetch_orders.php lazada --date=2024-08-28
 *   php fetch_orders.php all --from=2024-08-01 --to=2024-08-31
 */

require_once __DIR__ . '/pagination_manager.php';

// Parse command line arguments
$platform = $argv[1] ?? 'all';
$options = [];

foreach ($argv as $arg) {
    if (strpos($arg, '--') === 0) {
        $parts = explode('=', substr($arg, 2), 2);
        $options[$parts[0]] = $parts[1] ?? true;
    }
}

$date_from = $options['from'] ?? $options['date'] ?? date('Y-m-d');
$date_to = $options['to'] ?? $options['date'] ?? date('Y-m-d');
$max_orders = (int)($options['limit'] ?? 0); // 0 = ไม่จำกัด

echo "🚀 Dashboard Market - Order Fetcher\n";
echo "=====================================\n";
echo "วันที่: {$date_from} ถึง {$date_to}\n";
echo "Platform: " . ($platform === 'all' ? 'ทั้งหมด' : $platform) . "\n";
if ($max_orders > 0) echo "จำกัด: {$max_orders} orders\n";
echo "=====================================\n\n";

$platforms = [];
if ($platform === 'all') {
    $platforms = ['shopee', 'lazada'];
} else {
    $platforms = [$platform];
}

$allResults = [];

foreach ($platforms as $p) {
    echo "🏪 เริ่มดึงข้อมูล " . strtoupper($p) . "\n";
    echo str_repeat('-', 50) . "\n";
    
    try {
        // ตรวจสอบการตั้งค่า
        $settings = dm_settings_get_all($p);
        $enabled = ($settings['enabled'] ?? 'false') === 'true';
        
        if (!$enabled) {
            echo "⚠️ {$p} ถูกปิดใช้งาน - ข้าม\n\n";
            continue;
        }
        
        // สร้าง API instance
        $config = getAPIConfig();
        $api = null;
        
        switch ($p) {
            case 'shopee':
                $api = new ShopeeAPI('shopee', $config['shopee']);
                break;
            case 'lazada':
                $api = new LazadaAPI('lazada', $config['lazada']);
                break;
            default:
                throw new Exception("Platform {$p} not supported");
        }
        
        // สร้าง PaginationManager และดึงข้อมูล
        $manager = new PaginationManager($p, $api);
        $result = $manager->fetchAllOrders($date_from, $date_to, $max_orders);
        $allResults[$p] = $result;
        
        // แสดงสถิติหลังดึงข้อมูล
        echo "\n📊 สถิติการดึงข้อมูล {$p}:\n";
        $stats = $manager->getLastFetchStats();
        echo "- Orders ทั้งหมด: " . number_format($stats['total_orders']) . " รายการ\n";
        echo "- ยอดขายรวม: ₿" . number_format($stats['total_sales'], 2) . "\n";
        echo "- อัปเดตล่าสุด: " . ($stats['last_fetch_time'] ?: 'ไม่เคยดึง') . "\n";
        
    } catch (Exception $e) {
        echo "❌ ข้อผิดพลาด {$p}: " . $e->getMessage() . "\n";
        $allResults[$p] = ['success' => false, 'error' => $e->getMessage()];
    }
    
    echo "\n" . str_repeat('=', 50) . "\n\n";
}

// สรุปผลลั้ฟธ์ทั้งหมด
echo "📈 สรุปผลลัพธ์ทั้งหมด\n";
echo str_repeat('=', 50) . "\n";

$totalFetched = 0;
$totalSaved = 0;
$totalDuration = 0;

foreach ($allResults as $platform => $result) {
    if ($result['success'] ?? false) {
        $totalFetched += $result['total_fetched'];
        $totalSaved += $result['total_saved'];
        $totalDuration += $result['duration_seconds'];
        
        echo "✅ {$platform}: {$result['total_fetched']} fetched, {$result['total_saved']} saved\n";
        if (!empty($result['errors'])) {
            echo "   ⚠️ Errors: " . count($result['errors']) . "\n";
        }
    } else {
        echo "❌ {$platform}: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
}

echo str_repeat('-', 30) . "\n";
echo "🎯 รวม: {$totalFetched} orders ดึงมา, {$totalSaved} บันทึกแล้ว\n";
echo "⏱️ ใช้เวลา: " . round($totalDuration, 2) . " วินาที\n";

// แนะนำการใช้งาน
echo "\n💡 ข้อแนะนำ:\n";
echo "- ตั้ง cron job ให้รันทุก ๆ 1 ชั่วโมง: 0 * * * * php " . __FILE__ . " all\n";
echo "- ดึงข้อมูลย้อนหลัง: php " . basename(__FILE__) . " all --from=2024-08-01 --to=2024-08-31\n";
echo "- จำกัดจำนวน: php " . basename(__FILE__) . " shopee --limit=500\n";
echo "\n✨ เสร็จสิ้น!\n";

?>
