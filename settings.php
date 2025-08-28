<?php
require_once __DIR__ . '/db.php';
// Load settings from DB (fallback empty if not set yet)
$settings = [
    'shopee' => dm_settings_get_all('shopee') + [
        'partner_id' => '', 'partner_key' => '', 'shop_id' => '',
        'access_token' => '', 'refresh_token' => '', 'expires_at' => '',
        'env' => 'prod', 'enabled' => 'true'
    ],
    'lazada' => dm_settings_get_all('lazada') + [
        'app_key' => '', 'app_secret' => '', 'access_token' => '', 'refresh_token' => '',
        'enabled' => 'true'
    ],
    'tiktok' => dm_settings_get_all('tiktok') + [
        'client_key' => '', 'client_secret' => '', 'access_token' => '', 'refresh_token' => '',
        'enabled' => 'true'
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
                                    <option value="prod" <?php echo (($settings['shopee']['env']??'prod')==='prod')?'selected':''; ?>>Prod</option>
                                    <option value="sandbox" <?php echo (($settings['shopee']['env']??'prod')==='sandbox')?'selected':''; ?>>Sandbox</option>
                                </select>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" id="shopeeEnabled" <?php echo (($settings['shopee']['enabled']??'true')==='true') ? 'checked' : ''; ?>>
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
                                <input type="text" id="shopeePartnerId" value="<?php echo htmlspecialchars($settings['shopee']['partner_id']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Partner ID">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Partner Key (Secret)</label>
                                <input type="password" id="shopeePartnerKey" value="<?php echo htmlspecialchars($settings['shopee']['partner_key']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Partner Key">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Shop ID</label>
                                <input type="text" id="shopeeShopId" value="<?php echo htmlspecialchars($settings['shopee']['shop_id']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Shop ID">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                                <input type="text" id="shopeeAccessToken" value="<?php echo htmlspecialchars($settings['shopee']['access_token']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Access Token">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Refresh Token</label>
                                <input type="text" id="shopeeRefreshToken" value="<?php echo htmlspecialchars($settings['shopee']['refresh_token']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Refresh Token">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expires At (timestamp)</label>
                                <input type="text" id="shopeeExpiresAt" value="<?php echo htmlspecialchars($settings['shopee']['expires_at']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-shopee focus:border-shopee" placeholder="Expires At (Unix timestamp)">
                                <div class="text-xs text-gray-500 mt-1">ตัวอย่าง: 1720000000 (เวลาหมดอายุ access_token)</div>
                            </div>
                        </div>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <button onclick="testConnection('shopee')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm"><i class="fas fa-plug mr-2"></i>ทดสอบ</button>
                            <button onclick="generateShopeeAuthURL()" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 text-sm"><i class="fas fa-key mr-2"></i>OAuth Authorization</button>
                            <button onclick="refreshShopeeToken()" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 text-sm"><i class="fas fa-sync-alt mr-2"></i>รีเฟรช Token</button>
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
                                <input type="checkbox" class="sr-only peer" id="lazadaEnabled" <?php echo (($settings['lazada']['enabled']??'true')==='true') ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white"></div>
                                <span class="ml-3 text-white font-medium">เปิด</span>
                            </label>
                        </div>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">App Key</label>
                                <input type="text" id="lazadaAppKey" value="<?php echo htmlspecialchars($settings['lazada']['app_key']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="App Key">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">App Secret</label>
                                <input type="password" id="lazadaAppSecret" value="<?php echo htmlspecialchars($settings['lazada']['app_secret']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="App Secret">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                                <input type="text" id="lazadaAccessToken" value="<?php echo htmlspecialchars($settings['lazada']['access_token']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="Access Token (auto after OAuth)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Refresh Token</label>
                                <input type="text" id="lazadaRefreshToken" value="<?php echo htmlspecialchars($settings['lazada']['refresh_token']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="Refresh Token">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expires At (timestamp)</label>
                                <input type="text" id="lazadaExpiresAt" value="<?php echo htmlspecialchars($settings['lazada']['expires_at']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-lazada focus:border-lazada" placeholder="Expires At (Unix timestamp)">
                                <div class="text-xs text-gray-500 mt-1">ตัวอย่าง: 1720000000 (เวลาหมดอายุ access_token)</div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 items-center">
                            <!-- Redirect URI and Auth Code inputs removed per UI change -->
                        </div>
                        <div class="flex flex-wrap gap-3 mt-2">
                            <button onclick="testConnection('lazada')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm"><i class="fas fa-plug mr-2"></i>ทดสอบ</button>
                            <button onclick="saveSettings('lazada')" class="bg-lazada text-white px-4 py-2 rounded-lg hover:bg-blue-800 text-sm"><i class="fas fa-save mr-2"></i>บันทึก</button>
                        </div>
                    </div>
                </div>

                <!-- TikTok Settings (new) -->
                <div class="platform-card bg-white/70 backdrop-blur-lg rounded-2xl shadow-2xl overflow-hidden animate-scaleIn border border-white/20 mt-8">
                    <div class="bg-gradient-to-r from-[#25F4EE] to-[#FE2C55] p-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-white">
                                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mr-6 animate-float">
                                    <i class="fab fa-tiktok text-3xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-3xl font-bold mb-2">TikTok</h2>
                                    <p class="text-white/80 text-sm">ตั้งค่า TikTok for Business / OAuth</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" id="tiktokEnabled" <?php echo (($settings['tiktok']['enabled']??'true')==='true') ? 'checked' : ''; ?>>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white"></div>
                                    <span class="ml-3 text-white font-medium">เปิด</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client Key</label>
                                <input type="text" id="tiktokClientKey" value="<?php echo htmlspecialchars($settings['tiktok']['client_key']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-pink-400 focus:border-pink-400" placeholder="Client Key">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                                <input type="password" id="tiktokClientSecret" value="<?php echo htmlspecialchars($settings['tiktok']['client_secret']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-pink-400 focus:border-pink-400" placeholder="Client Secret">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                                <input type="text" id="tiktokAccessToken" value="<?php echo htmlspecialchars($settings['tiktok']['access_token']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-pink-400 focus:border-pink-400" placeholder="Access Token (auto after OAuth)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Refresh Token</label>
                                <input type="text" id="tiktokRefreshToken" value="<?php echo htmlspecialchars($settings['tiktok']['refresh_token']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-pink-400 focus:border-pink-400" placeholder="Refresh Token">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expires At (timestamp)</label>
                                <input type="text" id="tiktokExpiresAt" value="<?php echo htmlspecialchars($settings['tiktok']['expires_at']??''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-pink-400 focus:border-pink-400" placeholder="Expires At (Unix timestamp)">
                                <div class="text-xs text-gray-500 mt-1">ตัวอย่าง: 1720000000 (เวลาหมดอายุ access_token)</div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 items-center mt-4">
                            <!-- Redirect URI and Auth Code inputs removed -->
                        </div>
                        <div class="flex flex-wrap gap-3 mt-2">
                            <button onclick="testConnection('tiktok')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm"><i class="fas fa-plug mr-2"></i>ทดสอบ</button>
                            <button onclick="saveSettings('tiktok')" class="bg-pink-500 text-white px-4 py-2 rounded-lg hover:bg-pink-600 text-sm"><i class="fas fa-save mr-2"></i>บันทึก</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showMessage(message, type = 'success') {
            const messageEl = document.getElementById('message');
            const typeClasses = {
                success: 'bg-green-100 text-green-800',
                error: 'bg-red-100 text-red-800',
                info: 'bg-blue-100 text-blue-800'
            };
            const iconClasses = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };
            messageEl.className = `mb-6 p-4 rounded-lg ${typeClasses[type] || typeClasses['info']} animate-slideInRight`;
            messageEl.innerHTML = `<i class="fas ${iconClasses[type] || iconClasses['info']} mr-2"></i>${message}`;
            messageEl.classList.remove('hidden');
            
            setTimeout(() => {
                messageEl.classList.add('hidden');
            }, 5000);
        }

        function getSettingsFromForm(platform) {
            if (platform === 'shopee') {
                return {
                    partner_id: document.getElementById('shopeePartnerId').value.trim(),
                    partner_key: document.getElementById('shopeePartnerKey').value.trim(),
                    shop_id: document.getElementById('shopeeShopId').value.trim(),
                    access_token: document.getElementById('shopeeAccessToken').value.trim(),
                    refresh_token: document.getElementById('shopeeRefreshToken').value.trim(),
                    expires_at: document.getElementById('shopeeExpiresAt').value.trim(),
                    env: document.getElementById('shopeeEnv').value,
                    enabled: document.getElementById('shopeeEnabled').checked
                };
            } else if (platform === 'lazada') {
                return {
                    app_key: document.getElementById('lazadaAppKey').value.trim(),
                    app_secret: document.getElementById('lazadaAppSecret').value.trim(),
                    access_token: document.getElementById('lazadaAccessToken').value.trim(),
                    refresh_token: document.getElementById('lazadaRefreshToken').value.trim(),
                    expires_at: document.getElementById('lazadaExpiresAt').value.trim(),
                    enabled: document.getElementById('lazadaEnabled').checked
                };
            } else if (platform === 'tiktok') {
                return {
                    client_key: document.getElementById('tiktokClientKey').value.trim(),
                    client_secret: document.getElementById('tiktokClientSecret').value.trim(),
                    access_token: document.getElementById('tiktokAccessToken').value.trim(),
                    refresh_token: document.getElementById('tiktokRefreshToken').value.trim(),
                    expires_at: document.getElementById('tiktokExpiresAt').value.trim(),
                    enabled: document.getElementById('tiktokEnabled').checked
                };
            }
            return {};
        }

        async function saveSettings(platform) {
            const settings = getSettingsFromForm(platform);
            try {
                const response = await fetch(`api.php?action=save_settings&platform=${platform}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(settings)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const j = await response.json();
                if (j.success) {
                    showMessage(`บันทึก ${platform} เรียบร้อย`, 'success');
                } else {
                    showMessage(`บันทึก ${platform} ล้มเหลว: ${j.error || j.message}`, 'error');
                }
                return j;
            } catch (err) {
                console.error('Save settings error:', err);
                showMessage(`บันทึก ${platform} ล้มเหลว: ${err.message}`, 'error');
                throw err;
            }
        }

        function validateCredentials(platform) {
            if (platform === 'shopee') {
                const partnerId = document.getElementById('shopeePartnerId').value.trim();
                const partnerKey = document.getElementById('shopeePartnerKey').value.trim();
                const shopId = document.getElementById('shopeeShopId').value.trim();
                const accessToken = document.getElementById('shopeeAccessToken').value.trim();
                
                if (!partnerId || !partnerKey || !shopId || !accessToken) {
                    return 'Shopee: กรุณากรอกข้อมูลให้ครบทุกช่อง (Partner ID, Partner Key, Shop ID, Access Token)';
                }
                
                // Validate partner ID format
                const partnerIdClean = partnerId.replace(/\D/g, '');
                if (!partnerIdClean || partnerIdClean.length < 6 || partnerIdClean.length > 15) {
                    return 'Shopee Partner ID ควรเป็นตัวเลข 6-15 หลัก';
                }
            } else if (platform === 'lazada') {
                const appKey = document.getElementById('lazadaAppKey').value.trim();
                const appSecret = document.getElementById('lazadaAppSecret').value.trim();
                // *** ไม่บังคับ Access Token สำหรับการทดสอบ connection
                
                if (!appKey || !appSecret) {
                    return 'Lazada: กรุณากรอก App Key และ App Secret';
                }
                
                // ตรวจสอบ App Key format (Lazada App Key มักจะเป็นตัวเลข 6-8 หลัก)
                if (appKey.length < 6) {
                    return 'Lazada App Key ควรมีอย่างน้อย 6 ตัวอักษร';
                }
                
                // ตรวจสอบ App Secret format (ควรมีความยาวพอสมควร)
                if (appSecret.length < 20) {
                    return 'Lazada App Secret ควรมีอย่างน้อย 20 ตัวอักษร';
                }
            } else if (platform === 'tiktok') {
                const clientKey = document.getElementById('tiktokClientKey').value.trim();
                const clientSecret = document.getElementById('tiktokClientSecret').value.trim();
                
                if (!clientKey || !clientSecret) {
                    return 'TikTok: กรุณากรอก Client Key และ Client Secret';
                }
            }
            return null; // No validation errors
        }

        async function testConnection(platform) {
            // Validate credentials first
            const validationError = validateCredentials(platform);
            if (validationError) {
                showMessage(validationError, 'error');
                return;
            }
            
            showMessage('กำลังทดสอบ ' + platform + '...', 'info');
            try {
                await saveSettings(platform);
                const response = await fetch(`api.php?action=test_connection&platform=${platform}${platform === 'shopee' ? ('&env=' + document.getElementById('shopeeEnv').value) : ''}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const j = await response.json();
                if (j.success) {
                    showMessage(`เชื่อมต่อ ${platform} สำเร็จ: ${j.message || ''}`, 'success');
                } else {
                    showMessage(`ทดสอบ ${platform} ล้มเหลว: ${j.error || j.message}`, 'error');
                }
            } catch (error) {
                console.error('Test connection error:', error);
                showMessage(`ทดสอบ ${platform} ล้มเหลว: ${error.message}`, 'error');
            }
        }

        async function saveAllSettings() {
            showMessage('กำลังบันทึกการตั้งค่าทั้งหมด...', 'info');
            try {
                await saveSettings('shopee');
                await saveSettings('lazada');
                await saveSettings('tiktok');
                showMessage('บันทึกการตั้งค่าทั้งหมดเรียบร้อยแล้ว!', 'success');
                if (window.animations) {
                    window.animations.playNotificationSound('success');
                }
            } catch (error) {
                showMessage('เกิดข้อผิดพลาดในการบันทึกบางรายการ', 'error');
            }
        }

        async function testAllConnections() {
            showMessage('กำลังทดสอบการเชื่อมต่อทั้งหมด...', 'info');
            if (window.animations) {
                window.animations.playNotificationSound('newOrder');
            }
            await testConnection('shopee');
            await testConnection('lazada');
            await testConnection('tiktok');
        }

        async function generateShopeeAuthURL() {
            try {
                const partnerId = document.getElementById('shopeePartnerId').value;
                const partnerKey = document.getElementById('shopeePartnerKey').value;
                const env = document.getElementById('shopeeEnv').value;
                
                if (!partnerId || !partnerKey) {
                    showMessage('กรุณากรอก Partner ID และ Partner Key ก่อน', 'error');
                    return;
                }
                
                // Save settings first
                await saveSettings('shopee');
                
                // Generate auth URL
                const timestamp = Math.floor(Date.now() / 1000);
                const path = '/api/v2/shop/auth_partner';
                const currentHost = window.location.protocol + '//' + window.location.host;
                const redirectUrl = currentHost + window.location.pathname.replace('settings.php', 'shopee_callback.php');
                
                const baseUrl = env === 'sandbox' 
                    ? 'https://openplatform.sandbox.test-stable.shopee.sg'
                    : 'https://partner.shopeemobile.com';
                
                // Create signature (simplified - just timestamp)
                const baseString = partnerId + path + timestamp;
                
                const authUrl = `${baseUrl}${path}?partner_id=${partnerId}&redirect=${encodeURIComponent(redirectUrl)}&timestamp=${timestamp}`;
                
                // Show auth URL dialog
                showMessage(
                    `กรุณาคลิกลิงก์ด้านล่างเพื่อ authorize:<br>
                    <a href="${authUrl}" target="_blank" class="text-blue-600 underline break-all">${authUrl}</a><br><br>
                    <small>หลังจาก authorize สำเร็จ คุณจะถูกนำกลับมาที่หน้าเว็บนี้</small>`,
                    'info',
                    15000
                );
                
                // Also open in new tab
                window.open(authUrl, '_blank');
                
            } catch (error) {
                console.error('Generate auth URL error:', error);
                showMessage(`สร้าง Authorization URL ล้มเหลว: ${error.message}`, 'error');
            }
        }

        async function refreshShopeeToken() {
            showMessage('กำลังรีเฟรช Token ของ Shopee...', 'info');
            try {
                await saveSettings('shopee');
                const url = `api.php?action=refresh_token&platform=shopee&env=${document.getElementById('shopeeEnv').value}&partner_id=${encodeURIComponent(document.getElementById('shopeePartnerId').value.trim())}`;
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const j = await response.json();
                if (j.success && j.data) {
                    showMessage('รีเฟรช Token สำเร็จ! กำลังโหลดหน้าใหม่...', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('รีเฟรช Token ล้มเหลว: ' + (j.error || j.message || 'ไม่ทราบสาเหตุ'), 'error');
                }
            } catch (error) {
                console.error('Refresh token error:', error);
                showMessage(`รีเฟรช Token ล้มเหลว: ${error.message}`, 'error');
            }
        }

        function goBack() {
            window.location.href = 'index.php';
        }

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
