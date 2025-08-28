<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบ API Debug - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">ทดสอบ API Debug - ยอดขายและออเดอร์</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">ทดสอบ getSummary</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <button onclick="testSummary('shopee')" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg">
                    ทดสอบ Shopee Summary
                </button>
                <button onclick="testSummary('lazada')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    ทดสอบ Lazada Summary
                </button>
                <button onclick="testSummary('tiktok')" class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded-lg">
                    ทดสอบ TikTok Summary
                </button>
            </div>
            <button onclick="testAllSummaries()" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg mb-4">
                <i class="fas fa-rocket mr-2"></i>ทดสอบทุกแพลตฟอร์ม
            </button>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">ทดสอบ getRecentActivity</h2>
            <button onclick="testRecentActivity()" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-lg">
                <i class="fas fa-list mr-2"></i>ทดสอบกิจกรรมล่าสุด
            </button>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">ผลลัพธ์ Debug</h2>
            <div id="results" class="space-y-4">
                <div class="text-gray-500">คลิกปุ่มข้างต้นเพื่อเริ่มทดสอบ</div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Raw API Responses</h2>
            <pre id="rawData" class="bg-gray-100 p-4 rounded text-xs overflow-auto max-h-96 whitespace-pre-wrap"></pre>
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
        
        function addRawData(title, data) {
            const container = document.getElementById('rawData');
            container.textContent += `\n=== ${title} ===\n${JSON.stringify(data, null, 2)}\n`;
        }

        function formatTime(ms) {
            if (ms < 1000) return `${ms}ms`;
            return `${(ms/1000).toFixed(2)}s`;
        }

        async function testSummary(platform) {
            addResult(`<div class="text-blue-600">🔄 กำลังทดสอบ ${platform} getSummary...</div>`);
            
            try {
                const startTime = performance.now();
                const response = await fetch(`api.php?action=getSummary&platform=${platform}`);
                const data = await response.json();
                const totalTime = Math.round(performance.now() - startTime);
                
                addRawData(`${platform} getSummary`, data);
                
                if (data.success) {
                    const apiTime = data.data.loadTime || 'N/A';
                    const orders = data.data.totalOrders || 0;
                    const sales = data.data.totalSales || 0;
                    
                    if (orders > 0 && sales > 0) {
                        addResult(`<div class="${platformColors[platform]} font-semibold">✅ ${platform}: Frontend ${formatTime(totalTime)}, API ${formatTime(apiTime)} | ออเดอร์: ${orders}, ยอดขาย: ₿${sales.toLocaleString()}</div>`);
                    } else if (orders > 0) {
                        addResult(`<div class="${platformColors[platform]}">⚠️ ${platform}: มีออเดอร์ ${orders} แต่ยอดขาย 0 - อาจเป็นปัญหาการคำนวณ</div>`);
                    } else {
                        addResult(`<div class="text-yellow-600">⚠️ ${platform}: ไม่มีข้อมูล - ตรวจสอบการเชื่อมต่อ API</div>`);
                    }
                } else {
                    addResult(`<div class="text-red-600">❌ ${platform}: ${data.error} (${formatTime(totalTime)})</div>`);
                }
            } catch (error) {
                const totalTime = Math.round(performance.now() - startTime);
                addResult(`<div class="text-red-600">❌ ${platform}: Network error - ${error.message} (${formatTime(totalTime)})</div>`);
            }
        }

        async function testAllSummaries() {
            addResult(`<div class="text-green-600 font-semibold">🚀 กำลังทดสอบทุกแพลตฟอร์มพร้อมกัน...</div>`);
            
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
                
                let totalOrders = 0;
                let totalSales = 0;
                let successCount = 0;
                
                results.forEach(result => {
                    if (result.success && result.data.success) {
                        const orders = result.data.data.totalOrders || 0;
                        const sales = result.data.data.totalSales || 0;
                        totalOrders += orders;
                        totalSales += sales;
                        if (orders > 0 || sales > 0) successCount++;
                        
                        const apiTime = result.data.data.loadTime || 'N/A';
                        addResult(`<div class="${platformColors[result.platform]}">📊 ${result.platform}: API ${formatTime(apiTime)} | ออเดอร์: ${orders}, ยอดขาย: ₿${sales.toLocaleString()}</div>`);
                        addRawData(`${result.platform} Concurrent`, result.data);
                    } else {
                        const error = result.error || result.data.error || 'Unknown error';
                        addResult(`<div class="text-red-600">❌ ${result.platform}: ${error}</div>`);
                    }
                });
                
                addResult(`<div class="text-blue-600 font-semibold">📈 สรุป: ออเดอร์รวม ${totalOrders}, ยอดขายรวม ₿${totalSales.toLocaleString()}, แพลตฟอร์มที่มีข้อมูล ${successCount}/${platforms.length}</div>`);
            } catch (error) {
                addResult(`<div class="text-red-600">❌ Concurrent test failed: ${error.message}</div>`);
            }
        }

        async function testRecentActivity() {
            addResult(`<div class="text-purple-600 font-semibold">📋 กำลังทดสอบ getRecentActivity...</div>`);
            
            try {
                const startTime = performance.now();
                const response = await fetch('api.php?action=getRecentActivity');
                const data = await response.json();
                const totalTime = Math.round(performance.now() - startTime);
                
                addRawData('getRecentActivity', data);
                
                if (data.success) {
                    const apiTime = data.loadTime || 'N/A';
                    const count = data.data ? data.data.length : 0;
                    addResult(`<div class="text-purple-600">📋 Recent Activity: Frontend ${formatTime(totalTime)}, API ${formatTime(apiTime)} | กิจกรรม: ${count} รายการ</div>`);
                    
                    if (count === 0) {
                        addResult(`<div class="text-yellow-600">⚠️ ไม่มีกิจกรรมล่าสุด - ตรวจสอบว่าแพลตฟอร์มมีออเดอร์</div>`);
                    }
                } else {
                    addResult(`<div class="text-red-600">❌ Recent Activity: ${data.error} (${formatTime(totalTime)})</div>`);
                }
            } catch (error) {
                addResult(`<div class="text-red-600">❌ Recent Activity: Network error - ${error.message}</div>`);
            }
        }
    </script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</body>
</html>
