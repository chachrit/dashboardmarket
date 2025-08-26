<?php
$platform = $_GET['p'] ?? 'shopee';
$platformNames = [
    'shopee' => 'Shopee',
    'lazada' => 'Lazada', 
    'tiktok' => 'TikTok Shop'
];
$platformColors = [
    'shopee' => 'shopee',
    'lazada' => 'lazada',
    'tiktok' => 'tiktok'
];
$platformName = $platformNames[$platform] ?? 'Shopee';
$platformColor = $platformColors[$platform] ?? 'shopee';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $platformName; ?> - Dashboard ยอดขาย</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/animations.css" rel="stylesheet">
    <script src="assets/js/animations.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'shopee': '#EE4D2D',
                        'lazada': '#0F156D',
                        'tiktok': '#FF0050'
                    },
                    animation: {
                        'gradient': 'gradient 15s ease infinite',
                        'float': 'float 3s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes gradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-lg shadow-2xl border-b border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center animate-fadeIn">
                        <button onclick="goBack()" class="mr-4 p-3 rounded-xl hover:bg-gray-100 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-arrow-left text-gray-600 text-lg"></i>
                        </button>
                        <div class="relative mr-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-<?php echo $platformColor; ?> to-<?php echo $platformColor; ?>-600 rounded-2xl flex items-center justify-center animate-float">
                                <i class="fab fa-<?php echo $platform == 'shopee' ? 'shopify' : ($platform == 'tiktok' ? 'tiktok' : 'shopping-bag'); ?> text-3xl text-white"></i>
                            </div>
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full animate-pulse-slow border-2 border-white"></div>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-<?php echo $platformColor; ?> to-<?php echo $platformColor; ?>-700 bg-clip-text text-transparent"><?php echo $platformName; ?> Dashboard</h1>
                            <p class="text-gray-500 text-sm">ข้อมูลยอดขายและออเดอร์แบบเรียลไทม์</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 animate-fadeIn">
                        <button onclick="goToSettings()" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-cog mr-2"></i>ตั้งค่า
                        </button>
                        <div class="live-indicator bg-green-100 px-4 py-2 rounded-full border border-green-200">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2"></div>
                                <span class="text-green-800 text-sm font-medium">● Live</span>
                            </div>
                        </div>
                        <div class="text-gray-600 bg-white/50 px-3 py-2 rounded-lg" id="lastUpdate">อัพเดทล่าสุด: --</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Total Sales Card -->
                <div class="card-hover bg-gradient-to-br from-<?php echo $platformColor; ?> via-<?php echo $platformColor; ?> to-<?php echo $platformColor; ?>-800 rounded-2xl shadow-2xl p-8 text-white animate-scaleIn relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/80 text-sm font-medium mb-2">ยอดขายรวม</p>
                                <p class="text-4xl font-bold mb-1" id="todaySales">₿0</p>
                                <p class="text-white/60 text-xs">วันนี้</p>
                            </div>
                            <div class="bg-white/20 p-4 rounded-full">
                                <i class="fas fa-coins text-4xl animate-float"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/20">
                            <div class="flex items-center">
                                <i class="fas fa-trending-up text-green-300 mr-2"></i>
                                <span class="text-green-300 text-sm font-medium">เรียลไทม์</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Orders Card -->
                <div class="card-hover bg-white/70 backdrop-blur-lg rounded-2xl shadow-2xl p-8 animate-scaleIn border border-white/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium mb-2">ออเดอร์วันนี้</p>
                            <p class="text-4xl font-bold text-gray-900 mb-1" id="todayOrders">0</p>
                            <p class="text-gray-500 text-xs">รายการทั้งหมด</p>
                        </div>
                        <div class="bg-gradient-to-br from-<?php echo $platformColor; ?> to-<?php echo $platformColor; ?>-600 p-4 rounded-full">
                            <i class="fas fa-shopping-cart text-3xl text-white animate-float"></i>
                        </div>
                    </div>
            </div>

            <!-- Data Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Orders -->
                <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-2xl p-8 animate-fadeIn border border-white/20">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-700 to-gray-900 bg-clip-text text-transparent">รายการคำสั่งซื้อล่าสุด</h3>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-green-600 text-sm font-medium">10 รายการ</span>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">รหัสออเดอร์</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">สินค้า</th>
                                    <th class="text-right py-4 px-6 font-semibold text-gray-700">ยอด</th>
                                    <th class="text-center py-4 px-6 font-semibold text-gray-700">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody id="recentOrdersTable" class="divide-y divide-gray-200">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Best Selling Products -->
                <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-2xl p-8 animate-fadeIn border border-white/20">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-700 to-gray-900 bg-clip-text text-transparent">สินค้าขายดี</h3>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-orange-500 rounded-full animate-pulse"></div>
                            <span class="text-orange-600 text-sm font-medium">Top 10</span>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">อันดับ</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">สินค้า</th>
                                    <th class="text-right py-4 px-6 font-semibold text-gray-700">ขายได้</th>
                                </tr>
                            </thead>
                            <tbody id="topProductsTable" class="divide-y divide-gray-200">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize animations system
        let animations;
        let currentPlatform = '<?php echo $platform; ?>';
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            animations = new DashboardAnimations();
            loadPlatformData();
            
            // Add particle background
            if (animations) {
                animations.createParticleBackground();
            }
        });

        function loadPlatformData() {
            fetch(`api.php?platform=${currentPlatform}&action=getSummary`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateDashboard(data.data);
                        loadRecentOrders();
                        loadTopProducts();
                    } else {
                        console.error('Failed to load platform data:', data.message);
                        loadMockData();
                    }
                })
                .catch(error => {
                    console.error('Error loading platform data:', error);
                    loadMockData();
                });
        }

        function loadMockData() {
            const mockData = {
                totalSales: currentPlatform === 'shopee' ? 45000 : currentPlatform === 'lazada' ? 38000 : 27000,
                totalOrders: currentPlatform === 'shopee' ? 125 : currentPlatform === 'lazada' ? 98 : 67
            };
            updateDashboard(mockData);
            loadMockOrders();
            loadMockProducts();
        }

        function updateDashboard(data) {
            if (animations) {
                animations.animateNumber(document.getElementById('todaySales'), data.totalSales, '₿');
                animations.animateNumber(document.getElementById('todayOrders'), data.totalOrders);
            } else {
                document.getElementById('todaySales').textContent = `₿${data.totalSales.toLocaleString()}`;
                document.getElementById('todayOrders').textContent = data.totalOrders.toLocaleString();
            }

            // Update timestamp with glow effect
            const lastUpdateEl = document.getElementById('lastUpdate');
            lastUpdateEl.textContent = `อัพเดทล่าสุด: ${new Date().toLocaleTimeString('th-TH')}`;
            if (animations) {
                lastUpdateEl.classList.add('animate-glow');
                setTimeout(() => lastUpdateEl.classList.remove('animate-glow'), 2000);
            }
        }

        function loadRecentOrders() {
            fetch(`api.php?platform=${currentPlatform}&action=getOrders&limit=10`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.orders) {
                        displayRecentOrders(data.data.orders);
                    } else {
                        loadMockOrders();
                    }
                })
                .catch(error => {
                    console.error('Error loading recent orders:', error);
                    loadMockOrders();
                });
        }

        function loadMockOrders() {
            const mockOrders = [
                { order_id: 'SP2024001', product: 'เสื้อยืด Basic', amount: 890, status: 'confirmed' },
                { order_id: 'SP2024002', product: 'กางเกงยีนส์', amount: 1250, status: 'shipped' },
                { order_id: 'SP2024003', product: 'รองเท้าผ้าใบ', amount: 1890, status: 'delivered' },
                { order_id: 'SP2024004', product: 'เสื้อเชิ้ต', amount: 980, status: 'confirmed' },
                { order_id: 'SP2024005', product: 'กระเป๋าสะพาย', amount: 1450, status: 'shipped' }
            ];
            displayRecentOrders(mockOrders);
        }

        function displayRecentOrders(orders) {
            const tableBody = document.getElementById('recentOrdersTable');
            tableBody.innerHTML = '';
            
            orders.forEach((order, index) => {
                const statusClass = {
                    'confirmed': 'bg-yellow-100 text-yellow-800',
                    'shipped': 'bg-blue-100 text-blue-800', 
                    'delivered': 'bg-green-100 text-green-800',
                    'cancelled': 'bg-red-100 text-red-800'
                }[order.status] || 'bg-gray-100 text-gray-800';

                const statusText = {
                    'confirmed': 'ยืนยันแล้ว',
                    'shipped': 'จัดส่งแล้ว',
                    'delivered': 'ส่งสำเร็จ',
                    'cancelled': 'ยกเลิก'
                }[order.status] || 'รอดำเนินการ';
                
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 transition-colors duration-200 animate-slideInRight';
                row.style.animationDelay = `${index * 0.1}s`;
                row.innerHTML = `
                    <td class="py-4 px-6 font-medium text-gray-900">${order.order_id}</td>
                    <td class="py-4 px-6 text-gray-600">${order.product}</td>
                    <td class="py-4 px-6 text-right font-semibold text-<?php echo $platformColor; ?>">₿${order.amount.toLocaleString()}</td>
                    <td class="py-4 px-6 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-medium ${statusClass}">${statusText}</span>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        function loadTopProducts() {
            fetch(`api.php?platform=${currentPlatform}&action=getTopProducts&limit=10`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.products) {
                        displayTopProducts(data.data.products);
                    } else {
                        loadMockProducts();
                    }
                })
                .catch(error => {
                    console.error('Error loading top products:', error);
                    loadMockProducts();
                });
        }

        function loadMockProducts() {
            const mockProducts = [
                { name: 'เสื้อยืด Basic', sold: 245 },
                { name: 'กางเกงยีนส์', sold: 189 },
                { name: 'รองเท้าผ้าใบ', sold: 156 },
                { name: 'เสื้อเชิ้ต', sold: 134 },
                { name: 'กระเป๋าสะพาย', sold: 98 },
                { name: 'หูฟัง Wireless', sold: 87 },
                { name: 'นาฬิกาข้อมือ', sold: 76 },
                { name: 'แว่นตากันแดด', sold: 65 },
                { name: 'เข็มขัดหนัง', sold: 54 },
                { name: 'กระเป๋าตัง', sold: 43 }
            ];
            displayTopProducts(mockProducts);
        }

        function displayTopProducts(products) {
            const tableBody = document.getElementById('topProductsTable');
            tableBody.innerHTML = '';
            
            products.forEach((product, index) => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 transition-colors duration-200 animate-slideInRight';
                row.style.animationDelay = `${index * 0.1}s`;
                
                const rankColors = ['text-yellow-600', 'text-gray-500', 'text-orange-600'];
                const rankColor = rankColors[index] || 'text-gray-400';
                
                row.innerHTML = `
                    <td class="py-4 px-6">
                        <div class="flex items-center">
                            <span class="w-8 h-8 rounded-full bg-gradient-to-r from-<?php echo $platformColor; ?> to-<?php echo $platformColor; ?>-600 text-white flex items-center justify-center text-sm font-bold mr-3">
                                ${index + 1}
                            </span>
                        </div>
                    </td>
                    <td class="py-4 px-6 font-medium text-gray-900">${product.name}</td>
                    <td class="py-4 px-6 text-right">
                        <span class="font-semibold text-<?php echo $platformColor; ?>">${product.sold}</span>
                        <span class="text-gray-500 text-sm ml-1">ชิ้น</span>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        function goBack() {
            window.location.href = 'index.php';
        }

        function goToSettings() {
            window.location.href = 'settings.php';
        }

        // Auto-update every 30 seconds
        setInterval(loadPlatformData, 30000);

        // Add mouse movement particle effects
        let lastParticleTime = 0;
        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            if (now - lastParticleTime > 150 && animations) {
                animations.createMouseParticle(e.clientX, e.clientY);
                lastParticleTime = now;
            }
        });
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">รหัสออเดอร์</th>
                                    <th class="text-left py-2">สินค้า</th>
                                    <th class="text-right py-2">ยอดรวม</th>
                                    <th class="text-left py-2">เวลา</th>
                                </tr>
                            </thead>
                            <tbody id="recentOrdersTable">
                                <!-- Orders will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">สินค้าขายดี 10 รายการ</h3>
                    <div class="space-y-4" id="topProducts">
                        <!-- Products will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const platform = '<?php echo $platform; ?>';
        const platformColor = '<?php echo $platformColor; ?>';
        
        // Platform data will be loaded from API
        let platformData = {
            sales: 0,
            orders: 0
        };

        // Sample recent orders and top products will be replaced by API data
        let recentOrders = [];
        let topProducts = [];

        function loadPlatformData() {
            // Load summary data from API
            fetch(`api.php?action=summary&platform=${platform}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('API Error:', data.error);
                        loadFallbackData();
                        return;
                    }
                    
                    platformData.sales = data.sales || 0;
                    platformData.orders = data.orders || 0;
                    recentOrders = data.recent_orders || [];
                    topProducts = data.top_products || [];
                    
                    updateDashboard();
                    populateRecentOrders();
                    populateTopProducts();
                })
                .catch(error => {
                    console.error('Failed to load platform data:', error);
                    loadFallbackData();
                });
        }
        
        function loadFallbackData() {
            // Fallback to mock data if API fails
            platformData = {
                sales: platform === 'shopee' ? 45000 : platform === 'lazada' ? 38000 : 27000,
                orders: platform === 'shopee' ? 125 : platform === 'lazada' ? 98 : 67
            };
            
            recentOrders = [
                { id: 'ORD001', product: 'เสื้อยืด Basic', amount: 890, time: '2 นาทีที่แล้ว' },
                { id: 'ORD002', product: 'กางเกงยีนส์', amount: 1250, time: '5 นาทีที่แล้ว' },
                { id: 'ORD003', product: 'รองเท้าผ้าใบ', amount: 2100, time: '8 นาทีที่แล้ว' },
                { id: 'ORD004', product: 'เสื้อเชิ้ต', amount: 1590, time: '12 นาทีที่แล้ว' },
                { id: 'ORD005', product: 'กระเป๋าสะพาย', amount: 890, time: '15 นาทีที่แล้ว' },
                { id: 'ORD006', product: 'หูฟัง Bluetooth', amount: 2500, time: '18 นาทีที่แล้ว' },
                { id: 'ORD007', product: 'นาฬิกาข้อมือ', amount: 3200, time: '22 นาทีที่แล้ว' },
                { id: 'ORD008', product: 'แก้วน้ำสแตนเลส', amount: 450, time: '25 นาทีที่แล้ว' },
                { id: 'ORD009', product: 'เคสโทรศัพท์', amount: 350, time: '30 นาทีที่แล้ว' },
                { id: 'ORD010', product: 'ลำโพงพกพา', amount: 1800, time: '35 นาทีที่แล้ว' }
            ];

            topProducts = [
                { name: 'เสื้อยืด Basic', sales_count: 156, revenue: 138840 },
                { name: 'กางเกงยีนส์', sales_count: 89, revenue: 111250 },
                { name: 'รองเท้าผ้าใบ', sales_count: 45, revenue: 94500 },
                { name: 'เสื้อเชิ้ต', sales_count: 67, revenue: 106530 },
                { name: 'กระเป๋าสะพาย', sales_count: 34, revenue: 30260 },
                { name: 'หูฟัง Bluetooth', sales_count: 28, revenue: 70000 },
                { name: 'นาฬิกาข้อมือ', sales_count: 22, revenue: 70400 },
                { name: 'แก้วน้ำสแตนเลส', sales_count: 95, revenue: 42750 },
                { name: 'เคสโทรศัพท์', sales_count: 78, revenue: 27300 },
                { name: 'ลำโพงพกพา', sales_count: 15, revenue: 27000 }
            ];
            
            updateDashboard();
            populateRecentOrders();
            populateTopProducts();
        }

        function updateDashboard() {
            document.getElementById('todaySales').textContent = `₿${platformData.sales.toLocaleString()}`;
            document.getElementById('totalOrders').textContent = platformData.orders.toLocaleString();
            document.getElementById('lastUpdate').textContent = `อัพเดทล่าสุด: ${new Date().toLocaleTimeString('th-TH')}`;
        }

        function populateRecentOrders() {
            const tbody = document.getElementById('recentOrdersTable');
            tbody.innerHTML = recentOrders.map(order => `
                <tr class="border-b">
                    <td class="py-3 font-medium">${order.id}</td>
                    <td class="py-3">${order.product}</td>
                    <td class="py-3 text-right font-bold text-${platformColor}">₿${order.amount.toLocaleString()}</td>
                    <td class="py-3 text-gray-500 text-sm">${order.time}</td>
                </tr>
            `).join('');
        }

        function populateTopProducts() {
            const container = document.getElementById('topProducts');
            container.innerHTML = topProducts.map((product, index) => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-8 h-8 bg-${platformColor} text-white rounded-full flex items-center justify-center text-sm font-bold">
                            ${index + 1}
                        </div>
                        <div>
                            <p class="font-semibold">${product.name}</p>
                            <p class="text-gray-600 text-sm">ขายได้ ${product.sales_count || product.sales} ชิ้น</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-${platformColor}">₿${product.revenue.toLocaleString()}</p>
                    </div>
                </div>
            `).join('');
        }

        function goBack() {
            window.location.href = 'index.php';
        }

        function goToSettings() {
            window.location.href = 'settings.php';
        }

        function simulateRealTimeData() {
            // Random updates - reload from API periodically
            if (Math.random() > 0.7) {
                loadPlatformData();
            }
        }

        // Initialize - load real data from API
        loadPlatformData();

        // Auto-update every 30 seconds
        setInterval(simulateRealTimeData, 30000);
    </script>
</body>
</html>
