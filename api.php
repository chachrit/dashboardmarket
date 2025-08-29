<?php
// Production-ready error handling
$isProduction = false; // Force development mode for debugging

if ($isProduction) {
    // Production: Hide errors from output but log them
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else {
    // Development: Show all errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Load config if exists
$config = [];
if (file_exists(__DIR__ . '/config.php')) {
    $config = require __DIR__ . '/config.php';
}

// Global error/exception handler for JSON API
set_exception_handler(function($e) use ($isProduction) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    if ($isProduction) {
        // Production: Generic error message
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'code' => $e->getCode()
        ]);
        // Log detailed error
        error_log("Dashboard Market Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    } else {
        // Development: Detailed error
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    exit;
});

set_error_handler(function($errno, $errstr, $errfile, $errline) use ($isProduction) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    if ($isProduction) {
        echo json_encode([
            'success' => false,
            'error' => 'Server error occurred'
        ]);
        error_log("Dashboard Market Error: $errstr in $errfile:$errline");
    } else {
        echo json_encode([
            'success' => false,
            'error' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]);
    }
    exit;
});
require_once __DIR__ . '/db.php';
// Ensure all server-side date/time uses Thailand timezone (UTC+7)
date_default_timezone_set('Asia/Bangkok');
// Lazada / Multi-platform API Gateway (Real Lazada + Shopee integration)
// NOTE: เก็บ App Key / Secret / Tokens ให้ปลอดภัย (ควรย้ายไป .env / DB ในโปรดักชั่น)

// ------------------------------
// cURL Helpers (Windows/IIS friendly defaults)
// ------------------------------
function dm_find_ca_bundle(){
    // Priority: env -> php.ini -> project assets
    $dm = getenv('DM_CA_BUNDLE');
    if ($dm && is_file($dm)) return $dm;
    $env = getenv('CURL_CA_BUNDLE');
    if ($env && is_file($env)) return $env;
    $ini = ini_get('curl.cainfo');
    if ($ini && is_file($ini)) return $ini;
    $candidates = [
        __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'cacert.pem',
        __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem'
    ];
    foreach ($candidates as $p) { if (is_file($p)) return $p; }
    return null;
}
function dm_curl_defaults(array $opts){
    $defaults = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => isset($opts[CURLOPT_TIMEOUT]) ? $opts[CURLOPT_TIMEOUT] : 30,
        CURLOPT_USERAGENT      => 'dashboardmarket/1.0 (+PHP '.PHP_VERSION.'; '.php_uname('s').')',
    ];
    if (defined('CURL_IPRESOLVE_V4')) $defaults[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4; // avoid IPv6 issues on some hosts
    // Only force TLS1.2 if explicitly requested (default: let cURL negotiate)
    if (getenv('DM_FORCE_TLS12') === '1' && defined('CURL_SSLVERSION_TLSv1_2')) {
        $defaults[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
    }
    $defaults[CURLOPT_SSL_VERIFYHOST] = 2; $defaults[CURLOPT_SSL_VERIFYPEER] = true;
    // Optionally use Windows native trust store when available (Schannel backend)
    if (getenv('DM_CURL_TRUST_WIN') === '1' && defined('CURLOPT_SSL_OPTIONS') && defined('CURLSSLOPT_NATIVE_CA')) {
        $defaults[CURLOPT_SSL_OPTIONS] = (isset($defaults[CURLOPT_SSL_OPTIONS]) ? $defaults[CURLOPT_SSL_OPTIONS] : 0) | CURLSSLOPT_NATIVE_CA;
    }
    // Proxy (optional) — disabled by default to avoid unintended system-wide proxy
    // Enable only when DM_USE_PROXY=1 is set (or caller passes explicit CURLOPT_PROXY in $opts)
    if (getenv('DM_USE_PROXY') === '1') {
        $proxy = getenv('HTTPS_PROXY') ?: getenv('HTTP_PROXY');
        if ($proxy) $defaults[CURLOPT_PROXY] = $proxy;
    }
    // CA bundle if available
    $ca = dm_find_ca_bundle(); if ($ca) $defaults[CURLOPT_CAINFO] = $ca;
    // Verbose/insecure debug toggles via env
    if (getenv('DM_CURL_VERBOSE') === '1') $defaults[CURLOPT_VERBOSE] = true;
    if (getenv('DM_CURL_INSECURE') === '1') { $defaults[CURLOPT_SSL_VERIFYPEER] = false; error_log('[dashboardmarket] WARNING: SSL verification disabled via DM_CURL_INSECURE'); }
    // Merge user opts last to allow explicit overrides
    return $opts + $defaults;
}

// Diagnostic: probe a URL and return connection details
function dm_curl_probe_run($url, $resolveHost = null, $resolveIp = null){
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_NOBODY => false,
        CURLOPT_HTTPGET => true,
        CURLOPT_HEADER => false,
    ];
    if ($resolveHost && $resolveIp) {
        $parts = parse_url($url);
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : 'https';
        $port = ($scheme === 'https') ? 443 : (($scheme === 'http') ? 80 : 443);
        $opts[CURLOPT_RESOLVE] = ["{$resolveHost}:{$port}:{$resolveIp}"];
    }
    if (defined('CURLOPT_CERTINFO')) { $opts[CURLOPT_CERTINFO] = true; }
    curl_setopt_array($ch, dm_curl_defaults($opts));
    $body = curl_exec($ch);
    if ($body === false) {
        $error_msg = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [
            'http_code' => $http_code,
            'error' => 'cURL Error: ' . $error_msg,
            'body_sample' => null
        ];
    }
    $info = [
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        'primary_ip' => curl_getinfo($ch, CURLINFO_PRIMARY_IP),
        'local_ip' => (defined('CURLINFO_LOCAL_IP') ? (curl_getinfo($ch, CURLINFO_LOCAL_IP) ?: null) : null),
        'total_time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
        'namelookup_time' => curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME),
        'connect_time' => curl_getinfo($ch, CURLINFO_CONNECT_TIME),
        'appconnect_time' => curl_getinfo($ch, CURLINFO_APPCONNECT_TIME),
        'pretransfer_time' => curl_getinfo($ch, CURLINFO_PRETRANSFER_TIME),
        'redirect_count' => curl_getinfo($ch, CURLINFO_REDIRECT_COUNT),
        'redirect_time' => curl_getinfo($ch, CURLINFO_REDIRECT_TIME),
        'ssl_verify_result' => curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT),
        'error' => ($e = curl_error($ch)) ? $e : null,
        'body_sample' => is_string($body) ? substr($body,0,200) : null,
    ];
    if (defined('CURLINFO_CERTINFO')) {
        $certInfo = curl_getinfo($ch, CURLINFO_CERTINFO);
        if ($certInfo && is_array($certInfo)) {
            // Include only subject and issuer lines if present
            $parsed = [];
            foreach ($certInfo as $idx => $ci) {
                $lines = explode("\n", $ci['Cert'] ?? '');
                $keep = [];
                foreach ($lines as $ln) {
                    if (stripos($ln,'Subject:')!==false || stripos($ln,'Issuer:')!==false) $keep[] = trim($ln);
                }
                if ($keep) $parsed[] = $keep;
            }
            if ($parsed) $info['cert_info'] = $parsed;
        }
    }
    curl_close($ch);
    return $info;
}

// ------------------------------
// Database-based Order Functions
// ------------------------------

/**
 * ดึงข้อมูลออเดอร์จากฐานข้อมูลแทนการยิง API
 */
function getOrdersFromDatabase($platform, $date_from = null, $date_to = null, $limit = 100) {
    $pdo = dm_db();
    
    if (!$date_from) $date_from = date('Y-m-d');
    if (!$date_to) $date_to = date('Y-m-d');
    
    $sql = "SELECT * FROM orders 
            WHERE platform = ? 
            AND created_at >= ? 
            AND created_at <= ?
            ORDER BY created_at DESC 
            LIMIT ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $platform,
        $date_from . ' 00:00:00',
        $date_to . ' 23:59:59',
        $limit
    ]);
    
    $orders = [];
    $totalSales = 0;
    
    while ($row = $stmt->fetch()) {
        $items = json_decode($row['items'], true) ?: [];
        
        $orders[] = [
            'platform' => $row['platform'],
            'order_id' => $row['order_id'],
            'amount' => (float)$row['amount'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'items' => $items,
            'product' => !empty($items) ? $items[0]['name'] : 'N/A' // for backward compatibility
        ];
        $totalSales += (float)$row['amount'];
    }
    
    return [
        'total_sales' => $totalSales,
        'total_orders' => count($orders),
        'orders' => $orders,
        'source' => 'database'
    ];
}

/**
 * ตรวจสอบว่าข้อมูลในฐานข้อมูลเก่าหรือไม่
 */
function isDatabaseDataFresh($platform, $maxAgeMinutes = 30) {
    $pdo = dm_db();
    
    $sql = "SELECT MAX(fetched_at) as last_fetch FROM orders WHERE platform = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$platform]);
    $result = $stmt->fetch();
    
    if (!$result || !$result['last_fetch']) {
        return false; // ไม่มีข้อมูลเลย
    }
    
    $lastFetch = (int)$result['last_fetch'];
    $now = time();
    $ageMinutes = ($now - $lastFetch) / 60;
    
    return $ageMinutes <= $maxAgeMinutes;
}

/**
 * ดึงสถิติรวมจากฐานข้อมูล
 */
function getDatabaseStats($platform, $date_from = null, $date_to = null) {
    $pdo = dm_db();
    
    if (!$date_from) $date_from = date('Y-m-d');
    if (!$date_to) $date_to = date('Y-m-d');
    
    $sql = "SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(amount), 0) as total_sales,
                MAX(fetched_at) as last_fetch_time
            FROM orders 
            WHERE platform = ?
            AND created_at >= ?
            AND created_at <= ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $platform,
        $date_from . ' 00:00:00',
        $date_to . ' 23:59:59'
    ]);
    
    $stats = $stmt->fetch();
    
    return [
        'totalOrders' => (int)$stats['total_orders'],
        'totalSales' => (float)$stats['total_sales'],
        'lastFetchTime' => $stats['last_fetch_time'] ? date('Y-m-d H:i:s', $stats['last_fetch_time']) : null,
        'source' => 'database'
    ];
}

// ------------------------------
// Config Loader
// ------------------------------
function getAPIConfig() {
    // Lazada
    $laz = dm_settings_get_all('lazada');
    $lazada = [
        'app_key'       => $laz['app_key']       ?? '',
        'app_secret'    => $laz['app_secret']    ?? '',
        'access_token'  => $laz['access_token']  ?? '',
        'refresh_token' => $laz['refresh_token'] ?? '',
        'expires_in'    => (int)($laz['expires_in'] ?? 0),
        'refreshed_at'  => (int)($laz['refreshed_at'] ?? 0),
        'api_url'       => 'https://api.lazada.co.th/rest',
        'auth_url'      => 'https://auth.lazada.com/oauth/authorize'
    ];

    // Shopee
    $shp = dm_settings_get_all('shopee');
    $shopeeEnv = $shp['env'] ?? 'prod';
    $shopeeBase = ($shopeeEnv === 'sandbox')
        ? 'https://openplatform.sandbox.test-stable.shopee.sg'
        : 'https://partner.shopeemobile.com';
    $shopee = [
        'env'           => $shopeeEnv,
        'partner_id'    => $shp['partner_id']    ?? '',
        'partner_key'   => $shp['partner_key']   ?? '',
        'shop_id'       => $shp['shop_id']       ?? '',
        'access_token'  => $shp['access_token']  ?? '',
        'refresh_token' => $shp['refresh_token'] ?? '',
        'expires_at'    => (int)($shp['expires_at'] ?? 0),
        'api_url'       => $shopeeBase,
    ];

    // TikTok (stub)
    $tt = dm_settings_get_all('tiktok');
    $tiktok = [
        'client_key'    => $tt['client_key']    ?? '',
        'client_secret' => $tt['client_secret'] ?? '',
        'access_token'  => $tt['access_token']  ?? '',
        'refresh_token' => $tt['refresh_token'] ?? '',
        'enabled'       => isset($tt['enabled']) ? filter_var($tt['enabled'], FILTER_VALIDATE_BOOLEAN) : true,
    ];

    return [ 'lazada'=>$lazada, 'shopee'=>$shopee, 'tiktok'=>$tiktok ];
}

// ------------------------------
// Helper: Save Lazada tokens back to cookies (demo only)
// ------------------------------
function saveLazadaTokens($data) {
    if (isset($data['access_token'])) dm_settings_set('lazada','access_token',$data['access_token']);
    if (isset($data['refresh_token'])) dm_settings_set('lazada','refresh_token',$data['refresh_token']);
    if (isset($data['expires_in'])) dm_settings_set('lazada','expires_in',(string)$data['expires_in']);
    dm_settings_set('lazada','refreshed_at',(string)time());
}
// Helper: Save tokens and expiry for any platform
function savePlatformTokens($platform, $data) {
    if(isset($data['access_token'])) dm_settings_set($platform,'access_token',$data['access_token']);
    if(isset($data['refresh_token'])) dm_settings_set($platform,'refresh_token',$data['refresh_token']);
    if(isset($data['expire_in'])) dm_settings_set($platform,'expires_at', (string)(time() + (int)$data['expire_in']));
}

// Backward compatibility
function saveShopeeTokens($data){ savePlatformTokens('shopee', $data); }
function saveTikTokTokens($data){ savePlatformTokens('tiktok', $data); }

// ------------------------------
// Base Platform Class
// ------------------------------
class PlatformAPI {
    protected $platform;
    protected $config;
    public function __construct($platform, $config) { $this->platform = $platform; $this->config = $config; }
    // Default stubs
    public function getOrders($date_from=null,$date_to=null,$limit=100){ throw new Exception('Not implemented for '.$this->platform); } // เพิ่มเป็น 100 สำหรับข้อมูลจริง
    public function getProducts($limit=100){ return []; } // เพิ่มจาก 50 เป็น 100
}

// ------------------------------
// Shopee API Wrapper (subset)
// ------------------------------
class ShopeeAPI extends PlatformAPI {
    private function requireCreds(){
        foreach(['partner_id','partner_key','shop_id','access_token'] as $k){
            if(empty($this->config[$k])) throw new Exception('Shopee missing credential: '.$k);
        }
    }
    private function needRefresh(){
        // ถ้าไม่มี access_token หรือ expires_at ให้ถือว่าต้อง refresh
        if(empty($this->config['access_token']) || empty($this->config['expires_at'])) return true;
        // ถ้า access_token หมดอายุ (เหลือน้อยกว่า 5 นาที) ให้ refresh
        return time() >= ($this->config['expires_at'] - 300);
    }
    public function refreshAccessToken(){
        // Try to refresh access_token using refresh_token if available
        if (empty($this->config['refresh_token'])) throw new Exception('No refresh_token available');
        
        // Shopee expects a POST to /api/v2/auth/access_token/get with JSON payload
        $path = '/api/v2/auth/access_token/get';
        $urlBase = rtrim($this->config['api_url'], '/') . $path;
        // Include required query params: partner_id, timestamp and sign (try partner-level sign first)
        $ts = time();
        
        // Clean partner_id: Shopee expects numeric partner_id only. Remove any non-digits.
        $rawPartner = isset($this->config['partner_id']) ? (string)$this->config['partner_id'] : '';
        $partnerClean = preg_replace('/\D+/', '', $rawPartner);
        if (empty($partnerClean)) throw new Exception('No partner_id provided');
        
        // Validate partner_id format (should be 6-15 digits for Shopee - updated range)
        if (!is_numeric($partnerClean) || strlen($partnerClean) < 6 || strlen($partnerClean) > 15) {
            throw new Exception('Invalid partner_id format. Shopee requires 6-15 digit number, got: ' . $partnerClean . ' (length: ' . strlen($partnerClean) . ')');
        }
        
        error_log("[dashboardmarket] Shopee refresh partner_id_clean=".$partnerClean);
        
        // Many Shopee auth endpoints expect partner-level sign (no shop_id); try that first.
        $signNoShop = $this->signWithPartnerId($path, $ts, '', '', $partnerClean);
        
        // Log signature used (not the secret) to aid debugging
        error_log("[dashboardmarket] Shopee refresh signNoShop=".$signNoShop);
        $url = $urlBase . '?partner_id=' . urlencode($partnerClean) . '&timestamp=' . $ts . '&sign=' . $signNoShop;
        $payload = json_encode([
            'shop_id' => intval($this->config['shop_id']),
            'partner_id' => intval($partnerClean),
            'refresh_token' => $this->config['refresh_token']
        ]);
        $ch = curl_init();
        // Debug: log the outgoing refresh request (avoid full tokens in logs)
        $logUrl = $url;
        $logPayload = is_string($payload) ? substr($payload,0,256) : '';
        error_log("[dashboardmarket] Shopee refresh request URL=".$logUrl." payload=".$logPayload);
        curl_setopt_array($ch, dm_curl_defaults([
            CURLOPT_URL => $url,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload
        ]));
        $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    // Lightweight logging for debugging refresh issues. This writes to the PHP error log.
    // Avoid logging full tokens in production; here we log small snippets to help diagnose failures.
    $safeResp = is_string($res) ? substr($res,0,512) : '';
    error_log("[dashboardmarket] Shopee refresh HTTP={$code} err=".($err?:'none')." resp=".$safeResp);
    if ($err) throw new Exception('Shopee refresh cURL: '.$err);
        $json = json_decode($res, true); if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON from refresh');
        // response example returns access_token, refresh_token, expire_in
            if (isset($json['access_token'])){
            $respData = $json;
            // Save cookies for demo environment
            saveShopeeTokens($respData);
            // update local config so subsequent calls use the new token
            $this->config['access_token'] = $respData['access_token'];
            if(isset($respData['expire_in'])) $this->config['expires_at'] = time() + (int)$respData['expire_in'];
            if(isset($respData['refresh_token'])) $this->config['refresh_token'] = $respData['refresh_token'];
            return $respData;
        }
        $errMsg = $json['message'] ?? $json['error'] ?? json_encode($json);
    // If wrong sign, retry once using shop-level sign
    if (stripos($errMsg,'sign')!==false || stripos($errMsg,'wrong sign')!==false) {
            error_log("[dashboardmarket] Shopee refresh: wrong sign, retrying with shop-level sign");
            // recompute sign including shop_id
            $signWithShop = $this->signWithPartnerId($path, $ts, '', $this->config['shop_id'], $partnerClean);
            error_log("[dashboardmarket] Shopee refresh signWithShop=".$signWithShop);
            $url2 = $urlBase . '?partner_id=' . urlencode($partnerClean) . '&timestamp=' . $ts . '&sign=' . $signWithShop;
            // perform second attempt
            $ch2 = curl_init();
            curl_setopt_array($ch2, dm_curl_defaults([
                CURLOPT_URL => $url2,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $payload
            ]));
            $res2 = curl_exec($ch2); $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE); $err2 = curl_error($ch2); curl_close($ch2);
            $safeResp2 = is_string($res2) ? substr($res2,0,512) : '';
            error_log("[dashboardmarket] Shopee refresh retry HTTP={$code2} err=".($err2?:'none')." resp=".$safeResp2);
            if ($err2) throw new Exception('Shopee refresh cURL (retry): '.$err2);
            $json2 = json_decode($res2,true); if (json_last_error()!==JSON_ERROR_NONE) throw new Exception('Invalid JSON from refresh retry');
            if (isset($json2['access_token'])) {
                $respData = $json2;
                saveShopeeTokens($respData);
                $this->config['access_token'] = $respData['access_token'];
                if(isset($respData['expire_in'])) $this->config['expires_at'] = time() + (int)$respData['expire_in'];
                if(isset($respData['refresh_token'])) $this->config['refresh_token'] = $respData['refresh_token'];
                return $respData;
            }
            $errMsg2 = $json2['message'] ?? $json2['error'] ?? json_encode($json2);
            throw new Exception('Refresh failed (retry): '.$errMsg2);
        }
            // If error mentions partner_id format, try alternative payload format or sandbox host(s)
            if (stripos($errMsg,'partner_id')!==false || stripos($errMsg,'format')!==false) {
                error_log("[dashboardmarket] Shopee refresh: partner_id format error detected, attempting form-encoded retry and alternate host retry");
                // First try posting as application/x-www-form-urlencoded to the same URL
                $formBody = http_build_query(['shop_id'=>$this->config['shop_id'],'refresh_token'=>$this->config['refresh_token'],'partner_id'=>$partnerClean]);
                error_log("[dashboardmarket] Shopee refresh trying form-encoded to current host");
                $chf1 = curl_init();
                curl_setopt_array($chf1, dm_curl_defaults([
                    CURLOPT_URL => $url,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                    CURLOPT_POSTFIELDS => $formBody
                ]));
                $resf1 = curl_exec($chf1); $codef1 = curl_getinfo($chf1, CURLINFO_HTTP_CODE); $errf1 = curl_error($chf1); curl_close($chf1);
                $safef1 = is_string($resf1) ? substr($resf1,0,512) : '';
                error_log("[dashboardmarket] Shopee refresh form-encoded HTTP={$codef1} err=".($errf1?:'none')." resp=".$safef1);
                if (!$errf1) {
                    $jsonf1 = json_decode($resf1,true);
                    if (json_last_error()===JSON_ERROR_NONE && isset($jsonf1['access_token'])) {
                        $respData = $jsonf1;
                        saveShopeeTokens($respData);
                        $this->config['access_token'] = $respData['access_token'];
                        if(isset($respData['expire_in'])) $this->config['expires_at'] = time() + (int)$respData['expire_in'];
                        if(isset($respData['refresh_token'])) $this->config['refresh_token'] = $respData['refresh_token'];
                        return $respData;
                    }
                }
                error_log("[dashboardmarket] Shopee refresh form-encoded attempt failed, trying alternate hosts");
                $fallbackHosts = [
                    // alternative sandbox host sometimes used
                    'https://partner.test-stable.shopeemobile.com',
                    'https://openplatform.sandbox.test-stable.shopee.sg'
                ];
                foreach ($fallbackHosts as $host) {
                    // skip current host
                    if (stripos($urlBase, $host) !== false) continue;
                    $tryUrlBase = rtrim($host, '/') . $path;
                    $tryUrl = $tryUrlBase . '?partner_id=' . urlencode($partnerClean) . '&timestamp=' . $ts . '&sign=' . $signNoShop;
                    error_log("[dashboardmarket] Shopee refresh trying alternate host=".$tryUrl);
                    $chf = curl_init();
                    curl_setopt_array($chf, dm_curl_defaults([
                        CURLOPT_URL => $tryUrl,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_POST => true,
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                        CURLOPT_POSTFIELDS => $payload
                    ]));
                    $resf = curl_exec($chf); $codef = curl_getinfo($chf, CURLINFO_HTTP_CODE); $errf = curl_error($chf); curl_close($chf);
                    $safef = is_string($resf) ? substr($resf,0,512) : '';
                    error_log("[dashboardmarket] Shopee refresh alt HTTP={$codef} err=".($errf?:'none')." resp=".$safef);
                    if ($errf) continue;
                    $jsonf = json_decode($resf,true);
                    if (json_last_error()!==JSON_ERROR_NONE) continue;
                    if (isset($jsonf['access_token'])) {
                        $respData = $jsonf;
                        saveShopeeTokens($respData);
                        $this->config['access_token'] = $respData['access_token'];
                        if(isset($respData['expire_in'])) $this->config['expires_at'] = time() + (int)$respData['expire_in'];
                        if(isset($respData['refresh_token'])) $this->config['refresh_token'] = $respData['refresh_token'];
                        return $respData;
                    }
                }
            }
            throw new Exception('Refresh failed: '.$errMsg);
    }
    private function sign($path,$timestamp,$accessToken='',$shopId=''){
        // base string = partner_id + path + timestamp + access_token + shop_id (shop level)
        $base = $this->config['partner_id'].$path.$timestamp.$accessToken.$shopId;
        return hash_hmac('sha256',$base,$this->config['partner_key']);
    }
    
    private function signWithPartnerId($path,$timestamp,$accessToken='',$shopId='',$partnerId=''){
        // base string = partner_id + path + timestamp + access_token + shop_id (shop level)
        $usePartnerId = $partnerId ?: $this->config['partner_id'];
        $base = $usePartnerId.$path.$timestamp.$accessToken.$shopId;
        return hash_hmac('sha256',$base,$this->config['partner_key']);
    }
    private function httpGet($url){
    $ch=curl_init(); curl_setopt_array($ch, dm_curl_defaults([CURLOPT_URL=>$url, CURLOPT_TIMEOUT=>30]));
        $res=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
        if($err) throw new Exception('Shopee cURL: '.$err);
        $json=json_decode($res,true); if(json_last_error()!=JSON_ERROR_NONE) throw new Exception('Shopee JSON error');
        if($code!==200) throw new Exception('Shopee HTTP '.$code.': '.$res);
        if(isset($json['error']) && $json['error']) throw new Exception('Shopee API error: '.($json['message']??$json['error']));
        return $json;
    }
    private function callGet($path,$query){
        $this->requireCreds();
        // ตรวจสอบอายุ access_token และ refresh อัตโนมัติถ้าจำเป็น
        if($this->needRefresh()){
            try {
                $newTokens = $this->refreshAccessToken();
                // อัปเดต config ใน instance ทันที
                if(isset($newTokens['access_token'])) $this->config['access_token'] = $newTokens['access_token'];
                if(isset($newTokens['refresh_token'])) $this->config['refresh_token'] = $newTokens['refresh_token'];
                if(isset($newTokens['expire_in'])) $this->config['expires_at'] = time() + (int)$newTokens['expire_in'];
            } catch(Exception $e) { /* allow call to fail later with invalid token */ }
        }
        $ts=time();
        $sign = $this->sign($path,$ts,$this->config['access_token'],$this->config['shop_id']);
        $baseParams=[
            'partner_id'=>$this->config['partner_id'],
            'timestamp'=>$ts,
            'access_token'=>$this->config['access_token'],
            'shop_id'=>$this->config['shop_id'],
            'sign'=>$sign
        ];
        $qs = http_build_query(array_merge($baseParams,$query));
        return $this->httpGet($this->config['api_url'].$path.'?'.$qs);
    }
    public function getOrders($date_from=null,$date_to=null,$limit=100,$summaryOnly=false){ // เพิ่มเป็น 100 และ summaryOnly
        // Convert date range to epoch (Shopee requires unix)
        if(!$date_from || !$date_to){
            $date_from_ts = strtotime(date('Y-m-d 00:00:00')); // today start local
            $date_to_ts = strtotime(date('Y-m-d 23:59:59')); // today end local
        } else {
            $date_from_ts = strtotime($date_from);
            $date_to_ts = strtotime($date_to);
        }
        
        // Calculate optimal pageSize - Shopee max 50 per request, ใช้ batch processing
        $pageSize = min($limit, 50);
        $batchesNeeded = min(ceil($limit / 50), 40); // จำกัด 40 batches (100 orders max)
        
        // Build query
        $query = [
            'time_from'=>$date_from_ts,
            'time_to'=>$date_to_ts,
            'page_size'=>$pageSize,
            'time_range_field'=>'create_time'
        ];
        
        $allOrderSnList = [];
        $currentCursor = '';
        
        // ใช้ pagination เพื่อดึงข้อมูล 100 orders
        for ($batch = 0; $batch < $batchesNeeded; $batch++) {
            try {
                if ($batch > 0 && $currentCursor) {
                    $query['cursor'] = $currentCursor;
                }
                
                $resp = $this->callGet('/api/v2/order/get_order_list', $query);
                $orderSnList = $resp['response']['order_list'] ?? [];
                
                if (empty($orderSnList)) break;
                
                $allOrderSnList = array_merge($allOrderSnList, $orderSnList);
                
                // Check if there are more pages
                $hasMore = $resp['response']['more'] ?? false;
                $currentCursor = $resp['response']['next_cursor'] ?? '';
                
                if (!$hasMore || !$currentCursor) break;
                
                // หยุดถ้าได้ครบตามที่ต้องการแล้ว
                if (count($allOrderSnList) >= $limit) {
                    $allOrderSnList = array_slice($allOrderSnList, 0, $limit);
                    break;
                }
                
            } catch (Exception $e) {
                error_log("[Shopee getOrders] Batch $batch failed: " . $e->getMessage());
                break;
            }
        }
        
        if (empty($allOrderSnList)) {
            return ['total_sales'=>0,'total_orders'=>0,'orders'=>[]];
        }
        
        // ถ้าเป็น summary mode ยังต้องดึง order details เพราะ Shopee ไม่มี total_amount ใน order_list
        // แต่จะใช้ pagination เพื่อดึงข้อมูล 100+ orders อย่างมีประสิทธิภาพ
        if ($summaryOnly) {
            $totalSales = 0;
            $totalOrders = count($orderSnList);
            $currentCursor = '';
            $pagesProcessed = 0;
            $maxPages = 40; // จำกัด 40 pages (40 * 50 = 100 orders max)
            
            do {
                // ดึง order details แบบ batch 50 orders per request
                $sns = array_slice(array_map(fn($o)=>$o['order_sn'],$orderSnList), $pagesProcessed * 50, 50);
                if (empty($sns)) break;
                
                try {
                    $detailResp = $this->callGet('/api/v2/order/get_order_detail',[
                        'order_sn_list'=>implode(',',$sns),
                        'response_optional_fields'=>'total_amount' // เอาแค่ total_amount เพื่อความเร็ว
                    ]);
                    
                    $details = $detailResp['response']['order_list'] ?? [];
                    foreach($details as $order) {
                        $totalSales += (float)($order['total_amount'] ?? 0);
                    }
                    
                } catch (Exception $e) {
                    error_log("[Shopee getOrders] Summary mode batch failed: " . $e->getMessage());
                    break;
                }
                
                $pagesProcessed++;
                
                // ถ้ามี orders เพิ่มเติม ดึงต่อ
                if (count($orderSnList) > ($pagesProcessed * 50) && $pagesProcessed < $maxPages) {
                    // มี orders เหลือ ดึงหน้าถัดไป
                    try {
                        $nextPageResp = $this->callGet('/api/v2/order/get_order_list', [
                            'time_range_field' => 'create_time',
                            'time_from' => $date_from_ts,
                            'time_to' => $date_to_ts,
                            'page_size' => 50,
                            'cursor' => $currentCursor
                        ]);
                        
                        $nextOrders = $nextPageResp['response']['order_list'] ?? [];
                        $currentCursor = $nextPageResp['response']['more'] ? ($nextPageResp['response']['next_cursor'] ?? '') : '';
                        
                        if (!empty($nextOrders)) {
                            $orderSnList = array_merge($orderSnList, $nextOrders);
                            $totalOrders = count($orderSnList);
                        }
                        
                        if (!$hasMore || !$currentCursor) break;
                        
                    } catch (Exception $e) {
                        error_log("[Shopee getOrders] Summary pagination failed: " . $e->getMessage());
                        break;
                    }
                }
                
            } while ($hasMore && $currentCursor && $pagesProcessed < $maxPages);
            
            error_log("[Shopee getOrders] Summary mode: Orders=$totalOrders, Sales=$totalSales, Pages processed: $pagesProcessed");
            return ['total_sales'=>$totalSales,'total_orders'=>$totalOrders,'orders'=>[]];
        }
        
        // Full mode - ดึงรายละเอียด order (จำกัดเฉพาะที่จำเป็นเพื่อความเร็ว)
        $sns = array_map(fn($o)=>$o['order_sn'], array_slice($allOrderSnList, 0, min(200, count($allOrderSnList)))); // จำกัด 200 orders สำหรับ full mode
        
        try {
            $detailResp = $this->callGet('/api/v2/order/get_order_detail',[
                'order_sn_list'=>implode(',',$sns),
                'response_optional_fields'=>'item_list,total_amount,order_status,create_time'
            ]);
        } catch (Exception $e) {
            error_log("[Shopee getOrders] Failed to get order details: " . $e->getMessage());
            throw $e;
        }
        
        $details = $detailResp['response']['order_list'] ?? [];
        $out=[]; $totalSales=0; $totalOrders=0;
        foreach($details as $o){
            $items = [];
            if(isset($o['item_list']) && is_array($o['item_list'])){
                foreach($o['item_list'] as $item){
                    $items[] = [
                        'name' => $item['item_name'] ?? 'N/A',
                        'quantity' => $item['model_quantity_purchased'] ?? 1
                    ];
                }
            }
            $out[] = [
                'platform' => 'shopee',
                'order_id' => $o['order_sn'],
                'amount' => $o['total_amount'],
                'created_at' => date('c', $o['create_time']),
                'items' => $items,
                'status' => $o['order_status'] ?? 'N/A'
            ];
            $totalSales += (float)$o['total_amount'];
            $totalOrders++;
        }
        
        // Debug logging with more details  
        error_log("[Shopee getOrders] API Response: AllOrderSnList count=" . count($allOrderSnList) . ", Details count=" . count($details) . ", Final orders count=" . count($out));
        error_log("[Shopee getOrders] Total Sales: $totalSales, Total Orders: $totalOrders, Limit requested: $limit");
        
        return ['total_sales'=>$totalSales,'total_orders'=>$totalOrders,'orders'=>$out];
    }
    public function testConnection($params=[]){
        try {
            $this->requireCreds();
            
            // Validate partner_id format first
            $rawPartner = isset($this->config['partner_id']) ? (string)$this->config['partner_id'] : '';
            $partnerClean = preg_replace('/\D+/', '', $rawPartner);
            
            // Shopee Partner ID can be 6-10 digits (from screenshot: Test=1183136, Live=2012442)
            if (empty($partnerClean) || !is_numeric($partnerClean)) {
                return ['success'=>false, 'message'=>'Shopee Partner ID ไม่ถูกต้อง: ต้องเป็นตัวเลขเท่านั้น (ปัจจุบัน: ' . $rawPartner . ')'];
            }
            
            if (strlen($partnerClean) < 6 || strlen($partnerClean) > 10) {
                return ['success'=>false, 'message'=>'Shopee Partner ID ไม่ถูกต้อง: ต้องเป็นตัวเลข 6-10 หลัก (ปัจจุบัน: ' . $partnerClean . ' มี ' . strlen($partnerClean) . ' หลัก)'];
            }
            
            // Common invalid Partner IDs to reject early (but allow valid ones like 2012442)
            $invalidIds = ['1234567890', '0000000000', '1111111111', '123456'];
            if (in_array($partnerClean, $invalidIds) || $partnerClean < 100000) {
                return ['success'=>false, 'message'=>'Shopee Partner ID ไม่ถูกต้อง: ' . $partnerClean . ' ไม่ใช่ Partner ID จริง กรุณาตรวจสอบจาก Shopee Open Platform'];
            }
            
            // Validate other required fields
            if (empty($this->config['partner_key'])) {
                return ['success'=>false, 'message'=>'Shopee Partner Key (Secret) ขาดหายไป'];
            }
            if (empty($this->config['shop_id'])) {
                return ['success'=>false, 'message'=>'Shopee Shop ID ขาดหายไป'];  
            }
            if (empty($this->config['access_token'])) {
                return ['success'=>false, 'message'=>'Shopee Access Token ขาดหายไป - กรุณาทำ OAuth authorization ก่อน'];
            }
            
            // Try a simple test: get shop info. If it works, connection is OK.
            $res = $this->callGet('/api/v2/shop/get_shop_info',[]);
            if(isset($res['response']['shop_name'])){
                return ['success'=>true, 'message'=>'เชื่อมต่อ Shopee สำเร็จ: '.$res['response']['shop_name']];
            }
            $error_msg = isset($res['message']) ? $res['message'] : (isset($res['error']) ? $res['error'] : 'Unknown error');
            return ['success'=>false, 'message'=>'Shopee Test failed: ' . $error_msg];
        } catch (Exception $e) {
            return ['success'=>false, 'message'=>'Shopee Test error: ' . $e->getMessage()];
        }
    }
}

// ------------------------------
// Lazada API Wrapper (subset)
// ------------------------------
class LazadaAPI extends PlatformAPI {
    private function requireCreds(){
        foreach(['app_key','app_secret'] as $k){
            if(empty($this->config[$k])) throw new Exception('Lazada missing credential: '.$k);
        }
    }
    private function requireCredsWithToken(){
        foreach(['app_key','app_secret','access_token'] as $k){
            if(empty($this->config[$k])) throw new Exception('Lazada missing credential: '.$k);
        }
    }
    private function sign($path,$params){
        // Lazada signature algorithm:
        // 1. Sort parameters by key
        ksort($params);
        
        // 2. Create query string (key=value&key=value...)
        $stringToSign = '';
        foreach($params as $k=>$v) {
            $stringToSign .= $k . $v;
        }
        
        // 3. Build final string: API_PATH + sorted_params + API_SECRET
        $signString = $path . $stringToSign;
        
        // 4. HMAC-SHA256 with app_secret
        $signature = hash_hmac('sha256', $signString, $this->config['app_secret']);
        
        // 5. Return uppercase
        return strtoupper($signature);
    }
    private function httpGet($url,$params){
        $qs = http_build_query($params);
        $fullUrl = $url . '?' . $qs;
        
        $ch=curl_init(); 
        curl_setopt_array($ch, dm_curl_defaults([
            CURLOPT_URL => $fullUrl, 
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (compatible; LazadaBot/1.0)'
            ]
        ]));
        
        $res=curl_exec($ch); 
        $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); 
        $err=curl_error($ch); 
        curl_close($ch);
        
        if($err) throw new Exception('Lazada cURL: '.$err);
        
        $json=json_decode($res,true); 
        if(json_last_error()!=JSON_ERROR_NONE) {
            throw new Exception('Lazada JSON error: ' . json_last_error_msg() . ' - Response: ' . substr($res, 0, 200));
        }
        
        // Check for HTTP errors
        if($code !== 200) {
            $errorMsg = isset($json['message']) ? $json['message'] : 'HTTP Error ' . $code;
            throw new Exception('Lazada HTTP error (' . $code . '): ' . $errorMsg);
        }
        
        // Check for API errors but don't throw exception here, let caller handle it
        if(isset($json['code']) && $json['code'] !== '0' && $json['code'] !== 0) {
            // Log the error but return the response for caller to handle
            error_log('Lazada API returned error code: ' . $json['code'] . ' - ' . ($json['message'] ?? 'Unknown'));
        }
        
        return $json;
    }
    private function callGet($path,$queryParams){
        $this->requireCredsWithToken(); // ต้องมี access_token
        $timestamp = round(microtime(true)*1000); // Lazada ใช้ milliseconds
        $baseParams=[
            'app_key'=>$this->config['app_key'],
            'access_token'=>$this->config['access_token'],
            'timestamp'=>$timestamp,
            'sign_method'=>'sha256'
        ];
        $allParams = array_merge($baseParams,$queryParams);
        $allParams['sign'] = $this->sign($path,$allParams);
        
        // Debug logging
        error_log("[Lazada API] Path: $path, Timestamp: $timestamp");
        error_log("[Lazada API] Params: " . json_encode($allParams));
        
        // Build the full URL with path
        $fullUrl = rtrim($this->config['api_url'], '/') . $path;
        return $this->httpGet($fullUrl,$allParams);
    }
    
    private function callGetPublic($path,$queryParams){
        $this->requireCreds(); // เช็คแค่ app_key และ app_secret
        $timestamp = round(microtime(true)*1000); // Lazada ใช้ milliseconds
        $baseParams=[
            'app_key'=>$this->config['app_key'],
            'timestamp'=>$timestamp,
            'sign_method'=>'sha256'
        ];
        $allParams = array_merge($baseParams,$queryParams);
        $allParams['sign'] = $this->sign($path,$allParams);
        
        // Debug logging
        error_log("[Lazada Public API] Path: $path, Timestamp: $timestamp");
        error_log("[Lazada Public API] Params: " . json_encode($allParams));
        
        // Build the full URL with path
        $fullUrl = rtrim($this->config['api_url'], '/') . $path;
        return $this->httpGet($fullUrl,$allParams);
    }
    public function getOrders($date_from=null,$date_to=null,$limit=100,$summaryOnly=false){ // เพิ่มเป็น 100 และ summaryOnly
        if(!$date_from || !$date_to){
            // Lazada API ต้องการ ISO 8601 format
            $date_from = date('c', strtotime('-7 days')); // 7 วันที่แล้ว
            $date_to = date('c'); // วันนี้
        }
        
        try {
            // ใช้ Lazada API v2 format
            $resp = $this->callGet('/orders/get',[
                'created_after' => $date_from,
                'created_before' => $date_to,
                'limit' => $limit,
                'sort_by' => 'created_at',
                'sort_direction' => 'DESC'
            ]);
            
            $orders = $resp['data']['orders'] ?? [];
            $out=[]; $totalSales=0; $totalOrders=0;
            
            foreach($orders as $o){
                $items = [];
                
                // ใช้ order items API
                try {
                    $detailResp = $this->callGet('/order/items/get', [
                        'order_id' => $o['order_id'] ?? $o['order_number']
                    ]);
                    
                    if(isset($detailResp['data']) && is_array($detailResp['data'])){
                        foreach($detailResp['data'] as $item){
                            $items[] = [
                                'name' => $item['name'] ?? $item['item_name'] ?? 'N/A',
                                'quantity' => $item['quantity'] ?? $item['order_quantity'] ?? 1
                            ];
                        }
                    }
                } catch (Exception $e) {
                    // หากไม่สามารถดึง items ได้ให้ข้าม
                    error_log("Lazada get order items failed: " . $e->getMessage());
                }
                
                $out[] = [
                    'platform' => 'lazada',
                    'order_id' => $o['order_number'] ?? $o['order_id'],
                    'amount' => $o['price'] ?? $o['total_amount'] ?? 0,
                    'created_at' => $o['created_at'] ?? date('c'),
                    'items' => $items,
                    'status' => is_array($o['statuses'] ?? []) ? ($o['statuses'][0] ?? 'N/A') : ($o['status'] ?? 'N/A')
                ];
                $totalSales += (float)($o['price'] ?? $o['total_amount'] ?? 0);
                $totalOrders++;
            }
            
            // Debug logging
            error_log("[Lazada getOrders] Found " . count($out) . " orders, Total Sales: $totalSales, Total Orders: $totalOrders");
            
            return ['total_sales'=>$totalSales,'total_orders'=>$totalOrders,'orders'=>$out];
            
        } catch (Exception $e) {
            error_log("Lazada getOrders failed: " . $e->getMessage());
            return ['total_sales'=>0,'total_orders'=>0,'orders'=>[]];
        }
    }
    public function testConnection($params=[]){
        try {
            $this->requireCreds(); // เช็คแค่ app_key และ app_secret
            
            // ทดสอบ API endpoints ที่ไม่ต้องการ access_token ก่อน
            $publicEndpoints = [
                '/system/time/get' => 'timestamp',      // Public system endpoint
            ];
            
            // ถ้ามี access_token ให้ลอง endpoint ที่ต้องการ token
            $protectedEndpoints = [];
            if (!empty($this->config['access_token'])) {
                $protectedEndpoints = [
                    '/seller/get' => 'data',                // Seller info endpoint  
                    '/orders/get' => 'data',                // Orders endpoint
                    '/products/get' => 'data'               // Products endpoint
                ];
            }
            
            $allEndpoints = array_merge($publicEndpoints, $protectedEndpoints);
            $lastError = '';
            
            foreach ($allEndpoints as $endpoint => $checkPath) {
                try {
                    // สำหรับ public endpoints ให้ใช้ callGetPublic
                    if (array_key_exists($endpoint, $publicEndpoints)) {
                        $res = $this->callGetPublic($endpoint, []);
                    } else {
                        $res = $this->callGet($endpoint, []);
                    }
                    
                    // ตรวจสอบ response
                    if (isset($res['code'])) {
                        if ($res['code'] === '0' || $res['code'] === 0) {
                            $message = 'เชื่อมต่อ Lazada สำเร็จ (endpoint: ' . $endpoint . ')';
                            if ($endpoint === '/seller/get' && isset($res['data']['name'])) {
                                $message .= ' - ร้าน: ' . $res['data']['name'];
                            }
                            return ['success'=>true, 'message'=>$message];
                        } else {
                            $lastError = 'API Error Code: ' . $res['code'] . ' - ' . ($res['message'] ?? 'Unknown error');
                            continue;
                        }
                    }
                    
                    // ตรวจสอบ specific responses
                    if ($endpoint === '/system/time/get' && isset($res['timestamp'])) {
                        return ['success'=>true, 'message'=>'เชื่อมต่อ Lazada สำเร็จ (System Time API)'];
                    }
                    
                    if ($endpoint === '/seller/get' && isset($res['data'])) {
                        return ['success'=>true, 'message'=>'เชื่อมต่อ Lazada สำเร็จ (Seller API)'];
                    }
                    
                } catch (Exception $e) {
                    $lastError = $e->getMessage();
                    continue; // Try next endpoint
                }
            }
            
            return ['success'=>false, 'message'=>'Lazada Test failed - All endpoints failed. Last error: ' . $lastError];
        } catch (Exception $e) {
            return ['success'=>false, 'message'=>'Lazada Test error: ' . $e->getMessage()];
        }
    }
    
}

// ------------------------------
// TikTok API Wrapper (stub)
// ------------------------------
class TikTokAPI extends PlatformAPI {
    public function getOrders($date_from=null,$date_to=null,$limit=100,$summaryOnly=false){ // เพิ่มเป็น 100 และ summaryOnly
        try {
            // สำหรับ TikTok ยังไม่ได้เชื่อมต่อจริง ให้ส่งข้อมูลว่าง
            error_log("[TikTok getOrders] TikTok API not implemented yet, returning empty data");
            return ['total_sales'=>0,'total_orders'=>0,'orders'=>[]];
            
        } catch (Exception $e) {
            error_log("TikTok getOrders failed: " . $e->getMessage());
            return ['total_sales'=>0,'total_orders'=>0,'orders'=>[]];
        }
    }
    
    public function testConnection($params=[]){
        try {
            // Placeholder: In a real scenario, you'd call a simple TikTok API endpoint
            // like get_authorized_shops or similar.
            if(!empty($this->config['client_key']) && !empty($this->config['access_token'])){
                return ['success'=>true, 'message'=>'เชื่อมต่อ TikTok สำเร็จ (จำลอง)'];
            }
            return ['success'=>false, 'message'=>'TikTok credentials not set. กรุณาป้อน Client Key และ Access Token'];
        } catch (Exception $e) {
            return ['success'=>false, 'message'=>'TikTok Test error: ' . $e->getMessage()];
        }
    }
}

// ------------------------------
// API Action Router
// ------------------------------
function handle_request() {
    $action = $_GET['action'] ?? null;
    $platform = $_GET['platform'] ?? null;
    $config = getAPIConfig();

    $api = null;
    if ($platform) {
        switch ($platform) {
            case 'lazada': $api = new LazadaAPI('lazada', $config['lazada']); break;
            case 'shopee': $api = new ShopeeAPI('shopee', $config['shopee']); break;
            case 'tiktok': $api = new TikTokAPI('tiktok', $config['tiktok']); break;
            default: return ['success' => false, 'error' => 'Unknown platform'];
        }
    }

    switch ($action) {
        case 'getSummary':
            if (!$platform) return ['success' => false, 'error' => 'Platform required'];
            try {
                $startTime = microtime(true);
                $date_from = $_GET['date_from'] ?? date('Y-m-d');
                $date_to = $_GET['date_to'] ?? date('Y-m-d');
                
                // ตรวจสอบข้อมูลในฐานข้อมูลก่อน (ลด cache time เป็น 5 นาที เพื่อให้ข้อมูลใหม่สำหรับร้านขนาดใหญ่)
                if (isDatabaseDataFresh($platform, 5)) {
                    // ใช้ข้อมูลจากฐานข้อมูล (fresh ภายใน 5 นาที)
                    $summary = getDatabaseStats($platform, $date_from, $date_to);
                    $loadTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    error_log("[getSummary] Platform: $platform, Source: database (fresh), Orders: " . $summary['totalOrders'] . ", Load Time: {$loadTime}ms (5min cache)");
                    
                    return ['success' => true, 'data' => [
                        'totalSales' => $summary['totalSales'],
                        'totalOrders' => $summary['totalOrders'],
                        'loadTime' => $loadTime,
                        'source' => 'database',
                        'lastFetch' => $summary['lastFetchTime']
                    ]];
                } else {
                    // ข้อมูลเก่า หรือไม่มีข้อมูล -> ดึงจาก API
                    if (!$api) return ['success' => false, 'error' => 'API instance required'];
                    
                    try {
                        if (method_exists($api, 'getOrders')) {
                            $reflection = new ReflectionMethod($api, 'getOrders');
                            $params = $reflection->getParameters();
                            if (count($params) >= 4) {
                                $summary = $api->getOrders($date_from, $date_to, 100, true); // summaryOnly
                            } else {
                                $summary = $api->getOrders($date_from, $date_to, 100);
                            }
                        } else {
                            $summary = $api->getOrders($date_from, $date_to, 100);
                        }
                    } catch (Exception $e) {
                        error_log("[getSummary] API call failed for $platform, trying database fallback: " . $e->getMessage());
                        $summary = getDatabaseStats($platform, $date_from, $date_to);
                        
                        return ['success' => true, 'data' => [
                            'totalSales' => $summary['totalSales'],
                            'totalOrders' => $summary['totalOrders'],
                            'loadTime' => round((microtime(true) - $startTime) * 1000, 2),
                            'source' => 'database_fallback',
                            'note' => 'API failed, using cached data'
                        ]];
                    }
                    
                    $loadTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    error_log("[getSummary] Platform: $platform, Source: API, Orders: " . $summary['total_orders'] . ", Load Time: {$loadTime}ms");
                    
                    return ['success' => true, 'data' => [
                        'totalSales' => $summary['total_sales'],
                        'totalOrders' => $summary['total_orders'],
                        'loadTime' => $loadTime,
                        'source' => 'api'
                    ]];
                }
            } catch (Exception $e) {
                error_log("[getSummary] Platform: $platform, Error: " . $e->getMessage());
                return ['success' => false, 'error' => $e->getMessage()];
            }

        case 'getOrders':
            if (!$platform) return ['success' => false, 'error' => 'Platform required'];
            try {
                $startTime = microtime(true);
                $date_from = $_GET['date_from'] ?? date('Y-m-d');
                $date_to = $_GET['date_to'] ?? date('Y-m-d');
                $limit = (int)($_GET['limit'] ?? 100);
                
                // ตรวจสอบว่าข้อมูลในฐานข้อมูลยังใหม่หรือไม่ (ลดเป็น 5 นาทีสำหรับร้านขนาดใหญ่)
                if (isDatabaseDataFresh($platform, 5)) {
                    // ใช้ข้อมูลจากฐานข้อมูล
                    $orders = getOrdersFromDatabase($platform, $date_from, $date_to, $limit);
                    $loadTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    $orders['loadTime'] = $loadTime;
                    error_log("[getOrders] Platform: $platform, Source: database (fresh), Orders: " . count($orders['orders']) . ", Load Time: {$loadTime}ms");
                    
                    return ['success' => true, 'data' => $orders];
                } else {
                    // ข้อมูลเก่าหรือไม่มีข้อมูล -> ดึงจาก API
                    if (!$api) return ['success' => false, 'error' => 'API instance required'];
                    
                    try {
                        $orders = $api->getOrders($date_from, $date_to, $limit);
                        $loadTime = round((microtime(true) - $startTime) * 1000, 2);
                        
                        $orders['loadTime'] = $loadTime;
                        $orders['source'] = 'api';
                        
                        error_log("[getOrders] Platform: $platform, Source: API, Orders: " . count($orders['orders'] ?? []) . ", Load Time: {$loadTime}ms");
                        
                        return ['success' => true, 'data' => $orders];
                    } catch (Exception $e) {
                        // API failed, fallback to database
                        error_log("[getOrders] API failed for $platform, using database fallback: " . $e->getMessage());
                        
                        $orders = getOrdersFromDatabase($platform, $date_from, $date_to, $limit);
                        $loadTime = round((microtime(true) - $startTime) * 1000, 2);
                        
                        $orders['loadTime'] = $loadTime;
                        $orders['source'] = 'database_fallback';
                        $orders['note'] = 'API failed, using cached data';
                        
                        if (empty($orders['orders'])) {
                            return ['success' => false, 'error' => 'No data available: ' . $e->getMessage()];
                        }
                        
                        return ['success' => true, 'data' => $orders];
                    }
                }
            } catch (Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }

        case 'getRecentActivity':
            $startTime = microtime(true);
            // ดึงจากฐานข้อมูลแทนการยิง API ทุกแพลตฟอร์ม
            $allActivity = [];
            $platforms = ['shopee', 'lazada', 'tiktok'];
            $enabledCount = 0;
            
            foreach($platforms as $p){
                $p_conf = dm_settings_get_all($p);
                if(isset($p_conf['enabled']) && $p_conf['enabled']==='true'){
                    $enabledCount++;
                    try {
                        // ดึงจากฐานข้อมูลก่อน (เร็วกว่า)
                        $orders = getOrdersFromDatabase($p, date('Y-m-d'), date('Y-m-d'), 5);
                        
                        if(!empty($orders['orders'])) {
                            $allActivity = array_merge($allActivity, $orders['orders']);
                        }
                    } catch(Exception $e){
                        error_log("[getRecentActivity] Database query failed for $p: " . $e->getMessage());
                    }
                }
            }
            
            // Sort by created_at desc
            usort($allActivity, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Take only top 10 most recent
            $allActivity = array_slice($allActivity, 0, 10);
            
            $loadTime = round((microtime(true) - $startTime) * 1000, 2);
            error_log("[getRecentActivity] Enabled platforms: $enabledCount, Recent orders: " . count($allActivity) . ", Load Time: {$loadTime}ms");
            
            return ['success' => true, 'data' => [
                'recent_orders' => $allActivity,
                'enabled_platforms' => $enabledCount,
                'loadTime' => $loadTime,
                'source' => 'database'
            ]];

        case 'get_settings':
            if (!$platform) return ['success' => false, 'error' => 'Platform required'];
            $settings = dm_settings_get_all($platform);
            return ['success' => true, 'data' => $settings];

        case 'save_settings':
            if (!$platform) return ['success' => false, 'error' => 'Platform required'];
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) return ['success' => false, 'error' => 'Invalid data'];

            // Convert boolean 'enabled' to string 'true'/'false' before saving
            if (isset($data['enabled'])) {
                $data['enabled'] = $data['enabled'] ? 'true' : 'false';
            }
            
            foreach($data as $key => $value){
                dm_settings_set($platform, $key, is_bool($value) ? ($value ? 'true' : 'false') : $value);
            }
            // Also save to cookie for immediate UI feedback on non-sensitive fields
            if(isset($data['enabled'])) setcookie($platform.'_enabled', $data['enabled'], time()+3600*24*365, '/');

            return ['success' => true, 'message' => 'Settings saved'];

        case 'test_connection':
            if (!$api) return ['success' => false, 'error' => 'Platform required'];
            try {
                // Pass through any extra params from the request
                $params = $_GET;
                unset($params['action'], $params['platform']);
                $result = $api->testConnection($params);
                return $result;
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Test failed: ' . $e->getMessage()];
            }

        case 'refresh_token':
            if ($platform !== 'shopee') return ['success' => false, 'error' => 'Refresh only supported for Shopee'];
            try {
                $shopeeApi = new ShopeeAPI('shopee', $config['shopee']);
                $result = $shopeeApi->refreshAccessToken();
                return ['success' => true, 'data' => $result];
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Refresh failed: ' . $e->getMessage()];
            }

        case 'curl_info':
            $target = $_GET['target'] ?? 'https://www.google.com';
            $info = dm_curl_probe_run($target);
            return ['success'=>true, 'data'=>$info];

        default:
            return ['success' => false, 'error' => 'Invalid action'];
    }
}

// Main execution
header('Content-Type: application/json');
echo json_encode(handle_request());
