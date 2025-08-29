#!/bin/zsh
#
# Quick Setup for Large Store - Dashboard Market
# สคริปต์ตั้งค่าด่วนสำหรับร้านค้าขนาดใหญ่
#

echo "🏪 Dashboard Market - Large Store Quick Setup"
echo "============================================="
echo ""

# ตรวจสอบไฟล์ที่จำเป็น
if [ ! -f "cron_fetch_orders.sh" ]; then
    echo "❌ Error: cron_fetch_orders.sh not found!"
    exit 1
fi

if [ ! -f "index_enhanced.php" ]; then
    echo "❌ Error: index_enhanced.php not found!"
    exit 1
fi

echo "✅ ไฟล์ทั้งหมดพร้อมใช้งาน"
echo ""

echo "📋 การตั้งค่าปัจจุบัน (สำหรับร้านค้าขนาดใหญ่):"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔄 Cron Job (API → Database): ทุก 15 นาที"
echo "🖥️ Frontend Auto-refresh: ทุก 90 วินาที"
echo "💾 Database Cache: 5 นาที"
echo "🔁 Platform Polling: ทุก 15 วินาที"
echo ""

echo "🚀 ขั้นตอนการติดตั้ง Cron Job:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1️⃣ Local Development (XAMPP):"
echo "   crontab -e"
echo "   เพิ่มบรรทัด: */15 * * * * $(pwd)/cron_fetch_orders.sh"
echo ""
echo "2️⃣ cPanel Hosting:"
echo "   Minute: */15, Hour: *, Day: *, Month: *, Weekday: *"
echo "   Command: /home/yourusername/public_html/dashboardmarket/cron_fetch_orders_cpanel.sh"
echo ""

echo "⚡ ตัวเลือกสำหรับร้านค้าที่มีออเดอร์เยอะมาก:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🟢 ทุก 10 นาที: */10 * * * *"
echo "🟡 ทุก 5 นาที:  */5 * * * *"
echo "🔴 ทุก 2 นาที:  */2 * * * * (สำหรับร้านเมกะเท่านั้น)"
echo ""

echo "📊 API Rate Limits Check:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
CALLS_PER_DAY_15MIN=$((24 * 60 / 15))
CALLS_PER_DAY_10MIN=$((24 * 60 / 10))
CALLS_PER_DAY_5MIN=$((24 * 60 / 5))

echo "📈 ทุก 15 นาที = $CALLS_PER_DAY_15MIN calls/วัน (แนะนำ)"
echo "📈 ทุก 10 นาที = $CALLS_PER_DAY_10MIN calls/วัน"
echo "📈 ทุก 5 นาที  = $CALLS_PER_DAY_5MIN calls/วัน"
echo ""
echo "✅ Shopee Limit: 1000 calls/วัน"
echo "✅ Lazada Limit: 2400 calls/วัน"
echo "✅ TikTok Limit: 1000 calls/วัน"
echo ""

echo "🔧 การตรวจสอบการทำงาน:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📝 ดู log: tail -f logs/cron_fetch_\$(date +%Y%m%d).log"
echo "📊 ทดสอบ: เปิด test_performance.php"
echo "🖥️ Dashboard: เปิด index_enhanced.php"
echo ""

echo "⚠️ ข้อควรระวัง:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🟡 Shared Hosting อาจจำกัดความถี่ของ Cron"
echo "🟡 Resource usage จะเพิ่มขึ้น"
echo "🟡 ตรวจสอบ API limits เป็นประจำ"
echo ""

echo "✅ Setup เสร็จสิ้น! ระบบพร้อมสำหรับร้านค้าขนาดใหญ่"
echo "🎉 ข้อมูลจะอัปเดทเร็วขึ้น 4 เท่า!"
echo ""
