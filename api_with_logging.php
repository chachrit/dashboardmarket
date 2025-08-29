<?php
// Debug log for settings save attempts
$logFile = __DIR__ . '/logs/settings_debug.log';

// Create logs directory if not exists
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Log all requests
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
    'query_string' => $_SERVER['QUERY_STRING'] ?? '',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'get_params' => $_GET,
    'post_params' => $_POST,
    'php_input' => file_get_contents('php://input'),
    'action' => $_GET['action'] ?? null,
    'platform' => $_GET['platform'] ?? null
];

// Write to log file
file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);

// Now process the actual API request
require_once __DIR__ . '/api.php';
?>
