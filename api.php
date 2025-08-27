<?php
// DEBUG: Show all errors for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Global error/exception handler for JSON API
set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
});
// DEBUG: Show all errors for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Backward compatibility for Shopee
function saveShopeeTokens($data){ savePlatformTokens('shopee', $data); }
function saveLazadaTokens($data){ savePlatformTokens('lazada', $data); }
function saveTikTokTokens($data){ savePlatformTokens('tiktok', $data); }

// ------------------------------
// Base Platform Class
// ------------------------------
class PlatformAPI {
    protected $platform;
    protected $config;
    public function __construct($platform, $config) { $this->platform = $platform; $this->config = $config; }
    // Default stubs
    public function getOrders($date_from=null,$date_to=null,$limit=50){ throw new Exception('Not implemented for '.$this->platform); }
    public function getProducts($limit=50){ return []; }
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
    error_log("[dashboardmarket] Shopee refresh partner_id_clean=".$partnerClean);
    // Many Shopee auth endpoints expect partner-level sign (no shop_id); try that first.
    $origPartner = $this->config['partner_id'];
    $this->config['partner_id'] = $partnerClean;
    $signNoShop = $this->sign($path, $ts, '', '');
    $this->config['partner_id'] = $origPartner;
    // Log signature used (not the secret) to aid debugging
    error_log("[dashboardmarket] Shopee refresh signNoShop=".$signNoShop);
    $url = $urlBase . '?partner_id=' . urlencode($partnerClean) . '&timestamp=' . $ts . '&sign=' . $signNoShop;
        $payload = json_encode([
            'shop_id' => $this->config['shop_id'],
            'refresh_token' => $this->config['refresh_token'],
            'partner_id' => $partnerClean
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
            $signWithShop = $this->sign($path, $ts, '', $this->config['shop_id']);
            error_log("[dashboardmarket] Shopee refresh signWithShop=".$signWithShop);
            $url2 = $urlBase . '?partner_id=' . urlencode($this->config['partner_id']) . '&timestamp=' . $ts . '&sign=' . $signWithShop;
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
    public function getOrders($date_from=null,$date_to=null,$limit=50){
        // Convert date range to epoch (Shopee requires unix)
        if(!$date_from || !$date_to){
            $date_from_ts = strtotime(date('Y-m-d 00:00:00')); // today start local
            $date_to_ts   = time();
        } else {
            $date_from_ts = strtotime($date_from);
            $date_to_ts   = strtotime($date_to);
        }
        $pageSize = min($limit, 50);
        // Build query (remove invalid order_status=ALL; only include if caller specifies a concrete status)
        $query = [
            'time_from'=>$date_from_ts,
            'time_to'=>$date_to_ts,
            'time_range_field'=>'create_time',
            'page_size'=>$pageSize,
            'response_optional_fields'=>'order_status'
        ];
        if(isset($_GET['order_status']) && $_GET['order_status']!=='' && strtoupper($_GET['order_status'])!=='ALL'){
            $query['order_status'] = $_GET['order_status'];
        }
        $listResp = $this->callGet('/api/v2/order/get_order_list',$query);
        $orderSnList = $listResp['response']['order_list'] ?? [];
        if(!$orderSnList) return ['total_sales'=>0,'total_orders'=>0,'orders'=>[]];
        // Ensure we request details for the most recently created orders first
        try {
            usort($orderSnList, function($a,$b){
                $ta = isset($a['create_time']) ? (int)$a['create_time'] : 0;
                $tb = isset($b['create_time']) ? (int)$b['create_time'] : 0;
                return $tb <=> $ta; // newest first
            });
        } catch (Exception $e) { /* ignore sort errors */ }
        $sns = array_map(fn($o)=>$o['order_sn'],$orderSnList);
        $snChunk = array_slice($sns,0,$pageSize);
        $detailResp = $this->callGet('/api/v2/order/get_order_detail',[
            'order_sn_list'=>implode(',',$snChunk),
            'response_optional_fields'=>'item_list,total_amount,order_status,create_time'
        ]);
        $details = $detailResp['response']['order_list'] ?? [];
        $out=[]; $totalSales=0; $totalOrders=0;
        foreach($details as $o){
            $orderSn = $o['order_sn'] ?? 'N/A';
            $created = '';
            if (isset($o['create_time'])) {
                try {
                    // create DateTime from epoch (UTC) then convert to Bangkok timezone
                    $dt = new DateTime('@' . $o['create_time']);
                    $dt->setTimezone(new DateTimeZone('Asia/Bangkok'));
                    $created = $dt->format('Y-m-d\TH:i:sP'); // ISO8601 with +07:00
                } catch (Exception $e) {
                    $created = date('Y-m-d H:i:s', $o['create_time']);
                }
            }
            $status  = $o['order_status'] ?? 'UNKNOWN';
            $items = $o['item_list'] ?? [];
            $amount = 0;
            foreach($items as $it){
                $qty = (int)($it['model_quantity_purchased'] ?? $it['model_quantity'] ?? $it['quantity'] ?? 1);
                $price = (float)($it['model_discounted_price'] ?? $it['model_original_price'] ?? $it['item_price'] ?? 0);
                $amount += $price * $qty;
            }
            if(!$amount && isset($o['total_amount'])) $amount = (float)$o['total_amount'];
            $totalSales += $amount; $totalOrders++;
            $out[] = [
                'id'=>$orderSn,
                'product'=>'รายการสินค้า '.count($items).' รายการ',
                'amount'=>$amount,
                'status'=>strtolower($status),
                'created_at'=>$created
            ];
        }
        usort($out,function($a,$b){return strtotime($b['created_at']) - strtotime($a['created_at']);});
        return ['total_sales'=>$totalSales,'total_orders'=>$totalOrders,'orders'=>array_slice($out,0,$limit)];
    }
    public function getOrderItems($order_id){
        // Shopee detail returns multiple orders; reuse detail call for single if needed
        $detail = $this->callGet('/api/v2/order/get_order_detail',[
            'order_sn_list'=>$order_id,
            'response_optional_fields'=>'item_list'
        ]);
        $orders = $detail['response']['order_list'] ?? [];
        $items=[];
        foreach($orders as $o){ if(($o['order_sn']??'')!==$order_id) continue; foreach(($o['item_list']??[]) as $it){
            $items[]=[
                'name'=>$it['item_name'] ?? 'Unknown',
                'quantity'=>(int)($it['model_quantity_purchased'] ?? $it['model_quantity'] ?? $it['quantity'] ?? 1),
                'price'=>(float)($it['model_discounted_price'] ?? $it['model_original_price'] ?? $it['item_price'] ?? 0)
            ];
        }}
        return $items;
    }
    public function getProducts($limit=50){
        // Aggregate from today orders (basic proxy for top products)
        $orders = $this->getOrders(null,null,50);
        $agg=[];
        foreach($orders['orders'] as $o){
            $items = $this->getOrderItems($o['id']);
            foreach($items as $it){
                if(!isset($agg[$it['name']])) $agg[$it['name']] = ['name'=>$it['name'],'qty'=>0,'revenue'=>0];
                $agg[$it['name']]['qty'] += $it['quantity'];
                $agg[$it['name']]['revenue'] += $it['price'] * $it['quantity'];
            }
        }
        // Convert assoc to indexed array for sorting
        $aggList = array_values($agg);
        usort($aggList,function($a,$b){ return $b['revenue'] <=> $a['revenue']; });
        $out=[]; foreach(array_slice($aggList,0,$limit) as $row){ $out[]=['name'=>$row['name'],'sold'=>$row['qty']]; }
        return $out;
    }
}

// ------------------------------
// Lazada API Wrapper
// ------------------------------
class LazadaAPI extends PlatformAPI {
    private function signParams($path, $params) {
        $params['app_key']    = $this->config['app_key'];
        $params['timestamp']  = (string)(round(microtime(true)*1000));
        $params['sign_method']= 'sha256';
        if (!empty($this->config['access_token'])) $params['access_token'] = $this->config['access_token'];
        ksort($params);
        $str = $path; foreach ($params as $k=>$v){ if (is_array($v)) $v=json_encode($v); $str.=$k.$v; }
        $params['sign'] = strtoupper(hash_hmac('sha256', $str, $this->config['app_secret']));
        return $params;
    }
    private function httpGet($url) {
        $ch = curl_init();
    curl_setopt_array($ch, dm_curl_defaults([CURLOPT_URL=>$url, CURLOPT_TIMEOUT=>30]));
        $res = curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($err) throw new Exception('cURL: '.$err);
        $json = json_decode($res,true);
        if (json_last_error()!=JSON_ERROR_NONE) throw new Exception('Invalid JSON: '.json_last_error_msg());
        if ($code!==200) throw new Exception('HTTP '.$code.': '.$res);
        return $json;
    }
    private function call($path, $params) {
        if (empty($this->config['app_key']) || empty($this->config['app_secret'])) {
            throw new Exception('Missing app credentials');
        }
        $signed = $this->signParams($path, $params);
        $query = http_build_query($signed);
        return $this->httpGet($this->config['api_url'].$path.'?'.$query);
    }
    // OAuth Flow
    public function buildAuthUrl($redirectUri, $state='') {
        if (empty($this->config['app_key'])) throw new Exception('App Key not set');
        $params = [
            'response_type' => 'code',
            'force_auth'    => 'true',
            'redirect_uri'  => $redirectUri,
            'client_id'     => $this->config['app_key'],
        ];
        if ($state) $params['state']=$state;
        return $this->config['auth_url'].'?'.http_build_query($params);
    }
    public function exchangeCode($code, $redirectUri) {
        $path = '/auth/token/create';
        $params = [ 'code'=>$code, 'redirect_uri'=>$redirectUri ];
        $signed = $this->signParams($path, $params);
        $resp = $this->httpGet($this->config['api_url'].$path.'?'.http_build_query($signed));
        if (($resp['code']??'')!=='0') throw new Exception('Exchange failed: '.json_encode($resp));
        saveLazadaTokens($resp['data']);
        return $resp['data'];
    }
    // Data Endpoints
    public function getOrders($date_from=null,$date_to=null,$limit=50) {
        $path = '/orders/get';
        $params = [ 'limit'=>$limit ];
        if (!$date_from && !$date_to) {
            $params['created_after']  = date('Y-m-d\T00:00:00+07:00');
            $params['created_before'] = date('Y-m-d\T23:59:59+07:00');
        } else {
            if ($date_from) $params['created_after']=$date_from;
            if ($date_to)   $params['created_before']=$date_to;
        }
        $resp = $this->call($path,$params);
        if (($resp['code']??'')!=='0') throw new Exception('Orders error: '.json_encode($resp));
        return $this->normalizeOrders($resp['data']);
    }
    public function getOrderItems($order_id) {
        $path = '/order/items/get';
        $params = ['order_id' => $order_id];
        $resp = $this->call($path, $params);
        if (($resp['code'] ?? '') !== '0') throw new Exception('Order items error: ' . json_encode($resp));
        $rawItems = $resp['data']['order_items'] ?? $resp['data'] ?? [];
        if (!is_array($rawItems)) $rawItems = [];
        $items = [];
        foreach ($rawItems as $it) {
            if (!is_array($it)) continue;
            $name = $it['name'] ?? ($it['product_name'] ?? 'Unknown');
            $qty  = (int)($it['quantity'] ?? $it['qty'] ?? 1);
            $price = (float)($it['paid_price'] ?? $it['item_price'] ?? $it['price'] ?? 0);
            $items[] = [ 'name' => $name, 'quantity' => $qty, 'price' => $price ];
        }
        return $items;
    }
    private function normalizeOrders($data) {
        $raw = $data['orders'] ?? (is_array($data)?$data:[]);
        $list=[]; $totalSales=0; $totalOrders=0;
        foreach ($raw as $o) {
            $id = $o['order_number'] ?? $o['order_id'] ?? 'N/A';
            $statusArr = $o['statuses'] ?? [];
            $status = end($statusArr) ?: 'pending';
            $price = isset($o['price']) ? (float)$o['price'] : 0.0;
            $totalSales += $price; $totalOrders++;
            $list[] = [ 'id' => $id, 'product' => 'รายการสินค้า '.($o['items_count'] ?? 1).' รายการ', 'amount' => $price, 'status' => $this->mapStatus($status), 'created_at' => $this->formatDate($o['created_at'] ?? ''), ];
        }
        usort($list,function($a,$b){return strtotime($b['created_at'])-strtotime($a['created_at']);});
        return [ 'total_sales'=>$totalSales, 'total_orders'=>$totalOrders, 'orders'=>array_slice($list,0,50) ];
    }
    public function getProducts($limit=100) {
        $path = '/products/get';
        $params = [ 'filter'=>'live', 'limit'=>$limit ];
        $resp = $this->call($path,$params);
        if (($resp['code']??'')!=='0') throw new Exception('Products error: '.json_encode($resp));
        $out=[]; $prods = $resp['data']['products'] ?? [];
        foreach ($prods as $p) { $name = $p['attributes']['name'] ?? ($p['name'] ?? 'Unknown'); $out[] = [ 'name' => $name, 'sold' => 0, 'revenue' => null ]; }
        return array_slice($out,0,10);
    }
    private function mapStatus($s) { $map = [ 'pending'=>'pending', 'ready_to_ship'=>'processing','shipped'=>'shipped','delivered'=>'completed','canceled'=>'cancelled','returned'=>'returned' ]; return $map[$s] ?? 'pending'; }
    private function formatDate($d){
        if(!$d) return '';
        try{
            $dt = new DateTime($d);
            // convert to Bangkok timezone
            $tz = new DateTimeZone('Asia/Bangkok');
            $dt->setTimezone($tz);
            // Use ISO8601 with timezone offset so clients parse correctly
            return $dt->format('Y-m-d\TH:i:sP');
        } catch(Exception $e){
            return $d;
        }
    }
}

// ------------------------------
// TikTok API Stub
// Minimal implementation so UI/settings can register the platform.
// This is a non-functional stub — replace with real integration when available.
// ------------------------------
class TikTokAPI extends PlatformAPI {
    public function __construct($platform, $config){ parent::__construct($platform,$config); }
    public function getOrders($date_from=null,$date_to=null,$limit=50){
        // If credentials not present, return empty result rather than throwing
        $hasCreds = !empty($this->config['client_key']) && !empty($this->config['client_secret']);
        if (!$hasCreds) return ['total_sales'=>0,'total_orders'=>0,'orders'=>[]];
        // Placeholder: real implementation should call TikTok Shop APIs
        return ['total_sales'=>0,'total_orders'=>0,'orders'=>[]];
    }
    public function getOrderItems($order_id){
        return [];
    }
    public function getProducts($limit=50){ return []; }
}

// ------------------------------
// Factory
// ------------------------------
function createPlatformAPI($platform,$config){
    if ($platform==='lazada') return new LazadaAPI($platform,$config);
    if ($platform==='shopee') return new ShopeeAPI($platform,$config);
    if ($platform==='tiktok') return new TikTokAPI($platform,$config);
    throw new Exception('Unsupported platform');
}

// ------------------------------
// Optional Mock (only if explicitly requested via allow_mock=1)
// ------------------------------
// Mock helpers removed - API will not return synthesized/mock data

// ------------------------------
// Request Router
// ------------------------------
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    $action   = $_GET['action'];
    $platform = $_GET['platform'] ?? 'lazada';
    $allowMock = isset($_GET['allow_mock']) && $_GET['allow_mock']=='1';

    try {
        $cfgAll = getAPIConfig();
        if (!isset($cfgAll[$platform])) throw new Exception('Unknown platform');
        $cfg = $cfgAll[$platform];
        // Allow overrides for refresh action when cookies may not yet be present (useful for testing)
        if (isset($_GET['action']) && $_GET['action']==='refresh_token' && $platform==='shopee') {
            if (isset($_GET['partner_id'])) $cfg['partner_id'] = $_GET['partner_id'];
            if (isset($_GET['refresh_token'])) $cfg['refresh_token'] = $_GET['refresh_token'];
            if (isset($_GET['shop_id'])) $cfg['shop_id'] = $_GET['shop_id'];
        }
        // Allow runtime override of Shopee env via query param ?env=sandbox
        if ($platform === 'shopee' && isset($_GET['env'])) {
            $env = $_GET['env'];
            if (in_array($env,['prod','sandbox'])) {
                $cfg['env'] = $env;
                $cfg['api_url'] = ($env==='sandbox') ? 'https://openplatform.sandbox.test-stable.shopee.sg' : 'https://partner.shopeemobile.com';
            }
        }
        $api = createPlatformAPI($platform,$cfg);

        switch ($action) {
            case 'curl_probe':
                // Probe one or more URLs. Optional: host+ip to force SNI to a specific IP via CURLOPT_RESOLVE
                $urlsParam = $_GET['urls'] ?? ($_GET['url'] ?? '');
                $urls = [];
                if ($urlsParam) {
                    foreach (explode(',', $urlsParam) as $u) {
                        $u = trim($u);
                        if ($u) $urls[] = $u;
                    }
                }
                if (!$urls) throw new Exception('Provide ?urls=https://example.com,https://1.2.3.4');
                $host = $_GET['host'] ?? null;
                $ip = $_GET['ip'] ?? null;
                $out = [];
                foreach ($urls as $u) {
                    $out[] = [ 'url'=>$u, 'result'=> dm_curl_probe_run($u, $host, $ip) ];
                }
                echo json_encode(['success'=>true,'data'=>$out,'notes'=>'Optionally add &host=domain&ip=1.2.3.4 to test SNI over a specific IP']);
                break;
            case 'curl_info':
                $diag = [
                    'curl_installed'   => function_exists('curl_version'),
                    'curl_version'     => function_exists('curl_version') ? curl_version()['version'] : null,
                    'ssl_version'      => function_exists('curl_version') ? (curl_version()['ssl_version'] ?? null) : null,
                    'curl_cainfo_ini'  => ini_get('curl.cainfo') ?: null,
                    'ca_bundle_found'  => dm_find_ca_bundle(),
                    'ip_resolve'       => defined('CURL_IPRESOLVE_V4') ? 'V4' : 'DEFAULT',
                    'proxy'            => getenv('HTTPS_PROXY') ?: (getenv('HTTP_PROXY') ?: null),
                    'use_proxy_flag'   => getenv('DM_USE_PROXY') === '1',
                    'trust_windows_ca' => getenv('DM_CURL_TRUST_WIN') === '1',
                    'force_tls12'      => getenv('DM_FORCE_TLS12') === '1',
                    'dm_verbose'       => getenv('DM_CURL_VERBOSE') === '1',
                    'dm_insecure'      => getenv('DM_CURL_INSECURE') === '1',
                ];
                if (isset($_GET['test_url']) && $_GET['test_url']) {
                    $testUrl = $_GET['test_url'];
                    $ch = curl_init();
                    curl_setopt_array($ch, dm_curl_defaults([
                        CURLOPT_URL => $testUrl,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_NOBODY => false,
                        CURLOPT_HTTPGET => true,
                    ]));
                    $body = curl_exec($ch);
                    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $err  = curl_error($ch);
                    curl_close($ch);
                    $diag['test'] = [ 'url'=>$testUrl, 'http'=>$http, 'error'=>$err ?: null, 'body_sample'=> is_string($body) ? substr($body,0,200) : null ];
                }
                echo json_encode(['success'=>true,'data'=>$diag]);
                break;
            case 'save_settings':
                // Persist platform settings to DB; accepts JSON body or query params
                $plat = $_GET['platform'] ?? $_POST['platform'] ?? null;
                if (!$plat) $plat = $platform; // fallback to earlier parsed platform
                if (!in_array($plat, ['shopee','lazada','tiktok'])) throw new Exception('Unsupported platform');
                $raw = file_get_contents('php://input');
                $data = [];
                if ($raw) {
                    $j = json_decode($raw, true);
                    if (is_array($j)) $data = $j;
                }
                // Allow form-encoded fallback
                if (!$data) $data = $_POST ?: $_GET;
                // Whitelist per platform
                $allow = [
                    'shopee' => ['partner_id','partner_key','shop_id','access_token','refresh_token','expires_at','env','enabled'],
                    'lazada' => ['app_key','app_secret','access_token','refresh_token','expires_in','refreshed_at','enabled'],
                    'tiktok' => ['client_key','client_secret','access_token','refresh_token','enabled']
                ];
                $save = [];
                foreach ($allow[$plat] as $k) {
                    if (array_key_exists($k, $data)) {
                        $val = $data[$k];
                        if ($k === 'enabled') { $val = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false'; }
                        $save[$k] = (string)$val;
                    }
                }
                if (!$save) throw new Exception('No settings to save');
                dm_settings_set_many($plat, $save);
                echo json_encode(['success'=>true,'platform'=>$plat,'saved'=>array_keys($save)]);
                break;
            case 'auth_url':
                if(!$api instanceof LazadaAPI) throw new Exception('auth_url only for lazada in this version');
                $redirect = $_GET['redirect_uri'] ?? '';
                if (!$redirect) throw new Exception('redirect_uri required');
                $state = $_GET['state'] ?? '';
                $url = $api->buildAuthUrl($redirect,$state);
                echo json_encode(['success'=>true,'auth_url'=>$url]);
                break;
            case 'exchange_token':
                if(!$api instanceof LazadaAPI) throw new Exception('exchange_token only for lazada');
                $code = $_GET['code'] ?? '';
                $redirect = $_GET['redirect_uri'] ?? '';
                if (!$code||!$redirect) throw new Exception('code & redirect_uri required');
                $data = $api->exchangeCode($code,$redirect);
                echo json_encode(['success'=>true,'data'=>$data]);
                break;
            case 'getSummary':
            case 'summary':
                $orders = $api->getOrders(null,null,50);
                $products=[]; try { $products = $api->getProducts(50); } catch(Exception $e) { /* ignore */ }
                echo json_encode(['success'=>true,'data'=>[
                    'totalSales'=>$orders['total_sales'],
                    'totalOrders'=>$orders['total_orders'],
                    'orders'=>array_slice($orders['orders'],0,10),
                    'products'=>array_slice($products,0,10)
                ],'platform'=>$platform]);
                break;
            case 'getOrders':
                $limit = (int)($_GET['limit'] ?? 10);
                $orders = $api->getOrders(null,null,$limit);
                $mapped = [];
                foreach (array_slice($orders['orders'],0,$limit) as $o) {
                    $items = [];
                    if(method_exists($api,'getOrderItems')){ try { $items = $api->getOrderItems($o['id']); } catch(Exception $e) { $items = []; } }
                    if (!empty($items)) {
                        $productStrParts = []; $amountSum = 0;
                        foreach ($items as $it) { $productStrParts[] = $it['name'].' x'.$it['quantity']; $amountSum += $it['price'] * $it['quantity']; }
                        $productStr = implode(', ', $productStrParts);
                        if ($amountSum > 0) $o['amount'] = $amountSum;
                    } else { $productStr = $o['product']; }
                    $mapped[] = [ 'order_id'=>$o['id'], 'product'=>$productStr, 'items'=>$items, 'amount'=>$o['amount'], 'created_at'=>$o['created_at'] ];
                }
                echo json_encode(['success'=>true,'data'=>['orders'=>$mapped],'source'=>'live','platform'=>$platform]);
                break;
            case 'getRecentActivity':
                // Aggregate recent orders from all supported platforms (shopee, lazada, ...)
                $platformsToCheck = ['shopee','lazada','tiktok'];
                $events = [];
                foreach ($platformsToCheck as $p) {
                    try {
                        if (!isset($cfgAll[$p])) continue;
                        $cfgP = $cfgAll[$p];
                        // createPlatformAPI may throw for unsupported platforms (e.g., tiktok not implemented)
                        try { $apiP = createPlatformAPI($p, $cfgP); } catch (Exception $e) { continue; }

                        // fetch a small number of recent orders
                        $ordersResp = [];
                        try { $ordersResp = $apiP->getOrders(null, null, 5); } catch (Exception $e) { continue; }
                        $olist = $ordersResp['orders'] ?? [];
                        foreach ($olist as $o) {
                            $orderId = $o['id'] ?? ($o['order_id'] ?? 'N/A');
                            $created = $o['created_at'] ?? '';
                            $amount = isset($o['amount']) ? $o['amount'] : 0;
                            $items = [];
                            if (method_exists($apiP, 'getOrderItems')) {
                                try { $items = $apiP->getOrderItems($orderId); } catch (Exception $e) { $items = []; }
                            }
                            $productStr = $o['product'] ?? '';

                            // build event record expected by frontend
                            $events[] = [
                                'platform' => $p,
                                'order_id' => $orderId,
                                'items'    => $items,
                                'product'  => $productStr,
                                'amount'   => $amount,
                                'created_at' => $created,
                                'time'     => $created
                            ];
                        }
                    } catch (Exception $e) {
                        // skip platform on error
                        continue;
                    }
                }
                // sort by created_at (newest first) if possible
                usort($events, function($a,$b){
                    $ta = strtotime($a['created_at'] ?? 0);
                    $tb = strtotime($b['created_at'] ?? 0);
                    return $tb <=> $ta;
                });
                // limit events returned
                $events = array_slice($events, 0, 20);
                echo json_encode(['success'=>true,'data'=>$events,'source'=>'live']);
                break;
            case 'getTopProducts':
                $limit = (int)($_GET['limit'] ?? 10); $products = $api->getProducts($limit);
                $mapped=[]; foreach ($products as $p){ $mapped[]=['name'=>$p['name'],'sold'=>$p['sold']]; }
                echo json_encode(['success'=>true,'data'=>['products'=>array_slice($mapped,0,$limit)],'source'=>'live','platform'=>$platform]);
                break;
            case 'refresh_token':
                // รองรับทุก platform ที่มี refreshAccessToken
                if (!method_exists($api, 'refreshAccessToken')) {
                    throw new Exception('refresh_token not supported for this platform');
                }
                try {
                    $r = $api->refreshAccessToken();
                    echo json_encode(['success'=>true,'data'=>$r]);
                } catch(Exception $e) {
                    // เพิ่มรายละเอียด error log
                    error_log('[dashboardmarket] refresh_token error: ' . $e->getMessage());
                    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
                }
                break;
            case 'test_connection':
                $orders = $api->getOrders(null,null,5);
                echo json_encode(['success'=>true,'message'=>'Connection OK','data_count'=>$orders['total_orders'],'platform'=>$platform]);
                break;
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        // No mock fallbacks: always return the actual error to the client
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>$e->getMessage(),'platform'=>$platform]);
    }
    exit;
}
?>
