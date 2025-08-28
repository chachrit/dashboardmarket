# เงื่อนไขการดึงจำนวนออเดอร์ (Orders Fetching Conditions)

## 📋 เงื่อนไขหลักในการดึงออเดอร์

### 1. เงื่อนไขช่วงเวลา (Time Range)
- **ค่าเริ่มต้น**: ถ้าไม่ระบุ `date_from` และ `date_to` จะดึงออเดอร์ของวันนี้
  - `date_from`: วันนี้ 00:00:00 (เริ่มต้นวัน)
  - `date_to`: เวลาปัจจุบัน
- **Field ที่ใช้กรอง**: `create_time` (เวลาที่สร้างออเดอร์)
- **รูปแบบเวลา**: Unix timestamp สำหรับ Shopee API

### 2. เงื่อนไขจำนวน (Limit)
- **ค่าเริ่มต้น**: 50 รายการ
- **ขีดจำกัด API**: สูงสุด 50 รายการต่อการเรียก (Shopee API limitation)
- **พารามิเตอร์**: `page_size` ในการเรียก API
- **Frontend limit**: สามารถเลือก 10, 20, 30, 40, 50 รายการได้

### 3. เงื่อนไขสถานะ (Order Status)
- **ค่าเริ่มต้น**: ดึงทุกสถานะ (ไม่กรอง)
- **การกรอง**: ระบุ `order_status` เฉพาะเมื่อต้องการสถานะเฉพาะ
- **ข้อยกเว้น**: ถ้า `order_status=ALL` จะไม่ส่งพารามิเตอร์นี้ไปยัง API

### 4. การเรียงลำดับ (Sorting)
- **เกณฑ์เรียง**: `create_time` (เวลาสร้างออเดอร์)
- **ลำดับ**: จากใหม่สุดไปเก่าสุด (newest first)
- **กระบวนการ**: 
  1. API ส่งข้อมูลมา → เรียงใน PHP
  2. แสดงผลบน Frontend → เรียงอีกครั้งใน JavaScript

## 🔄 การ Auto-Refresh (Polling)

### เวลาในการอัพเดทอัตโนมัติ
- **เดิม**: ทุก 15 วินาที (15,000 ms)
- **ปัจจุบัน**: ทุก 30 วินาที (30,000 ms) ✅ **อัพเดทแล้ว**

### กระบวนการ Polling
```javascript
function startRecentOrdersPolling(){
    if (recentOrdersPollingStarted) return;
    recentOrdersPollingStarted = true;
    setInterval(()=>{
        fetch(buildAPIUrl('getOrders','&limit='+recentLimit))
            .then(r=>r.json())
            .then(d=>{ 
                if(d.success && d.data.orders){ 
                    displayRecentOrders(d.data.orders); 
                    updateTimestamp(); 
                } 
            })
            .catch(()=>{});
    }, 30000); // ← เปลี่ยนเป็น 30 วินาที
}
```

## 🔧 พารามิเตอร์ API

### Shopee API (`/api/v2/order/get_order_list`)
```php
$query = [
    'time_from'                => $date_from_ts,      // Unix timestamp start
    'time_to'                  => $date_to_ts,        // Unix timestamp end  
    'time_range_field'         => 'create_time',      // Field ที่ใช้กรองเวลา
    'page_size'                => $pageSize,          // จำนวนรายการ (max 50)
    'response_optional_fields' => 'order_status'      // ข้อมูลเพิ่มเติมที่ต้องการ
];

// เงื่อนไขสถานะ (optional)
if(isset($_GET['order_status']) && $_GET['order_status'] !== '' && strtoupper($_GET['order_status']) !== 'ALL'){
    $query['order_status'] = $_GET['order_status'];
}
```

### Lazada API (`/orders/get`)
```php
$params = [
    'created_after'  => date('Y-m-d\T00:00:00+07:00'),  // ISO 8601 format
    'created_before' => date('Y-m-d\T23:59:59+07:00'),  // ISO 8601 format
    'limit'          => $limit,                          // จำนวนรายการ
    'offset'         => 0                                // หน้าที่เริ่มต้น
];
```

## 📊 ข้อมูลที่ได้จากการดึงออเดอร์

### ข้อมูลพื้นฐานที่แสดง
- **Order ID**: รหัสออเดอร์
- **Product**: ชื่อสินค้าหรือจำนวนรายการ
- **Amount**: ยอดเงิน
- **Status**: สถานะออเดอร์ (confirmed, shipped, delivered, cancelled)
- **Created At**: วันเวลาที่สร้างออเดอร์

### การประมวลผลข้อมูล
1. **API Response** → Raw data จาก platform
2. **Normalization** → แปลงเป็นรูปแบบมาตรฐาน
3. **Sorting** → เรียงตามเวลาใหม่สุด
4. **Limiting** → จำกัดจำนวนตาม Frontend setting
5. **Display** → แสดงผลใน UI

## 🎯 การปรับแต่งเพิ่มเติม

### สำหรับผู้ใช้งาน
- เปลี่ยนจำนวนรายการที่แสดง (10-50)
- เลือก Platform ที่ต้องการดู
- ดูข้อมูล Real-time หรือ Mock data

### สำหรับนักพัฒนา
- ปรับเวลา Polling (ปัจจุบัน: 30 วินาที)
- เพิ่มเงื่อนไขการกรอง (สถานะ, ช่วงเวลา)
- ปรับแต่ง API timeout (ปัจจุบัน: 30 วินาที)

---

**อัพเดทล่าสุด**: เปลี่ยนเวลา Auto-refresh จาก 15 วินาที เป็น 30 วินาที แล้ว ✅
