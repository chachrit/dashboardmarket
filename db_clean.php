<?php
// Database helper using MySQL PDO only
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

// Settings functions
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
    foreach ($stmt->fetchAll() as $row) { 
        $out[$row['name']] = $row['value']; 
    }
    return $out;
}

function dm_settings_set_many($scope, array $assoc) {
    foreach ($assoc as $k => $v) { 
        dm_settings_set($scope, $k, $v); 
    }
}

// Database info functions
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

// Orders functions
function dm_order_save($platform, array $orders) {
    $pdo = dm_db();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO orders (platform, order_id, order_status, total_amount, currency, customer_name, customer_email, created_at, updated_at, order_data) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE order_status = VALUES(order_status), total_amount = VALUES(total_amount), customer_name = VALUES(customer_name), customer_email = VALUES(customer_email), updated_at = VALUES(updated_at), order_data = VALUES(order_data)");
        
        $now = time();
        $saved_count = 0;
        
        foreach ($orders as $order) {
            $stmt->execute([
                $platform,
                $order['order_id'] ?? '',
                $order['order_status'] ?? null,
                $order['total_amount'] ?? null,
                $order['currency'] ?? null,
                $order['customer_name'] ?? null,
                $order['customer_email'] ?? null,
                $order['created_at'] ?? $now,
                $now,
                json_encode($order)
            ]);
            $saved_count++;
        }
        
        error_log("dm_order_save: Saved $saved_count orders for platform $platform");
        return $saved_count;
    } catch (Exception $e) {
        error_log("dm_order_save error: " . $e->getMessage());
        return false;
    }
}

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

function dm_orders_get_stats($platform, $date_from = null, $date_to = null) {
    $pdo = dm_db();
    if (!$pdo) return [];
    
    try {
        if (!$date_from) $date_from = date('Y-m-d');
        if (!$date_to) $date_to = date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    COUNT(DISTINCT order_status) as status_count
                FROM orders 
                WHERE platform = ? 
                AND DATE(FROM_UNIXTIME(created_at)) >= ? 
                AND DATE(FROM_UNIXTIME(created_at)) <= ?";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$platform, $date_from, $date_to]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        error_log("dm_orders_get_stats error: " . $e->getMessage());
        return [];
    }
}

function dm_orders_count($platform = null) {
    $pdo = dm_db();
    if (!$pdo) return 0;
    
    try {
        if ($platform) {
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM orders WHERE platform = ?');
            $stmt->execute([$platform]);
        } else {
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM orders');
        }
        
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("dm_orders_count error: " . $e->getMessage());
        return 0;
    }
}

function dm_orders_delete($platform, $order_id) {
    $pdo = dm_db();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare('DELETE FROM orders WHERE platform = ? AND order_id = ?');
        $stmt->execute([$platform, $order_id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("dm_orders_delete error: " . $e->getMessage());
        return false;
    }
}

?>
