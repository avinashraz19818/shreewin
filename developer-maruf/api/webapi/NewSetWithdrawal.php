<?php
include "../../conn.php";
include "../../functions2.php";
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_withdrawal_rules.php';

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header('Access-Control-Allow-Origin: ' . $origin);
header('vary: Origin');


if (!function_exists('nw_frontend_signature')) {
    function nw_frontend_signature(array $payload): string
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
}

date_default_timezone_set('Asia/Kolkata');
$shnunc = date('Y-m-d H:i:s');
$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $shnunc,
];

$shonubody = file_get_contents('php://input');
$shonupost = json_decode($shonubody, true);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(405);
    echo json_encode($res);
    exit;
}

$required = ['amount', 'bid', 'language', 'pwd', 'random', 'signature', 'timestamp', 'type'];
foreach ($required as $key) {
    if (!is_array($shonupost) || !array_key_exists($key, $shonupost)) {
        $res['code'] = 7;
        $res['msg'] = 'Param is Invalid';
        $res['msgCode'] = 6;
        http_response_code(200);
        echo json_encode($res);
        exit;
    }
}

$amountRaw = mysqli_real_escape_string($conn, (string)$shonupost['amount']);
$bid = mysqli_real_escape_string($conn, (string)$shonupost['bid']);
$language = mysqli_real_escape_string($conn, (string)$shonupost['language']);
$pwd = mysqli_real_escape_string($conn, (string)$shonupost['pwd']);
$random = mysqli_real_escape_string($conn, (string)$shonupost['random']);
$signature = strtoupper(mysqli_real_escape_string($conn, (string)$shonupost['signature']));
$typeRaw = mysqli_real_escape_string($conn, (string)$shonupost['type']);

$shonustr = '{"amount":' . $amountRaw . ',"bid":"' . $bid . '","language":' . $language . ',"pwd":"' . $pwd . '","random":"' . $random . '","type":' . $typeRaw . '}';
$shonusign = nw_frontend_signature($shonupost);

if (!hash_equals($shonusign, $signature)) {
    $res['code'] = 5;
    $res['msg'] = 'Wrong signature';
    $res['msgCode'] = 3;
    http_response_code(200);
    echo json_encode($res);
    exit;
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$bearer = preg_split('/\s+/', trim($authHeader), 2);
$author = (count($bearer) === 2) ? $bearer[1] : '';
$is_jwt_valid = is_jwt_valid($author);
$data_auth = json_decode($is_jwt_valid, true);
if (!is_array($data_auth) || ($data_auth['status'] ?? '') !== 'Success') {
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit;
}

$shonuid = (int)($data_auth['payload']['id'] ?? 0);
$amount = round((float)$amountRaw, 2);
$type = (int)$typeRaw;
if ($shonuid <= 0 || !is_finite($amount) || $amount < 110 || $amount > 50000) {
    $res['code'] = 1;
    $res['msg'] = 'Insufficient balance or invalid amount range';
    $res['msgCode'] = 142;
    http_response_code(200);
    echo json_encode($res);
    exit;
}

$turnover = withdrawal_turnover($shonuid);
if (($turnover['remaining'] ?? 0) > 0) {
    $res['code'] = 1;
    $res['msg'] = 'Need to bet ₹' . number_format((float)$turnover['remaining'], 2) . ' to be able to withdraw';
    $res['msgCode'] = 142;
    http_response_code(200);
    echo json_encode($res);
    exit;
}

try {
    $conn->begin_transaction();

    $walletStmt = $conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara = ? FOR UPDATE');
    if (!$walletStmt) {
        throw new Exception('Wallet query prepare failed');
    }
    $walletStmt->bind_param('i', $shonuid);
    $walletStmt->execute();
    $walletResult = $walletStmt->get_result();
    $walletRow = $walletResult ? $walletResult->fetch_assoc() : null;
    $walletStmt->close();
    if (!$walletRow) {
        throw new Exception('Wallet not found');
    }

    $balance = round((float)$walletRow['motta'], 2);
    if ($amount > $balance) {
        $conn->rollback();
        $res['code'] = 1;
        $res['msg'] = 'Insufficient balance or invalid amount range';
        $res['msgCode'] = 142;
        http_response_code(200);
        echo json_encode($res);
        exit;
    }

    $demoStmt = $conn->prepare('SELECT 1 FROM demo WHERE balakedara = ? LIMIT 1');
    $isDemoUser = false;
    if ($demoStmt) {
        $demoStmt->bind_param('i', $shonuid);
        $demoStmt->execute();
        $demoResult = $demoStmt->get_result();
        $isDemoUser = $demoResult && $demoResult->num_rows > 0;
        $demoStmt->close();
    }

    $newBalance = round($balance - $amount, 2);
    $updateStmt = $conn->prepare('UPDATE shonu_kaichila SET motta = ? WHERE balakedara = ?');
    if (!$updateStmt) {
        throw new Exception('Wallet update prepare failed');
    }
    $updateStmt->bind_param('di', $newBalance, $shonuid);
    if (!$updateStmt->execute() || $updateStmt->affected_rows < 0) {
        $updateStmt->close();
        throw new Exception('Wallet update failed');
    }
    $updateStmt->close();

    $serial = 'W' . date('Ymd') . time() . random_int(1000, 9999);
    $state = $isDemoUser ? 1 : 0;
    $ticket = 'Applied';
    $insertStmt = $conn->prepare('INSERT INTO hintegedukolli (balakedara, motta, dharavahi, khateshonu, dinankavannuracisi, madari, tike, sthiti) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$insertStmt) {
        throw new Exception('Withdrawal insert prepare failed');
    }
    $insertStmt->bind_param('idsssisi', $shonuid, $amount, $serial, $bid, $shnunc, $type, $ticket, $state);
    if (!$insertStmt->execute()) {
        $err = $insertStmt->error;
        $insertStmt->close();
        throw new Exception('Withdrawal request could not be saved: ' . $err);
    }
    $insertStmt->close();

    $conn->commit();

    $res['data'] = [
        'shonuid' => $shonuid,
        'serial' => $serial,
        'amount' => $amount,
        'type' => $type,
        'time' => $shnunc,
        'state' => $state,
    ];
    $res['code'] = 0;
    $res['msg'] = 'Succeed';
    $res['msgCode'] = 0;
    http_response_code(200);
} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }
    error_log('NewSetWithdrawal failed: ' . $e->getMessage());
    $res['code'] = 1;
    $res['msg'] = 'Withdrawal request failed. Please try again.';
    $res['msgCode'] = 101;
    http_response_code(500);
}

echo json_encode($res);
mysqli_close($conn);
?>
