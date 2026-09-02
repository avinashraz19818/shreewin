<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function daman_body($relativePath)
{
    $path = __DIR__ . '/' . ltrim($relativePath, '/');
    $body = is_file($path) ? @file_get_contents($path) : false;
    return is_string($body) ? $body : '';
}

function daman_has($body, $needle)
{
    return $body !== '' && strpos($body, $needle) !== false;
}

$bundle = daman_body('assets/js/main.vue_vue_type_style_index_0_scoped_d3b4a951_lang-D7fD1kKt.js');
$timer = daman_body('assets/js/useWinGo3-C8D0ZIPn.js');
$bootstrap = daman_body('saas_lottery/bootstrap_live_v4.php');
$checks = array(
    'apiRouterPresent' => is_file(__DIR__ . '/api-live-v4/Lottery/index.php'),
    'apiRewritePresent' => is_file(__DIR__ . '/api-live-v4/.htaccess'),
    'drawRouterPresent' => is_file(__DIR__ . '/draw-live-v4/index.php'),
    'drawRewritePresent' => is_file(__DIR__ . '/draw-live-v4/.htaccess'),
    'sameOriginApiLocked' => daman_has($bundle, 's.baseURL=`${Re}/api-live-v4`'),
    'sameOriginDrawLocked' => daman_has($bundle, 'qe=Re+"/draw-live-v4"'),
    'staleApiStorageIgnored' => !daman_has($bundle, 'q.get("ar_api")'),
    'staleDrawStorageIgnored' => !daman_has($bundle, 'q.get("ar_api_json")'),
    'historyAutoRefreshEnabled' => daman_has($timer, 'for(let o=0;o<12;o++)'),
    'autoSchemaUpgradeEnabled' => daman_has($bootstrap, '20260813-daman-v9.0')
);

$providerUrl = 'https://draw.ar-lottery01.com/WinGo/WinGo_30S.json';
$providerContext = stream_context_create(array('http' => array(
    'method' => 'GET',
    'timeout' => 6,
    'ignore_errors' => true,
    'header' => "Accept: application/json\r\nUser-Agent: Daman-SaaS-Health/1.0\r\n"
)));
$providerBody = @file_get_contents($providerUrl, false, $providerContext);
$providerJson = is_string($providerBody) ? json_decode($providerBody, true) : null;
$providerReachable = is_array($providerJson) && isset($providerJson['current']['issueNumber']);

$ready = $providerReachable;
foreach ($checks as $value) {
    $ready = $ready && $value;
}

echo json_encode(array(
    'build' => '20260813-daman-single-domain-saas-v9',
    'ready' => $ready,
    'publicOrigin' => (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : ''),
    'sameOriginApiRoute' => '/api-live-v4',
    'sameOriginDrawRoute' => '/draw-live-v4',
    'providerReachableFromServer' => $providerReachable,
    'checks' => $checks
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
