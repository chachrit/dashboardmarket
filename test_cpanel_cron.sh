#!/bin/bash
# 
# 🧪 cPanel Cron Job Tester
# สำหรับทดสอบ Cron Job ก่อนใช้งานจริง
#

echo "🧪 cPanel Cron Job Tester - Dashboard Market"
echo "============================================"
echo ""

# ตรวจสอบ path ปัจจุบัน
echo "📁 Current Directory: $(pwd)"
echo "📁 Script Directory: $(dirname "$0")"
echo ""

# ตรวจสอบไฟล์ที่จำเป็น
echo "🔍 Checking required files:"

if [ -f "fetch_orders.php" ]; then
    echo "✅ fetch_orders.php found"
else
    echo "❌ fetch_orders.php NOT found"
    exit 1
fi

if [ -f "cron_fetch_orders_cpanel.sh" ]; then
    echo "✅ cron_fetch_orders_cpanel.sh found"
else
    echo "❌ cron_fetch_orders_cpanel.sh NOT found"  
    exit 1
fi

# ตรวจสอบ PHP
echo ""
echo "🔍 Checking PHP:"
if command -v php >/dev/null 2>&1; then
    PHP_VERSION=$(php -v | head -n 1)
    echo "✅ PHP found: $PHP_VERSION"
else
    echo "❌ PHP not found in PATH"
    echo "ℹ️  Try: /usr/local/bin/php or /usr/bin/php"
fi

# ตรวจสอบ permissions
echo ""
echo "🔍 Checking file permissions:"
ls -la cron_fetch_orders_cpanel.sh | awk '{print $1, $9}'

if [ -x "cron_fetch_orders_cpanel.sh" ]; then
    echo "✅ cron_fetch_orders_cpanel.sh is executable"
else
    echo "⚠️  cron_fetch_orders_cpanel.sh needs execute permission"
    echo "💡 Run: chmod +x cron_fetch_orders_cpanel.sh"
fi

# ตรวจสอบ logs directory
echo ""
echo "🔍 Checking logs directory:"
if [ -d "logs" ]; then
    echo "✅ logs directory exists"
    ls -la logs/ | tail -5
else
    echo "⚠️  logs directory doesn't exist"
    echo "💡 Creating logs directory..."
    mkdir -p logs
    echo "✅ logs directory created"
fi

echo ""
echo "🚀 Test Command:"
echo "cd $(pwd) && ./cron_fetch_orders_cpanel.sh"
echo ""
echo "📝 Add this to cPanel Cron Jobs:"
echo "$(pwd)/cron_fetch_orders_cpanel.sh"
echo ""
echo "✅ Test completed!"
