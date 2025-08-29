<?php
/**
 * Pagination Manager - จัดการการดึงข้อมูล Order แบบ Pagination
 * รองรับ Shopee และ Lazada API pagination
 */

require_once 'config.php';
require_once 'db.php';
require_once 'api.php';

class PaginationManager {
    private $platform;
    private $api;
    private $pdo;
    
    // Rate limiting settings
    private $delayBetweenRequests = 1000000; // 1 second in microseconds
    private $maxRetries = 3;
    
    public function __construct($platform, $api) {
        $this->platform = $platform;
        $this->api = $api;
        $this->pdo = dm_db();
        $this->initOrdersTable();
    }
    
    /**
     * สร้างตาราง orders หากยังไม่มี (MySQL only)
     */
    private function initOrdersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS orders (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(20) NOT NULL,
            order_id VARCHAR(100) NOT NULL,
            amount DECIMAL(15,2) DEFAULT 0,
            status VARCHAR(50),
            created_at VARCHAR(50),
            items TEXT,
            raw_data TEXT,
            fetched_at BIGINT DEFAULT UNIX_TIMESTAMP(),
            UNIQUE KEY uk_orders_platform_orderid (platform, order_id)
        )";
        
        $this->pdo->exec($sql);
        
        // Create indexes for performance
        try {
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_platform_created ON orders (platform, created_at DESC)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_fetched_at ON orders (fetched_at DESC)");
        } catch (Exception $e) {
            // Indexes might already exist, ignore
        }
    }
    
    /**
     * ดึงข้อมูล Orders ทั้งหมดจาก API โดยใช้ Pagination
     * @param string $date_from วันที่เริ่มต้น (Y-m-d)
     * @param string $date_to วันที่สิ้นสุด (Y-m-d)  
     * @param int $maxOrders จำนวน orders สูงสุดที่ต้องการ (0 = ไม่จำกัด)
     * @return array ผลลัพธ์การดึงข้อมูล
     */
    public function fetchAllOrders($date_from = null, $date_to = null, $maxOrders = 0) {
        $startTime = microtime(true);
        
        if (!$date_from) $date_from = date('Y-m-d');
        if (!$date_to) $date_to = date('Y-m-d');
        
        echo "🚀 เริ่มดึงข้อมูล {$this->platform} Orders: {$date_from} ถึง {$date_to}\n";
        
        $totalFetched = 0;
        $totalSaved = 0;
        $page = 1;
        $hasMore = true;
        $errors = [];
        
        // สำหรับ Shopee ใช้ cursor-based pagination
        $cursor = '';
        
        while ($hasMore && ($maxOrders == 0 || $totalFetched < $maxOrders)) {
            try {
                echo "📄 กำลังดึงหน้าที่ {$page}";
                
                $remainingOrders = $maxOrders > 0 ? ($maxOrders - $totalFetched) : 1000;
                $limit = min(100, $remainingOrders); // ดึงทีละ 100 orders หรือน้อยกว่าถ้าเหลือน้อย
                
                if ($this->platform === 'shopee') {
                    $result = $this->fetchShopeePage($date_from, $date_to, $limit, $cursor);
                    $cursor = $result['next_cursor'] ?? '';
                    $hasMore = $result['has_more'] ?? false;
                } else if ($this->platform === 'lazada') {
                    $result = $this->fetchLazadaPage($date_from, $date_to, $limit, ($page - 1) * $limit);
                    $hasMore = $result['has_more'] ?? false;
                } else {
                    throw new Exception("Platform {$this->platform} not supported");
                }
                
                $orders = $result['orders'] ?? [];
                $saved = $this->saveOrdersToDatabase($orders);
                
                $totalFetched += count($orders);
                $totalSaved += $saved;
                
                echo " - ได้ " . count($orders) . " orders, บันทึกแล้ว {$saved} รายการ\n";
                
                if (empty($orders) || count($orders) < $limit) {
                    $hasMore = false;
                }
                
                $page++;
                
                // Rate limiting - หน่วงเวลาระหว่างการเรียก API
                if ($hasMore) {
                    echo "⏳ รอ 1 วินาที เพื่อป้องกัน Rate Limit...\n";
                    usleep($this->delayBetweenRequests);
                }
                
            } catch (Exception $e) {
                $error = "หน้า {$page}: " . $e->getMessage();
                $errors[] = $error;
                echo "❌ " . $error . "\n";
                
                // หยุดถ้าเกิดข้อผิดพลาดต่อเนื่อง
                if (count($errors) >= $this->maxRetries) {
                    echo "🛑 หยุดการดึงข้อมูลเนื่องจากข้อผิดพลาดมากเกินไป\n";
                    break;
                }
                
                // รอนานขึ้นหากเกิดข้อผิดพลาด
                sleep(2);
            }
        }
        
        $duration = round((microtime(true) - $startTime), 2);
        
        $summary = [
            'success' => true,
            'platform' => $this->platform,
            'date_range' => "{$date_from} to {$date_to}",
            'total_fetched' => $totalFetched,
            'total_saved' => $totalSaved,
            'pages_processed' => $page - 1,
            'duration_seconds' => $duration,
            'errors' => $errors
        ];
        
        echo "✅ เสร็จสิ้น: ดึงได้ {$totalFetched} orders, บันทึกแล้ว {$totalSaved} รายการ ใช้เวลา {$duration} วินาที\n";
        
        return $summary;
    }
    
    /**
     * ดึงข้อมูล Shopee หน้าเดียว
     */
    private function fetchShopeePage($date_from, $date_to, $limit, $cursor) {
        $date_from_ts = strtotime($date_from . ' 00:00:00');
        $date_to_ts = strtotime($date_to . ' 23:59:59');
        
        $query = [
            'time_from' => $date_from_ts,
            'time_to' => $date_to_ts,
            'page_size' => min($limit, 50), // Shopee max 50 per request
            'time_range_field' => 'create_time'
        ];
        
        if ($cursor) {
            $query['cursor'] = $cursor;
        }
        
        $resp = $this->api->callGet('/api/v2/order/get_order_list', $query);
        $orderSnList = $resp['response']['order_list'] ?? [];
        
        if (empty($orderSnList)) {
            return ['orders' => [], 'has_more' => false, 'next_cursor' => ''];
        }
        
        // ดึงรายละเอียด orders
        $sns = array_map(fn($o) => $o['order_sn'], $orderSnList);
        $detailResp = $this->api->callGet('/api/v2/order/get_order_detail', [
            'order_sn_list' => implode(',', $sns),
            'response_optional_fields' => 'item_list,total_amount,order_status,create_time'
        ]);
        
        $details = $detailResp['response']['order_list'] ?? [];
        $orders = [];
        
        foreach ($details as $order) {
            $items = [];
            if (isset($order['item_list']) && is_array($order['item_list'])) {
                foreach ($order['item_list'] as $item) {
                    $items[] = [
                        'name' => $item['item_name'] ?? 'N/A',
                        'quantity' => $item['model_quantity_purchased'] ?? 1,
                        'price' => $item['model_original_price'] ?? 0
                    ];
                }
            }
            
            $orders[] = [
                'platform' => 'shopee',
                'order_id' => $order['order_sn'],
                'amount' => (float)($order['total_amount'] ?? 0),
                'status' => $order['order_status'] ?? 'unknown',
                'created_at' => date('c', $order['create_time']),
                'items' => $items,
                'raw_data' => $order
            ];
        }
        
        return [
            'orders' => $orders,
            'has_more' => $resp['response']['more'] ?? false,
            'next_cursor' => $resp['response']['next_cursor'] ?? ''
        ];
    }
    
    /**
     * ดึงข้อมูล Lazada หน้าเดียว
     */
    private function fetchLazadaPage($date_from, $date_to, $limit, $offset) {
        $date_from_iso = date('c', strtotime($date_from . ' 00:00:00'));
        $date_to_iso = date('c', strtotime($date_to . ' 23:59:59'));
        
        $params = [
            'created_after' => $date_from_iso,
            'created_before' => $date_to_iso,
            'limit' => $limit,
            'offset' => $offset,
            'sort_by' => 'created_at',
            'sort_direction' => 'DESC'
        ];
        
        $resp = $this->api->callGet('/orders/get', $params);
        $orderList = $resp['data']['orders'] ?? [];
        $orders = [];
        
        foreach ($orderList as $order) {
            $items = [];
            
            // ดึงรายละเอียดสินค้าใน order
            try {
                $detailResp = $this->api->callGet('/order/items/get', [
                    'order_id' => $order['order_id'] ?? $order['order_number']
                ]);
                
                if (isset($detailResp['data']) && is_array($detailResp['data'])) {
                    foreach ($detailResp['data'] as $item) {
                        $items[] = [
                            'name' => $item['name'] ?? $item['item_name'] ?? 'N/A',
                            'quantity' => $item['quantity'] ?? $item['order_quantity'] ?? 1,
                            'price' => $item['item_price'] ?? 0
                        ];
                    }
                }
            } catch (Exception $e) {
                error_log("Lazada get order items failed: " . $e->getMessage());
            }
            
            $orders[] = [
                'platform' => 'lazada',
                'order_id' => $order['order_number'] ?? $order['order_id'],
                'amount' => (float)($order['price'] ?? $order['total_amount'] ?? 0),
                'status' => $order['statuses'] ?? $order['status'] ?? 'unknown',
                'created_at' => $order['created_at'] ?? date('c'),
                'items' => $items,
                'raw_data' => $order
            ];
        }
        
        $hasMore = count($orderList) >= $limit; // ถ้าได้ครบตาม limit แสดงว่าอาจมีหน้าถัดไป
        
        return [
            'orders' => $orders,
            'has_more' => $hasMore
        ];
    }
    
    /**
     * บันทึกข้อมูล orders ลงฐานข้อมูล
     */
    private function saveOrdersToDatabase($orders) {
        if (empty($orders)) return 0;
        
        $saved = 0;
        // Use MySQL INSERT ... ON DUPLICATE KEY UPDATE
        $stmt = $this->pdo->prepare(
            "INSERT INTO orders (platform, order_id, amount, status, created_at, items, raw_data, fetched_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
                amount = VALUES(amount),
                status = VALUES(status),
                items = VALUES(items),
                raw_data = VALUES(raw_data),
                fetched_at = VALUES(fetched_at)"
        );
        
        foreach ($orders as $order) {
            try {
                $params = [
                    $order['platform'],
                    $order['order_id'],
                    $order['amount'],
                    $order['status'],
                    $order['created_at'],
                    json_encode($order['items'], JSON_UNESCAPED_UNICODE),
                    json_encode($order['raw_data'], JSON_UNESCAPED_UNICODE),
                    time()
                ];
                
                if ($stmt->execute($params)) {
                    $saved++;
                }
            } catch (Exception $e) {
                error_log("Failed to save order {$order['order_id']}: " . $e->getMessage());
            }
        }
        
        return $saved;
    }
    
    /**
     * ดึงข้อมูล Orders จากฐานข้อมูลเพื่อใช้ใน Dashboard
     */
    public function getOrdersFromDatabase($date_from = null, $date_to = null, $limit = 100) {
        if (!$date_from) $date_from = date('Y-m-d');
        if (!$date_to) $date_to = date('Y-m-d');
        
        $sql = "SELECT * FROM orders 
                WHERE platform = ? 
                AND created_at >= ? 
                AND created_at <= ?
                ORDER BY created_at DESC 
                LIMIT ?";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $this->platform,
            $date_from . ' 00:00:00',
            $date_to . ' 23:59:59',
            $limit
        ]);
        
        $orders = [];
        $totalSales = 0;
        
        while ($row = $stmt->fetch()) {
            $orders[] = [
                'platform' => $row['platform'],
                'order_id' => $row['order_id'],
                'amount' => (float)$row['amount'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'items' => json_decode($row['items'], true) ?: [],
            ];
            $totalSales += (float)$row['amount'];
        }
        
        return [
            'total_sales' => $totalSales,
            'total_orders' => count($orders),
            'orders' => $orders
        ];
    }
    
    /**
     * ดึงสถิติการดึงข้อมูลล่าสุด
     */
    public function getLastFetchStats() {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(amount) as total_sales,
                    MAX(fetched_at) as last_fetch_time,
                    MIN(created_at) as oldest_order,
                    MAX(created_at) as newest_order
                FROM orders 
                WHERE platform = ?";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->platform]);
        $stats = $stmt->fetch();
        
        return [
            'platform' => $this->platform,
            'total_orders' => (int)$stats['total_orders'],
            'total_sales' => (float)$stats['total_sales'],
            'last_fetch_time' => $stats['last_fetch_time'] ? date('Y-m-d H:i:s', $stats['last_fetch_time']) : null,
            'oldest_order' => $stats['oldest_order'],
            'newest_order' => $stats['newest_order']
        ];
    }
}

?>
