# 📋 การตั้งค่า Cron Job ใน cPanel

## 🔧 ขั้นตอนการตั้งค่า

### 1. เข้าสู่ cPanel Dashboard
- เข้าสู่ระบบ cPanel ของคุณ
- ค้นหาและคลิก "Cron Jobs"

### 2. เพิ่ม Cron Job ใหม่

**ความถี่ที่แนะนำ:**
```
# ทุกชั่วโมง (แนะนำ)
0 * * * *

# ทุก 2 ชั่วโมง (ประหยัด resource)
0 */2 * * *

# ทุก 30 นาที (สำหรับร้านค้าที่มีออเดอร์เยอะ)
*/30 * * * *
```

**Command Line สำหรับ cPanel:**
```bash
/home/yourusername/public_html/dashboardmarket/cron_fetch_orders_cpanel.sh
```

### 3. หาที่ตั้งไฟล์ที่ถูกต้อง

**วิธีหา path ที่ถูกต้อง:**
1. ใน cPanel File Manager ดูที่ตั้งโฟลเดอร์ `dashboardmarket`
2. path ทั่วไปจะเป็น:
   - `/home/username/public_html/dashboardmarket/`
   - `/home/username/domains/yourdomain.com/public_html/dashboardmarket/`

### 4. ทดสอบ Cron Job

**เรียกใช้ทดสอบใน cPanel Terminal (ถ้ามี):**
```bash
cd /home/yourusername/public_html/dashboardmarket
./cron_fetch_orders_cpanel.sh
```

**ตรวจสอบ log:**
```bash
cat logs/cron_fetch_$(date +%Y%m%d).log
```

## 📊 ตัวอย่างการตั้งค่าใน cPanel

### ตัวอย่างที่ 1: ทุกชั่วโมง (แนะนำ)
```
Minute: 0
Hour: *
Day: *
Month: *
Weekday: *
Command: /home/yourusername/public_html/dashboardmarket/cron_fetch_orders_cpanel.sh
```

### ตัวอย่างที่ 2: ทุก 2 ชั่วโมง
```
Minute: 0
Hour: */2
Day: *
Month: *
Weekday: *
Command: /home/yourusername/public_html/dashboardmarket/cron_fetch_orders_cpanel.sh
```

### ตัวอย่างที่ 3: เฉพาะเวลาทำการ (9:00-18:00)
```
Minute: 0
Hour: 9-18
Day: *
Month: *
Weekday: 1-5
Command: /home/yourusername/public_html/dashboardmarket/cron_fetch_orders_cpanel.sh
```

## ⚠️ ข้อควรระวัง

### 1. PHP Version
- ตรวจสอบว่า cPanel ใช้ PHP version ไหน
- อาจต้องระบุ path PHP เฉพาะ เช่น:
  ```bash
  /usr/local/bin/php80 /home/user/public_html/dashboardmarket/fetch_orders.php all
  ```

### 2. Permissions
- ตรวจสอบว่าไฟล์ script มีสิทธิ์ execute (755)
- โฟลเดอร์ `logs/` ต้องมีสิทธิ์เขียนได้ (755 หรือ 775)

### 3. Resource Limits
- Shared hosting อาจจำกัดเวลาการทำงานของ script
- หากมีออเดอร์เยอะมาก อาจต้องลดความถี่ลง

### 4. Alternative: Webhook Setup
หากพบปัญหากับ Cron Job สามารถใช้ Webhook แทนได้:
```php
// webhook_fetch_orders.php
<?php
require_once 'pagination_manager.php';

$secretKey = 'your_secret_key_here';
if ($_GET['key'] !== $secretKey) {
    http_response_code(401);
    exit('Unauthorized');
}

$platforms = ['shopee', 'lazada'];
foreach ($platforms as $platform) {
    // Fetch orders...
}
echo "Orders fetched successfully";
?>
```

จากนั้นใช้ external cron service เรียก:
```
https://yourdomain.com/dashboardmarket/webhook_fetch_orders.php?key=your_secret_key_here
```

## 📈 การตรวจสอบผลลัพธ์

### 1. ตรวจสอบ Log Files
```bash
ls -la logs/
tail -f logs/cron_fetch_$(date +%Y%m%d).log
```

### 2. ตรวจสอบข้อมูลใน Dashboard
- เข้า `order_management.php` 
- ดูสถิติการดึงข้อมูล
- ตรวจสอบ "อัปเดตล่าสุด"

### 3. ตรวจสอบฐานข้อมูล
```bash
# ใน cPanel phpMyAdmin หรือ Terminal
php -r "
require_once 'api.php';
\$stats = getDatabaseStats('shopee');
echo 'Orders: ' . \$stats['totalOrders'] . \"\\n\";
echo 'Last fetch: ' . \$stats['lastFetchTime'] . \"\\n\";
"
```

## 🎯 สรุป

**การอัพเดทข้อมูล:**
- **Dashboard**: Auto-refresh ทุก 5 นาที
- **Cache**: ข้อมูลใหม่ < 30 นาที
- **Cron Job**: ดึงข้อมูลใหม่ทุก 1-2 ชั่วโมง

**cPanel Compatible**: ✅ ใช้ได้ แต่ต้องปรับ path และ permissions
