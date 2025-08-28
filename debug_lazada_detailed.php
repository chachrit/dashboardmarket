<?php
/**
 * Lazada API Detailed Debug Tool
 * ใช้สำหรับ debug Lazada API signature และทดสอบแบบละเอียด
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'db.php';
require_once 'api.php';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lazada API Debug Tool</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.6; 
            background: #f8f9fa;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 { color: #333; }
        .success { 
            color: #28a745; 
            background: #d4edda; 
            padding: 10px; 
            border: 1px solid #c3e6cb; 
            border-radius: 4px; 
            margin: 10px 0; 
        }
        .error { 
            color: #dc3545; 
            background: #f8d7da; 
            padding: 10px; 
            border: 1px solid #f5c6cb; 
            border-radius: 4px; 
            margin: 10px 0; 
        }
        .info { 
            color: #0c5460; 
            background: #d1ecf1; 
            padding: 10px; 
            border: 1px solid #bee5eb; 
            border-radius: 4px; 
            margin: 10px 0; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0; 
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border: 1px solid #dee2e6;
        }
        th { 
            background-color: #e9ecef; 
            font-weight: bold;
        }
        tr:nth-child(even) { background-color: #f8f9fa; }
        code { 
            background: #f8f9fa; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        pre { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            border: 1px solid #dee2e6;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .step-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }
        .debug-section {
            background: #f1f3f4;
            border-left: 4px solid #4285f4;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 Lazada API Detailed Debug Tool</h1>
        
        <?php
        // Get Lazada settings
        $lazada_settings = dm_settings_get_all('lazada');
        
        echo "<h2>📋 Current Settings</h2>";
        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
        
        $settings_check = [
            'app_key' => $lazada_settings['app_key'] ?? '',
            'app_secret' => $lazada_settings['app_secret'] ?? '',
            'access_token' => $lazada_settings['access_token'] ?? '',
            'api_url' => $lazada_settings['api_url'] ?? '',
            'enabled' => $lazada_settings['enabled'] ?? 'false'
        ];
        
        foreach ($settings_check as $key => $value) {
            $display_value = $value;
            $status = '<span style="color: red;">❌ Missing</span>';
            
            if (!empty($value)) {
                if (in_array($key, ['app_secret', 'access_token'])) {
                    $display_value = substr($value, 0, 6) . '***' . substr($value, -3);
                }
                $status = '<span style="color: green;">✅ Set</span>';
            }
            
            echo "<tr>";
            echo "<td><strong>$key</strong></td>";
            echo "<td><code>$display_value</code></td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (empty($lazada_settings['app_key']) || empty($lazada_settings['app_secret'])) {
            echo "<div class='error'>";
            echo "❌ <strong>Missing Required Credentials</strong><br>";
            echo "Please configure App Key and App Secret in settings first.";
            echo "</div>";
            echo "<p><a href='settings.php' class='btn'>Go to Settings</a></p>";
            exit;
        }
        
        echo "<h2>🔧 Manual Signature Test</h2>";
        
        // Test signature generation
        $app_key = $lazada_settings['app_key'];
        $app_secret = $lazada_settings['app_secret'];
        $timestamp = round(microtime(true) * 1000); // milliseconds
        $path = '/system/time/get';
        
        // Build parameters for public API
        $params = [
            'app_key' => $app_key,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256'
        ];
        
        // Sort parameters by key
        ksort($params);
        
        echo "<div class='debug-section'>";
        echo "<h3>🔍 Step-by-Step Signature Generation</h3>";
        
        echo "<div class='step-box'>";
        echo "<strong>Step 1: Basic Parameters</strong>";
        echo "<table>";
        foreach ($params as $key => $value) {
            $display_val = ($key === 'app_key' && strlen($value) > 10) ? substr($value, 0, 6) . '***' : $value;
            echo "<tr><td><code>$key</code></td><td><code>$display_val</code></td></tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // Create concatenated string
        $stringToSign = '';
        foreach($params as $k => $v) {
            $stringToSign .= $k . $v;
        }
        
        echo "<div class='step-box'>";
        echo "<strong>Step 2: Concatenated Parameters</strong><br>";
        echo "<code>" . htmlspecialchars($stringToSign) . "</code>";
        echo "</div>";
        
        // Add path to front
        $signString = $path . $stringToSign;
        
        echo "<div class='step-box'>";
        echo "<strong>Step 3: Add API Path (Final String to Sign)</strong><br>";
        echo "<code>" . htmlspecialchars($signString) . "</code>";
        echo "</div>";
        
        // Generate signature
        $signature = strtoupper(hash_hmac('sha256', $signString, $app_secret));
        
        echo "<div class='step-box'>";
        echo "<strong>Step 4: HMAC-SHA256 Signature</strong><br>";
        echo "Algorithm: <code>UPPER(HMAC-SHA256(signString, app_secret))</code><br>";
        echo "Result: <code>$signature</code>";
        echo "</div>";
        
        echo "</div>";
        
        // Add signature to parameters
        $params['sign'] = $signature;
        
        echo "<h2>🌐 API Call Test</h2>";
        
        // Make API request
        $api_url = ($lazada_settings['api_url'] ?? 'https://api.lazada.co.th/rest') . $path;
        $query_string = http_build_query($params);
        $full_url = $api_url . '?' . $query_string;
        
        echo "<h3>Request Details</h3>";
        echo "<table>";
        echo "<tr><td><strong>Method</strong></td><td>GET</td></tr>";
        echo "<tr><td><strong>Base URL</strong></td><td><code>" . htmlspecialchars($api_url) . "</code></td></tr>";
        echo "<tr><td><strong>Query String</strong></td><td><code>" . htmlspecialchars($query_string) . "</code></td></tr>";
        echo "<tr><td><strong>Full URL</strong></td><td><code style='word-break: break-all;'>" . htmlspecialchars($full_url) . "</code></td></tr>";
        echo "</table>";
        
        // Execute request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $full_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'LazadaDebugTool/1.0',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $end_time = microtime(true);
        $request_time = round(($end_time - $start_time) * 1000, 2);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_info = curl_getinfo($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        echo "<h3>Response Details</h3>";
        echo "<table>";
        echo "<tr><td><strong>HTTP Status</strong></td><td><code>$http_code</code></td></tr>";
        echo "<tr><td><strong>Request Time</strong></td><td><code>{$request_time}ms</code></td></tr>";
        echo "<tr><td><strong>Content Type</strong></td><td><code>" . ($curl_info['content_type'] ?? 'unknown') . "</code></td></tr>";
        if ($curl_error) {
            echo "<tr><td><strong>cURL Error</strong></td><td><code style='color: red;'>$curl_error</code></td></tr>";
        }
        echo "</table>";
        
        if ($response) {
            echo "<h4>Raw Response</h4>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            
            // Try to decode JSON
            $json_data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<h4>Parsed JSON Response</h4>";
                echo "<pre>" . htmlspecialchars(json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
                
                // Analyze response
                if (isset($json_data['code'])) {
                    if ($json_data['code'] === '0' || $json_data['code'] === 0) {
                        echo "<div class='success'>";
                        echo "✅ <strong>API Call Successful!</strong><br>";
                        echo "The signature generation is working correctly.";
                        echo "</div>";
                    } else {
                        echo "<div class='error'>";
                        echo "❌ <strong>API Error (Code: " . $json_data['code'] . ")</strong><br>";
                        echo "Message: " . ($json_data['message'] ?? 'Unknown error');
                        if (isset($json_data['detail'])) {
                            echo "<br>Detail: " . $json_data['detail'];
                        }
                        echo "</div>";
                    }
                }
            } else {
                echo "<div class='error'>";
                echo "❌ <strong>Invalid JSON Response</strong><br>";
                echo "JSON Error: " . json_last_error_msg();
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>No Response Received</strong>";
            if ($curl_error) {
                echo "<br>cURL Error: $curl_error";
            }
            echo "</div>";
        }
        
        echo "<h2>🧪 Test with LazadaAPI Class</h2>";
        
        try {
            $config = getAPIConfig();
            $lazada_api = new LazadaAPI('lazada', $config['lazada']);
            
            echo "<div class='info'>";
            echo "✅ LazadaAPI class instantiated successfully";
            echo "</div>";
            
            // Test the class method
            $class_result = $lazada_api->testConnection();
            
            echo "<h4>Class Test Result</h4>";
            echo "<pre>" . htmlspecialchars(json_encode($class_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            
            if ($class_result['success']) {
                echo "<div class='success'>";
                echo "✅ " . $class_result['message'];
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "❌ " . $class_result['message'];
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "❌ <strong>Exception occurred:</strong><br>";
            echo $e->getMessage();
            echo "</div>";
        }
        
        echo "<h2>📚 Debugging Tips</h2>";
        echo "<div class='info'>";
        echo "<strong>Common Issues & Solutions:</strong>";
        echo "<ul>";
        echo "<li><strong>IncompleteSignature:</strong> Check parameter sorting and concatenation</li>";
        echo "<li><strong>InvalidTimestamp:</strong> Ensure timestamp is in milliseconds and within 15 minutes</li>";
        echo "<li><strong>InvalidAppKey:</strong> Verify App Key in Lazada Seller Center</li>";
        echo "<li><strong>Forbidden:</strong> Check API permissions and app status</li>";
        echo "<li><strong>InvalidSignature:</strong> Verify HMAC-SHA256 algorithm and secret key</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<h2>🔗 Useful Links</h2>";
        echo "<ul>";
        echo "<li><a href='https://open.lazada.com/doc/doc.htm' target='_blank'>Lazada Open Platform Documentation</a></li>";
        echo "<li><a href='https://open.lazada.com/doc/api.htm' target='_blank'>API Reference</a></li>";
        echo "<li><a href='settings.php'>Go back to Settings</a></li>";
        echo "<li><a href='test_lazada.php' target='_blank'>Simple JSON Test</a></li>";
        echo "</ul>";
        ?>
        
    </div>
</body>
</html>
