<?php
include "../../conn.php";
include "../../functions2.php";
require_once __DIR__ . '/_game_statistics_sync.php';

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
$serviceNow = date('Y-m-d H:i:s');

function gs_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function gs_fail(int $code, string $msg, int $msgCode, int $status = 200): void
{
    gs_send([
        'data' => null,
        'code' => $code,
        'msg' => $msg,
        'msgCode' => $msgCode,
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ], $status);
}

function gs_signature(array $payload): string
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

function gs_token(): string
{
    $header = '';
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
        if (!empty($_SERVER[$key])) {
            $header = trim((string)$_SERVER[$key]);
            break;
        }
    }
    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = trim((string)($headers['Authorization'] ?? $headers['authorization'] ?? ''));
    }
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
    gs_fail(11, 'Method not allowed', 12, 405);
}

$post = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($post)) {
    gs_fail(7, 'Param is Invalid', 6);
}
foreach (['language', 'random', 'signature', 'timestamp'] as $required) {
    if (!array_key_exists($required, $post)) {
        gs_fail(7, 'Param is Invalid', 6);
    }
}

$signature = strtoupper(trim((string)$post['signature']));
if ($signature === '' || !hash_equals(gs_signature($post), $signature)) {
    gs_fail(5, 'Wrong signature', 3);
}

$token = gs_token();
$verified = $token !== '' ? @json_decode(is_jwt_valid($token), true) : null;
if (!is_array($verified) || ($verified['status'] ?? '') !== 'Success') {
    gs_fail(4, 'No operation permission', 2, 401);
}
$userId = (int)($verified['payload']['id'] ?? 0);
if ($userId <= 0) {
    gs_fail(4, 'No operation permission', 2, 401);
}

$stmt = $conn->prepare('SELECT akshinak FROM shonu_subjects WHERE akshinak=? LIMIT 1');
if (!$stmt) {
    gs_fail(4, 'No operation permission', 2, 401);
}
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->store_result();
$sessionOk = $stmt->num_rows === 1;
$stmt->close();
if (!$sessionOk) {
    gs_fail(4, 'No operation permission', 2, 401);
}

$startDate = trim((string)($post['startDate'] ?? ''));
$endDate = trim((string)($post['endDate'] ?? ''));
send_synced_game_statistics($conn, $userId, $startDate, $endDate, $serviceNow);
