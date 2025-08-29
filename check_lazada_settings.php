<?php
// ตรวจสอบการตั้งค่า Lazada ใน cPanel
echo "<h1>🔍 ตรวจสอบการตั้งค่า Lazada</h1>";

try {
    require_once __DIR__ . '/db.php';
    
    echo "<h2>📊 สถานะฐานข้อมูล</h2>";
    $db = dm_db();
    if ($db) {
        echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br>";
        
        // ตรวจสอบตาราง dm_settings
        $stmt = $db->query("SHOW TABLES LIKE 'dm_settings'");
        if ($stmt->fetch()) {
            echo "✅ ตาราง dm_settings พบแล้ว<br>";
        } else {
            echo "❌ ไม่พบตาราง dm_settings<br>";
        }
    } else {
        echo "❌ ไม่สามารถเชื่อมต่อฐานข้อมูล<br>";
    }
    
    echo "<h2>🏪 การตั้งค่า Lazada</h2>";
    
    $lazada_settings = [
        'lazada_app_key' => 'App Key',
        'lazada_app_secret' => 'App Secret', 
        'lazada_access_token' => 'Access Token',
        'lazada_refresh_token' => 'Refresh Token',
        'lazada_seller_id' => 'Seller ID',
        'lazada_env' => 'Environment'
    ];
    
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border: 1px solid #6c757d;'>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #e9ecef;'><th style='padding: 10px;'>การตั้งค่า</th><th style='padding: 10px;'>สถานะ</th><th style='padding: 10px;'>รายละเอียด</th></tr>";
    
    $total_settings = 0;
    $found_settings = 0;
    
    foreach ($lazada_settings as $key => $name) {
        $total_settings++;
        $value = dm_settings_get($key, '');
        
        echo "<tr>";
        echo "<td style='padding: 8px;'><strong>$name</strong><br><small>($key)</small></td>";
        
        if (!empty($value)) {
            $found_settings++;
            echo "<td style='padding: 8px; color: green;'>✅ มี</td>";
            echo "<td style='padding: 8px;'>" . strlen($value) . " ตัวอักษร</td>";
        } else {
            echo "<td style='padding: 8px; color: red;'>❌ ไม่มี</td>";
            echo "<td style='padding: 8px;'>ว่างเปล่า</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    echo "<h3>📈 สรุป</h3>";
    echo "<div style='background: " . ($found_settings > 0 ? "#e8f5e8" : "#ffe6e6") . "; padding: 15px; margin: 10px 0; border: 1px solid " . ($found_settings > 0 ? "#4CAF50" : "red") . ";'>";
    echo "<strong>พบการตั้งค่า: $found_settings / $total_settings</strong><br>";
    
    if ($found_settings == 0) {
        echo "❌ ไม่พบการตั้งค่า Lazada เลย<br>";
        echo "<strong>วิธีแก้ไข:</strong><br>";
        echo "1. เข้าไปที่หน้า settings.php<br>";
        echo "2. กรอกข้อมูล Lazada API<br>";
        echo "3. กดปุ่ม Save Settings<br>";
    } elseif ($found_settings < $total_settings) {
        echo "⚠️ การตั้งค่าไม่ครบ<br>";
        echo "<strong>ขาดการตั้งค่า:</strong><br>";
        foreach ($lazada_settings as $key => $name) {
            $value = dm_settings_get($key, '');
            if (empty($value)) {
                echo "• $name ($key)<br>";
            }
        }
    } else {
        echo "✅ การตั้งค่าครบถ้วน<br>";
    }
    echo "</div>";
    
    // ตรวจสอบ API configuration
    echo "<h2>⚙️ ตรวจสอบ API Config</h2>";
    
    if (file_exists(__DIR__ . '/api.php')) {
        echo "✅ ไฟล์ api.php พบแล้ว<br>";
        
        // Load API config function
        require_once __DIR__ . '/api.php';
        
        if (function_exists('getAPIConfig')) {
            $config = getAPIConfig();
            if (isset($config['lazada'])) {
                echo "✅ พบ Lazada config<br>";
                echo "<div style='background: #f0f8ff; padding: 10px; margin: 10px 0; border: 1px solid #0066cc;'>";
                echo "<strong>Lazada Config:</strong><br>";
                foreach ($config['lazada'] as $key => $value) {
                    if ($key === 'app_secret' || $key === 'access_token' || $key === 'refresh_token') {
                        echo "• $key: " . (empty($value) ? "ไม่มี" : "มี (" . strlen($value) . " ตัวอักษร)") . "<br>";
                    } else {
                        echo "• $key: " . htmlspecialchars($value ?: 'ไม่มี') . "<br>";
                    }
                }
                echo "</div>";
            } else {
                echo "❌ ไม่พบ Lazada config ใน getAPIConfig()<br>";
            }
        } else {
            echo "❌ ไม่พบฟังก์ชัน getAPIConfig()<br>";
        }
    } else {
        echo "❌ ไม่พบไฟล์ api.php<br>";
    }
    
    echo "<h2>🔗 การทำงานต่อ</h2>";
    echo "<div style='background: #e1f5fe; padding: 15px; margin: 10px 0; border: 1px solid #0288d1;'>";
    echo "<strong>หากต้องการตั้งค่า Lazada:</strong><br>";
    echo "1. <a href='settings.php' target='_blank'>เปิดหน้า Settings</a><br>";
    echo "2. เลื่อนลงไปส่วน Lazada API<br>";
    echo "3. กรอกข้อมูล:<br>";
    echo "&nbsp;&nbsp;• App Key<br>";
    echo "&nbsp;&nbsp;• App Secret<br>";
    echo "&nbsp;&nbsp;• Access Token<br>";
    echo "&nbsp;&nbsp;• Refresh Token<br>";
    echo "&nbsp;&nbsp;• Seller ID<br>";
    echo "4. เลือก Environment (sandbox/production)<br>";
    echo "5. กดปุ่ม Save Settings<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 15px; margin: 10px 0; border: 1px solid red;'>";
    echo "<strong>❌ เกิดข้อผิดพลาด:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}

echo "<hr>";
echo "<small>ตรวจสอบเมื่อ: " . date('Y-m-d H:i:s') . "</small>";
?>
