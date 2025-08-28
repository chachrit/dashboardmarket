# 🛒 Shopee API Setup Guide

## ❌ ปัญหาที่พบบ่อย

### 1. Partner ID ไม่ถูกต้อง

**อาการ:** `Invalid partner_id format` หรือ `error params, the format of partner_id parameter is wrong`

**สาเหตุ:** 
- ใช้ Partner ID ที่ไม่ใช่รูปแบบของ Shopee
- Partner ID ต้องเป็นตัวเลข 10 หลักพอดี
- Partner ID `2012442` ไม่ถูกต้อง (7 หลัก)

**วิธีแก้:**
1. เข้า [Shopee Open Platform](https://open.shopee.com/)
2. สร้าง Application ใหม่
3. ใช้ Partner ID ที่ได้จากระบบ (จะเป็นตัวเลข 10 หลัก เช่น `2000000001`)

### 2. การขอ Access Token

**ขั้นตอนที่ถูกต้อง:**
1. ใช้ Partner ID และ Partner Key จาก Shopee Open Platform
2. สร้าง Authorization URL
3. User ต้องไปที่ URL และ authorize
4. ระบบจะ redirect กลับมาพร้อม `code`
5. ใช้ `code` แลก Access Token

## ✅ ข้อมูลที่ต้องมี

### Environment: Sandbox
- **API Base URL:** `https://openplatform.sandbox.test-stable.shopee.sg`
- **Partner ID:** 6-8 หลัก (เช่น `1183136` จากภาพ)
- **Partner Key:** Secret key จาก Shopee
- **Shop ID:** รหัสร้านค้าทดสอบ
- **Access Token:** ได้จากการ OAuth (หมดอายุ 4 ชั่วโมง)
- **Refresh Token:** ใช้ต่ออายุ Access Token (หมดอายุ 30 วัน)

### Environment: Production
- **API Base URL:** `https://partner.shopeemobile.com`
- **Partner ID:** 6-8 หลัก (เช่น `2012442` จากภาพ)
- **Partner Key:** Secret key จาก Shopee
- **Shop ID:** รหัสร้านค้าจริง
- **Access Token:** ได้จากการ OAuth (หมดอายุ 4 ชั่วโมง)
- **Refresh Token:** ใช้ต่ออายุ Access Token (หมดอายุ 30 วัน)

## 🔧 วิธีการตั้งค่า (ไม่ต้องใช้ Node.js)

1. **สมัคร Shopee Open Platform**
   ```
   https://open.shopee.com/
   ```

2. **สร้าง Application**
   - เลือก Country/Region: Thailand
   - ระบุ Redirect URL: `https://yourdomain.com/shopee_callback.php`

3. **ได้รับ Credentials**
   - Partner ID (6-8 หลัก เช่น 2012442)
   - Partner Key (Secret)

4. **ตั้งค่าในระบบ**
   - กรอก Partner ID และ Partner Key ในหน้า settings.php
   - เลือก Environment (Sandbox/Production)
   - คลิกปุ่ม "OAuth Authorization"

5. **OAuth Flow (อัตโนมัติด้วย PHP)**
   - ระบบจะสร้าง Authorization URL อัตโนมัติ
   - คุณจะถูกนำไป Shopee เพื่อ authorize
   - หลัง authorize สำเร็จ จะกลับมาที่ `shopee_callback.php`
   - ระบบจะแลก code เป็น Access Token อัตโนมัติ
   - Token จะถูกบันทึกลง database

## 🐛 Debug Tips

1. **ตรวจสอบ Partner ID**
   - ต้องเป็นตัวเลข 10 หลักพอดี
   - ไม่ใช่ 7 หลักหรือ 8 หลัก

2. **ตรวจสอบ Signature**
   - ใช้ HMAC-SHA256
   - Base string: partner_id + path + timestamp + access_token + shop_id

3. **ตรวจสอบ Timestamp**
   - ใช้ Unix timestamp
   - ห้ามเก่าหรือใหม่เกินไป (±15 นาที)

## 📞 การติดต่อ Support

**Shopee Open Platform Support:**
- Email: open.platform@shopee.com
- Documentation: https://open.shopee.com/documents

**Dashboard Market Support:**
- ใช้ไฟล์ debug_api.php เพื่อตรวจสอบปัญหา
- เปิด browser developer tools ดู Network tab
- ตรวจสอบ log files ใน logs/ folder
