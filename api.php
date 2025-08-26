<?php
// Lazada / Multi-platform API Gateway (Real Lazada + Shopee integration)
// NOTE: เก็บ App Key / Secret / Tokens ให้ปลอดภัย (ควรย้ายไป .env / DB ในโปรดักชั่น)

// ------------------------------
// Config Loader
// ------------------------------
function getAPIConfig() {
    // Determine shopee environment from cookie (default prod)
    $shopeeEnv = $_COOKIE['shopee_env'] ?? 'prod';
    $shopeeBase = ($shopeeEnv === 'sandbox')
        ? 'https://openplatform.sandbox.test-stable.shopee.sg'
        : 'https://partner.shopeemobile.com';
    return [
        'lazada' => [
            'app_key'       => $_COOKIE['lazada_app_key'] ?? '',
            'app_secret'    => $_COOKIE['lazada_app_secret'] ?? '',
            'access_token'  => $_COOKIE['lazada_access_token'] ?? '',
            'refresh_token' => $_COOKIE['lazada_refresh_token'] ?? '',
            'expires_in'    => (int)($_COOKIE['lazada_expires_in'] ?? 0),
            'refreshed_at'  => (int)($_COOKIE['lazada_refreshed_at'] ?? 0),
            'api_url'       => 'https://api.lazada.co.th/rest',
            'auth_url'      => 'https://auth.lazada.com/oauth/authorize'
        ],
        // Shopee real config (token / partner key stored in cookies for demo)
        'shopee' => [
            'env'           => $shopeeEnv,
            'partner_id'    => $_COOKIE['shopee_partner_id'] ?? '',
            'partner_key'   => $_COOKIE['shopee_partner_key'] ?? '',
            'shop_id'       => $_COOKIE['shopee_shop_id'] ?? '',
            'access_token'  => $_COOKIE['shopee_access_token'] ?? '',
            'refresh_token' => $_COOKIE['shopee_refresh_token'] ?? '',
            'expires_at'    => (int)($_COOKIE['shopee_expires_at'] ?? 0), // epoch time
            'api_url'       => $shopeeBase,
        ],
        'tiktok' => []
    ];
}

// ------------------------------
// Helper: Save Lazada tokens back to cookies (demo only)
// ------------------------------
function saveLazadaTokens($data) {
    $options = ['path' => '/', 'httponly' => false];
    if (isset($data['access_token'])) setcookie('lazada_access_token', $data['access_token'], time()+31536000, '/');
    if (isset($data['refresh_token'])) setcookie('lazada_refresh_token', $data['refresh_token'], time()+31536000, '/');
    if (isset($data['expires_in'])) setcookie('lazada_expires_in', $data['expires_in'], time()+31536000, '/');
    setcookie('lazada_refreshed_at', time(), time()+31536000, '/');
}
// Helper Shopee (demo) – store updated expiry if refreshed elsewhere
function saveShopeeTokens($data){
    if(isset($data['access_token'])) setcookie('shopee_access_token',$data['access_token'],time()+31536000,'/');
    if(isset($data['refresh_token'])) setcookie('shopee_refresh_token',$data['refresh_token'],time()+31536000,'/');
    if(isset($data['expire_in'])) setcookie('shopee_expires_at', time() + (int)$data['expire_in'], time()+31536000,'/');
}

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
        if(empty($this->config['access_token'])) return false;
        if(empty($this->config['expires_at'])) return false;
        return time() >= ($this->config['expires_at'] - 300); // 5 min before
    }
    private function sign($path,$timestamp,$accessToken='',$shopId=''){
        // base string = partner_id + path + timestamp + access_token + shop_id (shop level)
        $base = $this->config['partner_id'].$path.$timestamp.$accessToken.$shopId;
        return hash_hmac('sha256',$base,$this->config['partner_key']);
    }
    private function httpGet($url){
        $ch=curl_init(); curl_setopt_array($ch,[CURLOPT_URL=>$url,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30]);
        $res=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
        if($err) throw new Exception('Shopee cURL: '.$err);
        $json=json_decode($res,true); if(json_last_error()!=JSON_ERROR_NONE) throw new Exception('Shopee JSON error');
        if($code!==200) throw new Exception('Shopee HTTP '.$code.': '.$res);
        if(isset($json['error']) && $json['error']) throw new Exception('Shopee API error: '.($json['message']??$json['error']));
        return $json;
    }
    private function callGet($path,$query){
        $this->requireCreds();
        if($this->needRefresh()) {/* refresh flow could be added here */}
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
            $created = isset($o['create_time']) ? date('Y-m-d H:i:s',$o['create_time']) : '';
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
        curl_setopt_array($ch,[CURLOPT_URL=>$url,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30]);
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
    private function formatDate($d){ if(!$d) return ''; try{$dt=new DateTime($d); return $dt->format('Y-m-d H:i:s');}catch(Exception $e){return $d;} }
}

// ------------------------------
// Factory
// ------------------------------
function createPlatformAPI($platform,$config){
    if ($platform==='lazada') return new LazadaAPI($platform,$config);
    if ($platform==='shopee') return new ShopeeAPI($platform,$config);
    throw new Exception('Unsupported platform');
}

// ------------------------------
// Optional Mock (only if explicitly requested via allow_mock=1)
// ------------------------------
function mockOrders() { return [ 'total_sales'=>38000, 'total_orders'=>98, 'orders'=>[ ['id'=>'MOCK1','product'=>'Demo','amount'=>1000,'status'=>'completed','created_at'=>date('Y-m-d H:i:s')] ] ]; }
function mockProducts() { return [ ['name'=>'Demo Product','sold'=>10] ]; }

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
            case 'getTopProducts':
                $limit = (int)($_GET['limit'] ?? 10); $products = $api->getProducts($limit);
                $mapped=[]; foreach ($products as $p){ $mapped[]=['name'=>$p['name'],'sold'=>$p['sold']]; }
                echo json_encode(['success'=>true,'data'=>['products'=>array_slice($mapped,0,$limit)],'source'=>'live','platform'=>$platform]);
                break;
            case 'test_connection':
                $orders = $api->getOrders(null,null,5);
                echo json_encode(['success'=>true,'message'=>'Connection OK','data_count'=>$orders['total_orders'],'platform'=>$platform]);
                break;
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        if ($allowMock) {
            if (in_array($action,['getSummary','summary'])) { $orders = mockOrders(); $products = mockProducts(); echo json_encode(['success'=>true,'data'=>[ 'totalSales'=>$orders['total_sales'],'totalOrders'=>$orders['total_orders'], 'orders'=>array_slice($orders['orders'],0,10),'products'=>array_slice($products,0,10) ],'source'=>'mock','warning'=>$e->getMessage(),'platform'=>$platform]); }
            elseif ($action==='getOrders') { echo json_encode(['success'=>true,'data'=>['orders'=>mockOrders()['orders']],'source'=>'mock','warning'=>$e->getMessage(),'platform'=>$platform]); }
            elseif ($action==='getTopProducts') { echo json_encode(['success'=>true,'data'=>['products'=>mockProducts()],'source'=>'mock','warning'=>$e->getMessage(),'platform'=>$platform]); }
            else { http_response_code(400); echo json_encode(['success'=>false,'error'=>$e->getMessage(),'platform'=>$platform]); }
        } else { http_response_code(400); echo json_encode(['success'=>false,'error'=>$e->getMessage(),'platform'=>$platform]); }
    }
    exit;
}
?>
