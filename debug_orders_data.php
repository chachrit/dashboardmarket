<?php
/**
 * Debug Orders Data - ตรวจสอบข้อมูลออเดอร์ที่ส่งกลับจาก API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'db.php';
require_once 'api.php';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Debug Orders Data</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .platform-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }
        .platform-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 4px;
            color: white;
        }
        .shopee { background: #EE4D2D; }
        .lazada { background: #0F156D; }
        .tiktok { background: #FF0050; }
        pre { 
            background: #f8f9fa; 
            padding: 10px; 
            border-radius: 4px; 
            overflow-x: auto; 
            font-size: 12px;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }
        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            text-align: center;
            flex: 1;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .success {
            color: #155724;
            background: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Orders Data</h1>
        <p>ตรวจสอบข้อมูลออเดอร์ที่ส่งกลับจาก API แต่ละแพลตฟอร์ม</p>
        
        <?php
        $platforms = ['shopee', 'lazada', 'tiktok'];
        
        foreach ($platforms as $platform) {
            echo "<div class='platform-section'>";
            echo "<div class='platform-title $platform'>" . strtoupper($platform) . "</div>";
            
            try {
                // ตรวจสอบการตั้งค่า
                $settings = dm_settings_get_all($platform);
                $enabled = ($settings['enabled'] ?? 'false') === 'true';
                
                if (!$enabled) {
                    echo "<div class='error'>❌ Platform ถูกปิดใช้งาน</div>";
                    echo "</div>";
                    continue;
                }
                
                // ทดสอบเรียก API
                echo "<p>🔄 กำลังเรียก API...</p>";
                
                // เรียก getSummary
                $summaryUrl = "api.php?action=getSummary&platform=$platform";
                $summaryResponse = file_get_contents($summaryUrl);
                $summaryData = json_decode($summaryResponse, true);
                
                if ($summaryData && $summaryData['success']) {
                    echo "<div class='success'>✅ API Response สำเร็จ</div>";
                    
                    // แสดงสถิติ
                    echo "<div class='stats'>";
                    echo "<div class='stat-box'>";
                    echo "<div class='stat-number'>" . number_format($summaryData['data']['totalOrders']) . "</div>";
                    echo "<div class='stat-label'>จำนวนออเดอร์</div>";
                    echo "</div>";
                    echo "<div class='stat-box'>";
                    echo "<div class='stat-number'>₿" . number_format($summaryData['data']['totalSales']) . "</div>";
                    echo "<div class='stat-label'>ยอดขาย</div>";
                    echo "</div>";
                    echo "</div>";
                    
                } else {
                    echo "<div class='error'>❌ API Error: " . ($summaryData['error'] ?? 'Unknown') . "</div>";
                }
                
                // แสดง Raw Response
                echo "<details>";
                echo "<summary>📋 Raw API Response</summary>";
                echo "<pre>" . htmlspecialchars($summaryResponse) . "</pre>";
                echo "</details>";
                
                // เรียก getOrders แบบละเอียด
                echo "<br><p>🔄 กำลังเรียก getOrders...</p>";
                $ordersUrl = "api.php?action=getOrders&platform=$platform&limit=5";
                $ordersResponse = file_get_contents($ordersUrl);
                $ordersData = json_decode($ordersResponse, true);
                
                if ($ordersData && $ordersData['success']) {
                    $orders = $ordersData['data']['orders'] ?? [];
                    echo "<div class='success'>✅ พบออเดอร์ " . count($orders) . " รายการ</div>";
                    
                    if (!empty($orders)) {
                        echo "<h4>📝 ตัวอย่างออเดอร์ล่าสุด:</h4>";
                        foreach (array_slice($orders, 0, 3) as $i => $order) {
                            echo "<div style='background:white; padding:10px; margin:5px 0; border-radius:4px; border:1px solid #ddd;'>";
                            echo "<strong>Order " . ($i + 1) . ":</strong> " . ($order['order_id'] ?? 'N/A');
                            echo " | ยอด: ₿" . number_format($order['amount'] ?? 0);
                            echo " | วันที่: " . ($order['created_at'] ?? 'N/A');
                            echo "</div>";
                        }
                    }
                } else {
                    echo "<div class='error'>❌ getOrders Error: " . ($ordersData['error'] ?? 'Unknown') . "</div>";
                }
                
                // แสดง Raw Orders Response
                echo "<details>";
                echo "<summary>📋 Raw getOrders Response</summary>";
                echo "<pre>" . htmlspecialchars($ordersResponse) . "</pre>";
                echo "</details>";
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
            }
            
            echo "</div>";
        }
        ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <p><a href="index.php">← กลับหน้าหลัก</a> | <a href="settings.php">⚙️ Settings</a></p>
            <p style="font-size: 12px; color: #666;">เวลาทดสอบ: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
