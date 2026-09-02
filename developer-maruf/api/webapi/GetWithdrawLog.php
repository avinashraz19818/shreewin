<?php
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? (string)$_SERVER['HTTP_ORIGIN'] : '';
if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

date_default_timezone_set('Asia/Kolkata');

function gwl_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function gwl_fail(int $code, string $msg, int $msgCode, int $status = 200): void
{
    gwl_send([
        'data' => null,
        'code' => $code,
        'msg' => $msg,
        'msgCode' => $msgCode,
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ], $status);
}

/* Same signature contract as the bundled frontend request interceptor. */
function gwl_signature(array $payload): string
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

function gwl_auth_header(): string
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

function gwl_token(): string
{
    $header = gwl_auth_header();
    if ($header === '') {
        return '';
    }
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return trim($m[1]);
    }
    return trim($header);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gwl_fail(11, 'Method not allowed', 12, 405);
}

$post = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($post)) {
    gwl_fail(8, 'Required parameters missing', 7, 400);
}

foreach (['type', 'pageNo', 'pageSize', 'signature'] as $required) {
    if (!array_key_exists($required, $post)) {
        gwl_fail(8, 'Required parameters missing', 7, 400);
    }
}

$signature = strtoupper(trim((string)$post['signature']));
if ($signature === '' || !hash_equals(gwl_signature($post), $signature)) {
    gwl_fail(7, 'Invalid signature', 6, 403);
}

$token = gwl_token();
$verified = $token !== '' ? @json_decode(is_jwt_valid($token), true) : null;
if (!is_array($verified) || ($verified['status'] ?? '') !== 'Success') {
    gwl_fail(5, 'Invalid JWT', 3, 401);
}

$userId = (int)($verified['payload']['id'] ?? 0);
if ($userId <= 0) {
    gwl_fail(5, 'Invalid JWT', 3, 401);
}

$stmt = $conn->prepare('SELECT akshinak FROM shonu_subjects WHERE akshinak=? LIMIT 1');
if (!$stmt) {
    gwl_fail(4, 'No operation permission', 2, 403);
}
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->store_result();
$sessionOk = $stmt->num_rows === 1;
$stmt->close();
if (!$sessionOk) {
    gwl_fail(4, 'No operation permission', 2, 403);
}

$type = (int)$post['type'];
$pageNo = max(1, (int)$post['pageNo']);
$pageSize = max(1, min(100, (int)$post['pageSize']));
$startDate = trim((string)($post['startDate'] ?? ''));
$endDate = trim((string)($post['endDate'] ?? ''));

$where = ['balakedara = ' . $userId];
if (in_array($type, [1, 2, 3, 4], true)) {
    $where[] = 'madari = ' . $type;
}
if ($startDate !== '') {
    $safeStart = mysqli_real_escape_string($conn, $startDate);
    $where[] = "DATE(dinankavannuracisi) >= DATE('{$safeStart}')";
}
if ($endDate !== '') {
    $safeEnd = mysqli_real_escape_string($conn, $endDate);
    $where[] = "DATE(dinankavannuracisi) <= DATE('{$safeEnd}')";
}
$whereSql = implode(' AND ', $where);
$offset = ($pageNo - 1) * $pageSize;

$countResult = $conn->query("SELECT COUNT(*) AS totalCount FROM hintegedukolli WHERE {$whereSql}");
if (!$countResult) {
    gwl_fail(6, 'Database query failed', 4, 500);
}
$totalCount = (int)(($countResult->fetch_assoc()['totalCount'] ?? 0));
$totalPage = $totalCount > 0 ? (int)ceil($totalCount / $pageSize) : 0;

$result = $conn->query(
    "SELECT shonu, motta, remarks, dinankavannuracisi, madari, sthiti, dharavahi
     FROM hintegedukolli
     WHERE {$whereSql}
     ORDER BY shonu DESC
     LIMIT {$pageSize} OFFSET {$offset}"
);
if (!$result) {
    gwl_fail(6, 'Database query failed', 4, 500);
}

$typeNames = [
    1 => 'BANK CARD',
    2 => 'UPI',
    3 => 'USDT',
    4 => 'E-WALLET',
];
$list = [];
while ($row = $result->fetch_assoc()) {
    $rowType = (int)$row['madari'];
    $amount = (float)$row['motta'];
    $state = (int)$row['sthiti'];
    $list[] = [
        'withdrawID' => $row['shonu'],
        'type' => $rowType,
        'withdrawNumber' => $row['dharavahi'],
        'withdrawName' => $typeNames[$rowType] ?? 'OTHER',
        'price' => $amount,
        'addTime' => $row['dinankavannuracisi'],
        'realityAmount' => $amount,
        'remark' => $row['remarks'],
        'state' => $state,
        'thirdpartyState' => $state,
    ];
}

gwl_send([
    'data' => [
        'list' => $list,
        'pageNo' => $pageNo,
        'totalPage' => $totalPage,
        'totalCount' => $totalCount,
    ],
    'code' => 0,
    'msg' => 'Succeed',
    'msgCode' => 0,
    'serviceNowTime' => date('Y-m-d H:i:s'),
]);
