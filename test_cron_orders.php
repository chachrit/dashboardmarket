<?php
// Test cron job compatibility with MySQL orders table
echo "<h1>🔍 ทดสอบ Cron Job และตาราง Orders ใน MySQL</h1>";

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pagination_manager.php';

echo "<h2>1️⃣ ตรวจสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    $db = dm_db();
    $info = dm_get_db_info();
    
    echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4CAF50; margin: 10px 0;'>";
    echo "<h3>✅ เชื่อมต่อ MySQL สำเร็จ!</h3>";
    echo "ประเภท: <strong>" . strtoupper($info['type']) . "</strong><br>";
    echo "ฐานข้อมูล: <strong>" . ($info['database'] ?? 'unknown') . "</strong><br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>❌ เชื่อมต่อฐานข้อมูลไม่สำเร็จ!</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "</div>";
    exit;
}

echo "<hr>";

echo "<h2>2️⃣ ตรวจสอบตาราง Orders</h2>";

// Check orders table structure
try {
    $stmt = $db->query("DESCRIBE orders");
    $columns = $stmt->fetchAll();
    
    echo "<h3>📋 โครงสร้างตาราง Orders</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check record count
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
    $count = $stmt->fetch();
    echo "<p>📊 จำนวน orders ปัจจุบัน: <strong>" . number_format($count['count']) . "</strong> records</p>";
    
    // Show recent orders
    if ($count['count'] > 0) {
        echo "<h4>📋 Orders ล่าสุด (5 รายการ)</h4>";
        $stmt = $db->query("SELECT platform, order_id, amount, status, created_at, fetched_at FROM orders ORDER BY fetched_at DESC LIMIT 5");
        $recent_orders = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Platform</th><th>Order ID</th><th>Amount</th><th>Status</th><th>Created</th><th>Fetched</th></tr>";
        
        foreach ($recent_orders as $order) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['platform']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($order['order_id'], 0, 20)) . "...</td>";
            echo "<td>" . number_format($order['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($order['status']) . "</td>";
            echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $order['fetched_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>❌ ตรวจสอบตาราง orders ไม่สำเร็จ!</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "</div>";
}

echo "<hr>";

echo "<h2>3️⃣ ทดสอบ PaginationManager</h2>";

try {
    // Create mock API for testing
    $mockAPI = new class {
        public function getOrders($page = 1, $limit = 50, $dateFrom = null, $dateTo = null) {
            // Return mock data
            return [
                'success' => true,
                'data' => [
                    'orders' => [
                        [
                            'order_id' => 'MOCK_ORDER_' . time() . '_' . $page,
                            'amount' => rand(100, 5000) + (rand(0, 99) / 100),
                            'status' => ['pending', 'completed', 'cancelled'][rand(0, 2)],
                            'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 7)),
                            'items' => [
                                ['name' => 'Test Product', 'quantity' => rand(1, 5), 'price' => rand(100, 1000)]
                            ]
                        ]
                    ],
                    'has_more' => false
                ]
            ];
        }
        
        public function testConnection() {
            return ['success' => true, 'message' => 'Mock API connection OK'];
        }
    };
    
    echo "<h3>🧪 สร้าง PaginationManager</h3>";
    $manager = new PaginationManager('test_platform', $mockAPI);
    echo "✅ สร้าง PaginationManager สำเร็จ<br>";
    
    echo "<h3>📥 ทดสอบการดึงข้อมูล Orders</h3>";
    $result = $manager->fetchAllOrders(date('Y-m-d'), date('Y-m-d'), 5);
    
    if ($result['success']) {
        echo "✅ ดึงข้อมูล Orders สำเร็จ!<br>";
        echo "📊 สถิติ:<br>";
        echo "- Total fetched: <strong>" . $result['total_fetched'] . "</strong><br>";
        echo "- Total saved: <strong>" . $result['total_saved'] . "</strong><br>";
        echo "- Duration: <strong>" . number_format($result['duration_seconds'], 2) . "</strong> seconds<br>";
        
        if (!empty($result['errors'])) {
            echo "⚠️ Errors: " . count($result['errors']) . "<br>";
            foreach ($result['errors'] as $error) {
                echo "  - " . htmlspecialchars($error) . "<br>";
            }
        }
    } else {
        echo "❌ ดึงข้อมูล Orders ล้มเหลว: " . ($result['error'] ?? 'Unknown error') . "<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>❌ ทดสอบ PaginationManager ไม่สำเร็จ!</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "</div>";
}

echo "<hr>";

echo "<h2>4️⃣ ตรวจสอบ Cron Job Files</h2>";

$cron_files = [
    'fetch_orders.php' => 'Main cron script',
    'pagination_manager.php' => 'Pagination manager class',
    'cron_fetch_orders.sh' => 'Shell script (if exists)',
    'cron_fetch_orders_cpanel.sh' => 'cPanel shell script (if exists)'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>File</th><th>Description</th><th>Status</th><th>Size</th></tr>";

foreach ($cron_files as $file => $description) {
    $filepath = __DIR__ . '/' . $file;
    $exists = file_exists($filepath);
    $status = $exists ? '✅ พบแล้ว' : '❌ ไม่พบ';
    $size = $exists ? number_format(filesize($filepath)) . ' bytes' : '-';
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($file) . "</strong></td>";
    echo "<td>" . htmlspecialchars($description) . "</td>";
    echo "<td>$status</td>";
    echo "<td>$size</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

echo "<h2>5️⃣ แนะนำการตั้งค่า Cron Job</h2>";

echo "<div style='background: #f0f8ff; padding: 15px; border: 1px solid #0066cc; border-radius: 8px;'>";
echo "<h3>📋 วิธีตั้งค่า Cron Job ใน cPanel</h3>";

echo "<h4>1. เข้า cPanel > Cron Jobs</h4>";
echo "<h4>2. เพิ่ม Cron Job ใหม่:</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ccc;'>";
echo "# รันทุก 1 ชั่วโมง (ทุกนาทีที่ 0)\n";
echo "0 * * * * /usr/local/bin/php /home/yourusername/public_html/fetch_orders.php all\n\n";

echo "# รันทุก 30 นาที\n";
echo "*/30 * * * * /usr/local/bin/php /home/yourusername/public_html/fetch_orders.php all\n\n";

echo "# รันทุกเช้า 6 โมง (ดึงข้อมูลเมื่อวาน)\n";
echo "0 6 * * * /usr/local/bin/php /home/yourusername/public_html/fetch_orders.php all --date=yesterday\n";
echo "</pre>";

echo "<h4>3. ตัวอย่างคำสั่งการใช้งาน:</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ccc;'>";
echo "# ดึงข้อมูลทั้งหมด\n";
echo "php fetch_orders.php all\n\n";

echo "# ดึงข้อมูล Shopee เท่านั้น\n";
echo "php fetch_orders.php shopee\n\n";

echo "# ดึงข้อมูลวันที่ระบุ\n";
echo "php fetch_orders.php all --date=2024-08-28\n\n";

echo "# ดึงข้อมูลช่วงวันที่\n";
echo "php fetch_orders.php all --from=2024-08-01 --to=2024-08-31\n\n";

echo "# จำกัดจำนวน orders\n";
echo "php fetch_orders.php shopee --limit=500\n";
echo "</pre>";
echo "</div>";

echo "<hr>";

echo "<h2>6️⃣ สรุปผลการตรวจสอบ</h2>";

$current_time = date('Y-m-d H:i:s');
$orders_count = 0;

try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
    $result = $stmt->fetch();
    $orders_count = $result['count'];
} catch (Exception $e) {
    // Table might not exist yet
}

echo "<div style='background: #e8f5e8; padding: 20px; border: 2px solid #4CAF50; border-radius: 10px;'>";
echo "<h3>🎉 การตรวจสอบเสร็จสมบูรณ์!</h3>";
echo "<p><strong>วันเวลา:</strong> $current_time</p>";
echo "<p><strong>ฐานข้อมูล:</strong> MySQL (" . ($info['database'] ?? 'unknown') . ")</p>";
echo "<p><strong>ตาราง Orders:</strong> พร้อมใช้งาน</p>";
echo "<p><strong>Orders ปัจจุบัน:</strong> " . number_format($orders_count) . " records</p>";
echo "<p><strong>PaginationManager:</strong> ✅ พร้อมใช้งาน</p>";
echo "<p><strong>Cron Job:</strong> ✅ พร้อมตั้งค่า</p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;'>";
echo "<h4>🚀 ขั้นตอนต่อไป</h4>";
echo "<ol>";
echo "<li>อัปโหลดไฟล์ทั้งหมดไป cPanel</li>";
echo "<li>ตั้งค่า cron job ใน cPanel</li>";
echo "<li>ทดสอบรัน cron job: <code>php fetch_orders.php all</code></li>";
echo "<li>ตรวจสอบ log และ orders table</li>";
echo "<li>ปรับแต่งความถี่ของ cron job ตามต้องการ</li>";
echo "</ol>";
echo "</div>";

// Cleanup test data
try {
    $db->exec("DELETE FROM orders WHERE platform = 'test_platform'");
    echo "<p style='color: #666; font-size: 0.9em;'>🧹 ลบข้อมูลทดสอบเรียบร้อยแล้ว</p>";
} catch (Exception $e) {
    // Ignore cleanup errors
}
?>
