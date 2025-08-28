<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบ Refresh Token - Shopee</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">ทดสอบ Refresh Token - Shopee</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">ข้อมูลการตั้งค่า Shopee</h2>
            <div id="shopeeSettings" class="space-y-2 text-sm text-gray-600">
                <div>กำลังโหลด...</div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">ทดสอบ Refresh Token</h2>
            <button id="refreshBtn" onclick="testRefresh()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-sync-alt mr-2"></i>ทดสอบ Refresh Token
            </button>
            <div id="refreshStatus" class="mt-4"></div>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">ผลลัพธ์ Debug</h2>
            <pre id="debugOutput" class="bg-gray-100 p-4 rounded text-xs overflow-auto max-h-96"></pre>
        </div>
    </div>

    <script>
        async function loadShopeeSettings() {
            try {
                const response = await fetch('api.php?action=get_settings&platform=shopee');
                const data = await response.json();
                
                if (data.success) {
                    const settings = data.data;
                    document.getElementById('shopeeSettings').innerHTML = `
                        <div><strong>Partner ID:</strong> ${settings.partner_id || 'ไม่ระบุ'}</div>
                        <div><strong>Shop ID:</strong> ${settings.shop_id || 'ไม่ระบุ'}</div>
                        <div><strong>Environment:</strong> ${settings.env || 'prod'}</div>
                        <div><strong>Has Access Token:</strong> ${settings.access_token ? 'มี' : 'ไม่มี'}</div>
                        <div><strong>Has Refresh Token:</strong> ${settings.refresh_token ? 'มี' : 'ไม่มี'}</div>
                        <div><strong>Expires At:</strong> ${settings.expires_at ? new Date(settings.expires_at * 1000).toLocaleString('th-TH') : 'ไม่ระบุ'}</div>
                    `;
                } else {
                    document.getElementById('shopeeSettings').innerHTML = `<div class="text-red-500">Error: ${data.error}</div>`;
                }
            } catch (error) {
                document.getElementById('shopeeSettings').innerHTML = `<div class="text-red-500">Error: ${error.message}</div>`;
            }
        }
        
        async function testRefresh() {
            const btn = document.getElementById('refreshBtn');
            const status = document.getElementById('refreshStatus');
            const debug = document.getElementById('debugOutput');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังทดสอบ...';
            
            status.innerHTML = '<div class="text-blue-500">กำลังทดสอบการ Refresh Token...</div>';
            debug.textContent = '';
            
            try {
                const response = await fetch('api.php?action=refresh_token&platform=shopee');
                const data = await response.json();
                
                // Show debug info
                debug.textContent = JSON.stringify(data, null, 2);
                
                if (data.success) {
                    status.innerHTML = `
                        <div class="text-green-500 font-semibold">✅ Refresh Token สำเร็จ!</div>
                        <div class="text-sm text-gray-600 mt-2">
                            Access Token ใหม่: ${data.data.access_token ? '✓' : '✗'}<br>
                            Refresh Token ใหม่: ${data.data.refresh_token ? '✓' : '✗'}<br>
                            หมดอายุใน: ${data.data.expire_in ? data.data.expire_in + ' วินาที' : 'ไม่ระบุ'}
                        </div>
                    `;
                    
                    // Reload settings after successful refresh
                    setTimeout(() => loadShopeeSettings(), 1000);
                } else {
                    status.innerHTML = `
                        <div class="text-red-500 font-semibold">❌ Refresh Token ล้มเหลว</div>
                        <div class="text-sm text-red-600 mt-2">${data.error}</div>
                    `;
                }
            } catch (error) {
                debug.textContent = 'Network Error: ' + error.message;
                status.innerHTML = `<div class="text-red-500 font-semibold">❌ เกิดข้อผิดพลาด: ${error.message}</div>`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>ทดสอบ Refresh Token';
            }
        }
        
        // Load settings on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadShopeeSettings();
        });
    </script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</body>
</html>
