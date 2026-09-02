<?php

require_once dirname(__DIR__, 2) . '/conn.php';
require_once dirname(__DIR__, 2) . '/functions2.php';
require_once dirname(__DIR__, 2) . '/app_core_live_v4.php';

app_install_schema($conn);

date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, AR-REAL-IP');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

$apiOrigin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
if ($apiOrigin !== '') {
    $originHost = strtolower((string)parse_url($apiOrigin, PHP_URL_HOST));
    $requestHost = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    if ($originHost !== '' && $requestHost !== '' && hash_equals($requestHost, $originHost)) {
        header('Access-Control-Allow-Origin: ' . $apiOrigin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function api_input(): array
{
    $data = $_POST;
    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $data = array_merge($data, $json);
        }
    }
    return $data;
}

function api_send($data = null, int $code = 0, string $msg = 'Succeed', int $http = 200, ?int $msgCode = null): void
{
    http_response_code($http);
    echo json_encode([
        'data' => $data,
        'code' => $code,
        'msg' => $msg,
        'msgCode' => $msgCode ?? ($code === 0 ? 0 : $code),
        'traceId' => '',
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        api_send(null, 11, 'Method not allowed', 405, 12);
    }
}

function api_signature_valid(array $body): bool
{
    $provided = strtoupper(trim((string)($body['signature'] ?? '')));
    if (!preg_match('/^[A-F0-9]{32}$/', $provided)) {
        return false;
    }

    unset($body['signature'], $body['timestamp']);
    foreach ($body as $key => $value) {
        if ($value === null || $value === '' || is_array($value) || is_object($value)) {
            unset($body[$key]);
        }
    }
    ksort($body, SORT_STRING);
    $canonical = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return is_string($canonical) && hash_equals(strtoupper(md5($canonical)), $provided);
}

function api_require_signature(array $body): void
{
    foreach (['language', 'random', 'signature', 'timestamp'] as $field) {
        if (!array_key_exists($field, $body)) {
            api_send(null, 7, 'Param is Invalid', 200, 6);
        }
    }
    if (!api_signature_valid($body)) {
        api_send(null, 5, 'Wrong signature', 200, 3);
    }
}

function api_bearer(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
    }
    return preg_match('/^Bearer\s+(.+)$/i', trim((string)$header), $match) ? trim($match[1]) : '';
}

function api_user(): array
{
    global $conn;
    $token = api_bearer();
    if ($token === '') {
        api_send(null, 4, 'No operation permission', 401, 2);
    }

    $verified = json_decode((string)is_jwt_valid($token), true);
    $id = (int)($verified['payload']['id'] ?? 0);
    if (!is_array($verified) || ($verified['status'] ?? '') !== 'Success' || $id < 1) {
        api_send(null, 4, 'No operation permission', 401, 2);
    }

    $stmt = $conn->prepare('SELECT id, mobile, status FROM shonu_subjects WHERE id=? AND akshinak=? LIMIT 1');
    if (!$stmt) {
        api_send(null, 8, 'Service temporarily unavailable', 503, 8);
    }
    $stmt->bind_param('is', $id, $token);
    $stmt->execute();
    $dbId = null;
    $mobile = null;
    $status = null;
    $stmt->bind_result($dbId, $mobile, $status);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found || (int)$status !== 1) {
        api_send(null, 4, 'No operation permission', 401, 2);
    }
    return ['id' => (int)$dbId, 'mobile' => (string)$mobile, 'token' => $token];
}

function api_table_exists(string $table): bool
{
    global $conn;
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }
    $escaped = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$escaped}'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function api_wallet_balance(int $userId): float
{
    global $conn;
    $stmt = $conn->prepare('SELECT COALESCE(motta,0) FROM shonu_kaichila WHERE balakedara=? LIMIT 1');
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $balance = 0.0;
    $stmt->bind_result($balance);
    $found = $stmt->fetch();
    $stmt->close();
    return $found ? (float)$balance : 0.0;
}

function api_wallet_payload(int $userId): array
{
    $balance = api_wallet_balance($userId);
    $vendors = ['Lottery', 'TB_Chess', 'Wickets9', 'CQ9', 'MG', 'JDB', 'DG', 'CMD', 'SaBa',
        'EVO_Video', 'JILI', 'Card365', 'V8Card', 'AG_Video', 'PG', 'TB', 'WM_Video', 'SEXY_Video'];
    $list = [];
    foreach ($vendors as $index => $vendor) {
        $list[] = ['vendorCode' => $vendor, 'balance' => $index === 0 ? $balance : 0.0];
    }
    return [
        'amount' => $balance,
        'thidGameBalanceList' => $list,
        'totalWithdraw' => 0.0,
        'totalRecharge' => 0.0,
    ];
}
