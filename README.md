# 🚀 Dashboard Market

ระบบจัดการ Dashboard แบบครบครัน สำหรับการเชื่อมต่อและจัดการข้อมูลจากแพลตฟอร์ม E-commerce หลายแพลตฟอร์ม

## ✨ คุณสมบัติหลัก

- 🛒 **Multi-Platform Integration**: รองรับ Shopee, Lazada, และ TikTok Shop
- 📊 **Real-time Analytics**: วิเคราะห์ข้อมูลแบบเรียลไทม์
- ⚙️ **Easy Configuration**: ตั้งค่า API ได้ง่ายผ่านหน้าเว็บ
- 🔒 **Secure Storage**: เก็บข้อมูล API credentials อย่างปลอดภัย
- 🎨 **Modern UI**: ออกแบบด้วย Tailwind CSS
- 📱 **Responsive Design**: ใช้งานได้ทั้งมือถือและคอมพิวเตอร์

## 🛠 ความต้องการของระบบ

### Server Requirements
- **PHP**: 7.4 หรือใหม่กว่า
- **Web Server**: Apache/Nginx
- **Database**: SQLite (ติดตั้งมาพร้อม PHP)

### PHP Extensions
- `sqlite3` - สำหรับฐานข้อมูล
- `curl` - สำหรับเรียก API
- `json` - สำหรับประมวลผล JSON
- `mbstring` - สำหรับการจัดการ string
- `openssl` - สำหรับความปลอดภัย

### Extensions (แนะนำ)
- `gd` - สำหรับการประมวลผลภาพ
- `zip` - สำหรับการบีบอัดไฟล์
- `xml` - สำหรับการประมวลผล XML

## 📦 การติดตั้ง

### วิธีที่ 1: ใช้ Setup Script (แนะนำ)

1. **อัปโหลดไฟล์ทั้งหมดไปยัง Web Server**

2. **ตรวจสอบ System Requirements**
   ```
   http://yourdomain.com/check_system.php
   ```

3. **รันการติดตั้งอัตโนมัติ**
   ```
   http://yourdomain.com/setup.php
   ```

4. **ทำตามขั้นตอนในหน้าติดตั้ง**
   - ตั้งค่า Environment
   - สร้างฐานข้อมูล
   - กำหนดค่าเริ่มต้น

### วิธีที่ 2: ติดตั้งด้วยตนเอง

1. **Clone repository**
   ```bash
   git clone [repository-url]
   cd dashboardmarket
   ```

2. **สร้างไฟล์การกำหนดค่า**
   ```bash
   cp config.example.php config.php
   ```

3. **แก้ไขการตั้งค่าใน config.php**
   ```php
   define('ENVIRONMENT', 'production');
   define('DEBUG_MODE', false);
   ```

4. **ตั้งค่า File Permissions**
   ```bash
   chmod 755 data/ logs/
   chmod 644 *.php
   ```

5. **เข้าถึงเว็บไซต์**
   ```
   http://yourdomain.com/
   ```

## 🔧 การกำหนดค่า

### 1. API Credentials

เข้าไปที่หน้า Settings (`settings.php`) และกรอกข้อมูล API:

#### Shopee
- **Partner ID**: ได้จาก Shopee Open Platform
- **Partner Key**: Secret key จาก Shopee
- **Shop ID**: รหัสร้านค้าของคุณ

#### Lazada
- **App Key**: Application Key จาก Lazada
- **App Secret**: Application Secret จาก Lazada
- **Access Token**: Token สำหรับเข้าถึง API

#### TikTok Shop
- **App Key**: Application Key จาก TikTok Shop
- **App Secret**: Application Secret จาก TikTok Shop

### 2. Environment Configuration

แก้ไขไฟล์ `config.php`:

```php
// Production Environment
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);

// Development Environment  
define('ENVIRONMENT', 'development');
define('DEBUG_MODE', true);
```

## 📁 โครงสร้างโปรเจค

```
dashboardmarket/
├── � index.php          # หน้าหลัก Dashboard
├── ⚙️ settings.php       # หน้าตั้งค่า API
├── 🔌 api.php           # API Handler หลัก
├── 💾 db.php            # Database functions
├── 🎨 styles.css        # CSS styles
├── 📊 dashboard.js      # JavaScript functions
├── 🔧 config.php        # การกำหนดค่าหลัก
├── 🛡️ .htaccess         # Apache configuration
├── 📋 check_system.php  # System requirements checker
├── 🚀 setup.php         # Installation script
├── 📚 DEPLOYMENT.md     # คู่มือการ Deploy
├── 📁 data/
│   └── 💾 dashboardmarket.sqlite  # ฐานข้อมูล SQLite
├── 📁 assets/
│   ├── 🔒 cacert.pem    # SSL certificates
│   ├── 📁 css/          # CSS files
│   └── 📁 js/           # JavaScript files
└── 📁 logs/             # Log files
```

## 🔒 ความปลอดภัย

### การป้องกันที่มีอยู่
- ✅ SQL Injection protection (PDO prepared statements)
- ✅ XSS prevention (HTML escaping)
- ✅ CSRF protection (form tokens)
- ✅ File access restrictions (.htaccess)
- ✅ Secure headers (X-Frame-Options, etc.)
- ✅ Input validation and sanitization

### คำแนะนำด้านความปลอดภัย
- 🔐 ใช้ HTTPS บน production server
- 🗄️ สำรองข้อมูลเป็นประจำ
- 🔄 อัปเดต PHP และ extensions เป็นประจำ
- 📁 ตรวจสอบ file permissions
- 🚫 ลบไฟล์ setup.php หลังติดตั้งเสร็จ

## 🚀 การใช้งาน

### 1. การตั้งค่าเริ่มต้น
1. เข้าไปที่ `settings.php`
2. เลือก Platform ที่ต้องการ
3. กรอก API credentials
4. กด "บันทึก" และ "ทดสอบการเชื่อมต่อ"

### 2. การดูข้อมูล Dashboard
1. เข้าไปที่ `index.php`
2. ดูข้อมูลสถิติจากแต่ละ platform
3. ตรวจสอบสถานะการเชื่อมต่อ

### 3. การจัดการ API
- ใช้ endpoint `/api.php` สำหรับการเรียกข้อมูล
- รองรับการส่งข้อมูลแบบ JSON
- มี error handling ที่ครบถ้วน

## 🐛 การแก้ไขปัญหา

### ปัญหาที่พบบ่อย

#### 1. ไม่สามารถเชื่อมต่อ API ได้
```bash
# ตรวจสอบ cURL
php -m | grep curl

# ตรวจสอบ SSL certificates
ls -la assets/cacert.pem
```

#### 2. Database error
```bash
# ตรวจสอบ permissions
chmod 755 data/
ls -la data/dashboardmarket.sqlite
```

#### 3. JavaScript ไม่ทำงาน
- ตรวจสอบ Console ในเบราว์เซอร์
- ตรวจสอบ syntax errors
- ดู Network tab สำหรับ API calls

#### 4. Page ไม่แสดงผล
```php
# เปิด debug mode ใน config.php
define('DEBUG_MODE', true);
```

### Log Files
ตรวจสอบ log files ในโฟลเดอร์ `logs/`:
- `error.log` - PHP errors
- `api.log` - API call logs
- `debug.log` - Debug information

## 📊 API Documentation

### Endpoints

#### GET /api.php?action=test_connection
ทดสอบการเชื่อมต่อ API

```json
{
  "platform": "shopee|lazada|tiktok"
}
```

#### POST /api.php?action=save_settings
บันทึกการตั้งค่า API

```json
{
  "platform": "shopee",
  "partner_id": "1234567",
  "partner_key": "your_secret_key",
  "shop_id": "987654321",
  "is_sandbox": true
}
```

#### GET /api.php?action=get_settings
ดึงการตั้งค่าปัจจุบัน

```json
{
  "platform": "shopee"
}
```

## 🤝 การสนับสนุน

### การรายงานปัญหา
- 📧 Email: [your-email@domain.com]
- 🐛 GitHub Issues: [repository-issues-url]

### การขอความช่วยเหลือ
- 📖 อ่านไฟล์ `DEPLOYMENT.md` สำหรับคำแนะนำการติดตั้ง
- 🔍 ตรวจสอบ `check_system.php` สำหรับ system requirements
- 💡 ดู log files ใน `logs/` สำหรับข้อมูล debug

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🔄 Updates

### Version 1.0.0
- ✅ Initial release
- ✅ Multi-platform support (Shopee, Lazada, TikTok)
- ✅ Web-based configuration
- ✅ Auto-setup script
- ✅ Security hardening
- ✅ Comprehensive documentation

---

**Made with ❤️ for E-commerce sellers in Thailand**
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
