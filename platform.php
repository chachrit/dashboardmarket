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
$shopeeEnv = $_COOKIE['shopee_env'] ?? 'prod'; // added
$useMock = isset($_GET['use_mock']) && $_GET['use_mock']=='1';
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
                        <?php if($platform==='shopee'): ?>
                        <div class="flex items-center space-x-2 bg-white/60 px-3 py-2 rounded-xl border border-<?php echo $platformColor; ?>/30">
                            <label for="envSelect" class="text-xs text-gray-600 font-medium">Env</label>
                            <select id="envSelect" onchange="changeShopeeEnv(this.value)" class="text-xs bg-transparent focus:outline-none font-semibold text-<?php echo $platformColor; ?>">
                                <option value="prod" <?php echo $shopeeEnv==='prod'?'selected':''; ?>>Prod</option>
                                <option value="sandbox" <?php echo $shopeeEnv==='sandbox'?'selected':''; ?>>Sandbox</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="live-indicator bg-green-100 px-4 py-2 rounded-full border border-green-200" id="sourceBadge">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2" id="sourceDot"></div>
                                <span class="text-green-800 text-sm font-medium" id="sourceText">● Live</span>
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
            </div>

            <!-- Data Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Orders -->
                <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-2xl p-8 animate-fadeIn border border-white/20">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-700 to-gray-900 bg-clip-text text-transparent">รายการคำสั่งซื้อล่าสุด</h3>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            <div class="flex items-center space-x-2">
                                <label for="recentLimitSelect" class="text-gray-500 text-sm">จำนวน</label>
                                <select id="recentLimitSelect" onchange="onRecentLimitChange(this.value)" class="text-sm bg-white/80 border border-gray-200 rounded-md px-2 py-1">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                    <option value="40">40</option>
                                    <option value="50">50</option>
                                </select>
                                <span id="recentCountLabel" class="text-green-600 text-sm font-medium">10 รายการ</span>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">รหัสออเดอร์</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">สินค้า</th>
                                    <th class="text-right py-4 px-6 font-semibold text-gray-700">ยอด</th>
                                    <!-- สถานะถูกนำออก -->
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
                                    <!-- คอลัมน์ขายได้ถูกนำออก -->
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
        let shopeeEnv = '<?php echo $shopeeEnv; ?>'; // added
        let useMock = <?php echo $useMock? 'true':'false'; ?>;
    let recentOrderIds = [];
    let recentOrdersPollingStarted = false;
    let recentLimit = 10; // number of recent orders to fetch/display
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            animations = new DashboardAnimations();
            loadPlatformData();
            startRecentOrdersPolling();
            
            // Add particle background
            if (animations) {
                animations.createParticleBackground();
            }
        });

        function loadPlatformData() {
            fetch(buildAPIUrl('getSummary'))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setSource('live');
                        updateDashboard(data.data);
                        loadRecentOrders();
                        loadTopProducts();
                    } else if(useMock){
                        setSource('mock');
                        loadMockData();
                    } else {
                        setSource('error');
                        showErrorToast('Summary error: '+(data.error||'unknown'));
                    }
                })
                .catch(error => {
                    if(useMock){ setSource('mock'); loadMockData(); } else { setSource('error'); showErrorToast('โหลด Summary ล้มเหลว'); }
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
            fetch(buildAPIUrl('getOrders','&limit='+recentLimit))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.orders) {
                        if(!useMock) setSource('live');
                        displayRecentOrders(data.data.orders);
                    } else if(useMock) {
                        setSource('mock');
                        loadMockOrders();
                    } else {
                        showErrorOrders();
                    }
                })
                .catch(error => {
                    if(useMock){ setSource('mock'); loadMockOrders(); } else { showErrorOrders(); }
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
            // Ensure orders are sorted newest -> oldest by created_at before rendering
            try {
                orders = (orders || []).slice();
                orders.sort((a, b) => {
                    const ta = a.created_at ? new Date(a.created_at).getTime() : 0;
                    const tb = b.created_at ? new Date(b.created_at).getTime() : 0;
                    return tb - ta;
                });
            } catch (e) {
                // if parsing fails, continue with original order
            }
            const tableBody = document.getElementById('recentOrdersTable');
            const previousIds = new Set(recentOrderIds);
            recentOrderIds = orders.map(o => o.order_id);
            tableBody.innerHTML = '';
            function formatCreatedAt(s){
                if(!s) return '';
                try{
                    // If string contains 'T', split date and time and remove timezone offset
                    if(typeof s === 'string'){
                        const parts = s.split('T');
                        if(parts.length>=2){
                            const datePart = parts[0];
                            // remove timezone offset like +07:00 or -05:00 or trailing Z
                            let timePart = parts.slice(1).join('T');
                            timePart = timePart.split(/\+|\-|Z/)[0];
                            return `${datePart}<div class="text-xs text-gray-400 mt-1">${timePart}</div>`;
                        }
                    }
                    return s;
                }catch(e){ return s; }
            }

            orders.slice(0, recentLimit).forEach((order, index) => {
                const isNew = !previousIds.has(order.order_id) && previousIds.size > 0; // treat as new only after first load
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 transition-colors duration-200 animate-slideInRight';
                if (isNew) {
                    row.classList.add('bg-amber-50');
                    setTimeout(()=>row.classList.remove('bg-amber-50'),3000);
                }
                row.style.animationDelay = `${index * 0.05}s`;
                let productHtml = '';
                if (order.items && order.items.length) {
                    productHtml = order.items.map(it => `<div class="text-gray-600 text-sm">${it.name} <span class="text-gray-400">x${it.quantity}</span></div>`).join('');
                } else {
                    productHtml = `<div class="text-gray-600 text-sm">${order.product}</div>`;
                }
                const createdDisplay = formatCreatedAt(order.created_at || '');
                row.innerHTML = `
                    <td class="py-4 px-6 font-medium text-gray-900 align-top">${order.order_id}${createdDisplay ? createdDisplay : ''}</td>
                    <td class="py-4 px-6 align-top space-y-1">${productHtml}</td>
                    <td class="py-4 px-6 text-right font-semibold text-<?php echo $platformColor; ?> align-top">₿${(order.amount||0).toLocaleString()}</td>
                `;
                tableBody.appendChild(row);
            });
        }

        function loadTopProducts() {
            fetch(buildAPIUrl('getTopProducts','&limit=10'))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.products) {
                        if(!useMock) setSource('live');
                        displayTopProducts(data.data.products);
                    } else if(useMock) {
                        setSource('mock');
                        loadMockProducts();
                    } else {
                        showErrorTopProducts();
                    }
                })
                .catch(error => {
                    if(useMock){ setSource('mock'); loadMockProducts(); } else { showErrorTopProducts(); }
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
                { name: 'กระเป๋าเงิน', sold: 43 }
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
                row.innerHTML = `
                    <td class="py-4 px-6"><div class="flex items-center"><span class="w-8 h-8 rounded-full bg-gradient-to-r from-<?php echo $platformColor; ?> to-<?php echo $platformColor; ?>-600 text-white flex items-center justify-center text-sm font-bold">${index + 1}</span></div></td>
                    <td class="py-4 px-6 font-medium text-gray-900">${product.name}</td>
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

        function startRecentOrdersPolling(){
            if (recentOrdersPollingStarted) return;
            recentOrdersPollingStarted = true;
            setInterval(()=>{
                fetch(buildAPIUrl('getOrders','&limit='+recentLimit))
                    .then(r=>r.json())
                    .then(d=>{ if(d.success && d.data.orders){ displayRecentOrders(d.data.orders); updateTimestamp(); } })
                    .catch(()=>{});
            },15000); // เปลี่ยนจาก 30000ms (30 วิ) เป็น 15000ms (15 วิ) สำหรับร้านค้าขนาดใหญ่
        }

        function onRecentLimitChange(val){
            const v = parseInt(val) || 10;
            recentLimit = v;
            const label = document.getElementById('recentCountLabel');
            if(label) label.textContent = `${recentLimit} รายการ`;
            // reload orders immediately with new limit
            document.getElementById('recentOrdersTable').innerHTML = '<tr><td colspan="3" class="py-4 px-6 text-center text-gray-400 text-sm">กำลังโหลด...</td></tr>';
            loadRecentOrders();
        }

        function updateTimestamp(){
            const lastUpdateEl = document.getElementById('lastUpdate');
            lastUpdateEl.textContent = `อัพเดทล่าสุด: ${new Date().toLocaleTimeString('th-TH')}`;
        }

        function buildAPIUrl(action, extraParams=''){
            let url = `api.php?platform=${currentPlatform}&action=${action}`;
            if(currentPlatform==='shopee') url += `&env=${shopeeEnv}`; // append env
            return url + (extraParams||'');
        }
        function changeShopeeEnv(env){
            document.cookie = 'shopee_env='+env+'; path=/';
            shopeeEnv = env;
            // clear tables before reload
            document.getElementById('recentOrdersTable').innerHTML='<tr><td colspan="3" class="py-4 px-6 text-center text-gray-400 text-sm">กำลังโหลด...</td></tr>';
            document.getElementById('topProductsTable').innerHTML='<tr><td colspan="2" class="py-4 px-6 text-center text-gray-400 text-sm">กำลังโหลด...</td></tr>';
            loadPlatformData();
        }

        function setSource(type){
            const badge = document.getElementById('sourceBadge');
            const dot = document.getElementById('sourceDot');
            const txt = document.getElementById('sourceText');
            if(!badge) return;
            if(type==='live'){ badge.className='live-indicator bg-green-100 px-4 py-2 rounded-full border border-green-200'; dot.className='w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2'; txt.textContent='● Live'; txt.className='text-green-800 text-sm font-medium'; }
            else if(type==='mock'){ badge.className='live-indicator bg-yellow-100 px-4 py-2 rounded-full border border-yellow-300'; dot.className='w-2 h-2 bg-yellow-500 rounded-full animate-pulse mr-2'; txt.textContent='● Mock'; txt.className='text-yellow-800 text-sm font-medium'; }
            else { badge.className='live-indicator bg-red-100 px-4 py-2 rounded-full border border-red-300'; dot.className='w-2 h-2 bg-red-500 rounded-full animate-pulse mr-2'; txt.textContent='● Error'; txt.className='text-red-800 text-sm font-medium'; }
        }
        function showErrorOrders(){
            const tb=document.getElementById('recentOrdersTable');
            tb.innerHTML='<tr><td colspan="3" class="py-6 px-6 text-center text-red-500 text-sm">โหลดออเดอร์ไม่ได้ (กำลังใช้ Live) - ตรวจ token / env</td></tr>';
        }
        function showErrorTopProducts(){
            const tb=document.getElementById('topProductsTable');
            if(tb) tb.innerHTML='<tr><td colspan="2" class="py-6 px-6 text-center text-red-500 text-sm">โหลด Top Products ไม่ได้</td></tr>';
        }
        function showErrorToast(msg){
            console.warn(msg);
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
    </script>
</body>
</html>
