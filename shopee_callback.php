<?php
/**
 * Shopee OAuth Callback Handler
 * This file handles the callback from Shopee OAuth authorization
 */

require_once 'config.php';
require_once 'db.php';
require_once 'api.php';

// Enable error reporting for debugging
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopee Authorization Callback</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-6">🛒 Shopee Authorization Result</h1>
        
        <?php
        // Check if we have required parameters
        $code = $_GET['code'] ?? null;
        $shop_id = $_GET['shop_id'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;
        
        if ($error) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>";
            echo "<strong>❌ Authorization Failed:</strong><br>";
            echo "Error: " . htmlspecialchars($error) . "<br>";
            echo "Description: " . htmlspecialchars($_GET['error_description'] ?? 'Unknown error');
            echo "</div>";
            echo "<a href='settings.php' class='bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>กลับไปหน้าตั้งค่า</a>";
            exit;
        }
        
        if (!$code) {
            echo "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4'>";
            echo "<strong>⚠️ Missing Authorization Code</strong><br>";
            echo "ไม่พบ authorization code กรุณาลองใหม่";
            echo "</div>";
            echo "<a href='settings.php' class='bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>กลับไปหน้าตั้งค่า</a>";
            exit;
        }
        
        echo "<div class='bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4'>";
        echo "<strong>✅ Authorization Successful!</strong><br>";
        echo "Authorization Code: " . substr($code, 0, 20) . "...<br>";
        if ($shop_id) echo "Shop ID: " . htmlspecialchars($shop_id) . "<br>";
        echo "</div>";
        
        // Now exchange the code for tokens
        try {
            // Get Shopee configuration
            $config = getAPIConfig()['shopee'];
            
            if (empty($config['partner_id']) || empty($config['partner_key'])) {
                throw new Exception('Missing Shopee Partner ID or Partner Key in settings');
            }
            
            // Prepare token exchange request
            $timestamp = time();
            $path = '/api/v2/auth/token/get';
            
            // Create signature for token exchange
            $partner_id = $config['partner_id'];
            $partner_key = $config['partner_key'];
            $base_string = $partner_id . $path . $timestamp;
            $sign = hash_hmac('sha256', $base_string, $partner_key);
            
            $api_base = ($config['env'] === 'sandbox') 
                ? 'https://openplatform.sandbox.test-stable.shopee.sg'
                : 'https://partner.shopeemobile.com';
            
            $url = $api_base . $path . '?' . http_build_query([
                'partner_id' => $partner_id,
                'timestamp' => $timestamp,
                'sign' => $sign
            ]);
            
            // Prepare payload
            $payload = json_encode([
                'code' => $code,
                'shop_id' => (int)$shop_id,
                'partner_id' => (int)$partner_id
            ]);
            
            // Make token request
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($curl_error) {
                throw new Exception('cURL Error: ' . $curl_error);
            }
            
            if ($http_code !== 200) {
                throw new Exception('HTTP Error ' . $http_code . ': ' . $response);
            }
            
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response: ' . json_last_error_msg());
            }
            
            if (isset($result['error'])) {
                throw new Exception('Token exchange failed: ' . $result['message']);
            }
            
            if (!isset($result['access_token'])) {
                throw new Exception('No access token in response: ' . json_encode($result));
            }
            
            // Save tokens to database
            dm_settings_set('shopee', 'access_token', $result['access_token']);
            if (isset($result['refresh_token'])) {
                dm_settings_set('shopee', 'refresh_token', $result['refresh_token']);
            }
            if (isset($result['expire_in'])) {
                $expires_at = time() + (int)$result['expire_in'];
                dm_settings_set('shopee', 'expires_at', (string)$expires_at);
            }
            dm_settings_set('shopee', 'shop_id', (string)$shop_id);
            dm_settings_set('shopee', 'enabled', 'true');
            
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'>";
            echo "<strong>🎉 Token Exchange Successful!</strong><br>";
            echo "Access Token: " . substr($result['access_token'], 0, 20) . "...<br>";
            echo "Expires in: " . ($result['expire_in'] ?? 'N/A') . " seconds<br>";
            echo "Shop ID saved: " . $shop_id;
            echo "</div>";
            
            echo "<div class='space-y-2'>";
            echo "<a href='settings.php' class='inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>กลับไปหน้าตั้งค่า</a>";
            echo "<a href='index.php' class='inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-2'>ไปหน้า Dashboard</a>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>";
            echo "<strong>❌ Token Exchange Failed:</strong><br>";
            echo htmlspecialchars($e->getMessage());
            echo "</div>";
            
            echo "<div class='bg-gray-100 p-4 rounded mb-4'>";
            echo "<strong>Debug Information:</strong><br>";
            echo "Code: " . htmlspecialchars($code) . "<br>";
            echo "Shop ID: " . htmlspecialchars($shop_id) . "<br>";
            echo "Partner ID: " . htmlspecialchars($config['partner_id'] ?? 'NOT SET') . "<br>";
            echo "</div>";
            
            echo "<a href='settings.php' class='bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>กลับไปหน้าตั้งค่า</a>";
        }
        ?>
        
        <div class="mt-6 p-4 bg-gray-50 rounded">
            <h3 class="font-semibold mb-2">ขั้นตอนถัดไป:</h3>
            <ol class="list-decimal list-inside text-sm space-y-1">
                <li>กลับไปที่หน้าตั้งค่าเพื่อทดสอบการเชื่อมต่อ</li>
                <li>หากสำเร็จ สามารถใช้งาน Dashboard ได้</li>
                <li>Access Token จะหมดอายุใน 4 ชั่วโมง</li>
                <li>ระบบจะ refresh token อัตโนมัติ</li>
            </ol>
        </div>
    </div>
</body>
</html>
