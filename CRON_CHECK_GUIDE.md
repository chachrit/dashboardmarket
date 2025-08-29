# 📊 คำสั่งเช็คการทำงานของ Cron Job

## 1. เช็ค Log Files (วิธีที่ดีที่สุด)

### ใน cPanel File Manager:
```
เข้าไปดูที่: /home/yourusername/public_html/dashboardmarket/logs/
ไฟล์: cron_fetch_YYYYMMDD.log (เช่น cron_fetch_20250828.log)
```

### ใน cPanel Terminal (ถ้ามี):
```bash
# ดู log ล่าสุด
cd /home/yourusername/public_html/dashboardmarket
tail -20 logs/cron_fetch_$(date +%Y%m%d).log

# ดู log แบบ real-time
tail -f logs/cron_fetch_$(date +%Y%m%d).log

# นับจำนวนครั้งที่ทำงานสำเร็จ
grep "completed successfully" logs/cron_fetch_$(date +%Y%m%d).log | wc -l
```

### สิ่งที่ควรเห็นใน Log:
```
=====================================
🚀 Auto Fetch Orders (cPanel) - Wed Aug 28 14:15:01 +07 2025
=====================================
📊 Fetching all platforms...
✅ Shopee: Found 15 new orders
✅ Lazada: Found 8 new orders  
✅ TikTok: Found 2 new orders
✅ Cron job completed successfully at Wed Aug 28 14:15:45 +07 2025
📈 Next execution: Wed Aug 28 14:30:01 +07 2025
```

## 2. เช็คฐานข้อมูล

### ใน cPanel phpMyAdmin:
```sql
-- เช็คจำนวน orders ทั้งหมด
SELECT platform, COUNT(*) as order_count 
FROM orders 
GROUP BY platform;

-- เช็ค orders วันนี้
SELECT platform, COUNT(*) as today_orders
FROM orders 
WHERE DATE(created_at) = CURDATE()
GROUP BY platform;

-- เช็คการอัปเดตล่าสุด
SELECT platform, 
       COUNT(*) as total_orders,
       FROM_UNIXTIME(MAX(fetched_at)) as last_fetch_time
FROM orders 
GROUP BY platform;
```

### ผ่าน PHP (สร้างไฟล์ check_cron.php):
```php
<?php
require_once 'db.php';

echo "<h2>🔍 Cron Job Status Check</h2>";

try {
    $pdo = dm_db();
    
    // เช็คจำนวน orders
    $stmt = $pdo->query("SELECT platform, COUNT(*) as count FROM orders GROUP BY platform");
    echo "<h3>📊 Orders Count by Platform:</h3>";
    while ($row = $stmt->fetch()) {
        echo "<p>• {$row['platform']}: {$row['count']} orders</p>";
    }
    
    // เช็คการอัปเดตล่าสุด
    $stmt = $pdo->query("SELECT platform, datetime(MAX(fetched_at), 'unixepoch', 'localtime') as last_fetch FROM orders GROUP BY platform");
    echo "<h3>⏰ Last Fetch Times:</h3>";
    while ($row = $stmt->fetch()) {
        echo "<p>• {$row['platform']}: {$row['last_fetch']}</p>";
    }
    
    echo "<p>✅ Database connection successful!</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
```

## 3. เช็คผ่าน Dashboard

### เปิด Dashboard:
```
https://yourdomain.com/dashboardmarket/index_enhanced.php
```

### สิ่งที่ควรเห็น:
- 📊 ตัวเลข Orders และ Sales มีข้อมูล (ไม่ใช่ 0)
- 💾 Data Source แสดง "database" 
- ⏰ "อัปเดตล่าสุด" แสดงเวลาที่ใหม่
- 📋 "กิจกรรมล่าสุด" มีรายการ orders

## 4. เช็คผ่าน Order Management

### เปิด:
```  
https://yourdomain.com/dashboardmarket/order_management.php
```

### ดูในส่วน:
- 📊 Statistics แสดงจำนวน orders
- 📅 วันที่ fetch ล่าสุด
- 🔄 สถานะการทำงาน
