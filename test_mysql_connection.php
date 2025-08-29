<?php
// Test MySQL connection for cPanel
echo "<h2>🔍 ทดสอบการเชื่อมต่อ MySQL</h2>";

// 1. Check PHP Extensions
echo "<h3>📦 PHP Extensions</h3>";
$extensions = ['pdo', 'pdo_mysql', 'mysql', 'mysqli'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✅ มี' : '❌ ไม่มี';
    echo "- $ext: $status<br>";
}

// 2. Test connection form
if (!$_POST) {
    echo "<h3>🔗 ทดสอบการเชื่อมต่อ MySQL</h3>";
    echo "<form method='post' style='background: #f0f8ff; padding: 15px; border: 1px solid #ccc;'>";
    echo "<table>";
    echo "<tr><td>Host:</td><td><input type='text' name='host' value='localhost' required style='width: 200px;'></td></tr>";
    echo "<tr><td>Database:</td><td><input type='text' name='database' value='realtime_marketplace' required style='width: 200px;'></td></tr>";
    echo "<tr><td>Username:</td><td><input type='text' name='username' value='' required style='width: 200px;' placeholder='MySQL username'></td></tr>";
    echo "<tr><td>Password:</td><td><input type='password' name='password' value='' style='width: 200px;' placeholder='MySQL password'></td></tr>";
    echo "</table><br>";
    echo "<button type='submit' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer;'>ทดสอบการเชื่อมต่อ</button>";
    echo "</form>";
    
    echo "<hr>";
    echo "<h3>💡 ข้อมูลสำหรับ cPanel</h3>";
    echo "- <strong>Host:</strong> localhost (ใน cPanel มักจะเป็น localhost)<br>";
    echo "- <strong>Database:</strong> username_dbname (รูปแบบใน cPanel)<br>";
    echo "- <strong>Username:</strong> username_dbuser (รูปแบบใน cPanel)<br>";
    echo "- <strong>Password:</strong> รหัสผ่านที่ตั้งไว้<br>";
}

// 3. Process connection test
if ($_POST) {
    $host = $_POST['host'];
    $database = $_POST['database'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<h3>🧪 ผลการทดสอบการเชื่อมต่อ</h3>";
    echo "<div style='background: #f0f8ff; padding: 10px; border: 1px solid #0066cc; margin: 10px 0;'>";
    echo "<strong>Host:</strong> $host<br>";
    echo "<strong>Database:</strong> $database<br>";
    echo "<strong>Username:</strong> $username<br>";
    echo "<strong>Password:</strong> " . str_repeat('*', strlen($password)) . "<br>";
    echo "</div>";
    
    try {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        
        echo "🔄 กำลังเชื่อมต่อ...<br>";
        $start_time = microtime(true);
        
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        $connect_time = round((microtime(true) - $start_time) * 1000, 2);
        
        echo "✅ <strong style='color: green;'>เชื่อมต่อสำเร็จ!</strong><br>";
        echo "⏱️ Connection Time: <strong>{$connect_time}ms</strong><br>";
        
        // Test basic queries
        echo "<h4>🧪 ทดสอบ SQL Queries</h4>";
        
        // MySQL Version
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "🖥️ <strong>MySQL Version:</strong> " . $version['version'] . "<br>";
        
        // Current Database
        $stmt = $pdo->query("SELECT DATABASE() as dbname");
        $dbInfo = $stmt->fetch();
        echo "🗄️ <strong>Current Database:</strong> " . $dbInfo['dbname'] . "<br>";
        
        // Check if dm_settings table exists
        echo "<h4>📋 ตรวจสอบตาราง dm_settings</h4>";
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'dm_settings'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                echo "✅ ตาราง dm_settings พบแล้ว<br>";
                
                // Count records
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM dm_settings");
                $count = $stmt->fetch()['count'];
                echo "📊 จำนวน records: <strong>$count</strong><br>";
                
                // Show sample data
                if ($count > 0) {
                    echo "<h5>📋 ข้อมูลตัวอย่าง:</h5>";
                    $stmt = $pdo->query("SELECT scope, name, LEFT(value, 50) as value_preview FROM dm_settings ORDER BY scope, name LIMIT 5");
                    $samples = $stmt->fetchAll();
                    
                    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                    echo "<tr><th>Scope</th><th>Name</th><th>Value (Preview)</th></tr>";
                    foreach ($samples as $row) {
                        echo "<tr>";
                        echo "<td>{$row['scope']}</td>";
                        echo "<td>{$row['name']}</td>";
                        echo "<td>" . htmlspecialchars($row['value_preview']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                // Test dm_settings functions
                echo "<h4>🧪 ทดสอบฟังก์ชัน Settings</h4>";
                
                // Create functions inline for testing
                $test_scope = 'mysql_test';
                $test_key = 'connection_test';
                $test_value = 'MySQL OK - ' . date('Y-m-d H:i:s');
                
                // Test INSERT/UPDATE
                $now = time();
                $stmt = $pdo->prepare('INSERT INTO dm_settings (scope, name, value, updated_at) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value), updated_at=VALUES(updated_at)');
                $stmt->execute([$test_scope, $test_key, $test_value, $now]);
                echo "✅ Insert/Update test passed<br>";
                
                // Test SELECT
                $stmt = $pdo->prepare('SELECT value FROM dm_settings WHERE scope = ? AND name = ?');
                $stmt->execute([$test_scope, $test_key]);
                $result = $stmt->fetch();
                if ($result && $result['value'] === $test_value) {
                    echo "✅ Select test passed<br>";
                } else {
                    echo "❌ Select test failed<br>";
                }
                
            } else {
                echo "⚠️ ไม่พบตาราง dm_settings<br>";
                echo "<h5>🛠️ สร้างตาราง dm_settings</h5>";
                
                $createTable = "CREATE TABLE IF NOT EXISTS `dm_settings` (
                    `scope` VARCHAR(50) NOT NULL,
                    `name` VARCHAR(100) NOT NULL,
                    `value` TEXT NULL,
                    `updated_at` BIGINT NULL,
                    PRIMARY KEY (`scope`, `name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $pdo->exec($createTable);
                echo "✅ สร้างตาราง dm_settings สำเร็จ<br>";
                
                // Insert initial data
                $stmt = $pdo->prepare("INSERT INTO dm_settings (scope, name, value, updated_at) VALUES (?,?,?,?)");
                $platforms = [
                    ['shopee', 'enabled', 'false'],
                    ['lazada', 'enabled', 'false'],
                    ['tiktok', 'enabled', 'false']
                ];
                
                foreach ($platforms as $platform) {
                    $stmt->execute([$platform[0], $platform[1], $platform[2], time()]);
                }
                echo "✅ เพิ่มข้อมูลเริ่มต้นสำเร็จ<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Table check error: " . $e->getMessage() . "<br>";
        }
        
        // Generate environment variables
        echo "<h4>⚙️ Environment Variables สำหรับ cPanel</h4>";
        echo "<div style='background: #f9f9f9; padding: 10px; border: 1px solid #ccc; font-family: monospace;'>";
        echo "# เพิ่มในไฟล์ .htaccess หรือ environment config<br>";
        echo "SetEnv DM_DB_DSN \"mysql:host=$host;dbname=$database;charset=utf8mb4\"<br>";
        echo "SetEnv DM_DB_USER \"$username\"<br>";
        echo "SetEnv DM_DB_PASS \"" . htmlspecialchars($password) . "\"<br>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "❌ <strong style='color: red;'>การเชื่อมต่อล้มเหลว</strong><br>";
        echo "<div style='background: #ffe6e6; padding: 10px; border: 1px solid #ff0000; margin: 10px 0;'>";
        echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>Code:</strong> " . $e->getCode() . "<br>";
        echo "</div>";
        
        echo "<h4>💡 คำแนะนำการแก้ไข:</h4>";
        echo "1. ตรวจสอบ Host (มักเป็น localhost)<br>";
        echo "2. ตรวจสอบชื่อฐานข้อมูล (อาจมี prefix เช่น username_dbname)<br>";
        echo "3. ตรวจสอบ username (อาจมี prefix เช่น username_dbuser)<br>";
        echo "4. ตรวจสอบรหัสผ่าน<br>";
        echo "5. ตรวจสอบว่าฐานข้อมูลถูกสร้างแล้ว<br>";
    }
    
    echo "<hr>";
    echo "<button onclick='window.location.href=\"" . $_SERVER['PHP_SELF'] . "\"' style='background: #2196F3; color: white; padding: 10px 20px; border: none; cursor: pointer;'>ทดสอบใหม่</button>";
}

echo "<hr>";
echo "<h3>📚 ขั้นตอนการตั้งค่าใน cPanel</h3>";
echo "1. เข้า cPanel > MySQL Databases<br>";
echo "2. สร้าง Database: realtime_marketplace<br>";
echo "3. สร้าง User และกำหนด Password<br>";
echo "4. Add User to Database พร้อม All Privileges<br>";
echo "5. ทดสอบการเชื่อมต่อด้วยไฟล์นี้<br>";
echo "6. รัน SQL จากไฟล์ setup_mysql.sql<br>";
?>
