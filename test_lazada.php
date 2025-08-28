<?php
/**
 * Lazada API Connection Test
 * ใช้สำหรับทดสอบการเชื่อมต่อ Lazada API โดยเฉพาะ
 * สำหรับใช้กับ AJAX calls
 */

require_once 'config.php';
require_once 'db.php';
require_once 'api.php';

// Check if this is an AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
}

// Enable error reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    // Get Lazada settings
    $lazada_settings = dm_settings_get_all('lazada');
    
    if (empty($lazada_settings['app_key']) || empty($lazada_settings['app_secret'])) {
        throw new Exception('Lazada App Key หรือ App Secret ขาดหายไป');
    }
    
    // สร้าง LazadaAPI instance
    $config = getAPIConfig()['lazada'];
    $lazadaAPI = new LazadaAPI('lazada', $config);
    
    // ทดสอบการเชื่อมต่อ
    $result = $lazadaAPI->testConnection();
    
    $response_data = [
        'success' => $result['success'],
        'message' => $result['message'],
        'timestamp' => date('c'),
        'app_key' => substr($config['app_key'], 0, 6) . '***',
        'has_access_token' => !empty($config['access_token']),
        'api_url' => $config['api_url']
    ];
    
    // ส่งผลลัพธ์กลับ
    if ($is_ajax) {
        echo json_encode($response_data, JSON_PRETTY_PRINT);
    } else {
        displayHtmlResult($response_data, $result);
    }
    
} catch (Exception $e) {
    $error_data = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c'),
        'debug_info' => [
            'app_key' => isset($lazada_settings['app_key']) ? substr($lazada_settings['app_key'], 0, 6) . '***' : 'NOT SET',
            'app_secret' => isset($lazada_settings['app_secret']) ? 'SET' : 'NOT SET',
            'access_token' => isset($lazada_settings['access_token']) ? 'SET' : 'NOT SET'
        ]
    ];
    
    if ($is_ajax) {
        echo json_encode($error_data, JSON_PRETTY_PRINT);
    } else {
        displayHtmlResult($error_data, null);
    }
}

function displayHtmlResult($data, $test_result) {
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lazada API Test Result</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f8f9fa; 
            color: #333;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { 
            color: #28a745; 
            background: #d4edda; 
            padding: 15px; 
            border-radius: 5px;
            border-left: 5px solid #28a745;
        }
        .error { 
            color: #dc3545; 
            background: #f8d7da; 
            padding: 15px; 
            border-radius: 5px;
            border-left: 5px solid #dc3545;
        }
        .info { 
            background: #e9ecef; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0;
        }
        pre { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            border: 1px solid #dee2e6;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px 10px 0;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        h1 { color: #333; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 Lazada API Test Result</h1>
        
        <?php if ($data['success']): ?>
            <div class="success">
                <h2>✅ Success!</h2>
                <p><strong><?php echo htmlspecialchars($data['message']); ?></strong></p>
            </div>
        <?php else: ?>
            <div class="error">
                <h2>❌ Test Failed</h2>
                <p><strong><?php echo htmlspecialchars($data['error'] ?? $data['message']); ?></strong></p>
            </div>
        <?php endif; ?>
        
        <div class="info">
            <h3>📊 Test Details</h3>
            <pre><?php echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
        
        <?php if (isset($test_result['debug'])): ?>
            <div class="info">
                <h3>🔍 Debug Information</h3>
                <pre><?php echo htmlspecialchars(json_encode($test_result['debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="settings.php" class="btn">← Back to Settings</a>
            <a href="debug_lazada_detailed.php" class="btn btn-success">🔧 Detailed Debug</a>
            <a href="javascript:location.reload()" class="btn">🔄 Test Again</a>
        </div>
        
        <div style="margin-top: 20px; font-size: 0.9em; color: #666; text-align: center;">
            <p>Test completed at: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
<?php
}
?>
