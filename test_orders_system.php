<?php
require_once __DIR__ . '/db.php';

echo "<h2>🛒 ทดสอบระบบ Orders Database</h2>";

// 1. ตรวจสอบการเชื่อมต่อฐานข้อมูล
echo "<h3>🔗 ตรวจสอบการเชื่อมต่อ</h3>";
$db_info = dm_get_db_info();
echo "<div style='background: #f0f8ff; padding: 10px; border: 1px solid #0066cc; margin: 10px 0;'>";
echo "<strong>Database Type:</strong> " . $db_info['type'] . "<br>";
echo "<strong>Status:</strong> " . $db_info['status'] . "<br>";
if (isset($db_info['version'])) {
    echo "<strong>Version:</strong> " . $db_info['version'] . "<br>";
}
if (isset($db_info['database'])) {
    echo "<strong>Database:</strong> " . $db_info['database'] . "<br>";
}
echo "</div>";

// 2. ตรวจสอบตาราง orders
echo "<h3>📋 ตรวจสอบตาราง Orders</h3>";
$db = dm_db();
if (!$db) {
    echo "❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้<br>";
    exit;
}

try {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    // Check table existence
    if ($driver === 'mysql') {
        $stmt = $db->query("SHOW TABLES LIKE 'orders'");
        $tableExists = $stmt->rowCount() > 0;
    } elseif ($driver === 'sqlsrv') {
        $stmt = $db->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'orders'");
        $result = $stmt->fetch();
        $tableExists = $result['count'] > 0;
    } else { // sqlite
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='orders'");
        $tableExists = $stmt->rowCount() > 0;
    }
    
    if ($tableExists) {
        echo "✅ ตาราง orders พบแล้ว<br>";
        
        // Count existing orders
        $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
        $count = $stmt->fetch()['count'];
        echo "📊 จำนวน orders ทั้งหมด: <strong>$count</strong><br>";
        
        // Show sample data
        if ($count > 0) {
            echo "<h4>📋 ตัวอย่างข้อมูล orders:</h4>";
            $stmt = $db->query("SELECT platform, order_id, amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
            $samples = $stmt->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Platform</th><th>Order ID</th><th>Amount</th><th>Status</th><th>Created At</th></tr>";
            foreach ($samples as $row) {
                echo "<tr>";
                echo "<td>{$row['platform']}</td>";
                echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
                echo "<td>" . number_format($row['amount'], 2) . "</td>";
                echo "<td>{$row['status']}</td>";
                echo "<td>{$row['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "⚠️ ไม่พบตาราง orders<br>";
        echo "<h4>🛠️ สร้างตาราง orders</h4>";
        
        if ($driver === 'mysql') {
            $createTable = "CREATE TABLE IF NOT EXISTS `orders` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `platform` VARCHAR(20) NOT NULL,
                `order_id` VARCHAR(100) NOT NULL,
                `amount` DECIMAL(15,2) DEFAULT 0.00,
                `status` VARCHAR(50) NULL,
                `created_at` DATETIME NULL,
                `items` JSON NULL,
                `raw_data` JSON NULL,
                `fetched_at` BIGINT NULL,
                UNIQUE KEY `uk_platform_order_id` (`platform`, `order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        } elseif ($driver === 'sqlsrv') {
            $createTable = "CREATE TABLE orders (
                id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                platform NVARCHAR(20) NOT NULL,
                order_id NVARCHAR(100) NOT NULL,
                amount DECIMAL(15,2) DEFAULT 0,
                status NVARCHAR(50) NULL,
                created_at DATETIME2(7) NULL,
                items NVARCHAR(MAX) NULL,
                raw_data NVARCHAR(MAX) NULL,
                fetched_at BIGINT NULL,
                CONSTRAINT UQ_orders_platform_order_id UNIQUE (platform, order_id)
            )";
        } else { // sqlite
            $createTable = "CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                order_id TEXT NOT NULL,
                amount DECIMAL(15,2) DEFAULT 0,
                status TEXT NULL,
                created_at DATETIME NULL,
                items TEXT NULL,
                raw_data TEXT NULL,
                fetched_at INTEGER NULL,
                UNIQUE(platform, order_id)
            )";
        }
        
        $db->exec($createTable);
        echo "✅ สร้างตาราง orders สำเร็จ<br>";
        
        // Create indexes
        if ($driver === 'mysql') {
            $db->exec("CREATE INDEX idx_orders_platform_created_at ON orders (platform, created_at DESC)");
            $db->exec("CREATE INDEX idx_orders_fetched_at ON orders (fetched_at DESC)");
        } elseif ($driver === 'sqlsrv') {
            $db->exec("CREATE INDEX IX_orders_platform_created_at ON orders (platform, created_at DESC)");
            $db->exec("CREATE INDEX IX_orders_fetched_at ON orders (fetched_at DESC)");
        }
        
        echo "✅ สร้าง indexes สำเร็จ<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Table check error: " . $e->getMessage() . "<br>";
}

// 3. ทดสอบฟังก์ชัน orders
echo "<h3>🧪 ทดสอบฟังก์ชัน Orders</h3>";

// Test data
$test_platform = 'test_shopee';
$test_order_id = 'TEST' . time();
$test_amount = 999.99;
$test_status = 'COMPLETED';
$test_created_at = date('Y-m-d H:i:s');
$test_items = ['item1' => 'Test Product', 'quantity' => 2];
$test_raw_data = ['full_order_data' => 'test', 'api_response' => true];

echo "<h4>📝 ทดสอบการบันทึก Order</h4>";
$save_result = dm_order_save($test_platform, $test_order_id, $test_amount, $test_status, $test_created_at, $test_items, $test_raw_data);

if ($save_result) {
    echo "✅ บันทึก order สำเร็จ<br>";
    echo "📋 Order ID: <strong>$test_order_id</strong><br>";
    echo "💰 Amount: <strong>" . number_format($test_amount, 2) . "</strong><br>";
    echo "📊 Status: <strong>$test_status</strong><br>";
} else {
    echo "❌ บันทึก order ล้มเหลว<br>";
}

echo "<h4>🔍 ทดสอบการดึงข้อมูล Orders</h4>";
$orders = dm_orders_get($test_platform, date('Y-m-d'), date('Y-m-d'), 10);
echo "📊 พบ orders จาก $test_platform: <strong>" . count($orders) . "</strong> รายการ<br>";

if (count($orders) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr><th>Order ID</th><th>Amount</th><th>Status</th><th>Created At</th></tr>";
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
        echo "<td>" . number_format($order['amount'], 2) . "</td>";
        echo "<td>{$order['status']}</td>";
        echo "<td>{$order['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h4>📈 ทดสอบสถิติ Orders</h4>";
$stats = dm_orders_get_stats($test_platform, date('Y-m-d'), date('Y-m-d'));
echo "<div style='background: #f9f9f9; padding: 10px; border: 1px solid #ccc; margin: 10px 0;'>";
echo "📊 <strong>สถิติ Platform:</strong> $test_platform<br>";
echo "🛒 <strong>จำนวน Orders:</strong> " . $stats['total_orders'] . "<br>";
echo "💰 <strong>ยอดขายรวม:</strong> " . number_format($stats['total_sales'], 2) . "<br>";
echo "📊 <strong>ยอดขายเฉลี่ย:</strong> " . number_format($stats['avg_order_value'], 2) . "<br>";
echo "</div>";

echo "<h4>⏰ ทดสอบความสดใหม่ของข้อมูล</h4>";
$is_fresh = dm_orders_is_fresh($test_platform, 30);
echo $is_fresh ? "✅ ข้อมูลยังสดใหม่ (อายุน้อยกว่า 30 นาที)" : "⚠️ ข้อมูลเก่าแล้ว (อายุมากกว่า 30 นาที)";
echo "<br>";

// 4. ทดสอบ API endpoint
echo "<h3>🌐 ทดสอบ API Endpoints</h3>";

// Test db_info API
echo "<h4>🔍 ทดสอบ API: db_info</h4>";
$api_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api.php?action=db_info';
echo "<a href='$api_url' target='_blank'>$api_url</a><br>";

echo "<hr>";
echo "<h3>💡 คำแนะนำการใช้งาน</h3>";
echo "1. <strong>บันทึก Orders:</strong> ใช้ฟังก์ชัน dm_order_save() ใน API<br>";
echo "2. <strong>ดึงข้อมูล Orders:</strong> ใช้ฟังก์ชัน dm_orders_get() หรือ API endpoint<br>";
echo "3. <strong>ตรวจสอบสถิติ:</strong> ใช้ฟังก์ชัน dm_orders_get_stats()<br>";
echo "4. <strong>ตรวจสอบความสดใหม่:</strong> ใช้ฟังก์ชัน dm_orders_is_fresh()<br>";

echo "<hr>";
echo "<h3>📚 API Endpoints ที่เกี่ยวข้อง</h3>";
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
echo "- <strong>ข้อมูลฐานข้อมูล:</strong> <a href='{$base_url}/api.php?action=db_info' target='_blank'>{$base_url}/api.php?action=db_info</a><br>";
echo "- <strong>ดึงข้อมูล Orders:</strong> {$base_url}/api.php?action=get_orders&platform=shopee<br>";
echo "- <strong>สถิติ Orders:</strong> {$base_url}/api.php?action=get_stats&platform=shopee<br>";

?>
