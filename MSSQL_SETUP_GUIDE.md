# 🗄️ MS SQL Server Configuration Guide

## 📋 ข้อมูลสำคัญ

### ✅ **คุณไม่ต้องสร้างฐานข้อมูลใหม่**
ระบบ Dashboard Market รองรับ MS SQL Server อยู่แล้ว แค่ใช้คำสั่ง SQL ที่เตรียมไว้

### 🔄 **การปรับแต่งใหม่**
- Frontend รีเฟรชทุก **3 นาที** (แทน 5 นาที)
- Cache timeout ลดเป็น **15 นาที** (แทน 30 นาที)
- แสดง countdown timer รีเฟรชถัดไป

## 🚀 วิธีการติดตั้ง

### 1. รันคำสั่ง SQL
```sql
-- เปิดไฟล์ setup_mssql.sql ใน SQL Server Management Studio
-- หรือรันผ่าน command line
sqlcmd -S server_name -d database_name -i setup_mssql.sql
```

### 2. ตั้งค่า Connection String

**แก้ไขไฟล์ `.env` หรือ environment variables:**
```bash
# MS SQL Server Configuration
DM_DB_DSN="sqlsrv:Server=localhost;Database=dashboardmarket"
DM_DB_USER="your_username"
DM_DB_PASS="your_password"

# หรือแยกส่วน
DM_DB_SERVER="localhost"
DM_DB_NAME="dashboardmarket" 
DM_DB_USER="your_username"
DM_DB_PASS="your_password"
```

**หรือใช้ connection string แบบเต็ม:**
```bash
DM_DB_DSN="sqlsrv:Server=localhost\SQLEXPRESS;Database=dashboardmarket;TrustServerCertificate=true"
```

### 3. ตรวจสอบการติดตั้ง
```bash
# ทดสอบการเชื่อมต่อ
php debug_api.php

# ทดสอบ database functions
php -r "
require_once 'api.php';
echo 'Testing database connection...' . PHP_EOL;
\$pdo = dm_db();
echo 'Driver: ' . \$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
echo 'Connection successful!' . PHP_EOL;
"
```

## ⚡ ข้อดีของ MS SQL Server

### 🔥 ประสิทธิภาพสูง
- **Indexing**: สร้าง indexes เฉพาะสำหรับ queries ที่ใช้บ่อย
- **Stored Procedures**: ประมวลผลเร็วกว่า dynamic queries
- **Views**: Query optimization อัตโนมัติ

### 🛡️ ความปลอดภัย
- **Authentication**: Windows Authentication + SQL Authentication
- **Encryption**: TLS/SSL connections
- **Audit**: Built-in logging และ monitoring

### 📊 เครื่องมือจัดการ
- **SSMS**: GUI ที่ใช้งานง่าย
- **Performance Monitor**: ติดตาม performance
- **Backup/Restore**: จัดการ backup อัตโนมัติ

## 🔧 การปรับแต่งเพิ่มเติม

### การปรับแต่งประสิทธิภาพ
```sql
-- เพิ่ม memory allocation
sp_configure 'max server memory (MB)', 4096;
RECONFIGURE;

-- เปิด optimize for ad hoc workloads
sp_configure 'optimize for ad hoc workloads', 1;
RECONFIGURE;
```

### การตั้งค่า Backup อัตโนมัติ
```sql
-- Full backup ทุกสัปดาห์
BACKUP DATABASE [dashboardmarket] 
TO DISK = 'C:\Backups\dashboardmarket_full.bak'
WITH COMPRESSION, INIT;

-- Transaction log backup ทุก 15 นาที
BACKUP LOG [dashboardmarket] 
TO DISK = 'C:\Backups\dashboardmarket_log.trn'
WITH COMPRESSION;
```

### การทำความสะอาดข้อมูลเก่า
```sql
-- รันทุกสัปดาห์เพื่อลบข้อมูลเก่า
EXEC sp_CleanupOldOrders @DaysToKeep = 90;
```

## 🌐 การใช้งานบน Cloud

### Azure SQL Database
```bash
DM_DB_DSN="sqlsrv:Server=your-server.database.windows.net;Database=dashboardmarket;Encrypt=yes;TrustServerCertificate=no"
DM_DB_USER="your-admin@your-server"
DM_DB_PASS="your-password"
```

### AWS RDS SQL Server
```bash
DM_DB_DSN="sqlsrv:Server=your-instance.region.rds.amazonaws.com;Database=dashboardmarket;Encrypt=yes"
DM_DB_USER="admin"
DM_DB_PASS="your-password"
```

## 📈 การตรวจสอบประสิทธิภาพ

### 1. ใน Dashboard
- ดูที่ "แหล่งข้อมูล" indicator
- 🟢 **Database** = เร็วที่สุด (< 50ms)
- 🟡 **API** = ช้ากว่า (1-3 วินาที)

### 2. ใน SQL Server
```sql
-- ดู query performance
SELECT 
    sql_text.text,
    query_stats.execution_count,
    query_stats.total_elapsed_time / query_stats.execution_count as avg_time_ms
FROM sys.dm_exec_query_stats query_stats
CROSS APPLY sys.dm_exec_sql_text(query_stats.sql_handle) sql_text
WHERE sql_text.text LIKE '%orders%'
ORDER BY avg_time_ms DESC;
```

### 3. Monitor Resource Usage
```sql
-- Memory usage
SELECT 
    counter_name,
    cntr_value
FROM sys.dm_os_performance_counters
WHERE counter_name IN ('Buffer cache hit ratio', 'Page life expectancy');

-- Connection count
SELECT COUNT(*) as active_connections
FROM sys.dm_exec_connections;
```

## 🔄 Migration จาก SQLite

หากคุณใช้ SQLite อยู่แล้วและต้องการย้ายไป MS SQL:

```sql
-- Export จาก SQLite
.output orders_export.sql
.dump orders
.dump dm_settings

-- Import ไป MS SQL (ปรับ syntax ตาม MS SQL)
-- ใช้ SQL Server Import/Export Wizard หรือ
-- แปลง SQL syntax แล้ว run ใน SSMS
```

## ⚠️ การแก้ปัญหา

### 1. Connection Issues
```bash
# ตรวจสอบ SQL Server ทำงานหรือไม่
netstat -an | findstr 1433

# ตรวจสอบ firewall
netsh advfirewall firewall show rule name="SQL Server"
```

### 2. Permission Issues
```sql
-- Grant permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON orders TO [your_user];
GRANT SELECT, INSERT, UPDATE, DELETE ON dm_settings TO [your_user];
```

### 3. Character Encoding
```php
// ใน config.php เพิ่ม
$pdo = new PDO($dsn, $user, $pass, [
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
    // ...other options
]);
```

## 🎯 สรุป

✅ **ไม่ต้องสร้างฐานข้อมูลใหม่** - ใช้ระบบเดิมได้เลย
✅ **รัน SQL script** ที่เตรียมไว้ 
✅ **ปรับ connection string** ให้ชี้ไป MS SQL Server
✅ **Frontend รีเฟรชทุก 3 นาที** พร้อม countdown timer
✅ **Cache timeout 15 นาที** เพื่อข้อมูลที่ทันสมัย

ระบบจะทำงานเร็วขึ้นและมีประสิทธิภาพดีขึ้นเมื่อใช้ MS SQL Server!
