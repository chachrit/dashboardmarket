<?php
// API Debug Script for cPanel - shows detailed request/response info
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$debug_info = [];

try {
    // 1. Capture request info
    $debug_info['request'] = [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'query_string' => $_SERVER['QUERY_STRING'] ?? '',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
        'get_params' => $_GET,
        'post_params' => $_POST,
        'headers' => getallheaders(),
        'php_input' => file_get_contents('php://input')
    ];
    
    // 2. Parse the actual request
    $action = $_GET['action'] ?? null;
    $platform = $_GET['platform'] ?? null;
    
    $debug_info['parsed'] = [
        'action' => $action,
        'platform' => $platform
    ];
    
    // 3. Try to parse JSON input
    $json_input = $debug_info['request']['php_input'];
    if ($json_input) {
        $parsed_json = json_decode($json_input, true);
        $debug_info['json_data'] = [
            'raw' => $json_input,
            'parsed' => $parsed_json,
            'json_error' => json_last_error_msg()
        ];
    }
    
    // 4. Test database if action is save_settings
    if ($action === 'save_settings' && $platform) {
        require_once __DIR__ . '/db.php';
        
        $debug_info['database'] = [];
        
        try {
            $pdo = dm_db();
            $debug_info['database']['connection'] = 'SUCCESS';
            $debug_info['database']['driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            // Get current settings before save
            $before = dm_settings_get_all($platform);
            $debug_info['database']['before_save'] = $before;
            
            if ($parsed_json && is_array($parsed_json)) {
                // Simulate the save_settings logic
                $data = $parsed_json;
                
                // Convert boolean 'enabled' to string 'true'/'false'
                if (isset($data['enabled'])) {
                    $data['enabled'] = $data['enabled'] ? 'true' : 'false';
                }
                
                $debug_info['database']['save_operations'] = [];
                
                foreach($data as $key => $value){
                    $save_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                    dm_settings_set($platform, $key, $save_value);
                    $debug_info['database']['save_operations'][] = [
                        'key' => $key,
                        'original_value' => $value,
                        'saved_value' => $save_value
                    ];
                }
                
                // Get settings after save
                $after = dm_settings_get_all($platform);
                $debug_info['database']['after_save'] = $after;
                
                $debug_info['result'] = [
                    'success' => true,
                    'message' => 'Settings saved successfully'
                ];
            } else {
                $debug_info['result'] = [
                    'success' => false,
                    'error' => 'Invalid or missing JSON data'
                ];
            }
            
        } catch (Exception $e) {
            $debug_info['database']['connection'] = 'FAILED';
            $debug_info['database']['error'] = $e->getMessage();
            $debug_info['result'] = [
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ];
        }
    } elseif ($action === 'save_settings') {
        $debug_info['result'] = [
            'success' => false,
            'error' => 'Platform parameter is required'
        ];
    } else {
        $debug_info['result'] = [
            'success' => true,
            'message' => 'Debug info collected, no action performed'
        ];
    }
    
} catch (Exception $e) {
    $debug_info['fatal_error'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    $debug_info['result'] = [
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage()
    ];
}

// Add timestamp
$debug_info['timestamp'] = date('Y-m-d H:i:s');
$debug_info['timezone'] = date_default_timezone_get();

echo json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
