<?php
/**
 * Same-origin live lottery issue proxy (tenant 6015).
 * GET /webapi/kv/issue/{gameCode} → https://maanwin16.com/webapi/kv/issue/{gameCode}
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept-Language, X-App-Version');
    http_response_code(204);
    exit;
}

$CHANNEL_ORIGIN = 'https://maanwin16.com';
$UPSTREAM = 'https://maanwin16.com';

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
$path = preg_replace('#\.html$#i', '', $path);

if (!preg_match('#^/webapi/kv/issue/[A-Za-z0-9_]+/?$#', $path)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => -1, 'msg' => 'Not found', 'msgCode' => -1, 'data' => null]);
    exit;
}

$path = rtrim($path, '/');
$target = $UPSTREAM . $path;
$qs = [];
foreach ($_GET as $k => $v) {
    $qs[$k] = $v;
}
if ($qs) {
    $target .= '?' . http_build_query($qs);
}

$headers = [
    'Origin: ' . $CHANNEL_ORIGIN,
    'Referer: ' . $CHANNEL_ORIGIN . '/',
    'Accept: application/json, text/plain, */*',
];
if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    $headers[] = 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'];
}
if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $headers[] = 'Accept-Language: ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'];
}

$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if ($auth === '' && function_exists('apache_request_headers')) {
    foreach (apache_request_headers() as $k => $v) {
        if (strtolower((string) $k) === 'authorization') {
            $auth = (string) $v;
            break;
        }
    }
}
$auth = trim((string) $auth);
if ($auth !== '' && $auth !== 'undefined' && $auth !== 'null' && $auth !== 'Bearer') {
    $headers[] = 'Authorization: ' . $auth;
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
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
http_response_code($status > 0 ? $status : 200);

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
