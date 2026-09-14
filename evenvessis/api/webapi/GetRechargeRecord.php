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

function grr_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function grr_fail(int $code, string $msg, int $msgCode, int $status = 200): void
{
    if (function_exists('app_log_event')) {
        app_log_event('warning', 'GetRechargeRecord API failure', [
            'code' => $code,
            'msg_code' => $msgCode,
            'http_status' => $status,
            'reason' => $msg,
            'request_meta' => $GLOBALS['grr_request_meta'] ?? [],
        ]);
    }
    grr_send([
        'data' => null,
        'code' => $code,
        'msg' => $msg,
        'msgCode' => $msgCode,
        'requestId' => function_exists('app_request_id') ? app_request_id() : null,
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ], $status);
}

function grr_auth_header(): string
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

function grr_token(): string
{
    $header = grr_auth_header();
    if ($header === '') {
        return '';
    }
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return trim($m[1]);
    }
    return trim($header);
}

/*
 * Same signature contract as the bundled frontend interceptor:
 * - language/random are injected by the interceptor
 * - signature/timestamp are excluded from the signed JSON
 * - empty strings/null are excluded
 * - keys are sorted alphabetically
 * - MD5 is upper-cased
 *
 * IMPORTANT: the deposit-page preview sends only payId, while the full history
 * component adds pagination/date/state fields. Both payload shapes are valid.
 */
function grr_signature(array $payload): string
{
    unset($payload['signature'], $payload['timestamp']);
    $ignore = ['track' => true, 'xosoBettingData' => true];
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    grr_fail(11, 'Method not allowed', 12, 405);
}

$raw = file_get_contents('php://input');
$post = json_decode((string)$raw, true);
$GLOBALS['grr_request_meta'] = [
    'body_length' => strlen((string)$raw),
    'json_error' => json_last_error_msg(),
    'body_keys' => is_array($post) ? array_keys($post) : [],
];
if (!is_array($post)) {
    grr_fail(7, 'Param is Invalid', 6);
}

// Two bundled components call this endpoint:
// 1. Deposit-page preview sends only payId (plus interceptor fields).
// 2. Full history sends pageNo/pageSize/date/state/payId/payTypeId.
// Pagination and state therefore need safe defaults instead of being required.
$payIdValue = $post['payId'] ?? $post['payid'] ?? null;
if ($payIdValue === null || !is_numeric($payIdValue) || !array_key_exists('signature', $post)) {
    grr_fail(7, 'Param is Invalid', 6);
}

foreach (['pageNo', 'pageSize', 'state'] as $numericKey) {
    if (array_key_exists($numericKey, $post) && !is_numeric($post[$numericKey])) {
        grr_fail(7, 'Param is Invalid', 6);
    }
}

$pageNo = max(1, (int)($post['pageNo'] ?? 1));
$pageSize = max(1, min(100, (int)($post['pageSize'] ?? 5)));
$payId = (int)$payIdValue;
$state = (int)($post['state'] ?? -1);
$startDate = trim((string)($post['startDate'] ?? ''));
$endDate = trim((string)($post['endDate'] ?? ''));
$signature = strtoupper(trim((string)$post['signature']));

if ($signature === '' || !hash_equals(grr_signature($post), $signature)) {
    grr_fail(5, 'Wrong signature', 3);
}

$token = grr_token();
if ($token === '') {
    grr_fail(4, 'No operation permission', 2, 401);
}

$verified = @json_decode(is_jwt_valid($token), true);
if (!is_array($verified) || ($verified['status'] ?? '') !== 'Success') {
    grr_fail(4, 'No operation permission', 2, 401);
}

$userId = (int)($verified['payload']['id'] ?? 0);
if ($userId <= 0) {
    grr_fail(4, 'No operation permission', 2, 401);
}

$stmt = $conn->prepare('SELECT akshinak FROM shonu_subjects WHERE akshinak=? LIMIT 1');
if (!$stmt) {
    grr_fail(4, 'No operation permission', 2, 401);
}
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->store_result();
$sessionOk = $stmt->num_rows === 1;
$stmt->close();
if (!$sessionOk) {
    grr_fail(4, 'No operation permission', 2, 401);
}

$offset = ($pageNo - 1) * $pageSize;
$where = ['balakedara = ' . $userId];
if ($state !== -1) {
    $where[] = 'sthiti = ' . $state;
}

// Preserve the old endpoint's date filtering, but only when dates are supplied.
if ($startDate !== '') {
    $safeStart = mysqli_real_escape_string($conn, $startDate);
    $where[] = "date(dinankavannuracisi) >= date('{$safeStart}')";
}
if ($endDate !== '') {
    $safeEnd = mysqli_real_escape_string($conn, $endDate);
    $where[] = "date(dinankavannuracisi) <= date('{$safeEnd}')";
}
$whereSql = implode(' AND ', $where);

$listSql = "SELECT dharavahi, dinankavannuracisi, madari, motta, sthiti, pavatiaidi, mula
            FROM thevani
            WHERE {$whereSql}
            ORDER BY shonu DESC
            LIMIT {$pageSize} OFFSET {$offset}";
$countSql = "SELECT COUNT(*) AS cnt FROM thevani WHERE {$whereSql}";

$listResult = false;
$countResult = false;
try {
    $listResult = $conn->query($listSql);
    $countResult = $conn->query($countSql);
} catch (Throwable $queryError) {
    if (function_exists('app_log_event')) {
        app_log_event('error', 'GetRechargeRecord database query threw an exception', [
            'exception' => get_class($queryError),
            'database_error' => $queryError->getMessage(),
            'file' => $queryError->getFile(),
            'line' => $queryError->getLine(),
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'pay_id' => $payId,
            'state' => $state,
        ]);
    }
}
if (!$listResult || !$countResult) {
    if (function_exists('app_log_event')) {
        app_log_event('error', 'GetRechargeRecord database query failed', [
            'database_error' => $conn->error,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'pay_id' => $payId,
            'state' => $state,
        ]);
    }
    // A history read failure must not break the recharge UI or emit code 6.
    grr_send([
        'data' => ['list' => [], 'pageNo' => $pageNo, 'pageSize' => $pageSize, 'totalPage' => 0, 'totalCount' => 0],
        'code' => 0,
        'msg' => 'Succeed',
        'msgCode' => 0,
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ]);
}

$countRow = $countResult->fetch_assoc();
$totalCount = (int)($countRow['cnt'] ?? 0);
$list = [];
while ($row = $listResult->fetch_assoc()) {
    $list[] = [
        'rechargeNumber' => $row['dharavahi'],
        'addTime' => $row['dinankavannuracisi'],
        'type' => (int)$row['madari'],
        'price' => (float)$row['motta'],
        'orderAmount' => (float)$row['motta'],
        'state' => (int)$row['sthiti'],
        'uRate' => null,
        'uGold' => 0,
        'payID' => (int)$row['pavatiaidi'],
        'payName' => $row['mula'],
    ];
}

grr_send([
    'data' => [
        'list' => $list,
        'pageNo' => $pageNo,
        'pageSize' => $pageSize,
        'totalPage' => $totalCount > 0 ? (int)ceil($totalCount / $pageSize) : 0,
        'totalCount' => $totalCount,
    ],
    'code' => 0,
    'msg' => 'Succeed',
    'msgCode' => 0,
    'serviceNowTime' => date('Y-m-d H:i:s'),
]);
