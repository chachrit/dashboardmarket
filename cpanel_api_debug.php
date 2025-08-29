<?php
// cPanel API Save Test - Detailed debugging
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown'
    ]
];

try {
    // 1. Capture all request data
    $debug_info['request'] = [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'query_string' => $_SERVER['QUERY_STRING'] ?? '',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
        'get_params' => $_GET,
        'post_params' => $_POST,
        'php_input' => file_get_contents('php://input'),
        'headers' => function_exists('getallheaders') ? getallheaders() : []
    ];
    
    // 2. Check if this is a save_settings request
    $action = $_GET['action'] ?? null;
    $platform = $_GET['platform'] ?? null;
    
    $debug_info['parsed_params'] = [
        'action' => $action,
        'platform' => $platform
    ];
    
    // 3. Parse JSON input
    $json_input = $debug_info['request']['php_input'];
    $parsed_json = null;
    if ($json_input) {
        $parsed_json = json_decode($json_input, true);
        $debug_info['json_parsing'] = [
            'raw_input' => $json_input,
            'parsed_data' => $parsed_json,
            'json_error' => json_last_error_msg(),
            'json_error_code' => json_last_error()
        ];
    }
    
    // 4. Test database connection
    $debug_info['database_test'] = [];
    try {
        require_once __DIR__ . '/db.php';
        $pdo = dm_db();
        
        $debug_info['database_test']['connection'] = 'SUCCESS';
        $debug_info['database_test']['driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        // Test basic query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM dm_settings");
        $count = $stmt->fetchColumn();
        $debug_info['database_test']['current_records'] = $count;
        
    } catch (Exception $e) {
        $debug_info['database_test']['connection'] = 'FAILED';
        $debug_info['database_test']['error'] = $e->getMessage();
        $debug_info['database_test']['error_code'] = $e->getCode();
    }
    
    // 5. If this is a save_settings request, process it
    if ($action === 'save_settings' && $platform && $parsed_json && $debug_info['database_test']['connection'] === 'SUCCESS') {
        $debug_info['save_test'] = [];
        
        try {
            // Get settings before save
            $before_settings = dm_settings_get_all($platform);
            $debug_info['save_test']['before_save'] = $before_settings;
            
            // Process the save
            $data = $parsed_json;
            
            // Convert boolean 'enabled' to string 'true'/'false'
            if (isset($data['enabled'])) {
                $data['enabled'] = $data['enabled'] ? 'true' : 'false';
            }
            
            $debug_info['save_test']['processed_data'] = $data;
            $debug_info['save_test']['save_operations'] = [];
            
            foreach($data as $key => $value) {
                $save_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                
                // Try to save
                try {
                    dm_settings_set($platform, $key, $save_value);
                    $debug_info['save_test']['save_operations'][] = [
                        'key' => $key,
                        'value' => $save_value,
                        'status' => 'SUCCESS'
                    ];
                } catch (Exception $e) {
                    $debug_info['save_test']['save_operations'][] = [
                        'key' => $key,
                        'value' => $save_value,
                        'status' => 'FAILED',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Get settings after save
            $after_settings = dm_settings_get_all($platform);
            $debug_info['save_test']['after_save'] = $after_settings;
            
            // Compare before and after
            $debug_info['save_test']['changes'] = [];
            foreach ($data as $key => $expected_value) {
                $expected = is_bool($expected_value) ? ($expected_value ? 'true' : 'false') : $expected_value;
                $before = $before_settings[$key] ?? 'NOT_SET';
                $after = $after_settings[$key] ?? 'NOT_FOUND';
                
                $debug_info['save_test']['changes'][$key] = [
                    'expected' => $expected,
                    'before' => $before,
                    'after' => $after,
                    'changed' => $before !== $after,
                    'correct' => $after === $expected
                ];
            }
            
            $debug_info['result'] = [
                'success' => true,
                'message' => 'Save test completed'
            ];
            
        } catch (Exception $e) {
            $debug_info['save_test']['error'] = $e->getMessage();
            $debug_info['save_test']['error_trace'] = $e->getTraceAsString();
            $debug_info['result'] = [
                'success' => false,
                'error' => 'Save test failed: ' . $e->getMessage()
            ];
        }
        
    } elseif ($action === 'save_settings') {
        $debug_info['result'] = [
            'success' => false,
            'error' => 'Save test cannot proceed',
            'reasons' => [
                'platform_missing' => !$platform,
                'json_data_missing' => !$parsed_json,
                'database_failed' => $debug_info['database_test']['connection'] !== 'SUCCESS'
            ]
        ];
        
    } else {
        $debug_info['result'] = [
            'success' => true,
            'message' => 'Debug info collected, no save operation requested'
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

// Output debug info
echo json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
