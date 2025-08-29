<?php
// Lightweight DB helper using PDO - MySQL only
// Configuration via environment variables:
// - DM_DB_DSN  e.g. mysql:host=localhost;dbname=dashboard;charset=utf8mb4
// - DM_DB_USER
// - DM_DB_PASS
// If DM_DB_DSN not set, we build DSN from:
// - DM_DB_SERVER (default: localhost), DM_DB_NAME, DM_DB_USER, DM_DB_PASS

function dm_db() {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dsn  = getenv('DM_DB_DSN');
    $user = getenv('DM_DB_USER') ?: null;
    $pass = getenv('DM_DB_PASS') ?: null;

    if (!$dsn) {
        $server = getenv('DM_DB_SERVER') ?: 'localhost';
        $name   = getenv('DM_DB_NAME')   ?: 'zcwuapsz_realtime_marketplace';
        $user   = getenv('DM_DB_USER')   ?: 'zcwuapsz';
        $pass   = getenv('DM_DB_PASS')   ?: 'Journal@25';
        
        // Build MySQL DSN
        $dsn = 'mysql:host=' . $server . ';dbname=' . $name . ';charset=utf8mb4';
    }

    // Connect with MySQL PDO
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT => 15
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        
    } catch (Throwable $e) {
        error_log("MySQL connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }

    // Ensure dm_settings table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `dm_settings` (
        `scope` VARCHAR(50) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `value` TEXT NULL,
        `updated_at` BIGINT NULL,
        PRIMARY KEY (`scope`, `name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Ensure orders table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `platform` VARCHAR(20) NOT NULL,
        `order_id` VARCHAR(100) NOT NULL,
        `order_status` VARCHAR(50) NULL,
        `total_amount` DECIMAL(10,2) NULL,
        `currency` VARCHAR(10) NULL,
        `customer_name` VARCHAR(255) NULL,
        `customer_email` VARCHAR(255) NULL,
        `created_at` BIGINT NULL,
        `updated_at` BIGINT NULL,
        `order_data` JSON NULL,
        UNIQUE KEY `unique_platform_order` (`platform`, `order_id`),
        INDEX `idx_platform` (`platform`),
        INDEX `idx_created_at` (`created_at`),
        INDEX `idx_order_status` (`order_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    return $pdo;
}

function dm_settings_get($scope, $name, $default = null) {
    $pdo = dm_db();
    $stmt = $pdo->prepare('SELECT value FROM dm_settings WHERE scope = ? AND name = ?');
    $stmt->execute([$scope, $name]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function dm_settings_set($scope, $name, $value) {
    $pdo = dm_db();
    $now = time();
    // Use MySQL's INSERT ... ON DUPLICATE KEY UPDATE for better performance
    $stmt = $pdo->prepare('INSERT INTO dm_settings (scope, name, value, updated_at) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = VALUES(updated_at)');
    $stmt->execute([$scope, $name, $value, $now]);
    return $stmt->rowCount() > 0;
}

function dm_settings_get_all($scope) {
    $pdo = dm_db();
    $stmt = $pdo->prepare('SELECT name, value FROM dm_settings WHERE scope = ?');
    $stmt->execute([$scope]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) { $out[$row['name']] = $row['value']; }
    return $out;
}

function dm_settings_set_many($scope, array $assoc) {
    foreach ($assoc as $k => $v) { dm_settings_set($scope, $k, $v); }
}

function dm_get_db_type() {
    try {
        $pdo = dm_db();
        return $pdo ? 'mysql' : 'unknown';
    } catch (Exception $e) {
        return 'unknown';
    }
}

function dm_get_db_info() {
    try {
        $pdo = dm_db();
        if (!$pdo) {
            return ['type' => 'unknown', 'status' => 'disconnected'];
        }
        
        $info = ['type' => 'mysql', 'status' => 'connected'];
        
        // Get MySQL version
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        $info['version'] = $version['version'] ?? 'unknown';
        
        // Get current database name
        $stmt = $pdo->query("SELECT DATABASE() as dbname");
        $dbInfo = $stmt->fetch();
        $info['database'] = $dbInfo['dbname'] ?? 'unknown';
        
        return $info;
    } catch (Exception $e) {
        return ['type' => 'mysql', 'status' => 'error', 'error' => $e->getMessage()];
    }
}

/**
 * บันทึกข้อมูล order ลงฐานข้อมูล
 */
function dm_order_save($platform, $order_id, $amount, $status, $created_at, $items = null, $raw_data = null) {
    global $dm_db_connection;
    
    if (!$dm_db_connection) {
        $dm_db_connection = dm_db();
    }
    
    if (!$dm_db_connection) {
        return false;
    }
    
    try {
        $driver = $dm_db_connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'mysql') {
            $sql = "INSERT INTO orders (platform, order_id, amount, status, created_at, items, raw_data, fetched_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                        amount = VALUES(amount),
                        status = VALUES(status),
                        created_at = VALUES(created_at),
                        items = VALUES(items),
                        raw_data = VALUES(raw_data),
                        fetched_at = VALUES(fetched_at)";
        } elseif ($driver === 'sqlsrv') {
            $sql = "IF NOT EXISTS (SELECT 1 FROM orders WHERE platform = ? AND order_id = ?)
                        INSERT INTO orders (platform, order_id, amount, status, created_at, items, raw_data, fetched_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ELSE
                        UPDATE orders SET 
                            amount = ?, status = ?, created_at = ?, items = ?, raw_data = ?, fetched_at = ?
                        WHERE platform = ? AND order_id = ?";
        } else { // sqlite
            $sql = "INSERT OR REPLACE INTO orders (platform, order_id, amount, status, created_at, items, raw_data, fetched_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        }
        
        $stmt = $dm_db_connection->prepare($sql);
        $fetched_at = time();
        
        $items_json = is_array($items) ? json_encode($items, JSON_UNESCAPED_UNICODE) : $items;
        $raw_data_json = is_array($raw_data) ? json_encode($raw_data, JSON_UNESCAPED_UNICODE) : $raw_data;
        
        if ($driver === 'sqlsrv') {
            // MSSQL ต้องใส่ parameter 2 ครั้ง
            $result = $stmt->execute([
                $platform, $order_id, // CHECK EXISTS
                $platform, $order_id, $amount, $status, $created_at, $items_json, $raw_data_json, $fetched_at, // INSERT
                $amount, $status, $created_at, $items_json, $raw_data_json, $fetched_at, $platform, $order_id // UPDATE
            ]);
        } else {
            $result = $stmt->execute([
                $platform, $order_id, $amount, $status, $created_at, $items_json, $raw_data_json, $fetched_at
            ]);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("dm_order_save error: " . $e->getMessage());
        return false;
    }
}

/**
 * ดึงข้อมูล orders จากฐานข้อมูล
 */
function dm_orders_get($platform, $date_from = null, $date_to = null, $limit = 100) {
    $pdo = dm_db();
    if (!$pdo) return [];
    
    try {
        if (!$date_from) $date_from = date('Y-m-d');
        if (!$date_to) $date_to = date('Y-m-d');
        
        $sql = "SELECT * FROM orders 
                WHERE platform = ? 
                AND DATE(FROM_UNIXTIME(created_at)) >= ? 
                AND DATE(FROM_UNIXTIME(created_at)) <= ?
                ORDER BY created_at DESC 
                LIMIT ?";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $platform,
            $date_from,
            $date_to,
            $limit
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("dm_orders_get error: " . $e->getMessage());
        return [];
    }
}

/**
 * ตรวจสอบความสดใหม่ของข้อมูล orders
 */
function dm_orders_is_fresh($platform, $maxAgeMinutes = 30) {
    $pdo = dm_db();
    if (!$pdo) return false;
    
    try {
        $maxAge = time() - ($maxAgeMinutes * 60);
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE platform = ? AND updated_at > ?");
        $stmt->execute([$platform, $maxAge]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("dm_orders_is_fresh error: " . $e->getMessage());
        return false;
    }
}
    
    if (!$dm_db_connection) {
        $dm_db_connection = dm_db();
    }
}

/**
 * ดึงสถิติ orders จากฐานข้อมูล
 */
function dm_orders_get_stats($platform, $date_from = null, $date_to = null) {
    
    if (!$dm_db_connection) {
        $dm_db_connection = dm_db();
    }
    
    if (!$dm_db_connection) {
        return ['total_orders' => 0, 'total_sales' => 0, 'avg_order_value' => 0];
    }
    
    try {
        if (!$date_from) $date_from = date('Y-m-d');
        if (!$date_to) $date_to = date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(amount), 0) as total_sales,
                    COALESCE(AVG(amount), 0) as avg_order_value
                FROM orders 
                WHERE platform = ? 
                AND created_at >= ? 
                AND created_at <= ?";
                
        $stmt = $dm_db_connection->prepare($sql);
        $stmt->execute([
            $platform,
            $date_from . ' 00:00:00',
            $date_to . ' 23:59:59'
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : ['total_orders' => 0, 'total_sales' => 0, 'avg_order_value' => 0];
    } catch (Exception $e) {
        error_log("dm_orders_get_stats error: " . $e->getMessage());
        return ['total_orders' => 0, 'total_sales' => 0, 'avg_order_value' => 0];
    }
}

?>
