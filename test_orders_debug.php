<?php
/**
 * ทดสอบข้อมูลออเดอร์จาก API แต่ละแพลตฟอร์ม
 */

require_once 'config.php';
require_once 'db.php';
require_once 'api.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== ทดสอบข้อมูลออเดอร์จาก API ===\n";
echo "เวลาทดสอบ: " . date('Y-m-d H:i:s') . "\n\n";

$platforms = ['shopee', 'lazada', 'tiktok'];

foreach ($platforms as $platform) {
    echo "🔍 ทดสอบ $platform:\n";
    echo str_repeat("-", 50) . "\n";
    
    try {
        // ตรวจสอบการตั้งค่า
        $settings = dm_settings_get_all($platform);
        $enabled = ($settings['enabled'] ?? 'false') === 'true';
        
        echo "📋 การตั้งค่า:\n";
        echo "   - เปิดใช้งาน: " . ($enabled ? 'ใช่' : 'ไม่') . "\n";
        
        if (!$enabled) {
            echo "   ❌ Platform ถูกปิดใช้งาน - ข้าม\n\n";
            continue;
        }
        
        // สร้าง API instance
        $config = getAPIConfig();
        
        $api = null;
        switch ($platform) {
            case 'shopee': 
                $api = new ShopeeAPI('shopee', $config['shopee']); 
                break;
            case 'lazada': 
                $api = new LazadaAPI('lazada', $config['lazada']); 
                break;
            case 'tiktok': 
                $api = new TikTokAPI('tiktok', $config['tiktok']); 
                break;
        }
        
        if (!$api) {
            echo "   ❌ ไม่สามารถสร้าง API instance ได้\n\n";
            continue;
        }
        
        echo "   ✅ API instance สร้างสำเร็จ\n";
        
        // เรียก getOrders โดยตรง
        echo "\n📊 เรียก getOrders() โดยตรง:\n";
        $start_time = microtime(true);
        
        $result = $api->getOrders(null, null, 10); // ขอแค่ 10 รายการ
        
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2);
        
        echo "   - เวลาที่ใช้: {$execution_time}ms\n";
        echo "   - Total Sales: " . number_format($result['total_sales']) . "\n";
        echo "   - Total Orders: " . number_format($result['total_orders']) . "\n";
        echo "   - Orders Array Size: " . count($result['orders']) . "\n";
        
        // แสดงตัวอย่างออเดอร์
        if (!empty($result['orders'])) {
            echo "\n📝 ตัวอย่างออเดอร์แรก:\n";
            $first_order = $result['orders'][0];
            echo "   - Order ID: " . ($first_order['order_id'] ?? 'N/A') . "\n";
            echo "   - Amount: " . number_format($first_order['amount'] ?? 0) . "\n";
            echo "   - Created: " . ($first_order['created_at'] ?? 'N/A') . "\n";
            echo "   - Platform: " . ($first_order['platform'] ?? $platform) . "\n";
        } else {
            echo "\n   📭 ไม่พบออเดอร์\n";
        }
        
        // ทดสอบผ่าน API endpoint
        echo "\n🌐 ทดสอบผ่าน API endpoint:\n";
        $api_url = "http://localhost/dashboardmarket/api.php?action=getSummary&platform=$platform";
        
        $api_start = microtime(true);
        $api_response = @file_get_contents($api_url);
        $api_end = microtime(true);
        $api_time = round(($api_end - $api_start) * 1000, 2);
        
        if ($api_response) {
            $api_data = json_decode($api_response, true);
            echo "   - เวลาที่ใช้: {$api_time}ms\n";
            echo "   - Success: " . ($api_data['success'] ? 'ใช่' : 'ไม่') . "\n";
            
            if ($api_data['success'] && isset($api_data['data'])) {
                echo "   - API Total Sales: " . number_format($api_data['data']['totalSales']) . "\n";
                echo "   - API Total Orders: " . number_format($api_data['data']['totalOrders']) . "\n";
            } else {
                echo "   - Error: " . ($api_data['error'] ?? 'Unknown') . "\n";
            }
        } else {
            echo "   ❌ ไม่สามารถเรียก API endpoint ได้\n";
        }
        
        // เปรียบเทียบผลลัพธ์
        if ($api_response) {
            $api_data = json_decode($api_response, true);
            if ($api_data['success'] && isset($api_data['data'])) {
                $direct_orders = $result['total_orders'];
                $api_orders = $api_data['data']['totalOrders'];
                
                echo "\n🔍 เปรียบเทียบผลลัพธ์:\n";
                echo "   - Direct call orders: $direct_orders\n";
                echo "   - API endpoint orders: $api_orders\n";
                
                if ($direct_orders == $api_orders) {
                    echo "   ✅ ผลลัพธ์ตรงกัน\n";
                } else {
                    echo "   ⚠️ ผลลัพธ์ไม่ตรงกัน!\n";
                }
                
                // ตรวจสอบว่าจำนวนเป็น 50 หรือไม่
                if ($api_orders == 50) {
                    echo "   🚨 พบปัญหา: จำนวนออเดอร์เป็น 50 (อาจเป็น limit หรือ mock data)\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

echo "🏁 การทดสอบเสร็จสิ้น\n";
?>
