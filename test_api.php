<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบ API</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">ทดสอบ API</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-bold mb-4 text-red-600">Shopee API</h3>
                <button onclick="testAPI('shopee')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    ทดสอบ Shopee
                </button>
                <div id="shopee-result" class="mt-4 text-sm"></div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-bold mb-4 text-blue-600">Lazada API</h3>
                <button onclick="testAPI('lazada')" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    ทดสอบ Lazada
                </button>
                <div id="lazada-result" class="mt-4 text-sm"></div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-bold mb-4 text-pink-600">TikTok API</h3>
                <button onclick="testAPI('tiktok')" class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600">
                    ทดสอบ TikTok
                </button>
                <div id="tiktok-result" class="mt-4 text-sm"></div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-xl font-bold mb-4">API Response Log</h3>
            <div id="api-log" class="bg-gray-100 p-4 rounded text-sm font-mono max-h-96 overflow-y-auto">
                กดปุ่มทดสอบเพื่อดู API Response...
            </div>
        </div>
        
        <div class="mt-8">
            <a href="index.php" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                กลับไปหน้าหลัก
            </a>
        </div>
    </div>

    <script>
        function testAPI(platform) {
            const resultDiv = document.getElementById(platform + '-result');
            const logDiv = document.getElementById('api-log');
            
            resultDiv.innerHTML = '<span class="text-blue-600">กำลังทดสอบ...</span>';
            
            const startTime = new Date().getTime();
            
            fetch(`api.php?action=summary&platform=${platform}`)
                .then(response => response.json())
                .then(data => {
                    const endTime = new Date().getTime();
                    const responseTime = endTime - startTime;
                    
                    if (data.error) {
                        resultDiv.innerHTML = `<span class="text-red-600">❌ Error: ${data.error}</span><br><span class="text-gray-500">Response Time: ${responseTime}ms</span>`;
                        
                        logDiv.innerHTML = `[${new Date().toLocaleTimeString()}] ${platform.toUpperCase()} ERROR:\n${JSON.stringify(data, null, 2)}\n\n` + logDiv.innerHTML;
                    } else {
                        resultDiv.innerHTML = `
                            <span class="text-green-600">✅ Success</span><br>
                            <span class="text-gray-700">Sales: ฿${(data.sales || 0).toLocaleString()}</span><br>
                            <span class="text-gray-700">Orders: ${(data.orders || 0).toLocaleString()}</span><br>
                            <span class="text-gray-700">Products: ${(data.top_products?.length || 0)}</span><br>
                            <span class="text-gray-500">Response Time: ${responseTime}ms</span>
                        `;
                        
                        logDiv.innerHTML = `[${new Date().toLocaleTimeString()}] ${platform.toUpperCase()} SUCCESS (${responseTime}ms):\n${JSON.stringify(data, null, 2)}\n\n` + logDiv.innerHTML;
                    }
                })
                .catch(error => {
                    const endTime = new Date().getTime();
                    const responseTime = endTime - startTime;
                    
                    resultDiv.innerHTML = `<span class="text-red-600">❌ Network Error</span><br><span class="text-gray-500">Response Time: ${responseTime}ms</span>`;
                    
                    logDiv.innerHTML = `[${new Date().toLocaleTimeString()}] ${platform.toUpperCase()} NETWORK ERROR (${responseTime}ms):\n${error.message}\n\n` + logDiv.innerHTML;
                });
        }
        
        // Test all APIs on page load
        setTimeout(() => {
            ['shopee', 'lazada', 'tiktok'].forEach((platform, index) => {
                setTimeout(() => testAPI(platform), index * 1000);
            });
        }, 500);
    </script>
</body>
</html>
