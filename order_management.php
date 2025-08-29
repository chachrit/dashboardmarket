<?php
/**
 * Order Management Dashboard
 * หน้าจัดการการดึงข้อมูล Orders และดูสถิติ
 */

require_once 'pagination_manager.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'];
        $platform = $_POST['platform'] ?? 'shopee';
        
        // ตรวจสอบการตั้งค่า
        $settings = dm_settings_get_all($platform);
        $enabled = ($settings['enabled'] ?? 'false') === 'true';
        
        if (!$enabled) {
            throw new Exception("Platform {$platform} ยังไม่ได้เปิดใช้งาน");
        }
        
        // สร้าง API instance
        $config = getAPIConfig();
        $api = null;
        
        switch ($platform) {
            case 'shopee':
                $api = new ShopeeAPI('shopee', $config['shopee']);
                break;
            case 'lazada':
                $api = new LazadaAPI('lazada', $config['lazada']);
                break;
            default:
                throw new Exception("Platform {$platform} not supported");
        }
        
        $manager = new PaginationManager($platform, $api);
        
        if ($action === 'fetch_orders') {
            $date_from = $_POST['date_from'] ?? date('Y-m-d');
            $date_to = $_POST['date_to'] ?? date('Y-m-d');
            $max_orders = (int)($_POST['max_orders'] ?? 0);
            
            $result = $manager->fetchAllOrders($date_from, $date_to, $max_orders);
            echo json_encode($result);
            
        } else if ($action === 'get_stats') {
            $stats = $manager->getLastFetchStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            
        } else if ($action === 'get_orders') {
            $date_from = $_POST['date_from'] ?? date('Y-m-d');
            $date_to = $_POST['date_to'] ?? date('Y-m-d');
            $limit = (int)($_POST['limit'] ?? 100);
            
            $orders = $manager->getOrdersFromDatabase($date_from, $date_to, $limit);
            echo json_encode(['success' => true, 'data' => $orders]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Dashboard Market</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .loading {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .progress-bar {
            background: linear-gradient(90deg, #3B82F6, #8B5CF6);
            animation: progress 2s ease-in-out infinite;
        }
        @keyframes progress {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                📊 Order Management System
            </h1>
            <p class="text-gray-600">จัดการการดึงข้อมูล Orders และดูสถิติ</p>
            <div class="mt-4">
                <a href="index.php" class="text-blue-600 hover:text-blue-800 mr-4">← กลับหน้าหลัก</a>
                <a href="settings.php" class="text-gray-600 hover:text-gray-800">⚙️ ตั้งค่า</a>
            </div>
        </div>

        <!-- Platform Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Shopee Stats -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-red-600">🛒 Shopee Orders</h3>
                    <button onclick="refreshStats('shopee')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-refresh"></i>
                    </button>
                </div>
                <div id="shopee-stats" class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Orders ทั้งหมด:</span>
                        <span id="shopee-total-orders" class="font-bold">กำลังโหลด...</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">ยอดขายรวม:</span>
                        <span id="shopee-total-sales" class="font-bold">₿0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">อัปเดตล่าสุด:</span>
                        <span id="shopee-last-fetch" class="text-sm text-gray-500">-</span>
                    </div>
                </div>
            </div>

            <!-- Lazada Stats -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-blue-600">🛍️ Lazada Orders</h3>
                    <button onclick="refreshStats('lazada')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-refresh"></i>
                    </button>
                </div>
                <div id="lazada-stats" class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Orders ทั้งหมด:</span>
                        <span id="lazada-total-orders" class="font-bold">กำลังโหลด...</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">ยอดขายรวม:</span>
                        <span id="lazada-total-sales" class="font-bold">₿0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">อัปเดตล่าสุด:</span>
                        <span id="lazada-last-fetch" class="text-sm text-gray-500">-</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fetch Orders Form -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h3 class="text-xl font-bold mb-6">🔄 ดึงข้อมูล Orders ใหม่</h3>
            
            <form id="fetch-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Platform</label>
                    <select name="platform" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="shopee">Shopee</option>
                        <option value="lazada">Lazada</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เริ่มต้น</label>
                    <input type="date" name="date_from" value="<?php echo date('Y-m-d'); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันที่สิ้นสุด</label>
                    <input type="date" name="date_to" value="<?php echo date('Y-m-d'); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">จำกัดจำนวน</label>
                    <input type="number" name="max_orders" placeholder="0 = ไม่จำกัด" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit" id="fetch-btn" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>ดึงข้อมูล
                    </button>
                </div>
            </form>

            <!-- Progress Bar -->
            <div id="progress-container" class="hidden mb-4">
                <div class="bg-gray-200 rounded-full h-2">
                    <div id="progress-bar" class="progress-bar h-2 rounded-full" style="width: 0%"></div>
                </div>
                <p id="progress-text" class="text-sm text-gray-600 mt-2">เตรียมดึงข้อมูล...</p>
            </div>

            <!-- Results -->
            <div id="fetch-results" class="hidden"></div>
        </div>

        <!-- Recent Orders Preview -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold">📋 Orders ล่าสุด</h3>
                <div class="flex space-x-2">
                    <select id="preview-platform" class="px-3 py-1 border border-gray-300 rounded text-sm">
                        <option value="shopee">Shopee</option>
                        <option value="lazada">Lazada</option>
                    </select>
                    <button onclick="loadRecentOrders()" class="bg-gray-100 px-3 py-1 rounded text-sm hover:bg-gray-200">
                        <i class="fas fa-refresh mr-1"></i>โหลดใหม่
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Order ID</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">ยอดเงิน</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">สถานะ</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">วันที่</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">รายการสินค้า</th>
                        </tr>
                    </thead>
                    <tbody id="recent-orders-table" class="divide-y divide-gray-200">
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

    <script>
        // Load stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            refreshStats('shopee');
            refreshStats('lazada');
            loadRecentOrders();
        });

        // Refresh platform stats
        async function refreshStats(platform) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_stats&platform=${platform}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById(`${platform}-total-orders`).textContent = 
                        data.data.total_orders.toLocaleString();
                    document.getElementById(`${platform}-total-sales`).textContent = 
                        '₿' + data.data.total_sales.toLocaleString();
                    document.getElementById(`${platform}-last-fetch`).textContent = 
                        data.data.last_fetch_time || 'ไม่เคยดึง';
                } else {
                    document.getElementById(`${platform}-total-orders`).textContent = 'Error';
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
                document.getElementById(`${platform}-total-orders`).textContent = 'Error';
            }
        }

        // Handle fetch form submission
        document.getElementById('fetch-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'fetch_orders');
            
            const fetchBtn = document.getElementById('fetch-btn');
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const resultsDiv = document.getElementById('fetch-results');
            
            // Show progress
            fetchBtn.disabled = true;
            fetchBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังดึง...';
            progressContainer.classList.remove('hidden');
            resultsDiv.classList.add('hidden');
            
            // Simulate progress (since we can't get real-time progress from PHP)
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
                progressText.textContent = `กำลังดึงข้อมูล... ${Math.round(progress)}%`;
            }, 500);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                progressText.textContent = 'เสร็จสิ้น!';
                
                // Show results
                resultsDiv.classList.remove('hidden');
                
                if (result.success) {
                    resultsDiv.innerHTML = `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <h4 class="text-green-800 font-semibold">ดึงข้อมูลสำเร็จ!</h4>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Platform:</span>
                                    <span class="font-semibold ml-2">${result.platform}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">ดึงได้:</span>
                                    <span class="font-semibold ml-2">${result.total_fetched} orders</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">บันทึกแล้ว:</span>
                                    <span class="font-semibold ml-2">${result.total_saved} orders</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">ใช้เวลา:</span>
                                    <span class="font-semibold ml-2">${result.duration_seconds}s</span>
                                </div>
                            </div>
                            ${result.errors.length > 0 ? 
                                `<div class="mt-3 text-sm text-orange-700">
                                    <strong>คำเตือน:</strong> พบข้อผิดพลาด ${result.errors.length} รายการ
                                </div>` : ''
                            }
                        </div>
                    `;
                    
                    // Refresh stats
                    refreshStats(result.platform);
                    if (document.getElementById('preview-platform').value === result.platform) {
                        loadRecentOrders();
                    }
                } else {
                    resultsDiv.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                <span class="text-red-800">${result.error}</span>
                            </div>
                        </div>
                    `;
                }
                
            } catch (error) {
                clearInterval(progressInterval);
                resultsDiv.classList.remove('hidden');
                resultsDiv.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-red-600 mr-2"></i>
                            <span class="text-red-800">เกิดข้อผิดพลาด: ${error.message}</span>
                        </div>
                    </div>
                `;
            } finally {
                fetchBtn.disabled = false;
                fetchBtn.innerHTML = '<i class="fas fa-download mr-2"></i>ดึงข้อมูล';
                setTimeout(() => {
                    progressContainer.classList.add('hidden');
                }, 2000);
            }
        });

        // Load recent orders
        async function loadRecentOrders() {
            const platform = document.getElementById('preview-platform').value;
            const tableBody = document.getElementById('recent-orders-table');
            
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-8 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลดข้อมูล...
                    </td>
                </tr>
            `;
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_orders');
                formData.append('platform', platform);
                formData.append('date_from', new Date().toISOString().split('T')[0]);
                formData.append('date_to', new Date().toISOString().split('T')[0]);
                formData.append('limit', '10');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.data.orders.length > 0) {
                    tableBody.innerHTML = result.data.orders.map(order => `
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 font-medium">${order.order_id}</td>
                            <td class="py-3 px-4">₿${parseFloat(order.amount).toLocaleString()}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    ${order.status}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600">
                                ${new Date(order.created_at).toLocaleDateString('th-TH')}
                            </td>
                            <td class="py-3 px-4 text-sm">
                                ${order.items.length > 0 ? 
                                    order.items.slice(0, 2).map(item => 
                                        `${item.name} (${item.quantity})`
                                    ).join(', ') + (order.items.length > 2 ? '...' : '')
                                    : 'ไม่มีข้อมูล'
                                }
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">
                                ไม่พบข้อมูล Orders สำหรับวันนี้
                            </td>
                        </tr>
                    `;
                }
                
            } catch (error) {
                console.error('Failed to load recent orders:', error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="py-8 text-center text-red-500">
                            เกิดข้อผิดพลาดในการโหลดข้อมูล
                        </td>
                    </tr>
                `;
            }
        }

        // Auto-refresh recent orders when platform changes
        document.getElementById('preview-platform').addEventListener('change', loadRecentOrders);
    </script>
</body>
</html>
