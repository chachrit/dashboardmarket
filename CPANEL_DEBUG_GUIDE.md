# cPanel Debugging Guide for Settings Save Issue

## ปัญหา
- ✅ บันทึกได้ใน localhost
- ❌ ไม่บันทึกใน cPanel

## ไฟล์ Debug ที่สร้างไว้

### 1. cpanel_debug.php
**วัตถุประสงค์:** ตรวจสอบ environment ของ cPanel
**วิธีใช้:** เปิดผ่าน browser
```
https://yourdomain.com/dashboardmarket/cpanel_debug.php
```
**ตรวจสอบ:**
- PHP version และ extensions
- File permissions
- Database connection
- Environment variables

### 2. cpanel_api_debug.php
**วัตถุประสงค์:** Debug API save_settings โดยละเอียด
**วิธีใช้:** เรียกผ่าน POST request เหมือน API จริง
```bash
curl -X POST "https://yourdomain.com/dashboardmarket/cpanel_api_debug.php?action=save_settings&platform=shopee" \
     -H "Content-Type: application/json" \
     -d '{"partner_id":"123","partner_key":"test","enabled":true}'
```

## สาเหตุที่เป็นไปได้

### 1. Database Connection Issues
- **SQL Server Driver ไม่มีใน cPanel**
  - ตรวจสอบ: extension `pdo_sqlsrv` หรือ `sqlsrv`
  - แก้ไข: ติดต่อ hosting provider หรือใช้ MySQL/SQLite แทน

### 2. File Permissions
- **โฟลเดอร์ /data ไม่มี write permission**
  - ตรวจสอบ: chmod 755 หรือ 775
  - แก้ไข: `chmod 775 data/`

### 3. PHP Configuration
- **Memory limit ต่ำเกินไป**
- **Execution time หมดเวลา**
- **Extensions ไม่ครบ**

### 4. Environment Variables
- **ไม่ได้ตั้ง environment variables สำหรับฐานข้อมูล**
  - ตรวจสอบใน cPanel File Manager หรือ .htaccess

## วิธีแก้ไขที่แนะนำ

### Option 1: ใช้ MySQL แทน SQL Server
แก้ไข `db.php`:
```php
if (!$dsn) {
    $server = getenv('DM_DB_SERVER') ?: 'localhost';
    $name   = getenv('DM_DB_NAME')   ?: 'your_mysql_db';
    $user   = getenv('DM_DB_USER')   ?: 'your_mysql_user';
    $pass   = getenv('DM_DB_PASS')   ?: 'your_mysql_pass';
    $dsn = 'mysql:host=' . $server . ';dbname=' . $name . ';charset=utf8mb4';
}
```

### Option 2: บังคับใช้ SQLite
แก้ไข `db.php` ให้ข้าม SQL Server:
```php
function dm_db() {
    static $pdo = null;
    if ($pdo) return $pdo;

    // Force SQLite for cPanel
    $dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dataDir)) { @mkdir($dataDir, 0775, true); }
    $dbPath = $dataDir . DIRECTORY_SEPARATOR . 'dashboardmarket.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // ... rest of the code
}
```

### Option 3: Environment Variables ใน .htaccess
สร้างไฟล์ `.htaccess` ใน root:
```apache
SetEnv DM_DB_DSN "mysql:host=localhost;dbname=your_db;charset=utf8mb4"
SetEnv DM_DB_USER "your_user"
SetEnv DM_DB_PASS "your_pass"
```

## ขั้นตอนการ Debug

1. **รันไฟล์ cpanel_debug.php ก่อน** - เพื่อดู environment
2. **ทดสอบ API ด้วย cpanel_api_debug.php** - เพื่อหา error จริง
3. **แก้ไขตาม error ที่พบ**
4. **ทดสอบด้วย API จริง** - api.php

## คำถามสำหรับ Hosting Provider

1. มี PHP extension `pdo_sqlsrv` ไหม?
2. สามารถเชื่อมต่อ SQL Server จาก PHP ได้ไหม?
3. MySQL database มีให้ใช้ไหม?
4. File permission policy เป็นอย่างไร?
