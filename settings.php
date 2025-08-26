<?php
// Get saved settings
$settings = [
    'shopee' => [
        'partner_id'    => $_COOKIE['shopee_partner_id']    ?? '',
        'partner_key'   => $_COOKIE['shopee_partner_key']   ?? '',
        'shop_id'       => $_COOKIE['shopee_shop_id']       ?? '',
        'access_token'  => $_COOKIE['shopee_access_token']  ?? '',
        'refresh_token' => $_COOKIE['shopee_refresh_token'] ?? '',
        'expires_at'    => $_COOKIE['shopee_expires_at']    ?? '',
        'env'           => $_COOKIE['shopee_env']           ?? 'prod',
        'enabled'       => isset($_COOKIE['shopee_enabled']) ? $_COOKIE['shopee_enabled'] === 'true' : true,
    ],
    'lazada' => [
        'app_key'       => $_COOKIE['lazada_app_key']       ?? '',
        'app_secret'    => $_COOKIE['lazada_app_secret']    ?? '',
        'access_token'  => $_COOKIE['lazada_access_token']  ?? '',
        'refresh_token' => $_COOKIE['lazada_refresh_token'] ?? '',
        'expires_in'    => $_COOKIE['lazada_expires_in']    ?? '',
        'refreshed_at'  => $_COOKIE['lazada_refreshed_at']  ?? '',
        'enabled'       => isset($_COOKIE['lazada_enabled']) ? $_COOKIE['lazada_enabled'] === 'true' : true,
    ],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าแพลตฟอร์ม - Dashboard ยอดขาย</title>
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
                        'lazada': '#0F156D'
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
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center animate-float">
                                <i class="fas fa-cog text-3xl text-white"></i>
                            </div>
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full animate-pulse-slow border-2 border-white"></div>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">ตั้งค่าแพลตฟอร์ม</h1>
                            <p class="text-gray-500 text-sm">จัดการการเชื่อมต่อ API สำหรับแต่ละแพลตฟอร์ม</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 animate-fadeIn">
                        <button onclick="saveAllSettings()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-xl hover:from-green-600 hover:to-green-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-save mr-2"></i>บันทึกทั้งหมด
                        </button>
                        <button onclick="testAllConnections()" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-plug mr-2"></i>ทดสอบการเชื่อมต่อ
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Success/Error Messages -->
            <div id="message" class="mb-6 hidden animate-slideInRight"></div>

            <!-- Platform Settings -->
            <div class="space-y-8">
                <!-- Shopee Settings (Expanded) -->
                <div class="platform-card bg-white/70 backdrop-blur-lg rounded-2xl shadow-2xl overflow-hidden animate-scaleIn border border-white/20">
                    <div class="bg-gradient-to-r from-shopee to-red-600 p-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-white">
                                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mr-6 animate-float">
                                    <i class="fas fa-store text-3xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-3xl font-bold mb-2">Shopee</h2>
                                    <p class="text-white/80 text-sm">กำหนดค่าการเชื่อมต่อ Shopee (Sandbox/Prod)</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <select id="shopeeEnv" class="px-3 py-2 rounded-lg text-sm text-gray-800">
                                    <option value="prod" <?php echo ($settings['shopee']['env']==='prod')?'selected':''; ?>>Prod</option>
                                    <option value="sandbox" <?php echo ($settings['shopee']['env']==='sandbox')?'selected':''; ?>>Sandbox</option>
                                </select>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" id="shopeeEnabled" <?php echo $settings['shopee']['enabled'] ? 'checked' : ''; ?>>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white"></div>
                                    <span class="ml-3 text-white font-medium">เปิด</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Partner ID</label>
                                <input type="text" id="shopeePartnerId" value="<?php echo htmlspecialchars($settings['shopee']['partner_id']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Partner ID">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Partner Key (Secret)</label>
                                <input type="password" id="shopeePartnerKey" value="<?php echo htmlspecialchars($settings['shopee']['partner_key']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Partner Key">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Shop ID</label>
                                <input type="text" id="shopeeShopId" value="<?php echo htmlspecialchars($settings['shopee']['shop_id']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Shop ID">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                                <input type="text" id="shopeeAccessToken" value="<?php echo htmlspecialchars($settings['shopee']['access_token']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Access Token">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Refresh Token</label>
                                <input type="text" id="shopeeRefreshToken" value="<?php echo htmlspecialchars($settings['shopee']['refresh_token']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Refresh Token">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expires At (Epoch)</label>
                                <input type="text" id="shopeeExpiresAt" value="<?php echo htmlspecialchars($settings['shopee']['expires_at']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="เช่น <?php echo time()+14400; ?>">
                                <p class="text-xs text-gray-500 mt-1" id="shopeeExpiryHuman"></p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quick JSON Response Paste (optional)</label>
                                <textarea id="shopeeTokenJson" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee h-24" placeholder='วาง JSON จาก refresh เช่น {"access_token":"...","refresh_token":"...","expire_in":14399}'></textarea>
                                <button type="button" onclick="parseShopeeTokenJson()" class="mt-2 text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded">Parse JSON</button>
                            </div>
                        </div>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <button onclick="testConnection('shopee')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm"><i class="fas fa-plug mr-2"></i>ทดสอบ</button>
                            <button onclick="saveSettings('shopee')" class="bg-shopee text-white px-4 py-2 rounded-lg hover:bg-red-600 text-sm"><i class="fas fa-save mr-2"></i>บันทึก</button>
                        </div>
                    </div>
                </div>

                <!-- Lazada Settings (Enhanced) -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-lazada p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-white">
                                <i class="fas fa-shopping-bag text-3xl mr-4"></i>
                                <div>
                                    <h2 class="text-2xl font-bold">Lazada</h2>
                                    <p class="opacity-80 text-sm">ตั้งค่า App Key/Secret + OAuth</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" id="lazadaEnabled" <?php echo $settings['lazada']['enabled'] ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white"></div>
                                <span class="ml-3 text-white font-medium">เปิด</span>
                            </label>
                        </div>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">App Key</label>
                                <input type="text" id="lazadaAppKey" value="<?php echo htmlspecialchars($settings['lazada']['app_key']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="App Key">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">App Secret</label>
                                <input type="password" id="lazadaAppSecret" value="<?php echo htmlspecialchars($settings['lazada']['app_secret']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="App Secret">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                                <input type="text" id="lazadaAccessToken" value="<?php echo htmlspecialchars($settings['lazada']['access_token']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="Access Token (auto after OAuth)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Refresh Token</label>
                                <input type="text" id="lazadaRefreshToken" value="<?php echo htmlspecialchars($settings['lazada']['refresh_token']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="Refresh Token">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expires In (sec)</label>
                                <input type="text" id="lazadaExpiresIn" value="<?php echo htmlspecialchars($settings['lazada']['expires_in']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="เช่น 3600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Refreshed At (epoch)</label>
                                <input type="text" id="lazadaRefreshedAt" value="<?php echo htmlspecialchars($settings['lazada']['refreshed_at']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="<?php echo time(); ?>">
                                <p class="text-xs text-gray-500 mt-1" id="lazadaExpiryHuman"></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 items-center">
                            <input type="text" id="lazadaRedirectUri" class="px-3 py-2 border rounded-lg text-sm w-full md:w-1/2" placeholder="Redirect URI สำหรับ OAuth (เช่น https://yourdomain.com/settings.php)">
                            <button onclick="getLazadaAuthUrl()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm"><i class="fas fa-link mr-2"></i>Get Auth URL</button>
                            <button onclick="exchangeLazadaCode()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm"><i class="fas fa-exchange-alt mr-2"></i>Exchange Code</button>
                            <input type="text" id="lazadaAuthCode" class="px-3 py-2 border rounded-lg text-sm w-full md:w-1/3" placeholder="วาง code ที่ได้หลัง redirect">
                        </div>
                        <div class="flex flex-wrap gap-3 mt-2">
                            <button onclick="testConnection('lazada')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm"><i class="fas fa-plug mr-2"></i>ทดสอบ</button>
                            <button onclick="saveSettings('lazada')" class="bg-lazada text-white px-4 py-2 rounded-lg hover:bg-blue-800 text-sm"><i class="fas fa-save mr-2"></i>บันทึก</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showMessage(message, type = 'success') {
            const messageEl = document.getElementById('message');
            messageEl.className = `mb-6 p-4 rounded-lg ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            messageEl.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle mr-2"></i>${message}`;
            messageEl.classList.remove('hidden');
            
            setTimeout(() => {
                messageEl.classList.add('hidden');
            }, 5000);
        }

        function saveSettings(platform){
            let settings={};
            if(platform==='shopee'){
                settings={
                    partner_id:document.getElementById('shopeePartnerId').value.trim(),
                    partner_key:document.getElementById('shopeePartnerKey').value.trim(),
                    shop_id:document.getElementById('shopeeShopId').value.trim(),
                    access_token:document.getElementById('shopeeAccessToken').value.trim(),
                    refresh_token:document.getElementById('shopeeRefreshToken').value.trim(),
                    expires_at:document.getElementById('shopeeExpiresAt').value.trim(),
                    env:document.getElementById('shopeeEnv').value,
                    enabled:document.getElementById('shopeeEnabled').checked
                };
            } else if(platform==='lazada'){
                settings={
                    app_key:document.getElementById('lazadaAppKey').value.trim(),
                    app_secret:document.getElementById('lazadaAppSecret').value.trim(),
                    access_token:document.getElementById('lazadaAccessToken').value.trim(),
                    refresh_token:document.getElementById('lazadaRefreshToken').value.trim(),
                    expires_in:document.getElementById('lazadaExpiresIn').value.trim(),
                    refreshed_at:document.getElementById('lazadaRefreshedAt').value.trim(),
                    enabled:document.getElementById('lazadaEnabled').checked
                };
            }
            Object.keys(settings).forEach(k=>{ document.cookie = `${platform}_${k}=${encodeURIComponent(settings[k])}; path=/; max-age=31536000`; });
            showMessage(`บันทึก ${platform} เรียบร้อย`, 'success');
            if(platform==='shopee') updateShopeeExpiryHuman();
            if(platform==='lazada') updateLazadaExpiryHuman();
        }

        function parseShopeeTokenJson(){
            try {
                const txt=document.getElementById('shopeeTokenJson').value.trim(); if(!txt) return;
                const data=JSON.parse(txt);
                if(data.access_token) document.getElementById('shopeeAccessToken').value=data.access_token;
                if(data.refresh_token) document.getElementById('shopeeRefreshToken').value=data.refresh_token;
                if(data.expire_in){ document.getElementById('shopeeExpiresAt').value = Math.floor(Date.now()/1000)+parseInt(data.expire_in); }
                saveSettings('shopee');
            } catch(e){ showMessage('JSON Shopee ไม่ถูกต้อง','error'); }
        }

        function updateShopeeExpiryHuman(){
            const el=document.getElementById('shopeeExpiryHuman'); if(!el) return;
            const v=parseInt(document.getElementById('shopeeExpiresAt').value||'0'); if(!v){ el.textContent=''; return; }
            const left=v - Math.floor(Date.now()/1000); const d=new Date(v*1000);
            el.textContent = 'หมดอายุ: '+d.toLocaleString()+' (เหลือ '+left+'s)'; el.className='text-xs '+(left<600?'text-red-600':'text-gray-500');
        }
        function updateLazadaExpiryHuman(){
            const el=document.getElementById('lazadaExpiryHuman'); if(!el) return;
            const expiresIn=parseInt(document.getElementById('lazadaExpiresIn').value||'0');
            const refreshedAt=parseInt(document.getElementById('lazadaRefreshedAt').value||'0');
            if(!expiresIn||!refreshedAt){ el.textContent=''; return; }
            const exp=refreshedAt + expiresIn; const left=exp - Math.floor(Date.now()/1000); const d=new Date(exp*1000);
            el.textContent='หมดอายุ: '+d.toLocaleString()+' (เหลือ '+left+'s)'; el.className='text-xs '+(left<600?'text-red-600':'text-gray-500');
        }

        function getLazadaAuthUrl(){
            saveSettings('lazada');
            const redirect=document.getElementById('lazadaRedirectUri').value.trim(); if(!redirect){ showMessage('กรุณาใส่ Redirect URI','error'); return; }
            fetch(`api.php?action=auth_url&platform=lazada&redirect_uri=${encodeURIComponent(redirect)}`)
              .then(r=>r.json()).then(j=>{ if(j.success){ window.open(j.auth_url,'_blank'); showMessage('เปิดหน้าขอสิทธิ์แล้ว','success'); } else showMessage('ผิดพลาด: '+(j.error||''),'error'); })
              .catch(()=>showMessage('เรียก auth_url ล้มเหลว','error'));
        }
        function exchangeLazadaCode(){
            saveSettings('lazada');
            const redirect=document.getElementById('lazadaRedirectUri').value.trim(); const code=document.getElementById('lazadaAuthCode').value.trim();
            if(!redirect||!code){ showMessage('กรอก Redirect URI & Code','error'); return; }
            fetch(`api.php?action=exchange_token&platform=lazada&redirect_uri=${encodeURIComponent(redirect)}&code=${encodeURIComponent(code)}`)
              .then(r=>r.json()).then(j=>{ if(j.success){
                  if(j.data){
                      if(j.data.access_token) document.getElementById('lazadaAccessToken').value=j.data.access_token;
                      if(j.data.refresh_token) document.getElementById('lazadaRefreshToken').value=j.data.refresh_token;
                      if(j.data.expires_in) document.getElementById('lazadaExpiresIn').value=j.data.expires_in;
                      document.getElementById('lazadaRefreshedAt').value=Math.floor(Date.now()/1000);
                      saveSettings('lazada'); updateLazadaExpiryHuman();
                  }
                  showMessage('แลก Code สำเร็จ','success');
              } else showMessage('แลก Code ล้มเหลว','error'); })
              .catch(()=>showMessage('Exchange token error','error'));
        }

        // Override testConnection to ensure saving new fields
        function testConnection(platform){
            showMessage('กำลังทดสอบ '+platform+'...','success');
            saveSettings(platform);
            setTimeout(()=>{
                fetch(`api.php?action=test_connection&platform=${platform}${platform==='shopee'?('&env='+document.getElementById('shopeeEnv').value):''}`)
                  .then(r=>r.json()).then(j=>{ if(j.success){ showMessage('เชื่อมต่อ '+platform+' OK','success'); } else showMessage('ล้มเหลว: '+(j.error||j.message),'error'); })
                  .catch(()=>showMessage('ทดสอบล้มเหลว','error'));
            },500);
        }

        function saveAllSettings() {
            const animations = window.animations;
            if (animations) {
                animations.playNotificationSound('success');
                animations.showNotification('กำลังบันทึกการตั้งค่า...', 'info');
            }
            
            saveSettings('shopee');
            setTimeout(() => saveSettings('lazada'), 100);
            setTimeout(() => {
                showMessage('บันทึกการตั้งค่าทั้งหมดเรียบร้อยแล้ว!', 'success');
                if (animations) {
                    animations.playNotificationSound('success');
                    animations.showNotification('บันทึกเรียบร้อยแล้ว!', 'success');
                }
            }, 200);
        }

        function testAllConnections() {
            const animations = window.animations;
            if (animations) {
                animations.playNotificationSound('newOrder');
                animations.showNotification('กำลังทดสอบการเชื่อมต่อทั้งหมด...', 'info');
            }
            
            testConnection('shopee');
            setTimeout(() => testConnection('lazada'), 2000);
        }

        function goBack() {
            window.location.href = 'index.php';
        }

        document.addEventListener('DOMContentLoaded',()=>{ updateShopeeExpiryHuman(); updateLazadaExpiryHuman(); });

        // Initialize animations on page load
        let animations;
        document.addEventListener('DOMContentLoaded', function() {
            animations = new DashboardAnimations();
            window.animations = animations;
            
            // Add entrance animations to cards
            const cards = document.querySelectorAll('.platform-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.2}s`;
                card.classList.add('animate-fadeIn');
            });
            
            // Add particle background
            if (animations) {
                animations.createParticleBackground();
            }
        });

        // Add mouse movement particle effects
        let lastParticleTime = 0;
        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            if (now - lastParticleTime > 200 && animations) {
                animations.createMouseParticle(e.clientX, e.clientY);
                lastParticleTime = now;
            }
        });
    </script>
</body>
</html>
