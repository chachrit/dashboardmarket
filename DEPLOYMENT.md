# Dashboard Market - Deployment Checklist

## ไฟล์ที่ต้องอัพโหลดไปยัง Server

### ไฟล์หลัก (จำเป็น)
- [ ] `index.php` - หน้าหลัก dashboard
- [ ] `settings.php` - หน้าตั้งค่า
- [ ] `platform.php` - หน้าแสดงข้อมูลแต่ละแพลตฟอร์ม
- [ ] `api.php` - API endpoint หลัก
- [ ] `db.php` - ฟังก์ชันจัดการฐานข้อมูล
- [ ] `.htaccess` - การตั้งค่า Apache
- [ ] `package.json` - ข้อมูล project (optional)
- [ ] `README.md` - คู่มือใช้งาน (optional)

### โฟลเดอร์ assets (จำเป็น)
- [ ] `assets/css/animations.css` - CSS animations
- [ ] `assets/js/animations.js` - JavaScript animations  
- [ ] `assets/cacert.pem` - SSL certificate bundle
- [ ] `styles.css` - CSS styles หลัก

### โฟลเดอร์ data (จำเป็น - ต้องสร้างใหม่บน server)
- [ ] `data/` - โฟลเดอร์สำหรับฐานข้อมูล (ต้องมี write permission)
- [ ] `data/dashboardmarket.sqlite` - ฐานข้อมูล (จะถูกสร้างอัตโนมัติ)

### ไฟล์ config (ต้องสร้างใหม่บน server)
- [ ] `config.php` - คัดลอกจาก `config.example.php` และแก้ไขค่า

## ขั้นตอนการ Deploy

### 1. เตรียม Server
```bash
# สร้างโฟลเดอร์และตั้งค่า permissions
mkdir -p /path/to/your/website
mkdir -p /path/to/your/website/data
mkdir -p /path/to/your/website/logs
chmod 755 /path/to/your/website
chmod 777 /path/to/your/website/data
chmod 777 /path/to/your/website/logs
```

### 2. อัพโหลดไฟล์
- อัพโหลดไฟล์ทั้งหมดไปยัง document root ของ website
- ตรวจสอบว่าโครงสร้างโฟลเดอร์ถูกต้อง

### 3. ตั้งค่า Config
```bash
# คัดลอกและแก้ไข config
cp config.example.php config.php
nano config.php
```

### 4. ตั้งค่า Permissions
```bash
# ตั้งค่า permissions สำหรับไฟล์และโฟลเดอร์
chmod 644 *.php
chmod 644 .htaccess
chmod 755 assets/
chmod 644 assets/css/*.css
chmod 644 assets/js/*.js
chmod 777 data/
chmod 777 logs/
```

### 5. ตรวจสอบ PHP Extensions
ตรวจสอบว่า server มี extensions เหล่านี้:
- [ ] `sqlite3` - สำหรับฐานข้อมูล
- [ ] `curl` - สำหรับเรียก API
- [ ] `json` - สำหรับจัดการ JSON
- [ ] `mbstring` - สำหรับ string handling
- [ ] `openssl` - สำหรับ HTTPS requests

### 6. ทดสอบการทำงาน
- [ ] เปิดเว็บไซต์ในเบราว์เซอร์
- [ ] ทดสอบหน้า Settings
- [ ] ทดสอบการบันทึกข้อมูล
- [ ] ทดสอบการเชื่อมต่อ API (ถ้ามีข้อมูล)

## การตั้งค่า SSL (แนะนำ)
1. ขอใบรับรอง SSL จาก hosting provider หรือ Let's Encrypt
2. แก้ไข config.php:
   ```php
   define('SECURE_COOKIES', true);
   ```
3. บังคับใช้ HTTPS ใน .htaccess:
   ```apache
   RewriteCond %{HTTPS} !=on
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

## การ Backup
สร้าง script สำหรับ backup ฐานข้อมูล:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
cp /path/to/data/dashboardmarket.sqlite /path/to/backups/dashboardmarket_$DATE.sqlite
# เก็บ backup แค่ 30 วันล่าสุด
find /path/to/backups/ -name "dashboardmarket_*.sqlite" -mtime +30 -delete
```

## Troubleshooting
- หากปุ่มไม่ทำงาน: เปิด Browser Developer Tools (F12) ดู Console errors
- หากเกิด 500 Error: ตรวจสอบ PHP error logs
- หากฐานข้อมูลไม่ทำงาน: ตรวจสอบ permissions โฟลเดอร์ data/
