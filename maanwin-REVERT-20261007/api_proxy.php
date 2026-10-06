<?php
/**
 * Same-origin Maanwin API proxy (tenant 6015).
 * Forwards /api/<Controller>/<Action> to https://maanwin16.com so Host/SNI
 * resolves tenant 6015. Body must be forwarded byte-for-byte (client signs it).
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept-Language, X-App-Version');
    http_response_code(204);
    exit;
}

$CHANNEL_ORIGIN = 'https://maanwin16.com';
$UPSTREAM = 'https://maanwin16.com';

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$path = (string) parse_url($uri, PHP_URL_PATH);
$path = '/' . ltrim($path, '/');

if (!preg_match('#^/api/[A-Za-z0-9_]+/[A-Za-z0-9_]+/?$#', $path)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => -1, 'msg' => 'Bad API endpoint', 'msgCode' => -1, 'data' => null]);
    exit;
}

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

$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if ($contentType !== '') {
    $headers[] = 'Content-Type: ' . $contentType;
} else {
    $headers[] = 'Content-Type: application/json;charset=UTF-8';
}

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
if ($auth === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
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

foreach (['HTTP_X_APP_VERSION' => 'X-App-Version'] as $srv => $name) {
    if (!empty($_SERVER[$srv])) {
        $headers[] = $name . ': ' . $_SERVER[$srv];
    }
}

$body = file_get_contents('php://input');
if ($body === false) {
    $body = '';
}

if (!function_exists('curl_init')) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'msg' => 'curl missing', 'msgCode' => 1, 'data' => null]);
    exit;
}

$ch = curl_init($target);
$opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CUSTOMREQUEST => strtoupper($_SERVER['REQUEST_METHOD'] ?? 'POST'),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_ENCODING => '',
];
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'GET' && $method !== 'HEAD') {
    $opts[CURLOPT_POSTFIELDS] = $body;
}
curl_setopt_array($ch, $opts);

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
