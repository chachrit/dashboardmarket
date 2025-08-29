<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Market - Enhanced Performance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .performance-indicator {
            transition: all 0.3s ease;
        }
        .performance-indicator.fast { color: #10b981; }
        .performance-indicator.medium { color: #f59e0b; }
        .performance-indicator.slow { color: #ef4444; }
        
        .data-source-indicator {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .source-database { 
            background: #dcfce7; 
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .source-api { 
            background: #fef3c7; 
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .source-fallback { 
            background: #fee2e2; 
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    📈 Dashboard Market
                </h1>
                <p class="text-gray-600 mt-2">Enhanced Performance with Database Caching</p>
            </div>
            <div class="flex space-x-4">
                <a href="order_management.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-cog mr-2"></i>จัดการ Orders
                </a>
                <a href="settings.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-settings mr-2"></i>ตั้งค่า
                </a>
            </div>
        </div>

        <!-- Performance Stats -->
        <div class="glass-card rounded-xl p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">การโหลดข้อมูล:</span>
                        <span id="loading-time" class="performance-indicator font-bold">-</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">แหล่งข้อมูล:</span>
                        <span id="data-source" class="data-source-indicator">-</span>
                    </div>
                    <div class="text-sm text-gray-600" id="last-fetch-info">
                        <span class="font-medium">อัปเดตล่าสุด:</span>
                        <span id="last-fetch-time">-</span>
                    </div>
                    <div class="text-sm text-gray-600" id="next-refresh-info">
                        <span class="font-medium">รีเฟรชอัตโนมัติใน:</span>
                        <span id="next-refresh-countdown" class="text-blue-600 font-semibold">1:30</span>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="refreshAllData()" id="refresh-btn" class="bg-blue-100 text-blue-600 px-3 py-1 rounded text-sm hover:bg-blue-200 transition-colors">
                        <i class="fas fa-refresh mr-1"></i>รีเฟรช
                    </button>
                    <button onclick="forceFetchFromAPI()" class="bg-orange-100 text-orange-600 px-3 py-1 rounded text-sm hover:bg-orange-200 transition-colors">
                        <i class="fas fa-download mr-1"></i>ดึงจาก API
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Sales -->
            <div class="glass-card rounded-xl shadow-lg p-6 animate-fadeIn">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-2">ยอดขายรวม</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalSales">₿0</p>
                        <p class="text-green-600 text-sm">+0% จากเมื่อวาน</p>
                    </div>
                    <div class="bg-gradient-to-br from-green-500 to-emerald-500 p-3 rounded-full">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Total Orders -->
            <div class="glass-card rounded-xl shadow-lg p-6 animate-fadeIn">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-2">ออเดอร์รวม</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalOrders">0</p>
                        <p class="text-blue-600 text-sm">วันนี้</p>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-500 p-3 rounded-full">
                        <i class="fas fa-shopping-cart text-2xl text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Database Stats -->
            <div class="glass-card rounded-xl shadow-lg p-6 animate-fadeIn">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-2">Orders ในฐานข้อมูล</p>
                        <p class="text-3xl font-bold text-gray-900" id="dbTotalOrders">0</p>
                        <p class="text-purple-600 text-sm">ทั้งหมด</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-pink-500 p-3 rounded-full">
                        <i class="fas fa-database text-2xl text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Performance -->
            <div class="glass-card rounded-xl shadow-lg p-6 animate-fadeIn">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-2">ประสิทธิภาพ</p>
                        <p class="text-3xl font-bold text-gray-900" id="performanceScore">-</p>
                        <p class="text-gray-500 text-sm">เวลาตอบสนอง</p>
                    </div>
                    <div class="bg-gradient-to-br from-yellow-500 to-orange-500 p-3 rounded-full">
                        <i class="fas fa-tachometer-alt text-2xl text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Platform Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Shopee Card -->
            <div class="glass-card rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-red-600">🛒 Shopee</h3>
                    <div class="flex items-center space-x-2">
                        <span id="shopee-source" class="data-source-indicator">-</span>
                        <span id="shopee-load-time" class="text-xs text-gray-500">-</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">ยอดขาย:</span>
                        <span id="shopeeSales" class="font-bold text-red-600">₿0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">ออเดอร์:</span>
                        <span id="shopeeOrders" class="font-bold text-red-600">0</span>
                    </div>
                    <button onclick="goToPlatform('shopee')" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors">
                        ดูรายละเอียด
                    </button>
                </div>
            </div>

            <!-- Lazada Card -->
            <div class="glass-card rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-blue-600">🛍️ Lazada</h3>
                    <div class="flex items-center space-x-2">
                        <span id="lazada-source" class="data-source-indicator">-</span>
                        <span id="lazada-load-time" class="text-xs text-gray-500">-</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">ยอดขาย:</span>
                        <span id="lazadaSales" class="font-bold text-blue-600">₿0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">ออเดอร์:</span>
                        <span id="lazadaOrders" class="font-bold text-blue-600">0</span>
                    </div>
                    <button onclick="goToPlatform('lazada')" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        ดูรายละเอียด
                    </button>
                </div>
            </div>

            <!-- TikTok Card -->
            <div class="glass-card rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-pink-600">🎵 TikTok</h3>
                    <div class="flex items-center space-x-2">
                        <span id="tiktok-source" class="data-source-indicator">-</span>
                        <span id="tiktok-load-time" class="text-xs text-gray-500">-</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">ยอดขาย:</span>
                        <span id="tiktokSales" class="font-bold text-pink-600">₿0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">ออเดอร์:</span>
                        <span id="tiktokOrders" class="font-bold text-pink-600">0</span>
                    </div>
                    <button onclick="goToPlatform('tiktok')" class="w-full bg-pink-600 text-white py-2 px-4 rounded-lg hover:bg-pink-700 transition-colors">
                        ดูรายละเอียด
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="glass-card rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold">📋 กิจกรรมล่าสุด</h3>
                <div class="flex items-center space-x-2">
                    <span id="activity-source" class="data-source-indicator">database</span>
                    <button onclick="loadRecentActivity()" class="bg-gray-100 text-gray-600 px-3 py-1 rounded text-sm hover:bg-gray-200">
                        <i class="fas fa-refresh mr-1"></i>โหลดใหม่
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Platform</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Order ID</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">ยอดเงิน</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">สินค้า</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">เวลา</th>
                        </tr>
                    </thead>
                    <tbody id="recentOrdersTable" class="divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลดข้อมูล...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/animations.js"></script>
    <script>
        let animations;
        let refreshInterval;
        let countdownInterval;
        let lastRefreshTime = 0;
        let nextRefreshTime = 0;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            animations = new AnimationEngine();
            loadAllData();
            
            // Auto refresh every 90 seconds (more frequent for large stores)
            refreshInterval = setInterval(checkAndRefresh, 90 * 1000);
            
            // Countdown timer every second
            countdownInterval = setInterval(updateCountdown, 1000);
            
            // Show current refresh interval in console
            console.log('🔄 Auto-refresh enabled: every 90 seconds (optimized for large stores)');
        });

        // Load all dashboard data
        async function loadAllData() {
            const startTime = performance.now();
            updateLoadingState(true);
            
            try {
                const platforms = ['shopee', 'lazada', 'tiktok'];
                const summaryPromises = platforms.map(platform => 
                    loadPlatformSummary(platform)
                );
                
                const results = await Promise.all(summaryPromises);
                
                let totalSales = 0;
                let totalOrders = 0;
                let totalLoadTime = 0;
                let dataSources = [];
                
                results.forEach((result, index) => {
                    if (result.success) {
                        const platform = platforms[index];
                        const data = result.data;
                        
                        totalSales += data.totalSales;
                        totalOrders += data.totalOrders;
                        totalLoadTime += data.loadTime || 0;
                        
                        if (data.source) {
                            dataSources.push(data.source);
                        }
                        
                        // Update platform displays
                        updatePlatformDisplay(platform, data);
                    }
                });
                
                // Update summary displays
                if (animations) {
                    animations.animateNumber(document.getElementById('totalSales'), totalSales, '₿');
                    animations.animateNumber(document.getElementById('totalOrders'), totalOrders);
                } else {
                    document.getElementById('totalSales').textContent = `₿${totalSales.toLocaleString()}`;
                    document.getElementById('totalOrders').textContent = totalOrders.toLocaleString();
                }
                
                // Update performance indicators
                const avgLoadTime = totalLoadTime / platforms.length;
                updatePerformanceIndicators(avgLoadTime, dataSources);
                
                // Load recent activity
                await loadRecentActivity();
                
                // Load database stats
                await loadDatabaseStats();
                
                lastRefreshTime = Date.now();
                nextRefreshTime = lastRefreshTime + (90 * 1000); // 90 seconds from now
                
            } catch (error) {
                console.error('Failed to load data:', error);
                showError('ไม่สามารถโหลดข้อมูลได้');
            } finally {
                updateLoadingState(false);
            }
        }

        // Load platform summary
        async function loadPlatformSummary(platform) {
            try {
                const response = await fetch(`api.php?action=getSummary&platform=${platform}`);
                return await response.json();
            } catch (error) {
                console.error(`Failed to load ${platform} summary:`, error);
                return { success: false, error: error.message };
            }
        }

        // Update platform display
        function updatePlatformDisplay(platform, data) {
            const salesId = `${platform}Sales`;
            const ordersId = `${platform}Orders`;
            const sourceId = `${platform}-source`;
            const loadTimeId = `${platform}-load-time`;
            
            // Update numbers
            if (animations) {
                animations.animateNumber(document.getElementById(salesId), data.totalSales, '₿');
                animations.animateNumber(document.getElementById(ordersId), data.totalOrders);
            } else {
                document.getElementById(salesId).textContent = `₿${data.totalSales.toLocaleString()}`;
                document.getElementById(ordersId).textContent = data.totalOrders.toLocaleString();
            }
            
            // Update source indicator
            const sourceEl = document.getElementById(sourceId);
            if (sourceEl) {
                sourceEl.textContent = data.source || 'unknown';
                sourceEl.className = `data-source-indicator source-${data.source || 'unknown'}`;
            }
            
            // Update load time
            const loadTimeEl = document.getElementById(loadTimeId);
            if (loadTimeEl && data.loadTime) {
                loadTimeEl.textContent = `${data.loadTime}ms`;
            }
        }

        // Update performance indicators
        function updatePerformanceIndicators(avgLoadTime, dataSources) {
            // Performance score based on load time
            const performanceEl = document.getElementById('performanceScore');
            const loadTimeEl = document.getElementById('loading-time');
            
            let performanceClass = 'fast';
            let performanceText = 'เยี่ยม';
            
            if (avgLoadTime > 1000) {
                performanceClass = 'slow';
                performanceText = 'ช้า';
            } else if (avgLoadTime > 500) {
                performanceClass = 'medium';
                performanceText = 'ปานกลาง';
            }
            
            performanceEl.textContent = performanceText;
            loadTimeEl.textContent = `${Math.round(avgLoadTime)}ms`;
            loadTimeEl.className = `performance-indicator ${performanceClass} font-bold`;
            
            // Data source indicator
            const sourceEl = document.getElementById('data-source');
            const mostCommonSource = dataSources.length > 0 ? 
                dataSources.reduce((a, b, i, arr) => 
                    arr.filter(v => v === a).length >= arr.filter(v => v === b).length ? a : b
                ) : 'unknown';
                
            sourceEl.textContent = mostCommonSource;
            sourceEl.className = `data-source-indicator source-${mostCommonSource}`;
        }

        // Load recent activity
        async function loadRecentActivity() {
            try {
                const response = await fetch('api.php?action=getRecentActivity');
                const result = await response.json();
                
                if (result.success) {
                    const orders = result.data.recent_orders || [];
                    displayRecentOrders(orders);
                    
                    const sourceEl = document.getElementById('activity-source');
                    if (sourceEl) {
                        sourceEl.textContent = result.data.source || 'database';
                        sourceEl.className = `data-source-indicator source-${result.data.source || 'database'}`;
                    }
                }
            } catch (error) {
                console.error('Failed to load recent activity:', error);
            }
        }

        // Display recent orders
        function displayRecentOrders(orders) {
            const tableBody = document.getElementById('recentOrdersTable');
            
            if (!orders || orders.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">
                            ไม่มีกิจกรรมล่าสุด
                        </td>
                    </tr>
                `;
                return;
            }
            
            tableBody.innerHTML = orders.map(order => {
                const platformColors = {
                    'shopee': 'text-red-600 bg-red-100',
                    'lazada': 'text-blue-600 bg-blue-100',
                    'tiktok': 'text-pink-600 bg-pink-100'
                };
                
                const platformClass = platformColors[order.platform] || 'text-gray-600 bg-gray-100';
                const productName = order.items && order.items[0] ? order.items[0].name : (order.product || 'N/A');
                const createdAt = new Date(order.created_at);
                const timeAgo = getTimeAgo(createdAt);
                
                return `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full ${platformClass} font-semibold">
                                ${order.platform.toUpperCase()}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-medium">${order.order_id}</td>
                        <td class="py-3 px-4 font-semibold">₿${parseFloat(order.amount).toLocaleString()}</td>
                        <td class="py-3 px-4 text-sm">
                            <div class="truncate max-w-xs" title="${productName}">
                                ${productName}
                                ${order.items && order.items.length > 1 ? ` +${order.items.length - 1} รายการ` : ''}
                            </div>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">${timeAgo}</td>
                    </tr>
                `;
            }).join('');
        }

        // Load database statistics
        async function loadDatabaseStats() {
            try {
                const platforms = ['shopee', 'lazada', 'tiktok'];
                let totalDbOrders = 0;
                
                for (const platform of platforms) {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=get_stats&platform=${platform}`
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        totalDbOrders += result.data.total_orders || 0;
                    }
                }
                
                if (animations) {
                    animations.animateNumber(document.getElementById('dbTotalOrders'), totalDbOrders);
                } else {
                    document.getElementById('dbTotalOrders').textContent = totalDbOrders.toLocaleString();
                }
                
            } catch (error) {
                console.error('Failed to load database stats:', error);
            }
        }

        // Utility functions
        function getTimeAgo(date) {
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            const diffDays = Math.floor(diffHours / 24);
            
            if (diffMins < 1) return 'เมื่อสักครู่';
            if (diffMins < 60) return `${diffMins} นาทีที่แล้ว`;
            if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`;
            return `${diffDays} วันที่แล้ว`;
        }

        function updateLoadingState(isLoading) {
            const refreshBtn = document.getElementById('refresh-btn');
            if (refreshBtn) {
                refreshBtn.disabled = isLoading;
                refreshBtn.innerHTML = isLoading ? 
                    '<i class="fas fa-spinner fa-spin mr-1"></i>กำลังโหลด...' :
                    '<i class="fas fa-refresh mr-1"></i>รีเฟรช';
            }
        }

        function showError(message) {
            // Simple error display - could be enhanced with a toast system
            console.error('Dashboard Error:', message);
        }

        function checkAndRefresh() {
            const now = Date.now();
            const timeSinceLastRefresh = now - lastRefreshTime;
            
            // Auto-refresh every 90 seconds (more frequent than cache timeout)
            if (timeSinceLastRefresh > 90 * 1000) {
                console.log('🔄 Auto-refreshing data (90 seconds passed)');
                loadAllData();
            }
        }

        // User actions
        function refreshAllData() {
            loadAllData();
        }

        function forceFetchFromAPI() {
            // This would trigger a fresh API fetch by calling the order management system
            window.location.href = 'order_management.php';
        }

        function goToPlatform(platform) {
            window.location.href = `platform.php?platform=${platform}`;
        }

        // Update countdown timer
        function updateCountdown() {
            const now = Date.now();
            const timeLeft = Math.max(0, nextRefreshTime - now);
            const minutes = Math.floor(timeLeft / 60000);
            const seconds = Math.floor((timeLeft % 60000) / 1000);
            
            const countdownEl = document.getElementById('next-refresh-countdown');
            if (countdownEl) {
                if (timeLeft > 0) {
                    countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    countdownEl.className = 'text-blue-600 font-semibold';
                } else {
                    countdownEl.textContent = 'กำลังรีเฟรช...';
                    countdownEl.className = 'text-orange-600 font-semibold animate-pulse';
                }
            }
        }

        // Cleanup
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
        });
    </script>
</body>
</html>
