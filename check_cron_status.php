<?php
// 🔍 Cron Job Status Checker
// ไฟล์สำหรับเช็คสถานะการทำงานของ Cron Job

require_once 'db.php';

header('Content-Type: application/json');

try {
    $pdo = dm_db();
    
    // เช็คจำนวน orders แต่ละ platform
    $stmt = $pdo->query("
        SELECT 
            platform, 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN DATE(created_at) = DATE('now') THEN 1 END) as today_orders,
            MAX(fetched_at) as last_fetch_timestamp,
            datetime(MAX(fetched_at), 'unixepoch', 'localtime') as last_fetch_time
        FROM orders 
        GROUP BY platform
    ");
    
    $platforms = [];
    $totalOrders = 0;
    $totalTodayOrders = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $platforms[] = [
            'platform' => $row['platform'],
            'total_orders' => (int)$row['total_orders'],
            'today_orders' => (int)$row['today_orders'],
            'last_fetch_time' => $row['last_fetch_time'],
            'last_fetch_timestamp' => $row['last_fetch_timestamp'],
            'minutes_since_fetch' => $row['last_fetch_timestamp'] ? 
                round((time() - $row['last_fetch_timestamp']) / 60, 1) : null
        ];
        
        $totalOrders += (int)$row['total_orders'];
        $totalTodayOrders += (int)$row['today_orders'];
    }
    
    // เช็ค log file ล่าสุด
    $logFile = 'logs/cron_fetch_' . date('Ymd') . '.log';
    $logExists = file_exists($logFile);
    $logSize = $logExists ? filesize($logFile) : 0;
    $lastLogEntry = null;
    
    if ($logExists && $logSize > 0) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", trim($logContent));
        $lastLogEntry = end($lines);
        
        // หาบรรทัดที่มี timestamp ล่าสุด
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (strpos($lines[$i], '🚀 Auto Fetch Orders') !== false) {
                $lastLogEntry = $lines[$i];
                break;
            }
        }
    }
    
    // สถานะ cron job
    $cronStatus = 'unknown';
    $cronMessage = 'ยังไม่มีข้อมูล';
    
    if (!empty($platforms)) {
        $latestFetch = max(array_column($platforms, 'last_fetch_timestamp'));
        $minutesSinceLastFetch = ($latestFetch) ? round((time() - $latestFetch) / 60, 1) : null;
        
        if ($minutesSinceLastFetch !== null) {
            if ($minutesSinceLastFetch < 20) {
                $cronStatus = 'active';
                $cronMessage = "ทำงานปกติ (อัปเดตล่าสุด {$minutesSinceLastFetch} นาทีที่แล้ว)";
            } elseif ($minutesSinceLastFetch < 60) {
                $cronStatus = 'warning'; 
                $cronMessage = "อาจมีปัญหา (อัปเดตล่าสุด {$minutesSinceLastFetch} นาทีที่แล้ว)";
            } else {
                $cronStatus = 'error';
                $cronMessage = "ไม่ทำงาน (อัปเดตล่าสุด {$minutesSinceLastFetch} นาทีที่แล้ว)";
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'cron_status' => $cronStatus,
        'cron_message' => $cronMessage,
        'summary' => [
            'total_orders' => $totalOrders,
            'today_orders' => $totalTodayOrders,
            'active_platforms' => count($platforms)
        ],
        'platforms' => $platforms,
        'log_info' => [
            'file_exists' => $logExists,
            'file_size' => $logSize,
            'last_entry' => $lastLogEntry
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
