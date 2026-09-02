<?php
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Credentials: true');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? (string)$_SERVER['HTTP_ORIGIN'] : '';
if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

date_default_timezone_set('Asia/Kolkata');

function recharge_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function recharge_fail(int $code, string $msg, int $msgCode, int $status = 200): void
{
    if (function_exists('app_log_event')) {
        app_log_event('warning', 'GetRechargeTypes API failure', [
            'code' => $code,
            'msg_code' => $msgCode,
            'http_status' => $status,
            'reason' => $msg,
            'request_meta' => $GLOBALS['recharge_request_meta'] ?? [],
        ]);
    }
    recharge_send([
        'data' => null,
        'code' => $code,
        'msg' => $msg,
        'msgCode' => $msgCode,
        'requestId' => function_exists('app_request_id') ? app_request_id() : null,
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ], $status);
}

function recharge_auth_header(): string
{
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
        if (!empty($_SERVER[$key])) {
            return trim((string)$_SERVER[$key]);
        }
    }
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach (['Authorization', 'authorization'] as $key) {
            if (!empty($headers[$key])) {
                return trim((string)$headers[$key]);
            }
        }
    }
    return '';
}

function recharge_bearer_token(): string
{
    $header = recharge_auth_header();
    if ($header === '') {
        return '';
    }
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return trim($m[1]);
    }
    // Keep compatibility with installations where tokenHeader is already
    // concatenated into Authorization without the literal "Bearer" prefix.
    return trim($header);
}

/**
 * Rebuild the signature exactly like the bundled frontend interceptor:
 * - signature/timestamp are removed before signing
 * - language/random/deviceType and any other non-empty payload fields are signed
 * - keys are sorted alphabetically
 */
function recharge_signature(array $payload): string
{
    unset($payload['signature'], $payload['timestamp']);
    $ignore = ['signature' => true, 'track' => true, 'xosoBettingData' => true];
    $clean = [];
    ksort($payload, SORT_STRING);
    foreach ($payload as $key => $value) {
        if (isset($ignore[$key]) || $value === null || $value === '') {
            continue;
        }
        $clean[$key] = $value;
    }
    return strtoupper(md5(json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
}

function recharge_legacy_signature(array $payload): string
{
    $legacy = [
        'language' => $payload['language'] ?? null,
        'payTypeId' => isset($payload['payTypeId']) ? (int)$payload['payTypeId'] : 0,
        'payid' => isset($payload['payid']) ? (int)$payload['payid'] : 0,
        'random' => isset($payload['random']) ? (string)$payload['random'] : '',
    ];
    // Mirrors the legacy client/server format while keeping valid JSON strings.
    return strtoupper(md5(json_encode($legacy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
}

function recharge_site_url(): string
{
    $forwarded = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $scheme = ($forwarded === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) ? 'https' : 'http';
    $host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return $scheme . '://' . ($host ?: 'localhost');
}

function recharge_quick_list(array $amounts, float $bonusRate = 0.0): array
{
    $out = [];
    foreach ($amounts as $amount) {
        $amount = (float)$amount;
        $out[] = [
            'rechargeAmount' => $amount,
            // The card-level 3% is provided through newRechargeRiftRate.
            // Keeping quick gift at zero avoids double-counting in the frontend.
            'giftAmount' => 0.0,
        ];
    }
    return $out;
}

function recharge_channel(int $payId, int $payTypeId, string $name, string $sysName, string $url, array $amounts, float $bonusRate = 0.0, int $sort = 90000): array
{
    $min = (float)min($amounts);
    $max = 50000.0;
    return [
        'payTypeID' => $payTypeId,
        'payID' => $payId,
        'payName' => $name,
        'paySysName' => $sysName,
        'miniPrice' => $min,
        'maxPrice' => $max,
        'scope' => implode('|', array_map(static fn($v) => (string)(int)$v, $amounts)),
        'paySendUrl' => $url,
        'parameters' => '',
        'startTime' => '00:00',
        'endTime' => '24:00',
        'rechargeRifts' => $bonusRate,
        'newRechargeRiftRate' => $bonusRate,
        'serviceFeeRate' => 0.0,
        'c2cUnitAmount' => null,
        'quickConfig' => '',
        'quickConfigList' => recharge_quick_list($amounts, $bonusRate),
        'random' => 0.8192269882695508,
        'sort' => $sort,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    recharge_fail(11, 'Method not allowed', 12, 405);
}

$raw = file_get_contents('php://input');
$post = json_decode((string)$raw, true);
$GLOBALS['recharge_request_meta'] = [
    'body_length' => strlen((string)$raw),
    'json_error' => json_last_error_msg(),
    'body_keys' => is_array($post) ? array_keys($post) : [],
];
if (!is_array($post)) {
    recharge_fail(7, 'Param is Invalid', 6);
}

// payid is the only field required to choose a channel. The interceptor adds
// language/random/signature/timestamp; payTypeId may validly be 0.
if (!array_key_exists('payid', $post) || !is_numeric($post['payid'])) {
    recharge_fail(7, 'Param is Invalid', 6);
}

$payId = (int)$post['payid'];
$signature = strtoupper(trim((string)($post['signature'] ?? '')));
if ($signature === '') {
    recharge_fail(5, 'Wrong signature', 3);
}

$expected = recharge_signature($post);
$legacyExpected = recharge_legacy_signature($post);
if (!hash_equals($expected, $signature) && !hash_equals($legacyExpected, $signature)) {
    recharge_fail(5, 'Wrong signature', 3);
}

$token = recharge_bearer_token();
if ($token === '') {
    recharge_fail(4, 'No operation permission', 2, 401);
}

$verified = @json_decode(is_jwt_valid($token), true);
if (!is_array($verified) || ($verified['status'] ?? '') !== 'Success') {
    recharge_fail(4, 'No operation permission', 2, 401);
}

$stmt = $conn->prepare('SELECT akshinak FROM shonu_subjects WHERE akshinak=? LIMIT 1');
if (!$stmt) {
    if (function_exists('app_log_event')) {
        app_log_event('error', 'GetRechargeTypes session query prepare failed', [
            'database_error' => $conn->error,
            'pay_id' => $payId,
        ]);
    }
    recharge_fail(4, 'No operation permission', 2, 401);
}
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->store_result();
$sessionOk = $stmt->num_rows === 1;
$stmt->close();
if (!$sessionOk) {
    recharge_fail(4, 'No operation permission', 2, 401);
}

$site = recharge_site_url();
$inrAmounts = [100, 200, 300, 400, 500, 1000, 2000, 3000, 5000];
$channels = [];

switch ($payId) {
    case 2: // Innate UPI-QR – matches the reference layout supplied by user.
        $channels[] = recharge_channel(2, 1023, 'Phonepe_QR', 'Phonepe_QR', $site . '/pay/wepay.php', $inrAmounts, 0.03);
        break;
    case 1: // Expert Paytm-QR
        $channels[] = recharge_channel(1, 1010, 'Paytm_QR', 'Paytm_QR', $site . '/pay/wepay.php', $inrAmounts, 0.03);
        break;
    case 3: // UPI-QR PAY
        $channels[] = recharge_channel(3, 1030, 'UPI_QR', 'UPI_QR', $site . '/pay/wepay.php', $inrAmounts, 0.03);
        break;
    case 13: // UPI-QR / ArUpiPay tab in this build
        $channels[] = recharge_channel(13, 2191, 'Phonepe_QR', 'ArUpiPay', $site . '/pay/wepay.php', $inrAmounts, 0.03, 95000);
        break;
    case 11: // USDT
        $channels[] = recharge_channel(11, 2123, 'UPAY-USDT', '825', $site . '/pay/usdt.php', [10, 20, 50, 100, 200, 500, 1000, 2500, 5000, 10000], 0.02, 95000);
        break;
    case 21: // ARPay top-level tab; give the frontend a valid selectable channel.
        $channels[] = recharge_channel(21, 21001, 'ARPay', 'ARPay', $site . '/pay/wepay.php', $inrAmounts, 0.02, 96000);
        break;
    default:
        // Do not return an undefined rechargetypelist; a safe local UPI fallback
        // keeps the page interactive for any stale/legacy pay tab id.
        $channels[] = recharge_channel($payId, 1023, 'Phonepe_QR', 'Phonepe_QR', $site . '/pay/wepay.php', $inrAmounts, 0.03);
        break;
}

recharge_send([
    'data' => [
        'rechargetypelist' => $channels,
        'banklist' => [],
        'localUsdtlist' => [],
        'thirdPayBankList' => [],
    ],
    'code' => 0,
    'msg' => 'Succeed',
    'msgCode' => 0,
    'serviceNowTime' => date('Y-m-d H:i:s'),
]);
