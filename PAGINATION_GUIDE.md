# 🚀 Enhanced Dashboard Market - Pagination & Database Caching System

## 📋 ภาพรวมระบบ

ระบบ Dashboard Market ได้รับการปรับปรุงใหม่เพื่อแก้ปัญหา Rate Limiting และเพิ่มประสิทธิภาพการโหลดข้อมูล โดยใช้:

1. **Pagination System** - ดึงข้อมูลแบบทีละหน้า (100 orders ต่อครั้ง)
2. **Database Caching** - เก็บข้อมูลในฐานข้อมูลเพื่อลดการยิง API
3. **Rate Limiting Protection** - หน่วงเวลาระหว่างการเรียก API
4. **Auto-Sync System** - ดึงข้อมูลใหม่แบบอัตโนมัติ

## 🏗️ สถาปัตยกรรมระบบ

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend PHP    │    │   Database      │
│   (Dashboard)   │◄──►│   (API Layer)    │◄──►│   (SQLite/SQL)  │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │  External APIs   │
                       │  (Shopee/Lazada) │
                       └──────────────────┘
```

## 📁 ไฟล์และโครงสร้าง

### ไฟล์หลัก
- `pagination_manager.php` - จัดการการดึงข้อมูลแบบ pagination
- `fetch_orders.php` - CLI tool สำหรับดึงข้อมูลทั้งหมด
- `order_management.php` - Web interface สำหรับจัดการ orders
- `index_enhanced.php` - Dashboard หน้าใหม่ที่ใช้ cache
- `cron_fetch_orders.sh` - Cron job script

### ไฟล์ที่ปรับปรุง
- `api.php` - เพิ่ม database functions และ smart caching
- `db.php` - ฐานข้อมูลเดิม (ใช้ร่วมกัน)

### โฟลเดอร์ใหม่
- `logs/` - เก็บ log files จาก cron jobs

## 🔄 วิธีการทำงาน

### 1. การดึงข้อมูลแบบ Pagination

```php
// ตัวอย่างการใช้งาน
$manager = new PaginationManager('shopee', $api);
$result = $manager->fetchAllOrders('2024-08-28', '2024-08-28', 1000);
```

**กระบวนการ:**
1. ส่งคำสั่งดึง orders หน้าแรก (100 รายการ)
2. ตรวจสอบว่ามีข้อมูลเพิ่มเติม (`has_more`)
3. วนลูปดึงหน้าถัดไปจนกว่าจะหมด
4. หน่วงเวลา 1 วินาทีระหว่างแต่ละ request
5. บันทึกข้อมูลลงฐานข้อมูล

### 2. Database Caching System

```sql
-- ตารางสำหรับเก็บ orders
CREATE TABLE orders (
    id INTEGER PRIMARY KEY,
    platform TEXT NOT NULL,
    order_id TEXT NOT NULL,
    amount DECIMAL(15,2),
    status TEXT,
    created_at TEXT,
    items TEXT, -- JSON
    raw_data TEXT, -- JSON
    fetched_at INTEGER,
    UNIQUE(platform, order_id)
);
```

**Logic การใช้ cache:**
1. ตรวจสอบว่าข้อมูลในฐานข้อมูลใหม่หรือไม่ (< 30 นาที)
2. หากใหม่ → ใช้ข้อมูลจาก database (เร็ว)
3. หากเก่า → ยิง API ใหม่ → อัปเดต database

### 3. Smart Data Loading

```javascript
// Frontend จะตรวจสอบแหล่งข้อมูล
if (result.data.source === 'database') {
    showDataSource('📊 Database Cache');
} else if (result.data.source === 'api') {
    showDataSource('🌐 Live API');
}
```

## 🛠️ การติดตั้งและใช้งาน

### 1. ติดตั้งระบบ

```bash
# ให้สิทธิ์ execute
chmod +x fetch_orders.php
chmod +x cron_fetch_orders.sh

# สร้างโฟลเดอร์ logs
mkdir -p logs
```

### 2. การใช้งานผ่าน CLI

```bash
# ดึงข้อมูลทั้งสองแพลตฟอร์ม (วันนี้)
php fetch_orders.php all

# ดึงข้อมูลเฉพาะ Shopee
php fetch_orders.php shopee

# ดึงข้อมูลย้อนหลัง
php fetch_orders.php all --from=2024-08-01 --to=2024-08-31

# จำกัดจำนวน
php fetch_orders.php shopee --limit=500
```

### 3. ตั้งค่า Cron Job

```bash
# แก้ไข crontab
crontab -e

# เพิ่มบรรทัดนี้ (ดึงข้อมูลทุกชั่วโมง)
0 * * * * /Applications/XAMPP/xamppfiles/htdocs/dashboardmarket/cron_fetch_orders.sh

# หรือดึงทุก 2 ชั่วโมง
0 */2 * * * /Applications/XAMPP/xamppfiles/htdocs/dashboardmarket/cron_fetch_orders.sh
```

### 4. การใช้งานผ่าน Web

1. **Order Management**: `order_management.php`
   - ดูสถิติ orders
   - ดึงข้อมูลใหม่แบบ manual
   - ดู orders ล่าสุด

2. **Enhanced Dashboard**: `index_enhanced.php`
   - แสดงข้อมูลจาก cache (เร็ว)
   - แสดงแหล่งข้อมูลและเวลาโหลด
   - Auto-refresh ทุก 5 นาที

## 📊 การตรวจสอบประสิทธิภาพ

### Performance Indicators

- **เร็ว** (< 500ms): 🟢 เยี่ยม
- **ปานกลาง** (500-1000ms): 🟡 ปานกลาง  
- **ช้า** (> 1000ms): 🔴 ช้า

### Data Sources

- **🟢 Database**: ข้อมูลจาก cache (เร็วสุด)
- **🟡 API**: ข้อมูลจาก API (ช้ากว่า)
- **🔴 Fallback**: API ล้มเหลว ใช้ cache เก่า

## 🔧 การปรับแต่ง

### ปรับเวลา Cache

```php
// ใน api.php - ปรับเวลา cache (นาที)
if (isDatabaseDataFresh($platform, 30)) { // 30 นาที
    // ใช้ cache
}
```

### ปรับ Rate Limiting

```php
// ใน PaginationManager
private $delayBetweenRequests = 1000000; // 1 วินาที (microseconds)
```

### ปรับขนาด Batch

```php
// การดึงข้อมูลต่อครั้ง
$limit = min(100, $remainingOrders); // สูงสุด 100 รายการ
```

## 📈 ข้อดีของระบบใหม่

1. **เร็วขึ้น 10x**: ใช้ database cache แทน API
2. **ไม่ติด Rate Limit**: มี delay ระหว่างการเรียก
3. **ได้ข้อมูลครบ**: pagination ดึงได้ไม่จำกัด
4. **ทำงานแบบ Background**: cron job อัตโนมัติ
5. **Fallback**: หาก API ล้มเหลว ยังใช้ cache ได้

## 🐛 การแก้ปัญหา

### ปัญหา Rate Limit
```bash
# เพิ่มเวลา delay
# แก้ไขใน pagination_manager.php
private $delayBetweenRequests = 2000000; // 2 วินาที
```

### ข้อมูลไม่อัปเดต
```bash
# Force ดึงข้อมูลใหม่
php fetch_orders.php shopee --limit=100

# หรือลบ cache
sqlite3 data/dashboardmarket.sqlite "DELETE FROM orders WHERE platform='shopee';"
```

### ฐานข้อมูลใหญ่เกินไป
```bash
# ลบข้อมูลเก่า (เก็บ 30 วัน)
sqlite3 data/dashboardmarket.sqlite "DELETE FROM orders WHERE fetched_at < strftime('%s', 'now', '-30 days');"
```

## 📝 Log Files

- `logs/cron_fetch_YYYYMMDD.log` - Log จาก cron job
- PHP Error Log - ข้อผิดพลาด API
- Browser Console - ข้อผิดพลาด Frontend

## 🔮 ทิศทางพัฒนาต่อไป

1. **Real-time Updates**: WebSocket สำหรับ orders ใหม่
2. **Advanced Analytics**: กราฟแสดงแนวโน้มยอดขาย
3. **Multi-shop Support**: รองรับหลายร้านค้า
4. **API Rate Monitor**: ติดตาม rate limit แบบ real-time
5. **Data Export**: ส่งออกข้อมูลเป็น CSV/Excel

## 📞 การสนับสนุน

- **Issues**: เปิด GitHub Issue
- **Documentation**: อ่าน `README.md` และ `API_DOCUMENTATION.md`
- **Testing**: รัน `test_orders_debug.php` เพื่อตรวจสอบ
