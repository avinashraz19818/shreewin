<?php
/**
 * Same-origin lottery draw JSON proxy.
 * GET /lottery-draw/{WinGo|TrxWinGo|VideoWinGo|K3|D5}/...json
 * → https://draw.ar-lottery01.com/{same path}
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept-Language, X-App-Version');
    http_response_code(204);
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'GET' && $method !== 'HEAD') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => -1, 'msg' => 'Method not allowed', 'msgCode' => -1, 'data' => null]);
    exit;
}

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$path = (string) parse_url($uri, PHP_URL_PATH);
$path = '/' . ltrim($path, '/');
if (strpos($path, '/lottery-draw/') === 0) {
    $path = substr($path, strlen('/lottery-draw'));
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }
}

if (!preg_match('#^/(WinGo|TrxWinGo|VideoWinGo|K3|D5)/[A-Za-z0-9_]+(/GetHistoryIssuePage)?\.json$#', $path)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => -1, 'msg' => 'Not found', 'msgCode' => -1, 'data' => null]);
    exit;
}

$UPSTREAM = 'https://draw.ar-lottery01.com';
$target = $UPSTREAM . $path;
$qs = [];
foreach ($_GET as $k => $v) {
    $qs[$k] = $v;
}
if ($qs) {
    $target .= '?' . http_build_query($qs);
}

$headers = [
    'Origin: https://maanwin16.com',
    'Referer: https://maanwin16.com/',
    'Accept: application/json, text/plain, */*',
];
if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    $headers[] = 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'];
}

if (!function_exists('curl_init')) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'msg' => 'curl missing', 'msgCode' => 1, 'data' => null]);
    exit;
}

$ch = curl_init($target);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_CONNECTTIMEOUT => 12,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_ENCODING => '',
]);

$raw = curl_exec($ch);
$errno = curl_errno($ch);
$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

if ($raw === false || $errno) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'msg' => 'ProxyError', 'msgCode' => 1, 'data' => null]);
    exit;
}

$respHeaders = substr($raw, 0, $headerSize);
$respBody = substr($raw, $headerSize);
http_response_code($http > 0 ? $http : 200);

$sentType = false;
foreach (explode("\r\n", $respHeaders) as $line) {
    if (stripos($line, 'Content-Type:') === 0) {
        header($line, true);
        $sentType = true;
    }
}
if (!$sentType) {
    header('Content-Type: application/json; charset=utf-8');
}

echo $respBody;
