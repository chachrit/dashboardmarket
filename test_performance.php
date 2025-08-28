<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบ Performance - API Load Time</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">ทดสอบ Performance - API Load Time</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">ทดสอบความเร็ว API</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <button onclick="testSingle('shopee')" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg">
                    ทดสอบ Shopee
                </button>
                <button onclick="testSingle('lazada')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    ทดสอบ Lazada
                </button>
                <button onclick="testSingle('tiktok')" class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded-lg">
                    ทดสอบ TikTok
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button onclick="testAllPlatforms()" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg">
                    <i class="fas fa-rocket mr-2"></i>ทดสอบทุกแพลตฟอร์ม (getSummary)
                </button>
                <button onclick="testGetOrdersComparison()" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-lg">
                    <i class="fas fa-chart-bar mr-2"></i>ทดสอบ getOrders (Limit 50 vs 100 vs 300)
                </button>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">ผลลัพธ์</h2>
            <div id="results" class="space-y-4">
                <div class="text-gray-500">คลิกปุ่มข้างต้นเพื่อเริ่มทดสอบ</div>
            </div>
        </div>
    </div>

    <script>
        const platforms = ['shopee', 'lazada', 'tiktok'];
        const platformColors = {
            'shopee': 'text-orange-600',
            'lazada': 'text-blue-600',
            'tiktok': 'text-pink-600'
        };

        function addResult(html) {
            const container = document.getElementById('results');
            if (container.children.length === 1 && container.children[0].textContent.includes('คลิกปุ่ม')) {
                container.innerHTML = '';
            }
            container.innerHTML += html + '<br>';
        }

        function formatTime(ms) {
            if (ms < 1000) return `${ms}ms`;
            return `${(ms/1000).toFixed(2)}s`;
        }

        async function testSingle(platform) {
            addResult(`<div class="text-blue-600">🔄 กำลังทดสอบ ${platform}...</div>`);
            
            try {
                const startTime = performance.now();
                const response = await fetch(`api.php?action=getSummary&platform=${platform}`);
                const data = await response.json();
                const totalTime = Math.round(performance.now() - startTime);
                
                if (data.success) {
                    const apiTime = data.data.loadTime || 'N/A';
                    addResult(`<div class="${platformColors[platform]} font-semibold">✅ ${platform}: Frontend ${formatTime(totalTime)}, API ${formatTime(apiTime)} | Orders: ${data.data.totalOrders}, Sales: ₿${data.data.totalSales.toLocaleString()}</div>`);
                } else {
                    addResult(`<div class="text-red-600">❌ ${platform}: ${data.error} (${formatTime(totalTime)})</div>`);
                }
            } catch (error) {
                const totalTime = Math.round(performance.now() - startTime);
                addResult(`<div class="text-red-600">❌ ${platform}: Network error - ${error.message} (${formatTime(totalTime)})</div>`);
            }
        }

        async function testAllPlatforms() {
            addResult(`<div class="text-blue-600 font-semibold">🚀 กำลังทดสอบทุกแพลตฟอร์มพร้อมกัน...</div>`);
            
            const startTime = performance.now();
            const promises = platforms.map(platform => 
                fetch(`api.php?action=getSummary&platform=${platform}`)
                    .then(response => response.json())
                    .then(data => ({ platform, data, success: true }))
                    .catch(error => ({ platform, error: error.message, success: false }))
            );
            
            try {
                const results = await Promise.all(promises);
                const totalTime = Math.round(performance.now() - startTime);
                
                addResult(`<div class="text-green-600 font-semibold">⚡ ทดสอบเสร็จใน ${formatTime(totalTime)} (Concurrent)</div>`);
                
                results.forEach(result => {
                    if (result.success && result.data.success) {
                        const apiTime = result.data.data.loadTime || 'N/A';
                        addResult(`<div class="${platformColors[result.platform]}">📊 ${result.platform}: API ${formatTime(apiTime)} | Orders: ${result.data.data.totalOrders}, Sales: ₿${result.data.data.totalSales.toLocaleString()}</div>`);
                    } else {
                        const error = result.error || result.data.error || 'Unknown error';
                        addResult(`<div class="text-red-600">❌ ${result.platform}: ${error}</div>`);
                    }
                });
            } catch (error) {
                addResult(`<div class="text-red-600">❌ Concurrent test failed: ${error.message}</div>`);
            }
        }

        async function testGetOrdersComparison() {
            addResult(`<div class="text-purple-600 font-semibold">📊 กำลังทดสอบ getOrders กับ limit ต่างๆ...</div>`);
            
            const limits = [50, 100, 300]; // ปรับ limits ให้เหมาะสำหรับข้อมูลจริง
            const platform = 'shopee'; // Test with Shopee as example
            
            for (const limit of limits) {
                try {
                    const startTime = performance.now();
                    const response = await fetch(`api.php?action=getOrders&platform=${platform}&limit=${limit}`);
                    const data = await response.json();
                    const totalTime = Math.round(performance.now() - startTime);
                    
                    if (data.success) {
                        const apiTime = data.data.loadTime || 'N/A';
                        const orderCount = data.data.orders ? data.data.orders.length : 0;
                        const totalOrders = data.data.total_orders || 0;
                        const totalSales = data.data.total_sales || 0;
                        addResult(`<div class="text-purple-600">🔢 Limit ${limit}: Frontend ${formatTime(totalTime)}, API ${formatTime(apiTime)} | Retrieved: ${orderCount}/${totalOrders} orders, ₿${totalSales.toLocaleString()}</div>`);
                    } else {
                        addResult(`<div class="text-red-600">❌ Limit ${limit}: ${data.error} (${formatTime(totalTime)})</div>`);
                    }
                } catch (error) {
                    addResult(`<div class="text-red-600">❌ Limit ${limit}: Network error - ${error.message}</div>`);
                }
                
                // Add small delay between requests
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            
            addResult(`<div class="text-blue-600 font-semibold">💡 หมายเหตุ: getSummary ใช้ Summary Mode (เร็วกว่า) ส่วน getOrders ดึงรายละเอียดครบ (ช้ากว่า)</div>`);
        }
    </script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</body>
</html>
