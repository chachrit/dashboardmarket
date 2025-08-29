<?php
// Force SQLite version of db.php for cPanel compatibility
function dm_db() {
    static $pdo = null;
    if ($pdo) return $pdo;

    // Always use SQLite on cPanel to avoid SQL Server driver issues
    $dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dataDir)) { 
        @mkdir($dataDir, 0755, true); 
    }
    
    $dbPath = $dataDir . DIRECTORY_SEPARATOR . 'dashboardmarket.sqlite';
    
    try {
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Create table if not exists
        $pdo->exec('CREATE TABLE IF NOT EXISTS dm_settings (
            scope TEXT NOT NULL,
            name TEXT NOT NULL,
            value TEXT,
            updated_at INTEGER,
            PRIMARY KEY (scope, name)
        )');
        
    } catch (PDOException $e) {
        // If SQLite fails, try to create a more compatible version
        error_log("Database error: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }

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
    
    // Use UPSERT for better compatibility
    $stmt = $pdo->prepare('
        INSERT OR REPLACE INTO dm_settings (scope, name, value, updated_at) 
        VALUES (?, ?, ?, ?)
    ');
    $result = $stmt->execute([$scope, $name, $value, $now]);
    
    if (!$result) {
        throw new Exception("Failed to save setting: $scope.$name");
    }
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
?>
