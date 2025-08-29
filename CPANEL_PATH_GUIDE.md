# 🔍 วิธีหา Path ที่ถูกต้องใน cPanel

## ขั้นตอนการหา Path:

### 1. เปิด File Manager
- ใน cPanel คลิก "File Manager"
- เลือก "public_html"

### 2. ดูที่ตั้งโฟลเดอร์
- หาโฟลเดอร์ `dashboardmarket`
- คลิกขวาที่โฟลเดอร์ > "Properties" หรือ "Details"
- จดบันทึก path เต็ม

### 3. Path ทั่วไป:
```
/home/yourusername/public_html/dashboardmarket/
/home/yourusername/domains/yourdomain.com/public_html/dashboardmarket/
/home/cpanelusername/public_html/dashboardmarket/
```

### 4. วิธีตรวจสอบ:
- เปิด Terminal ใน cPanel (ถ้ามี)
- ใช้คำสั่ง: `pwd` เพื่อดู current directory
- หรือใช้: `ls -la /home/yourusername/public_html/` เพื่อดูโฟลเดอร์

## ตัวอย่าง Command ที่ถูกต้อง:
```bash
/home/john123/public_html/dashboardmarket/cron_fetch_orders_cpanel.sh
/home/myshop/domains/mystore.com/public_html/dashboardmarket/cron_fetch_orders_cpanel.sh
```
