<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Lazada API</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Debug Lazada API</h1>
        
        <?php
        include_once 'api.php';
        
        try {
            $config = getAPIConfig()['lazada'];
            $api = createPlatformAPI('lazada', $config);
            
            echo "<div class='bg-white p-6 rounded-lg shadow mb-6'>";
            echo "<h3 class='text-xl font-bold mb-4'>Configuration</h3>";
            echo "<pre class='bg-gray-100 p-4 rounded text-sm overflow-x-auto'>";
            echo "API URL: " . $config['api_url'] . "\n";
            echo "App Key: " . ($config['app_key'] ? substr($config['app_key'], 0, 6) . "..." : "Not set") . "\n";
            echo "App Secret: " . ($config['app_secret'] ? substr($config['app_secret'], 0, 6) . "..." : "Not set") . "\n";
            echo "Access Token: " . ($config['access_token'] ? substr($config['access_token'], 0, 20) . "..." : "Not set") . "\n";
            echo "</pre>";
            echo "</div>";
            
            echo "<div class='bg-white p-6 rounded-lg shadow mb-6'>";
            echo "<h3 class='text-xl font-bold mb-4'>Test Orders API</h3>";
            
            $orders_result = $api->getOrders();
            echo "<pre class='bg-gray-100 p-4 rounded text-sm overflow-x-auto'>";
            echo json_encode($orders_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo "</pre>";
            echo "</div>";
            
            echo "<div class='bg-white p-6 rounded-lg shadow mb-6'>";
            echo "<h3 class='text-xl font-bold mb-4'>Test Products API</h3>";
            
            $products_result = $api->getProducts();
            echo "<pre class='bg-gray-100 p-4 rounded text-sm overflow-x-auto'>";
            echo json_encode($products_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo "</pre>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>";
            echo "<strong>Error:</strong> " . $e->getMessage();
            echo "</div>";
        }
        ?>
        
        <div class="mt-8">
            <a href="index.php" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                กลับไปหน้าหลัก
            </a>
        </div>
    </div>
</body>
</html>
