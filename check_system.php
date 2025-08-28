<?php
/**
 * System Requirements Checker for Dashboard Market
 * Run this file to check if your server meets the requirements
 */
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Requirements Check - Dashboard Market</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; }
        .check { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .check.pass { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .check.fail { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .check.warn { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .icon { font-weight: bold; margin-right: 10px; }
        .summary { margin-top: 20px; padding: 15px; border-radius: 4px; text-align: center; }
        .summary.ready { background: #d4edda; color: #155724; }
        .summary.not-ready { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard Market - System Requirements Check</h1>
        
        <?php
        $checks = [];
        $critical_fails = 0;
        
        // Check PHP Version
        $php_version = PHP_VERSION;
        $php_required = '7.4.0';
        if (version_compare($php_version, $php_required, '>=')) {
            $checks[] = ['pass', 'PHP Version', "✓ PHP $php_version (Required: $php_required+)"];
        } else {
            $checks[] = ['fail', 'PHP Version', "✗ PHP $php_version (Required: $php_required+)"];
            $critical_fails++;
        }
        
        // Check Required Extensions
        $required_extensions = ['sqlite3', 'curl', 'json', 'mbstring', 'openssl'];
        foreach ($required_extensions as $ext) {
            if (extension_loaded($ext)) {
                $checks[] = ['pass', "Extension: $ext", "✓ $ext extension is loaded"];
            } else {
                $checks[] = ['fail', "Extension: $ext", "✗ $ext extension is missing"];
                $critical_fails++;
            }
        }
        
        // Check Optional Extensions
        $optional_extensions = ['gd', 'zip', 'xml'];
        foreach ($optional_extensions as $ext) {
            if (extension_loaded($ext)) {
                $checks[] = ['pass', "Optional Extension: $ext", "✓ $ext extension is loaded"];
            } else {
                $checks[] = ['warn', "Optional Extension: $ext", "⚠ $ext extension is missing (recommended)"];
            }
        }
        
        // Check Directory Permissions
        $data_dir = __DIR__ . '/data';
        $logs_dir = __DIR__ . '/logs';
        
        if (!is_dir($data_dir)) {
            @mkdir($data_dir, 0777, true);
        }
        if (!is_dir($logs_dir)) {
            @mkdir($logs_dir, 0777, true);
        }
        
        if (is_writable($data_dir)) {
            $checks[] = ['pass', 'Data Directory', '✓ data/ directory is writable'];
        } else {
            $checks[] = ['fail', 'Data Directory', '✗ data/ directory is not writable'];
            $critical_fails++;
        }
        
        if (is_writable($logs_dir)) {
            $checks[] = ['pass', 'Logs Directory', '✓ logs/ directory is writable'];
        } else {
            $checks[] = ['warn', 'Logs Directory', '⚠ logs/ directory is not writable (recommended)'];
        }
        
        // Check cURL functionality
        if (function_exists('curl_init')) {
            $ch = curl_init('https://www.google.com');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($result !== false && $http_code === 200) {
                $checks[] = ['pass', 'cURL Connection', '✓ cURL can connect to external URLs'];
            } else {
                $checks[] = ['warn', 'cURL Connection', '⚠ cURL connection test failed (check firewall/proxy)'];
            }
        }
        
        // Check PHP Settings
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = return_bytes($memory_limit);
        if ($memory_bytes >= 128 * 1024 * 1024) { // 128MB
            $checks[] = ['pass', 'Memory Limit', "✓ Memory limit: $memory_limit"];
        } else {
            $checks[] = ['warn', 'Memory Limit', "⚠ Memory limit: $memory_limit (recommended: 128M+)"];
        }
        
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time >= 30 || $max_execution_time == 0) {
            $checks[] = ['pass', 'Execution Time', "✓ Max execution time: {$max_execution_time}s"];
        } else {
            $checks[] = ['warn', 'Execution Time', "⚠ Max execution time: {$max_execution_time}s (recommended: 30s+)"];
        }
        
        // Display results
        foreach ($checks as $check) {
            echo "<div class='check {$check[0]}'><strong>{$check[1]}:</strong> {$check[2]}</div>";
        }
        
        // Summary
        if ($critical_fails === 0) {
            echo "<div class='summary ready'>";
            echo "<strong>✓ Your server is ready for Dashboard Market!</strong><br>";
            echo "You can proceed with the deployment.";
            echo "</div>";
        } else {
            echo "<div class='summary not-ready'>";
            echo "<strong>✗ Your server needs attention before deployment</strong><br>";
            echo "Please fix the $critical_fails critical issue(s) above.";
            echo "</div>";
        }
        
        function return_bytes($val) {
            $val = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            $val = (int)$val;
            switch($last) {
                case 'g': $val *= 1024;
                case 'm': $val *= 1024;
                case 'k': $val *= 1024;
            }
            return $val;
        }
        ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 4px;">
            <strong>Next Steps:</strong>
            <ol>
                <li>Fix any critical issues shown above</li>
                <li>Copy <code>config.example.php</code> to <code>config.php</code> and modify settings</li>
                <li>Upload all files to your web server</li>
                <li>Set proper file permissions (see DEPLOYMENT.md)</li>
                <li>Access your website and test functionality</li>
            </ol>
        </div>
    </div>
</body>
</html>
