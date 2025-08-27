<?php
// Lightweight DB helper using PDO. Preferred DB: SQL Server (sqlsrv PDO driver).
// Configuration via environment variables:
// - DM_DB_DSN  e.g. sqlsrv:Server=localhost;Database=dashboard
// - DM_DB_USER
// - DM_DB_PASS
// If DM_DB_DSN not set, we build a SQL Server DSN from:
// - DM_DB_SERVER (default: localhost), DM_DB_NAME (default: dashboard), DM_DB_USER (default: sa), DM_DB_PASS (default: Journal@25)
// If PDO sqlsrv is unavailable or connect fails, fallback to SQLite ./data/dashboardmarket.sqlite

function dm_db() {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dsn  = getenv('DM_DB_DSN');
    $user = getenv('DM_DB_USER') ?: null;
    $pass = getenv('DM_DB_PASS') ?: null;

    if (!$dsn) {
        $server = getenv('DM_DB_SERVER') ?: 'localhost';
        $name   = getenv('DM_DB_NAME')   ?: 'dashboard';
        $user   = getenv('DM_DB_USER')   ?: 'sa';
        $pass   = getenv('DM_DB_PASS')   ?: 'Journal@25';
        $dsn = 'sqlsrv:Server=' . $server . ';Database=' . $name;
    }

    // Try to connect with PDO (sqlsrv or others)
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        // Fallback to SQLite
        $dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($dataDir)) { @mkdir($dataDir, 0775, true); }
        $dbPath = $dataDir . DIRECTORY_SEPARATOR . 'dashboardmarket.sqlite';
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    // Ensure table exists
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec('CREATE TABLE IF NOT EXISTS dm_settings (
            scope TEXT NOT NULL,
            name TEXT NOT NULL,
            value TEXT,
            updated_at INTEGER,
            PRIMARY KEY (scope, name)
        )');
    } elseif ($driver === 'sqlsrv') {
        $pdo->exec("IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[dm_settings]') AND type in (N'U'))
        BEGIN
            CREATE TABLE [dbo].[dm_settings] (
                [scope] NVARCHAR(50) NOT NULL,
                [name] NVARCHAR(100) NOT NULL,
                [value] NVARCHAR(MAX) NULL,
                [updated_at] BIGINT NULL,
                CONSTRAINT [PK_dm_settings] PRIMARY KEY ([scope],[name])
            )
        END");
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS dm_settings (
            scope VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            value TEXT NULL,
            updated_at BIGINT NULL,
            PRIMARY KEY (scope, name)
        )');
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
    $stmt = $pdo->prepare('UPDATE dm_settings SET value = ?, updated_at = ? WHERE scope = ? AND name = ?');
    $stmt->execute([$value, $now, $scope, $name]);
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare('INSERT INTO dm_settings (scope, name, value, updated_at) VALUES (?,?,?,?)');
        $stmt->execute([$scope, $name, $value, $now]);
    }
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

?>
