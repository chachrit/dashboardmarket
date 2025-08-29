# การแก้ไขปัญหา Save Settings ไม่เข้าฐานข้อมูล

## ปัญหาที่พบ
ปัญหาเกิดจาก API ใน `api.php` ที่มีการตรวจสอบ platform และ return error "Unknown platform" ก่อนที่จะไปถึงฟังก์ชัน `save_settings` ทำให้ข้อมูลไม่ถูกบันทึกลงฐานข้อมูล

## การแก้ไข
1. แก้ไข `handle_request()` function ใน `api.php` ให้สร้าง API object เฉพาะเมื่อจำเป็น
2. ย้าย logic การตรวจสอบ platform ไปไว้ใน actions ที่ต้องการ API object เท่านั้น
3. `save_settings` action จะทำงานได้กับทุก platform โดยไม่ต้องสร้าง API object

## ไฟล์ที่แก้ไข
- `api.php` - แก้ไข handle_request() function

## การทดสอบหลังแก้ไข
- ✅ API save_settings ทำงานได้ปกติ
- ✅ ข้อมูลเข้าฐานข้อมูลสำเร็จ
- ✅ รองรับทุก platform (shopee, lazada, tiktok)

## สำหรับ cPanel
เมื่ออัปโหลดไฟล์ไป cPanel แล้ว สามารถใช้ไฟล์เหล่านี้เพื่อ debug:

### 1. debug_save_settings.php
เรียกผ่าน browser เพื่อตรวจสอบ environment และทดสอบฟังก์ชัน save:
- `https://yourdomain.com/debug_save_settings.php?debug=1` - ตรวจสอบ environment
- `https://yourdomain.com/debug_save_settings.php?test_save=1` - ทดสอบ save function

### 2. debug_api_save.php
เรียกผ่าน POST request เพื่อ debug API โดยละเอียด:
```bash
curl -X POST "https://yourdomain.com/debug_api_save.php?action=save_settings&platform=shopee" \
     -H "Content-Type: application/json" \
     -d '{"partner_id":"123","partner_key":"key","enabled":true}'
```

### 3. test_db.php
เรียกผ่าน browser เพื่อดูข้อมูลในฐานข้อมูล:
```
https://yourdomain.com/test_db.php
```

## หมายเหตุสำคัญ
- ตรวจสอบให้แน่ใจว่าโฟลเดอร์ `data/` มี permission เขียนได้
- SQLite database จะถูกสร้างอัตโนมัติที่ `data/dashboardmarket.sqlite`
- หากใช้ MS SQL Server ให้ตั้งค่า environment variables ตาม `db.php`
