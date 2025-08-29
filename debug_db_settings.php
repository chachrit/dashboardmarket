<?php
// ดูข้อมูลจริงใน dm_settings table
echo "<h1>🔍 ตรวจสอบข้อมูลจริงใน dm_settings</h1>";

require_once __DIR__ . '/db.php';

try {
    $pdo = dm_db();
    
    echo "<h2>📊 ข้อมูลทั้งหมดใน dm_settings</h2>";
    $stmt = $pdo->query("SELECT * FROM dm_settings ORDER BY scope, name");
    $all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Scope</th><th>Name</th><th>Value</th><th>Updated At</th></tr>";
    
    foreach ($all_data as $row) {
        $value = $row['value'];
        if (strlen($value) > 100) {
            $value = substr($value, 0, 100) . '...';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['scope']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "<td>" . htmlspecialchars($row['updated_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🛒 ข้อมูล Lazada ที่พบ</h2>";
    
    // ทดลองหา scope ที่เป็น lazada
    $stmt = $pdo->prepare("SELECT * FROM dm_settings WHERE scope = 'lazada' ORDER BY name");
    $stmt->execute();
    $lazada_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($lazada_data)) {
        echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid red;'>";
        echo "❌ <strong>ไม่พบข้อมูลใน scope = 'lazada'</strong><br>";
        
        // หาข้อมูลที่เกี่ยวข้องกับ lazada
        $stmt = $pdo->prepare("SELECT * FROM dm_settings WHERE scope LIKE '%lazada%' OR name LIKE '%lazada%' ORDER BY scope, name");
        $stmt->execute();
        $related_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($related_data)) {
            echo "<br><strong>พบข้อมูลที่เกี่ยวข้อง:</strong><br>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
            echo "<tr style='background: #f8f9fa;'><th>Scope</th><th>Name</th><th>Value</th></tr>";
            foreach ($related_data as $row) {
                $value = strlen($row['value']) > 50 ? substr($row['value'], 0, 50) . '...' : $row['value'];
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['scope']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($value) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "</div>";
    } else {
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4CAF50;'>";
        echo "✅ <strong>พบข้อมูล Lazada " . count($lazada_data) . " รายการ</strong><br>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background: #f8f9fa;'><th>Name</th><th>Value</th><th>Updated At</th></tr>";
        foreach ($lazada_data as $row) {
            $value = strlen($row['value']) > 50 ? substr($row['value'], 0, 50) . '...' : $row['value'];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($value) . "</td>";
            echo "<td>" . htmlspecialchars($row['updated_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    echo "<h2>🛠️ ทดสอบ getAPIConfig()</h2>";
    
    if (file_exists(__DIR__ . '/api.php')) {
        require_once __DIR__ . '/api.php';
        $config = getAPIConfig();
        
        echo "<div style='background: #f0f8ff; padding: 15px; border: 1px solid #0066cc;'>";
        echo "<strong>Lazada Config จาก getAPIConfig():</strong><br>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background: #f8f9fa;'><th>Key</th><th>Value</th><th>Status</th></tr>";
        
        foreach ($config['lazada'] as $key => $value) {
            $status = empty($value) ? "❌ ว่างเปล่า" : "✅ มีข้อมูล";
            $display_value = empty($value) ? "ไม่มี" : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($key) . "</td>";
            echo "<td>" . htmlspecialchars($display_value) . "</td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid red;'>";
    echo "<strong>❌ เกิดข้อผิดพลาด:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br>";
    echo "</div>";
}

echo "<hr>";
echo "<small>ตรวจสอบเมื่อ: " . date('Y-m-d H:i:s') . "</small>";
?>
