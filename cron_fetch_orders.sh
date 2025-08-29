#!/bin/zsh
#
# Dashboard Market - Auto Fetch Orders Cron Job
# จับเวลาดึงข้อมูล Orders ทุก ๆ 15 นาที (สำหรับร้านค้าขนาดใหญ่)
#
# ติดตั้ง Cron Job สำหรับร้านค้าขนาดใหญ่:
# crontab -e
# เพิ่มบรรทัด: */15 * * * * /path/to/dashboardmarket/cron_fetch_orders.sh
#
# หรือสำหรับร้านค้าที่มีออเดอร์เยอะมาก:
# */10 * * * * /path/to/dashboardmarket/cron_fetch_orders.sh (ทุก 10 นาที)
# */5 * * * * /path/to/dashboardmarket/cron_fetch_orders.sh (ทุก 5 นาที)
#

# Configuration
SCRIPT_DIR="$(dirname "$0")"
LOG_DIR="${SCRIPT_DIR}/logs"
PHP_BIN="/usr/bin/php"

# หา PHP binary อัตโนมัติ
if [ ! -x "$PHP_BIN" ]; then
    PHP_BIN=$(which php)
fi

if [ ! -x "$PHP_BIN" ]; then
    echo "Error: PHP not found" >&2
    exit 1
fi

# สร้างโฟลเดอร์ logs หากไม่มี
mkdir -p "$LOG_DIR"

# Log file with timestamp
LOG_FILE="${LOG_DIR}/cron_fetch_$(date +%Y%m%d).log"

echo "=====================================" >> "$LOG_FILE"
echo "🚀 Auto Fetch Orders - $(date)" >> "$LOG_FILE"
echo "=====================================" >> "$LOG_FILE"

# เปลี่ยนไปยัง directory ของโปรเจค
cd "$SCRIPT_DIR"

# ดึงข้อมูลทั้งสอง platform
echo "📊 Fetching all platforms..." >> "$LOG_FILE"
"$PHP_BIN" fetch_orders.php all --date="$(date +%Y-%m-%d)" >> "$LOG_FILE" 2>&1

# ตรวจสอบผลลัพธ์
if [ $? -eq 0 ]; then
    echo "✅ Cron job completed successfully at $(date)" >> "$LOG_FILE"
else
    echo "❌ Cron job failed at $(date)" >> "$LOG_FILE"
fi

echo "" >> "$LOG_FILE"

# ลบ log files เก่า (เก็บไว้ 7 วัน)
find "$LOG_DIR" -name "cron_fetch_*.log" -mtime +7 -delete

echo "Cron job completed - check $LOG_FILE for details"
