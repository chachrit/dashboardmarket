# Dashboard ยอดขาย Realtime

Dashboard แสดงยอดขายแบบ realtime สำหรับ 3 แพลตฟอร์ม: Shopee, Lazada, และ TikTok Shop

## คุณสมบัติ

- 📊 Dashboard หน้าแรกแสดงยอดขายรวมของทุกแพลตฟอร์ม
- 🏪 หน้าแยกสำหรับแต่ละแพลตฟอร์มแสดง:
  - จำนวนออเดอร์
  - ยอดขาย
  - ออเดอร์ล่าสุด
  - สินค้าขายดี
- 🔄 อัพเดทข้อมูลแบบ realtime
- 📱 Responsive design ใช้งานได้บน mobile
- 🎨 UI สวยงามด้วย Tailwind CSS

## เทคโนโลยีที่ใช้

- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Backend**: PHP
- **Charts**: Chart.js
- **Icons**: Font Awesome

## การติดตั้ง

1. Clone โปรเจค:
```bash
git clone <repository-url>
cd dashboardmarket
```

2. เริ่ม PHP development server:
```bash
php -S localhost:8000
```

หรือใช้ npm script:
```bash
npm start
```

3. เปิดเบราว์เซอร์ไปที่ `http://localhost:8000`

## โครงสร้างไฟล์

```
dashboardmarket/
├── index.php          # หน้าแรก dashboard
├── platform.php       # หน้าแสดงข้อมูลแต่ละแพลตฟอร์ม
├── api.php            # API สำหรับดึงข้อมูล
├── dashboard.js       # JavaScript utilities
├── styles.css         # CSS เพิ่มเติม
├── package.json       # Package configuration
└── README.md          # คู่มือการใช้งาน
```

## การตั้งค่า API

ในไฟล์ `api.php` ให้แก้ไข access token และ refresh token สำหรับแต่ละแพลตฟอร์ม:

```php
$api_config = [
    'lazada' => [
        'access_token' => 'YOUR_LAZADA_ACCESS_TOKEN',
        'refresh_token' => 'YOUR_LAZADA_REFRESH_TOKEN',
        // ...
    ],
    'shopee' => [
        'access_token' => 'YOUR_SHOPEE_ACCESS_TOKEN',
        'refresh_token' => 'YOUR_SHOPEE_REFRESH_TOKEN',
        // ...
    ],
    'tiktok' => [
        'access_token' => 'YOUR_TIKTOK_ACCESS_TOKEN',
        'refresh_token' => 'YOUR_TIKTOK_REFRESH_TOKEN',
        // ...
    ]
];
```

## การใช้งาน

1. **หน้าแรก (index.php)**:
   - แสดงการ์ดยอดขายรวมของทุกแพลตฟอร์ม
   - คลิกปุ่ม "ดูรายละเอียด" เพื่อไปยังหน้าแยกของแต่ละแพลตฟอร์ม

2. **หน้าแพลตฟอร์ม (platform.php)**:
   - แสดงข้อมูลแยกของแต่ละแพลตฟอร์ม
   - กราฟแสดงยอดขายและจำนวนออเดอร์ 7 วันย้อนหลัง
   - ตารางออเดอร์ล่าสุด
   - รายการสินค้าขายดี

## คุณสมบัติ Realtime

- ข้อมูลจะอัพเดทอัตโนมัติทุก 5 วินาที (หน้าแรก) และ 10 วินาที (หน้าแพลตฟอร์ม)
- มี Live indicator แสดงสถานะการเชื่อมต่อ
- แสดงเวลาอัพเดทล่าสุด

## การปรับแต่ง

### เปลี่ยนความถี่ในการอัพเดท:
```javascript
// ในไฟล์ index.php หรือ platform.php
setInterval(simulateRealTimeData, 5000); // 5 วินาที
```

### เปลี่ยนสี theme:
```javascript
// ใน tailwind.config
tailwind.config = {
    theme: {
        extend: {
            colors: {
                'shopee': '#EE4D2D',
                'lazada': '#0F156D',
                'tiktok': '#FF0050'
            }
        }
    }
}
```

## API Endpoints

- `GET api.php?action=orders&platform=shopee` - ดึงข้อมูลออเดอร์
- `GET api.php?action=products&platform=lazada` - ดึงข้อมูลสินค้า
- `GET api.php?action=summary&platform=tiktok` - ดึงข้อมูลสรุป

## การ Deploy

### บน Shared Hosting:
1. อัพโหลดไฟล์ทั้งหมดไปยัง public_html
2. ตั้งค่า API credentials
3. เข้าถึงผ่าน domain ของคุณ

### บน VPS/Cloud:
1. ติดตั้ง PHP และ Apache/Nginx
2. Clone repository
3. ตั้งค่า virtual host
4. ตั้งค่า SSL certificate (แนะนำ)

## การแก้ไขปัญหา

### ไม่มีข้อมูลแสดง:
- ตรวจสอบ access token ในไฟล์ `api.php`
- ดู error log ใน developer console
- ตรวจสอบการเชื่อมต่อ internet

### กราฟไม่แสดง:
- ตรวจสอบว่าโหลด Chart.js แล้ว
- ดู error ใน console
- ตรวจสอบข้อมูลที่ส่งให้กราฟ

## License

MIT License - ดูรายละเอียดในไฟล์ LICENSE

## การสนับสนุน

หากมีปัญหาหรือคำถาม กรุณา:
1. ตรวจสอบ Issues ใน repository
2. สร้าง Issue ใหม่พร้อมรายละเอียดปัญหา
3. ติดต่อผู้พัฒนา

---

**หมายเหตุ**: Dashboard นี้ใช้ข้อมูล mock สำหรับการทดสอบ ในการใช้งานจริงต้องเชื่อมต่อกับ API จริงของแต่ละแพลตฟอร์ม
