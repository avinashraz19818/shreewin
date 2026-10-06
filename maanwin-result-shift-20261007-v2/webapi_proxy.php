<?php
/**
 * ============================================================================
 *  CURRENT PERIOD PROXY  (/webapi/kv/issue/{gameCode})  — with period shift
 * ============================================================================
 *  Pehle draw.ar-lottery01.com try karta hai (frontend bhi wahi use karta
 *  hai). Agar woh fail ho to maanwin16.com par fallback. Dono me se jo bhi
 *  aaye, usi shift (-1) lagao -> period aur result EK HI source se.
 * ============================================================================
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept-Language, X-App-Version');
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/period_shift.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'GET' && $method !== 'HEAD') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => -1, 'msg' => 'Method not allowed', 'msgCode' => -1, 'data' => null]);
    exit;
}

$uri  = (string) ($_SERVER['REQUEST_URI'] ?? '');
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

function ar_try_upstream(string $path, string $base, array $extraHeaders = []): array
{
    $target = $base . $path;
    $qs = [];
    foreach ($_GET as $k => $v) { $qs[$k] = $v; }
    if ($qs) $target .= '?' . http_build_query($qs);

    $headers = array_merge([
        'Origin: https://maanwin16.com',
        'Referer: https://maanwin16.com/',
        'Accept: application/json, text/plain, */*',
    ], $extraHeaders);
    if (!empty($_SERVER['HTTP_USER_AGENT']))     $headers[] = 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'];
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $headers[] = 'Accept-Language: ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'];

    $ch = curl_init($target);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_ENCODING       => '',
    ]);
    $raw        = curl_exec($ch);
    $errno      = curl_errno($ch);
    $status     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($raw === false || $errno) return [0, '', ''];
    return [$status, substr($raw, 0, $headerSize), substr($raw, $headerSize)];
}

if (!function_exists('curl_init')) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'msg' => 'curl missing', 'msgCode' => 1, 'data' => null]);
    exit;
}

// 1) DRAW source (frontend jaisa)
list($status, $respHeaders, $respBody) = ar_try_upstream($path, 'https://draw.ar-lottery01.com');
$ok = ($status >= 200 && $status < 300 && trim($respBody) !== '' && json_decode($respBody, true) !== null);

// 2) fallback -> maanwin16.com
if (!$ok) {
    list($status, $respHeaders, $respBody) = ar_try_upstream($path, 'https://maanwin16.com');
    $ok = ($status >= 200 && $status < 300 && trim($respBody) !== '');
}

if (!$ok) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'msg' => 'ProxyError', 'msgCode' => 1, 'data' => null]);
    exit;
}

// ---- SHIFT ----
$respBody = ar_shift_json_body($respBody, (int) AR_PERIOD_SHIFT);
// --------------

http_response_code($status > 0 ? $status : 200);
$sentType = false;
foreach (explode("\r\n", (string) $respHeaders) as $line) {
    if (stripos($line, 'Content-Type:') === 0)  { header($line, true); $sentType = true; }
}
if (!$sentType) header('Content-Type: application/json; charset=utf-8');

echo $respBody;
