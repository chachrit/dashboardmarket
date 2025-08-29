<?php
// Debug and Fix Shopee & Lazada API Issues
echo "<h1>🔧 แก้ไขปัญหา Shopee & Lazada API</h1>";

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api.php';

$config = getAPIConfig();
$shopeeConfig = $config['shopee'];
$lazadaConfig = $config['lazada'];

echo "<h2>1️⃣ ตรวจสอบรายละเอียด Shopee</h2>";

echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px 0; border: 1px solid #0066cc;'>";
echo "<h3>📋 ข้อมูลปัจจุบัน:</h3>";
echo "• Partner ID: " . htmlspecialchars($shopeeConfig['partner_id']) . "<br>";
echo "• Shop ID: " . htmlspecialchars($shopeeConfig['shop_id']) . "<br>";
echo "• Environment: " . htmlspecialchars($shopeeConfig['env']) . "<br>";
echo "• API URL: " . htmlspecialchars($shopeeConfig['api_url']) . "<br>";

if (!empty($shopeeConfig['expires_at'])) {
    $expiresAt = (int)$shopeeConfig['expires_at'];
    $now = time();
    $timeLeft = $expiresAt - $now;
    
    echo "• Access Token หมดอายุ: " . date('Y-m-d H:i:s', $expiresAt);
    if ($timeLeft <= 0) {
        echo " <span style='color: red; font-weight: bold;'>❌ หมดอายุแล้ว!</span>";
    } elseif ($timeLeft < 3600) {
        echo " <span style='color: orange; font-weight: bold;'>⚠️ หมดอายุในอีก " . round($timeLeft/60) . " นาที</span>";
    } else {
        echo " <span style='color: green; font-weight: bold;'>✅ ยังไม่หมดอายุ</span>";
    }
    echo "<br>";
}

echo "</div>";

echo "<h2>2️⃣ ทดสอบ API Call แบบละเอียด</h2>";

try {
    $shopeeAPI = new ShopeeAPI('shopee', $shopeeConfig);
    
    // Test basic shop info call
    echo "<h3>🏪 ทดสอบ Shop Info API</h3>";
    
    // Test basic connection instead of direct API call
    echo "<h3>🏪 ทดสอบการเชื่อมต่อ Shopee</h3>";
    
    $testResult = $shopeeAPI->testConnection();
    
    echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border: 1px solid #6c757d;'>";
    echo "<h4>🧪 Test Connection Result:</h4>";
    echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
    echo htmlspecialchars(json_encode($testResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "</pre>";
    echo "</div>";
    
    if ($testResult['success']) {
        echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border: 1px solid #4CAF50;'>";
        echo "✅ <strong>การเชื่อมต่อสำเร็จ!</strong><br>";
        echo "📝 " . htmlspecialchars($testResult['message']) . "<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0; border: 1px solid red;'>";
        echo "❌ <strong>การเชื่อมต่อล้มเหลว</strong><br>";
        echo "📝 " . htmlspecialchars($testResult['message'] ?? 'Unknown error') . "<br>";
        
        // แนะนำวิธีแก้ไข
        $message = $testResult['message'] ?? '';
        if (strpos($message, 'access_token') !== false || strpos($message, 'authorization') !== false) {
            echo "<br><strong>💡 วิธีแก้ไข:</strong><br>";
            echo "1. Access Token หมดอายุหรือไม่ถูกต้อง<br>";
            echo "2. ต้องทำ OAuth Authorization ใหม่<br>";
            echo "3. หรือใช้ Refresh Token<br>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0; border: 1px solid red;'>";
    echo "❌ <strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "</div>";
}

echo "<h2>2️⃣ ตรวจสอบรายละเอียด Lazada</h2>";

echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px 0; border: 1px solid #0066cc;'>";
echo "<h3>🛒 ข้อมูลปัจจุบัน:</h3>";

$lazada_required = ['app_key', 'app_secret', 'access_token', 'seller_id', 'env'];
$lazada_found = 0;
$lazada_total = count($lazada_required);

foreach ($lazada_required as $key) {
    if (!empty($lazadaConfig[$key])) {
        $lazada_found++;
    }
}

echo "• App Key: " . (!empty($lazadaConfig['app_key']) ? "✅ มี" : "❌ ไม่มี") . "<br>";
echo "• App Secret: " . (!empty($lazadaConfig['app_secret']) ? "✅ มี" : "❌ ไม่มี") . "<br>";
echo "• Access Token: " . (!empty($lazadaConfig['access_token']) ? "✅ มี" : "❌ ไม่มี") . "<br>";
echo "• Refresh Token: " . (!empty($lazadaConfig['refresh_token']) ? "✅ มี" : "❌ ไม่มี") . "<br>";
echo "• Seller ID: " . (!empty($lazadaConfig['seller_id']) ? "✅ มี" : "❌ ไม่มี") . "<br>";
echo "• Environment: " . htmlspecialchars($lazadaConfig['env'] ?: 'sandbox') . "<br>";
echo "• API URL: " . htmlspecialchars($lazadaConfig['api_url'] ?: 'N/A') . "<br>";

echo "<br><strong>สรุป: พบการตั้งค่า $lazada_found / $lazada_total</strong>";

if ($lazada_found == 0) {
    echo " <span style='color: red; font-weight: bold;'>❌ ไม่มีการตั้งค่าเลย</span>";
} elseif ($lazada_found < $lazada_total) {
    echo " <span style='color: orange; font-weight: bold;'>⚠️ ไม่ครบ</span>";
} else {
    echo " <span style='color: green; font-weight: bold;'>✅ ครบถ้วน</span>";
}

echo "</div>";

// ทดสอบ Lazada API ถ้ามีข้อมูลพอ
if ($lazada_found >= 3) {
    echo "<h3>🛒 ทดสอบการเชื่อมต่อ Lazada</h3>";
    
    try {
        $lazadaAPI = new LazadaAPI('lazada', $lazadaConfig);
        $testResult = $lazadaAPI->testConnection();
        
        echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border: 1px solid #6c757d;'>";
        echo "<h4>🧪 Lazada Test Result:</h4>";
        echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
        echo htmlspecialchars(json_encode($testResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "</pre>";
        echo "</div>";
        
        if ($testResult['success']) {
            echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border: 1px solid #4CAF50;'>";
            echo "✅ <strong>Lazada เชื่อมต่อสำเร็จ!</strong><br>";
            echo "📝 " . htmlspecialchars($testResult['message']) . "<br>";
            echo "</div>";
        } else {
            echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0; border: 1px solid red;'>";
            echo "❌ <strong>Lazada เชื่อมต่อล้มเหลว</strong><br>";
            echo "📝 " . htmlspecialchars($testResult['message'] ?? 'Unknown error') . "<br>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0; border: 1px solid red;'>";
        echo "❌ <strong>Lazada Exception:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "</div>";
    }
}

echo "<h2>3️⃣ วิธีแก้ไข Shopee</h2>";

echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border: 1px solid #ffc107;'>";
echo "<h3>🛠️ ขั้นตอนการแก้ไข Shopee:</h3>";
echo "<ol>";
echo "<li><strong>ตรวจสอบ Access Token:</strong> หากหมดอายุ ต้องทำ Authorization ใหม่</li>";
echo "<li><strong>OAuth Authorization:</strong>";
echo "<ul>";
echo "<li>เข้าหน้า <a href='settings.php' target='_blank'>settings.php</a></li>";
echo "<li>กดปุ่ม 'OAuth Authorization' ในส่วน Shopee</li>";
echo "<li>ทำตาม redirect เพื่อ authorize</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>ใช้ Refresh Token:</strong>";
echo "<ul>";
echo "<li>กดปุ่ม 'รีเฟรช Token' ในหน้า settings</li>";
echo "<li>หรือเรียก API refresh_token</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>ทดสอบอีกครั้ง:</strong> หลังจากได้ Token ใหม่</li>";
echo "</ol>";
echo "</div>";

// แสดงปุ่มสำหรับ Refresh Token
if (!empty($shopeeConfig['refresh_token'])) {
    echo "<div style='background: #e8f5e8; padding: 15px; margin: 10px 0; border: 1px solid #4CAF50;'>";
    echo "<h3>🔄 ลอง Refresh Token</h3>";
    echo "<p>พบ Refresh Token ในระบบ สามารถลองรีเฟรชได้:</p>";
    echo "<a href='?refresh_token=1' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔄 Refresh Shopee Token</a>";
    echo "</div>";
    
    if (isset($_GET['refresh_token'])) {
        echo "<h3>🔄 ผลการ Refresh Token</h3>";
        try {
            $refreshResult = $shopeeAPI->refreshAccessToken();
            
            echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border: 1px solid #4CAF50;'>";
            echo "✅ <strong>Refresh Token สำเร็จ!</strong><br>";
            echo "📝 New Access Token: " . substr($refreshResult['access_token'] ?? 'N/A', 0, 20) . "...<br>";
            if (isset($refreshResult['expire_in'])) {
                $newExpiry = time() + (int)$refreshResult['expire_in'];
                echo "⏰ หมดอายุใหม่: " . date('Y-m-d H:i:s', $newExpiry) . "<br>";
            }
            echo "<br><a href='test_api_shopee_lazada.php' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🧪 ทดสอบอีกครั้ง</a>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0; border: 1px solid red;'>";
            echo "❌ <strong>Refresh Token ล้มเหลว:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<br><strong>💡 วิธีแก้ไข:</strong><br>";
            echo "1. Refresh Token อาจหมดอายุแล้ว<br>";
            echo "2. ต้องทำ OAuth Authorization ใหม่<br>";
            echo "3. <a href='settings.php' target='_blank'>ไปหน้า Settings</a> และกดปุ่ม 'OAuth Authorization'<br>";
            echo "</div>";
        }
    }
}

echo "<h2>4️⃣ ตั้งค่า Lazada</h2>";

echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px 0; border: 1px solid #0066cc;'>";
echo "<h3>🛒 การตั้งค่า Lazada:</h3>";
echo "<p>Lazada ยังไม่มีข้อมูล App Key และ App Secret:</p>";
echo "<ol>";
echo "<li><strong>สมัครใช้งาน:</strong> เข้า <a href='https://open.lazada.com/' target='_blank'>Lazada Open Platform</a></li>";
echo "<li><strong>สร้าง App:</strong> สร้างแอปพลิเคชันใหม่</li>";
echo "<li><strong>รับ Credentials:</strong> App Key และ App Secret</li>";
echo "<li><strong>ตั้งค่า:</strong> ใส่ข้อมูลใน <a href='settings.php' target='_blank'>หน้า Settings</a></li>";
echo "<li><strong>OAuth:</strong> ทำ Authorization เพื่อรับ Access Token</li>";
echo "</ol>";
echo "</div>";

echo "<h2>5️⃣ Quick Fix</h2>";

echo "<div style='background: #e8f5e8; padding: 15px; margin: 10px 0; border: 1px solid #4CAF50;'>";
echo "<h3>⚡ วิธีแก้ไขเร็ว:</h3>";
echo "<p><strong>สำหรับ Shopee (มีข้อมูลครบแล้ว):</strong></p>";
echo "<a href='settings.php' target='_blank' style='background: #EE4D2D; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin: 5px;'>🏪 ตั้งค่า Shopee</a>";
echo "<a href='?refresh_token=1' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin: 5px;'>🔄 Refresh Token</a>";

echo "<p style='margin-top: 15px;'><strong>สำหรับ Lazada (ต้องตั้งค่าใหม่):</strong></p>";
echo "<a href='https://open.lazada.com/' target='_blank' style='background: #0F156D; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin: 5px;'>🛒 Lazada Open Platform</a>";
echo "<a href='settings.php' target='_blank' style='background: #0F156D; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin: 5px;'>⚙️ ตั้งค่า Lazada</a>";
echo "</div>";
?>
