<?php
// Test and Fix API for Shopee and Lazada
echo "<h1>🧪 ทดสอบและแก้ไข API Shopee และ Lazada</h1>";

// Load required files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api.php';

echo "<h2>1️⃣ ตรวจสอบการตั้งค่า Database และ Settings</h2>";

// Test database connection
try {
    $db_info = dm_get_db_info();
    echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border: 1px solid #4CAF50;'>";
    echo "✅ ฐานข้อมูล: " . strtoupper($db_info['type']) . " (" . $db_info['status'] . ")<br>";
    echo "📊 Database: " . ($db_info['database'] ?? 'unknown') . "<br>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0; border: 1px solid red;'>";
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    echo "</div>";
    exit;
}

// Test API config loading
echo "<h3>⚙️ ทดสอบการโหลด API Config</h3>";
try {
    $config = getAPIConfig();
    
    echo "<h4>📱 Shopee Configuration:</h4>";
    echo "<div style='background: #f0f8ff; padding: 10px; margin: 5px 0;'>";
    echo "• Environment: <strong>" . ($config['shopee']['env'] ?? 'not set') . "</strong><br>";
    echo "• Partner ID: <strong>" . (empty($config['shopee']['partner_id']) ? '❌ ไม่มี' : '✅ มี (' . substr($config['shopee']['partner_id'], 0, 4) . '...)') . "</strong><br>";
    echo "• Partner Key: <strong>" . (empty($config['shopee']['partner_key']) ? '❌ ไม่มี' : '✅ มี') . "</strong><br>";
    echo "• Shop ID: <strong>" . (empty($config['shopee']['shop_id']) ? '❌ ไม่มี' : '✅ มี (' . $config['shopee']['shop_id'] . ')') . "</strong><br>";
    echo "• Access Token: <strong>" . (empty($config['shopee']['access_token']) ? '❌ ไม่มี' : '✅ มี (' . substr($config['shopee']['access_token'], 0, 10) . '...)') . "</strong><br>";
    echo "• API URL: <strong>" . $config['shopee']['api_url'] . "</strong><br>";
    echo "</div>";
    
    echo "<h4>🛒 Lazada Configuration:</h4>";
    echo "<div style='background: #f0f8ff; padding: 10px; margin: 5px 0;'>";
    echo "• App Key: <strong>" . (empty($config['lazada']['app_key']) ? '❌ ไม่มี' : '✅ มี (' . substr($config['lazada']['app_key'], 0, 6) . '...)') . "</strong><br>";
    echo "• App Secret: <strong>" . (empty($config['lazada']['app_secret']) ? '❌ ไม่มี' : '✅ มี') . "</strong><br>";
    echo "• Access Token: <strong>" . (empty($config['lazada']['access_token']) ? '❌ ไม่มี' : '✅ มี (' . substr($config['lazada']['access_token'], 0, 10) . '...)') . "</strong><br>";
    echo "• API URL: <strong>" . $config['lazada']['api_url'] . "</strong><br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0; border: 1px solid red;'>";
    echo "❌ Config Error: " . $e->getMessage() . "<br>";
    echo "</div>";
    exit;
}

echo "<hr>";

echo "<h2>2️⃣ ทดสอบการสร้าง API Objects</h2>";

// Test Shopee API
echo "<h3>📱 ทดสอบ ShopeeAPI</h3>";
try {
    $shopeeAPI = new ShopeeAPI('shopee', $config['shopee']);
    echo "✅ ShopeeAPI Object สร้างสำเร็จ<br>";
    
    // Test connection
    $shopeeTest = $shopeeAPI->testConnection();
    if ($shopeeTest['success']) {
        echo "<div style='background: #e8f5e8; padding: 10px; margin: 5px 0; border: 1px solid #4CAF50;'>";
        echo "✅ <strong>Shopee Test Connection สำเร็จ!</strong><br>";
        echo "📝 " . $shopeeTest['message'] . "<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; margin: 5px 0; border: 1px solid #ffc107;'>";
        echo "⚠️ <strong>Shopee Test Connection ล้มเหลว</strong><br>";
        echo "📝 " . $shopeeTest['message'] . "<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 10px; margin: 5px 0; border: 1px solid red;'>";
    echo "❌ ShopeeAPI Error: " . $e->getMessage() . "<br>";
    echo "</div>";
}

// Test Lazada API
echo "<h3>🛒 ทดสอบ LazadaAPI</h3>";
try {
    $lazadaAPI = new LazadaAPI('lazada', $config['lazada']);
    echo "✅ LazadaAPI Object สร้างสำเร็จ<br>";
    
    // Test connection
    $lazadaTest = $lazadaAPI->testConnection();
    if ($lazadaTest['success']) {
        echo "<div style='background: #e8f5e8; padding: 10px; margin: 5px 0; border: 1px solid #4CAF50;'>";
        echo "✅ <strong>Lazada Test Connection สำเร็จ!</strong><br>";
        echo "📝 " . $lazadaTest['message'] . "<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; margin: 5px 0; border: 1px solid #ffc107;'>";
        echo "⚠️ <strong>Lazada Test Connection ล้มเหลว</strong><br>";
        echo "📝 " . $lazadaTest['message'] . "<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 10px; margin: 5px 0; border: 1px solid red;'>";
    echo "❌ LazadaAPI Error: " . $e->getMessage() . "<br>";
    echo "</div>";
}

echo "<hr>";

echo "<h2>3️⃣ ข้อเสนอแนะการแก้ไข</h2>";

echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px 0; border: 1px solid #0066cc;'>";
echo "<h3>📋 สิ่งที่ต้องแก้ไขให้ API ทำงานได้:</h3>";

// Check Shopee requirements
echo "<h4>📱 Shopee:</h4>";
$shopeeIssues = [];
if (empty($config['shopee']['partner_id'])) $shopeeIssues[] = "Partner ID ขาดหายไป";
if (empty($config['shopee']['partner_key'])) $shopeeIssues[] = "Partner Key ขาดหายไป";
if (empty($config['shopee']['shop_id'])) $shopeeIssues[] = "Shop ID ขาดหายไป";
if (empty($config['shopee']['access_token'])) $shopeeIssues[] = "Access Token ขาดหายไป - ต้องทำ OAuth";

if (empty($shopeeIssues)) {
    echo "✅ การตั้งค่า Shopee สมบูรณ์แล้ว<br>";
} else {
    echo "<ul>";
    foreach ($shopeeIssues as $issue) {
        echo "<li>❌ $issue</li>";
    }
    echo "</ul>";
}

// Check Lazada requirements
echo "<h4>🛒 Lazada:</h4>";
$lazadaIssues = [];
if (empty($config['lazada']['app_key'])) $lazadaIssues[] = "App Key ขาดหายไป";
if (empty($config['lazada']['app_secret'])) $lazadaIssues[] = "App Secret ขาดหายไป";
if (empty($config['lazada']['access_token'])) $lazadaIssues[] = "Access Token ขาดหายไป - ต้องทำ OAuth";

if (empty($lazadaIssues)) {
    echo "✅ การตั้งค่า Lazada สมบูรณ์แล้ว<br>";
} else {
    echo "<ul>";
    foreach ($lazadaIssues as $issue) {
        echo "<li>❌ $issue</li>";
    }
    echo "</ul>";
}

echo "</div>";

echo "<hr>";

echo "<h2>4️⃣ ขั้นตอนการแก้ไข</h2>";

echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border: 1px solid #ffc107;'>";
echo "<h3>🛠️ วิธีการแก้ไข:</h3>";
echo "<ol>";
echo "<li><strong>เข้าหน้า Settings:</strong> <a href='settings.php' target='_blank'>settings.php</a></li>";
echo "<li><strong>กรอกข้อมูลที่ขาดหายไป:</strong> Partner ID, API Keys ที่ได้จาก Platform</li>";
echo "<li><strong>ทำ OAuth Authorization:</strong> สำหรับ Access Token</li>";
echo "<li><strong>ทดสอบการเชื่อมต่อ:</strong> กดปุ่ม 'ทดสอบการเชื่อมต่อ'</li>";
echo "<li><strong>บันทึกการตั้งค่า:</strong> กดปุ่ม 'บันทึก'</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #e8f5e8; padding: 15px; margin: 10px 0; border: 1px solid #4CAF50;'>";
echo "<h3>📚 ลิงก์ที่เป็นประโยชน์:</h3>";
echo "<ul>";
echo "<li><strong>Shopee Open Platform:</strong> <a href='https://open.shopee.com/' target='_blank'>https://open.shopee.com/</a></li>";
echo "<li><strong>Lazada Open Platform:</strong> <a href='https://open.lazada.com/' target='_blank'>https://open.lazada.com/</a></li>";
echo "<li><strong>การตั้งค่าระบบ:</strong> <a href='settings.php' target='_blank'>settings.php</a></li>";
echo "<li><strong>ทดสอบ API:</strong> <a href='test_api.php' target='_blank'>test_api.php</a></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";

echo "<h2>5️⃣ สรุปสถานะ</h2>";

$current_time = date('Y-m-d H:i:s');
echo "<div style='background: #f8f9fa; padding: 20px; border: 2px solid #6c757d; border-radius: 10px;'>";
echo "<h3>📊 สถานะปัจจุบัน</h3>";
echo "<p><strong>วันเวลา:</strong> $current_time</p>";
echo "<p><strong>ฐานข้อมูล:</strong> " . strtoupper($db_info['type']) . " (" . $db_info['status'] . ")</p>";

// Count total settings
$shopeeSettings = count(dm_settings_get_all('shopee'));
$lazadaSettings = count(dm_settings_get_all('lazada'));

echo "<p><strong>Shopee Settings:</strong> $shopeeSettings รายการ</p>";
echo "<p><strong>Lazada Settings:</strong> $lazadaSettings รายการ</p>";

// Overall status
$totalIssues = count($shopeeIssues) + count($lazadaIssues);
if ($totalIssues == 0) {
    echo "<p><strong>สถานะ:</strong> <span style='color: green; font-weight: bold;'>✅ พร้อมใช้งาน</span></p>";
} else {
    echo "<p><strong>สถานะ:</strong> <span style='color: orange; font-weight: bold;'>⚠️ ต้องแก้ไข $totalIssues รายการ</span></p>";
}

echo "</div>";

// Test simple API call if possible
if ($totalIssues == 0) {
    echo "<h2>6️⃣ ทดสอบการดึงข้อมูลจริง</h2>";
    
    echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0;'>";
    echo "🚀 <strong>ระบบพร้อมใช้งาน!</strong> ลองทดสอบการดึงข้อมูลจาก API<br>";
    echo "<a href='?test_real_api=1' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 10px;'>ทดสอบดึงข้อมูลจริง</a>";
    echo "</div>";
    
    if (isset($_GET['test_real_api'])) {
        echo "<h3>📊 ผลการทดสอบดึงข้อมูลจริง</h3>";
        
        // Test Shopee orders
        try {
            echo "<h4>📱 ทดสอบ Shopee Orders:</h4>";
            $shopeeOrders = $shopeeAPI->getOrders(5); // Get 5 orders
            echo "<div style='background: #f0f8ff; padding: 10px; margin: 5px 0;'>";
            echo "📊 Total Sales: " . number_format($shopeeOrders['total_sales'] ?? 0, 2) . " THB<br>";
            echo "📦 Total Orders: " . ($shopeeOrders['total_orders'] ?? 0) . "<br>";
            echo "📋 Orders Retrieved: " . count($shopeeOrders['orders'] ?? []) . "<br>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div style='background: #ffe6e6; padding: 10px; margin: 5px 0;'>";
            echo "❌ Shopee Orders Error: " . $e->getMessage() . "<br>";
            echo "</div>";
        }
        
        // Test Lazada orders
        try {
            echo "<h4>🛒 ทดสอบ Lazada Orders:</h4>";
            $lazadaOrders = $lazadaAPI->getOrders(5); // Get 5 orders
            echo "<div style='background: #f0f8ff; padding: 10px; margin: 5px 0;'>";
            echo "📊 Total Sales: " . number_format($lazadaOrders['total_sales'] ?? 0, 2) . " THB<br>";
            echo "📦 Total Orders: " . ($lazadaOrders['total_orders'] ?? 0) . "<br>";
            echo "📋 Orders Retrieved: " . count($lazadaOrders['orders'] ?? []) . "<br>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div style='background: #ffe6e6; padding: 10px; margin: 5px 0;'>";
            echo "❌ Lazada Orders Error: " . $e->getMessage() . "<br>";
            echo "</div>";
        }
    }
}
?>
