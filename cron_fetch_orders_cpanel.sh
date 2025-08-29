#!/bin/bash
#
# Dashboard Market - cPanel Compatible Cron Job
# สำหรับใช้งานใน cPanel Shared Hosting (สำหรับร้านค้าขนาดใหญ่)
#
# วิธีตั้งค่าใน cPanel (สำหรับร้านค้าขนาดใหญ่):
# 1. ไปที่ Cron Jobs ใน cPanel
# 2. เลือกความถี่: */15 * * * * (ทุก 15 นาที) 
# 3. หรือ */10 * * * * (ทุก 10 นาที สำหรับร้านที่มีออเดอร์เยอะมาก)
# 4. Command: /home/yourusername/public_html/dashboardmarket/cron_fetch_orders_cpanel.sh
#

# Configuration for cPanel
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOG_DIR="${SCRIPT_DIR}/logs"
PHP_BIN="/usr/local/bin/php" # cPanel default PHP path

# Alternative PHP paths for different hosting providers
if [ ! -x "$PHP_BIN" ]; then
    PHP_BIN="/usr/bin/php"
fi

if [ ! -x "$PHP_BIN" ]; then
    PHP_BIN=$(which php)
fi

if [ ! -x "$PHP_BIN" ]; then
    echo "Error: PHP not found" >&2
    exit 1
fi

# Create logs directory if not exists
mkdir -p "$LOG_DIR"

# Log file with timestamp
LOG_FILE="${LOG_DIR}/cron_fetch_$(date +%Y%m%d).log"

# Start logging
echo "=====================================" >> "$LOG_FILE"
echo "🚀 Auto Fetch Orders (cPanel) - $(date)" >> "$LOG_FILE"
echo "=====================================" >> "$LOG_FILE"
echo "Script Dir: $SCRIPT_DIR" >> "$LOG_FILE"
echo "PHP Binary: $PHP_BIN" >> "$LOG_FILE"

# Change to project directory
cd "$SCRIPT_DIR" || {
    echo "❌ Cannot change to script directory: $SCRIPT_DIR" >> "$LOG_FILE"
    exit 1
}

# Fetch data for all platforms
echo "📊 Fetching all platforms (last 1 hour)..." >> "$LOG_FILE"
"$PHP_BIN" fetch_orders.php all --since=1h >> "$LOG_FILE" 2>&1

# Check exit status
EXIT_CODE=$?
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ Cron job completed successfully at $(date)" >> "$LOG_FILE"
    echo "📈 Next execution: $(date -d '+15 minutes')" >> "$LOG_FILE"
else
    echo "❌ Cron job failed at $(date) with exit code: $EXIT_CODE" >> "$LOG_FILE"
fi

echo "" >> "$LOG_FILE"

# Keep only last 7 days of logs (optional - saves space)
find "$LOG_DIR" -name "cron_fetch_*.log" -mtime +7 -delete 2>/dev/null

exit $EXIT_CODE
