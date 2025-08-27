<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Dashboard Realtime</title>
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
        <header class="bg-white/80 backdrop-blur-lg shadow-lg border-b border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center animate-fadeIn">
                        <div class="relative">
                            <i class="fas fa-chart-line text-3xl text-blue-600 mr-3 animate-float"></i>
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse-slow"></div>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Journal Dashboard</h1>
                            <p class="text-gray-500 text-sm">Realtime Dashboard</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 animate-fadeIn">
                        <button onclick="goToSettings()" class="btn-modern bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
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
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Total Sales Card -->
                <div class="card-hover bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700 rounded-2xl shadow-2xl p-8 text-white animate-scaleIn gradient-animate relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm font-medium mb-2">ยอดขายรวม</p>
                                <p class="text-4xl font-bold mb-1" id="totalSales">₿0</p>
                                <p class="text-purple-100 text-xs">วันนี้</p>
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
                            <p class="text-gray-600 text-sm font-medium mb-2">ออเดอร์รวม</p>
                            <p class="text-4xl font-bold text-gray-900 mb-1" id="totalOrders">0</p>
                            <p class="text-gray-500 text-xs">รายการทั้งหมด</p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-500 to-purple-500 p-4 rounded-full">
                            <i class="fas fa-shopping-cart text-3xl text-white animate-float"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Platform Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" id="platformCards">
                <!-- Shopee Card -->
                <div class="platform-card bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-500 animate-scaleIn transform hover:scale-105">
                    <div class="bg-gradient-to-r from-shopee to-red-600 p-4">
                        <div class="flex items-center justify-between text-white">
                            <h3 class="text-xl font-bold">Shopee</h3>
                            <div class="platform-icon">
                                <i class="fab fa-shopify text-2xl animate-float"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse connection-status"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 bg-gradient-to-br from-white to-gray-50">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 flex items-center">
                                    <i class="fas fa-coins text-shopee mr-2"></i>ยอดขาย
                                </span>
                                <span class="font-bold text-lg text-shopee sales-counter" id="shopeeSales">₿0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 flex items-center">
                                    <i class="fas fa-shopping-cart text-shopee mr-2"></i>ออเดอร์
                                </span>
                                <span class="font-semibold orders-counter" id="shopeeOrders">0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">สถานะ</span>
                                <span class="status-badge px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs border border-green-200">
                                    <i class="fas fa-wifi mr-1"></i>เชื่อมต่อแล้ว
                                </span>
                            </div>
                        </div>
                        <button onclick="goToPlatform('shopee')" class="w-full mt-6 bg-gradient-to-r from-shopee to-red-600 text-white py-3 px-4 rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-300 transform hover:translateY(-1px) shadow-lg hover:shadow-xl">
                            <i class="fas fa-arrow-right mr-2"></i>ดูรายละเอียด
                        </button>
                    </div>
                </div>

                <!-- Lazada Card -->
                <div class="platform-card bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-500 animate-scaleIn transform hover:scale-105">
                    <div class="bg-gradient-to-r from-lazada to-blue-800 p-4">
                        <div class="flex items-center justify-between text-white">
                            <h3 class="text-xl font-bold">Lazada</h3>
                            <div class="platform-icon relative">
                                <i class="fas fa-shopping-bag text-2xl animate-float"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse connection-status"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 bg-gradient-to-br from-white to-gray-50">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 flex items-center">
                                    <i class="fas fa-coins text-lazada mr-2"></i>ยอดขาย
                                </span>
                                <span class="font-bold text-lg text-lazada sales-counter" id="lazadaSales">₿0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 flex items-center">
                                    <i class="fas fa-shopping-cart text-lazada mr-2"></i>ออเดอร์
                                </span>
                                <span class="font-semibold orders-counter" id="lazadaOrders">0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">สถานะ</span>
                                <span class="status-badge px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs border border-green-200">
                                    <i class="fas fa-wifi mr-1"></i>เชื่อมต่อแล้ว
                                </span>
                            </div>
                        </div>
                        <button onclick="goToPlatform('lazada')" class="w-full mt-6 bg-gradient-to-r from-lazada to-blue-800 text-white py-3 px-4 rounded-lg hover:from-blue-800 hover:to-blue-900 transition-all duration-300 transform hover:translateY(-1px) shadow-lg hover:shadow-xl">
                            <i class="fas fa-arrow-right mr-2"></i>ดูรายละเอียด
                        </button>
                    </div>
                </div>

                <!-- TikTok Shop Card -->
                <div class="platform-card bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-500 animate-scaleIn transform hover:scale-105">
                    <div class="bg-gradient-to-r from-tiktok to-pink-600 p-4">
                        <div class="flex items-center justify-between text-white">
                            <h3 class="text-xl font-bold">TikTok Shop</h3>
                            <div class="platform-icon relative">
                                <i class="fab fa-tiktok text-2xl animate-float"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse connection-status"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 bg-gradient-to-br from-white to-gray-50">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 flex items-center">
                                    <i class="fas fa-coins text-tiktok mr-2"></i>ยอดขาย
                                </span>
                                <span class="font-bold text-lg text-tiktok sales-counter" id="tiktokSales">₿0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 flex items-center">
                                    <i class="fas fa-shopping-cart text-tiktok mr-2"></i>ออเดอร์
                                </span>
                                <span class="font-semibold orders-counter" id="tiktokOrders">0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">สถานะ</span>
                                <span class="status-badge px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs border border-green-200">
                                    <i class="fas fa-wifi mr-1"></i>เชื่อมต่อแล้ว
                                </span>
                            </div>
                        </div>
                        <button onclick="goToPlatform('tiktok')" class="w-full mt-6 bg-gradient-to-r from-tiktok to-pink-600 text-white py-3 px-4 rounded-lg hover:from-pink-600 hover:to-pink-700 transition-all duration-300 transform hover:translateY(-1px) shadow-lg hover:shadow-xl">
                            <i class="fas fa-arrow-right mr-2"></i>ดูรายละเอียด
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-2xl p-8 animate-fadeIn border border-white/20">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-700 to-gray-900 bg-clip-text text-transparent">กิจกรรมล่าสุด</h3>
                    <div class="activity-indicator">
                        <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                    </div>
                </div>
                <div class="space-y-4" id="recentActivity">
                    <!-- recent activity will be populated by loadRecentActivity() -->
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize animations system
        let animations;
        let salesData = {
            shopee: { sales: 0, orders: 0 },
            lazada: { sales: 0, orders: 0 },
            tiktok: { sales: 0, orders: 0 }
        };
        let previousData = JSON.parse(JSON.stringify(salesData));

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            animations = new DashboardAnimations();
            
            // Add entrance animations to cards
            const cards = document.querySelectorAll('.platform-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.2}s`;
                card.classList.add('animate-fadeIn');
            });
            
            // Add particle background effect
            if (animations) {
                animations.createParticleBackground();
            }

            // Hide disabled platforms based on cookies and load initial data
            const enabledPlatforms = [];
            ['shopee','lazada','tiktok'].forEach(p => {
                const c = document.cookie.split(';').map(s=>s.trim()).find(s=>s.startsWith(`${p}_enabled=`));
                let enabled = true;
                if(c){ enabled = c.split('=')[1] === 'true'; }
                if(!enabled){
                    // Hide card and zero out numbers
                    const card = document.querySelector(`#${p}Sales`)?document.querySelector(`#${p}Sales`).closest('.platform-card'):null;
                    if(card) card.style.display = 'none';
                    const statusEl = document.querySelector(`#${p}Sales`)?.closest('.platform-card')?.querySelector('.status-badge');
                    if(statusEl){ statusEl.textContent = 'ปิดการใช้งาน'; statusEl.className='status-badge px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs border border-gray-200'; }
                } else {
                    enabledPlatforms.push(p);
                }
            });

            loadAllPlatformsData(enabledPlatforms);
            // Load recent activity from API
            loadRecentActivity();
        });
        
        function loadRecentActivity() {
            const container = document.getElementById('recentActivity');
            container.innerHTML = ''; // clear
            fetch('api.php?action=getRecentActivity')
                .then(res => res.json())
                .then(json => {
                    if(json.success && Array.isArray(json.data)){
                        // sort newest first by created_at/time
                        try{
                            json.data.sort((a,b)=>{
                                const ta = new Date(a.created_at || a.time || 0).getTime() || 0;
                                const tb = new Date(b.created_at || b.time || 0).getTime() || 0;
                                return tb - ta;
                            });
                        }catch(e){}
                        json.data.forEach((item, idx) => {
                            const el = renderRecentActivityItem(item);
                            if(el) container.appendChild(el);
                        });
                    } else {
                        container.innerHTML = '<div class="text-sm text-gray-500">ไม่พบกิจกรรมล่าสุด</div>';
                    }
                })
                .catch(err => {
                    console.error('Failed to load recent activity', err);
                    container.innerHTML = '<div class="text-sm text-gray-500">โหลดกิจกรรมล้มเหลว</div>';
                });

            // refresh periodically
            if(window._recentActivityTimer) clearTimeout(window._recentActivityTimer);
            window._recentActivityTimer = setTimeout(loadRecentActivity, 15000);
        }

        function renderRecentActivityItem(item){
            // expected order shape: { platform, order_id, items: [{name,quantity}], product, amount, created_at, time }
            if(!item || !item.platform) return null;
            const platform = item.platform;
            const wrapper = document.createElement('div');
            wrapper.className = 'activity-item flex items-center justify-between p-4 rounded-xl border animate-slideInRight bg-white';

            // build product HTML similar to platform.php
            let productHtml = '';
            if(item.items && Array.isArray(item.items) && item.items.length){
                productHtml = item.items.map(it=>`<div class="text-gray-600 text-sm">${it.name} <span class="text-gray-400">x${it.quantity||1}</span></div>`).join('');
            } else if(item.product){
                productHtml = `<div class="text-gray-600 text-sm">${item.product}</div>`;
            }

            const iconClass = platform==='lazada' ? 'fas fa-shopping-bag' : (platform==='shopee' ? 'fab fa-shopify' : 'fab fa-tiktok');
            const amountText = item.amount ? `₿${Number(item.amount).toLocaleString()}` : '';
            const createdAtRaw = item.created_at || item.time || '';

            // format createdAt to remove timezone suffix and show date+time like platform.php
            function formatCreatedAtForIndex(s){
                if(!s) return '';
                try{
                    if(typeof s === 'string'){
                        const parts = s.split('T');
                        if(parts.length>=2){
                            const datePart = parts[0];
                            let timePart = parts.slice(1).join('T');
                            timePart = timePart.split(/\+|\-|Z/)[0];
                            return `${datePart}<div class="text-xs text-gray-400 mt-1">${timePart}</div>`;
                        }
                    }
                    return s;
                }catch(e){ return s; }
            }
            const createdAt = formatCreatedAtForIndex(createdAtRaw);

            wrapper.innerHTML = `
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-${platform} to-${platform}-600 rounded-full flex items-center justify-center animate-float">
                        <i class="${iconClass} text-white text-lg"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">${item.order_id || ''}</p>
                                <div class="text-xs text-gray-400 mt-1">${createdAt}</div>
                        ${productHtml}
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold text-${platform} text-lg">${amountText}</p>
                    <p class="text-gray-500 text-sm">${createdAtRaw.split('T')[0] || createdAtRaw}</p>
                </div>
            `;
            return wrapper;
        }

        function loadAllPlatformsData(platforms = ['shopee', 'lazada', 'tiktok']) {
            let completedRequests = 0;
            
            if(platforms.length===0){
                // Nothing to load
                updateDashboardWithAnimations();
                return;
            }

            platforms.forEach(platform => {
                fetch(`api.php?action=getSummary&platform=${platform}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            salesData[platform] = {
                                sales: data.data.totalSales || 0,
                                orders: data.data.totalOrders || 0
                            };
                        } else {
                            console.log(`No live data for ${platform}:`, data.error);
                            // Do not use mock values — set to zero so totals reflect real data only
                            salesData[platform] = { sales: 0, orders: 0 };
                        }
                    })
                    .catch(error => {
                        console.error(`Failed to load ${platform} data:`, error);
                        // On fetch error, don't fill with mock numbers
                        salesData[platform] = { sales: 0, orders: 0 };
                    })
                    .finally(() => {
                        completedRequests++;
                        if (completedRequests === platforms.length) {
                            updateDashboardWithAnimations();
                        }
                    });
            });
         }

        function updateDashboardWithAnimations() {
            const totalSales = Object.values(salesData).reduce((sum, platform) => sum + platform.sales, 0);
            const totalOrders = Object.values(salesData).reduce((sum, platform) => sum + platform.orders, 0);

            // Check for changes and trigger notifications
            Object.keys(salesData).forEach(platform => {
                const current = salesData[platform];
                const previous = previousData[platform];
                
                if (current.sales > previous.sales || current.orders > previous.orders) {
                    // New sale/order detected - play sound and show a subtle animation
                    if (animations) {
                        animations.playNotificationSound('newOrder');
                        // Add shake animation to platform card only
                        const platformCard = document.querySelector(`#${platform}Sales`).closest('.platform-card');
                        if (platformCard) {
                            platformCard.classList.add('animate-shake');
                            setTimeout(() => platformCard.classList.remove('animate-shake'), 1000);
                        }
                    }
                }
            });

            // Animate number updates
            if (animations) {
                animations.animateNumber(document.getElementById('totalSales'), totalSales, '₿');
                animations.animateNumber(document.getElementById('totalOrders'), totalOrders);
                
                // Animate platform numbers
                animations.animateNumber(document.getElementById('shopeeSales'), salesData.shopee.sales, '₿');
                animations.animateNumber(document.getElementById('shopeeOrders'), salesData.shopee.orders);
                
                animations.animateNumber(document.getElementById('lazadaSales'), salesData.lazada.sales, '₿');
                animations.animateNumber(document.getElementById('lazadaOrders'), salesData.lazada.orders);
                
                animations.animateNumber(document.getElementById('tiktokSales'), salesData.tiktok.sales, '₿');
                animations.animateNumber(document.getElementById('tiktokOrders'), salesData.tiktok.orders);
            } else {
                // Fallback without animations
                document.getElementById('totalSales').textContent = `₿${totalSales.toLocaleString()}`;
                document.getElementById('totalOrders').textContent = totalOrders.toLocaleString();
                
                document.getElementById('shopeeSales').textContent = `₿${salesData.shopee.sales.toLocaleString()}`;
                document.getElementById('shopeeOrders').textContent = salesData.shopee.orders.toLocaleString();
                
                document.getElementById('lazadaSales').textContent = `₿${salesData.lazada.sales.toLocaleString()}`;
                document.getElementById('lazadaOrders').textContent = salesData.lazada.orders.toLocaleString();
                
                document.getElementById('tiktokSales').textContent = `₿${salesData.tiktok.sales.toLocaleString()}`;
                document.getElementById('tiktokOrders').textContent = salesData.tiktok.orders.toLocaleString();
            }

            // Update timestamp with glow effect
            const lastUpdateEl = document.getElementById('lastUpdate');
            lastUpdateEl.textContent = `อัพเดทล่าสุด: ${new Date().toLocaleTimeString('th-TH')}`;
            lastUpdateEl.classList.add('animate-glow');
            setTimeout(() => lastUpdateEl.classList.remove('animate-glow'), 2000);

            // Store current data for comparison
            previousData = JSON.parse(JSON.stringify(salesData));
        }

    // Recent activity is populated only from API results; no synthesized "new order" entries

        function goToPlatform(platform) {
            window.location.href = `platform.php?p=${platform}`;
        }

        function goToSettings() {
            window.location.href = 'settings.php';
        }

        

    // No client-side simulation: dashboard reflects only live API results

        // Add mouse movement particle effects
        let lastParticleTime = 0;
        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            if (now - lastParticleTime > 100 && animations) {
                animations.createMouseParticle(e.clientX, e.clientY);
                lastParticleTime = now;
            }
        });
    </script>
</body>
</html>
