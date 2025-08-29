<?php
// ตรวจสอบการเชื่อมต่อ MSSQL Server ในไฟล์ db.php
echo "<h2>🔍 ตรวจสอบการเชื่อมต่อ MSSQL Server</h2>";

// 1. ตรวจสอบ PHP Extensions
echo "<h3>📦 PHP Extensions</h3>";
$extensions = ['sqlsrv', 'pdo_sqlsrv'];
$hasExtensions = true;
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '✅ มี' : '❌ ไม่มี';
    echo "- $ext: $status<br>";
    if (!$loaded) $hasExtensions = false;
}

if (!$hasExtensions) {
    echo "<div style='background: #ffe6e6; padding: 10px; border: 1px solid #ff0000; margin: 10px 0;'>";
    echo "❌ <strong>ขาด PHP Extensions สำหรับ MSSQL</strong><br>";
    echo "- ติดตั้ง Microsoft Drivers for PHP for SQL Server<br>";
    echo "- หรือเปิด extension ใน php.ini<br>";
    echo "</div>";
}

// 2. ตรวจสอบการตั้งค่า Environment Variables
echo "<h3>🌍 Environment Variables</h3>";
$env_vars = ['DM_DB_DSN', 'DM_DB_SERVER', 'DM_DB_NAME', 'DM_DB_USER', 'DM_DB_PASS'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value !== false) {
        $display_value = ($var === 'DM_DB_PASS') ? str_repeat('*', strlen($value)) : $value;
        echo "- $var: ✅ $display_value<br>";
    } else {
        echo "- $var: ❌ ไม่ได้ตั้งค่า (จะใช้ default)<br>";
    }
}

// 3. แสดงการตั้งค่าที่จะใช้จริง
echo "<h3>⚙️ การตั้งค่าที่จะใช้</h3>";
$dsn  = getenv('DM_DB_DSN');
$user = getenv('DM_DB_USER') ?: null;
$pass = getenv('DM_DB_PASS') ?: null;

if (!$dsn) {
    $server = getenv('DM_DB_SERVER') ?: '203.154.130.236';
    $name   = getenv('DM_DB_NAME')   ?: 'realtime_marketplace';
    $user   = getenv('DM_DB_USER')   ?: 'sa';
    $pass   = getenv('DM_DB_PASS')   ?: 'Journal@25';
    $dsn = 'sqlsrv:Server=' . $server . ';Database=' . $name;
}

echo "<div style='background: #f0f8ff; padding: 10px; border: 1px solid #0066cc; margin: 10px 0;'>";
echo "<strong>DSN:</strong> $dsn<br>";
echo "<strong>Username:</strong> $user<br>";
echo "<strong>Password:</strong> " . ($pass ? str_repeat('*', strlen($pass)) : 'NULL') . "<br>";
echo "</div>";

// 4. ทดสอบการเชื่อมต่อแบบ Manual
echo "<h3>🔗 ทดสอบการเชื่อมต่อแบบ Manual</h3>";
try {
    echo "🔄 กำลังเชื่อมต่อด้วย PDO...<br>";
    
    $start_time = microtime(true);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10  // 10 seconds timeout
    ]);
    $connect_time = round((microtime(true) - $start_time) * 1000, 2);
    
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    echo "✅ <strong>เชื่อมต่อสำเร็จ!</strong><br>";
    echo "📊 Database Driver: <strong>$driver</strong><br>";
    echo "⏱️ Connection Time: <strong>{$connect_time}ms</strong><br>";
    
    if ($driver === 'sqlsrv') {
        // ทดสอบ Query เบื้องต้น
        echo "<h4>🧪 ทดสอบ SQL Queries</h4>";
        
        // SQL Server Version
        $stmt = $pdo->query("SELECT @@VERSION as version");
        $version = $stmt->fetch();
        echo "🖥️ <strong>SQL Server Version:</strong><br>";
        echo "<small>" . htmlspecialchars(substr($version['version'], 0, 200)) . "...</small><br><br>";
        
        // Current Database
        $stmt = $pdo->query("SELECT DB_NAME() as dbname");
        $dbInfo = $stmt->fetch();
        echo "🗄️ <strong>Current Database:</strong> " . $dbInfo['dbname'] . "<br>";
        
        // Check dm_settings table
        echo "<h4>📋 ตรวจสอบตาราง dm_settings</h4>";
        try {
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_NAME = 'dm_settings'
            ");
            $tableExists = $stmt->fetch()['count'] > 0;
            
            if ($tableExists) {
                echo "✅ ตาราง dm_settings พบแล้ว<br>";
                
                // Count records
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM dm_settings");
                $recordCount = $stmt->fetch()['count'];
                echo "📊 จำนวน records: <strong>$recordCount</strong><br>";
                
                // Show sample data
                if ($recordCount > 0) {
                    echo "<h5>📋 ข้อมูลตัวอย่าง (5 records แรก):</h5>";
                    $stmt = $pdo->query("SELECT TOP 5 scope, name, value FROM dm_settings ORDER BY scope, name");
                    $samples = $stmt->fetchAll();
                    
                    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                    echo "<tr><th>Scope</th><th>Name</th><th>Value</th></tr>";
                    foreach ($samples as $row) {
                        $value = strlen($row['value']) > 50 ? substr($row['value'], 0, 50) . '...' : $row['value'];
                        echo "<tr>";
                        echo "<td>{$row['scope']}</td>";
                        echo "<td>{$row['name']}</td>";
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "⚠️ ไม่พบตาราง dm_settings (จะถูกสร้างอัตโนมัติ)<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error checking table: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "⚠️ <strong>Warning:</strong> ไม่ใช่ MSSQL Driver (got: $driver)<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>การเชื่อมต่อล้มเหลว</strong><br>";
    echo "<div style='background: #ffe6e6; padding: 10px; border: 1px solid #ff0000; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Code:</strong> " . $e->getCode() . "<br>";
    echo "</div>";
    
    // แสดงคำแนะนำการแก้ไข
    echo "<h4>💡 คำแนะนำการแก้ไข:</h4>";
    echo "1. ตรวจสอบว่าเซิร์ฟเวอร์ 203.154.130.236 เปิดรับการเชื่อมต่อ<br>";
    echo "2. ตรวจสอบ username/password (sa/Journal@25)<br>";
    echo "3. ตรวจสอบ database name (realtime_marketplace)<br>";
    echo "4. ตรวจสอบ firewall และ SQL Server configuration<br>";
}

// 5. ทดสอบการเชื่อมต่อผ่านฟังก์ชัน dm_db()
echo "<hr><h3>🧪 ทดสอบฟังก์ชัน dm_db()</h3>";
try {
    require_once 'db.php';
    
    echo "🔄 กำลังเรียกใช้ dm_db()...<br>";
    $start_time = microtime(true);
    $pdo = dm_db();
    $func_time = round((microtime(true) - $start_time) * 1000, 2);
    
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "✅ <strong>dm_db() ทำงานสำเร็จ!</strong><br>";
    echo "📊 ใช้ Database Driver: <strong>$driver</strong><br>";
    echo "⏱️ Function Time: <strong>{$func_time}ms</strong><br>";
    
    if ($driver === 'sqlsrv') {
        echo "🎉 <strong>กำลังใช้ MSSQL Server</strong> (ถูกต้อง)<br>";
        
        // ทดสอบฟังก์ชัน settings
        echo "<h4>🧪 ทดสอบฟังก์ชัน Settings</h4>";
        $test_key = 'connection_test_' . time();
        $test_value = 'OK - ' . date('Y-m-d H:i:s');
        
        // Test set
        dm_settings_set('test', $test_key, $test_value);
        echo "✅ dm_settings_set() ทำงาน<br>";
        
        // Test get
        $retrieved = dm_settings_get('test', $test_key, 'FAIL');
        if ($retrieved === $test_value) {
            echo "✅ dm_settings_get() ทำงานถูกต้อง<br>";
        } else {
            echo "❌ dm_settings_get() มีปัญหา: expected '$test_value', got '$retrieved'<br>";
        }
        
        // Test get_all
        $all_settings = dm_settings_get_all('test');
        if (isset($all_settings[$test_key])) {
            echo "✅ dm_settings_get_all() ทำงานถูกต้อง<br>";
        } else {
            echo "❌ dm_settings_get_all() มีปัญหา<br>";
        }
        
    } else {
        echo "⚠️ <strong>Fallback ไป $driver</strong> (MSSQL ไม่สำเร็จ)<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>dm_db() ล้มเหลว</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<div style='text-align: center; padding: 20px; background: #f8f9fa;'>";
echo "<h3>📊 สรุปผลการตรวจสอบ</h3>";
if (isset($pdo) && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlsrv') {
    echo "✅ <strong style='color: green;'>การเชื่อมต่อ MSSQL Server ใช้งานได้ปกติ</strong>";
} else {
    echo "❌ <strong style='color: red;'>การเชื่อมต่อ MSSQL Server มีปัญหา</strong>";
}
echo "</div>";
?>
