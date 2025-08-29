<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Cron Job Status - Dashboard Market</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .status-active { color: #10b981; }
        .status-warning { color: #f59e0b; }
        .status-error { color: #ef4444; }
        .status-unknown { color: #6b7280; }
        
        .pulse-animation {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    🔍 Cron Job Status
                </h1>
                <p class="text-gray-600 mt-2">ตรวจสอบสถานะการทำงานของ Cron Job</p>
            </div>
            <div class="flex space-x-4">
                <button onclick="refreshStatus()" id="refresh-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-refresh mr-2"></i>รีเฟรช
                </button>
                <a href="index_enhanced.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-dashboard mr-2"></i>Dashboard
                </a>
            </div>
        </div>

        <!-- Status Overview -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold">📊 สถานะรวม</h2>
                <div id="last-check" class="text-sm text-gray-500">
                    กำลังโหลด...
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600" id="total-orders">-</div>
                    <div class="text-sm text-gray-600">Orders ทั้งหมด</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600" id="today-orders">-</div>
                    <div class="text-sm text-gray-600">Orders วันนี้</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600" id="active-platforms">-</div>
                    <div class="text-sm text-gray-600">Platforms</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold" id="cron-status-icon">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-sm" id="cron-status-text">กำลังตรวจสอบ...</div>
                </div>
            </div>
        </div>

        <!-- Platform Details -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4">🏪 รายละเอียดแต่ละ Platform</h2>
            <div id="platforms-container" class="space-y-4">
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลดข้อมูล...
                </div>
            </div>
        </div>

        <!-- Log Information -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-4">📝 ข้อมูล Log</h2>
            <div id="log-container" class="bg-gray-50 rounded-lg p-4">
                <div class="text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลด log...
                </div>
            </div>
        </div>
    </div>

    <script>
        let statusInterval;

        // Load status on page load
        document.addEventListener('DOMContentLoaded', function() {
            refreshStatus();
            // Auto refresh every 30 seconds
            statusInterval = setInterval(refreshStatus, 30000);
        });

        async function refreshStatus() {
            const refreshBtn = document.getElementById('refresh-btn');
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังตรวจสอบ...';

            try {
                const response = await fetch('check_cron_status.php');
                const data = await response.json();
                
                if (data.success) {
                    updateStatusDisplay(data);
                } else {
                    showError(data.error || 'Unknown error');
                }
            } catch (error) {
                showError('Network error: ' + error.message);
            } finally {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fas fa-refresh mr-2"></i>รีเฟรช';
                
                // Update last check time
                document.getElementById('last-check').textContent = 'ตรวจสอบล่าสุด: ' + new Date().toLocaleTimeString('th-TH');
            }
        }

        function updateStatusDisplay(data) {
            // Update summary
            document.getElementById('total-orders').textContent = data.summary.total_orders.toLocaleString();
            document.getElementById('today-orders').textContent = data.summary.today_orders.toLocaleString();
            document.getElementById('active-platforms').textContent = data.summary.active_platforms;

            // Update cron status
            const statusIcon = document.getElementById('cron-status-icon');
            const statusText = document.getElementById('cron-status-text');
            
            statusIcon.className = `text-2xl font-bold status-${data.cron_status}`;
            statusText.className = `text-sm status-${data.cron_status}`;
            statusText.textContent = data.cron_message;

            switch (data.cron_status) {
                case 'active':
                    statusIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                    break;
                case 'warning':
                    statusIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                    break;
                case 'error':
                    statusIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
                    break;
                default:
                    statusIcon.innerHTML = '<i class="fas fa-question-circle"></i>';
            }

            // Update platforms
            updatePlatformsDisplay(data.platforms);
            
            // Update log info
            updateLogDisplay(data.log_info);
        }

        function updatePlatformsDisplay(platforms) {
            const container = document.getElementById('platforms-container');
            
            if (!platforms || platforms.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 py-8">ไม่มีข้อมูล Platform</div>';
                return;
            }

            container.innerHTML = platforms.map(platform => {
                const statusClass = platform.minutes_since_fetch < 20 ? 'text-green-600' : 
                                   platform.minutes_since_fetch < 60 ? 'text-yellow-600' : 'text-red-600';
                
                return `
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-lg capitalize">${platform.platform}</h3>
                                <div class="text-sm text-gray-600">
                                    <span>Orders ทั้งหมด: <strong>${platform.total_orders.toLocaleString()}</strong></span>
                                    <span class="ml-4">วันนี้: <strong>${platform.today_orders.toLocaleString()}</strong></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="${statusClass} font-medium">
                                    อัปเดตล่าสุด: ${platform.minutes_since_fetch ? platform.minutes_since_fetch + ' นาทีที่แล้ว' : 'ไม่ทราบ'}
                                </div>
                                <div class="text-xs text-gray-500">
                                    ${platform.last_fetch_time || 'ไม่เคยอัปเดต'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateLogDisplay(logInfo) {
            const container = document.getElementById('log-container');
            
            const statusIcon = logInfo.file_exists ? 
                '<i class="fas fa-check-circle text-green-600"></i>' : 
                '<i class="fas fa-exclamation-triangle text-yellow-600"></i>';
                
            container.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center mb-2">
                            ${statusIcon}
                            <span class="ml-2 font-medium">Log File Status</span>
                        </div>
                        <div class="text-sm text-gray-600">
                            <p>ไฟล์: logs/cron_fetch_${new Date().toISOString().slice(0,10).replace(/-/g,'')}.log</p>
                            <p>ขนาด: ${logInfo.file_size} bytes</p>
                            <p>สถานะ: ${logInfo.file_exists ? 'มีไฟล์' : 'ไม่มีไฟล์'}</p>
                        </div>
                    </div>
                    <div>
                        <div class="font-medium mb-2">Log Entry ล่าสุด:</div>
                        <div class="text-xs bg-gray-100 p-2 rounded font-mono">
                            ${logInfo.last_entry || 'ไม่มีข้อมูล'}
                        </div>
                    </div>
                </div>
            `;
        }

        function showError(message) {
            document.getElementById('platforms-container').innerHTML = 
                `<div class="text-center text-red-500 py-8">
                    <i class="fas fa-exclamation-triangle mr-2"></i>${message}
                </div>`;
        }

        // Cleanup interval on page unload
        window.addEventListener('beforeunload', function() {
            if (statusInterval) {
                clearInterval(statusInterval);
            }
        });
    </script>
</body>
</html>
