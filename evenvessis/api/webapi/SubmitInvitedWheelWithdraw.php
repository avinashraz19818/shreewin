<?php 
include "../../conn.php";
include "../../functions2.php";

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
|
| Signature check ko ON/OFF karne ke liye flag.
| Abhi testing ke liye false rakho, jab client ka exact sign logic mil jaye
| to isse true kar dena.
|
*/
$ENABLE_SIGNATURE_CHECK = false;

/*
|--------------------------------------------------------------------------
| Helper: Server-side signature build
|--------------------------------------------------------------------------
|
| Yahan wohi string banao jaisa client use karta hai MD5 ke liye.
| Abhi example ke liye tumhara old pattern dala hai:
|   {"language":0,"random":"xxxx"}
| Client ka exact code mil jaaye to yahi function update kar dena.
|
*/
function build_server_signature($language, $random, $timestamp) {
    // TODO: yahan client ke exact sign logic se match karao
    // Example pattern (tumhara purana)
    $str = '{"language":'.$language.',"random":"'.$random.'"}';
    return strtoupper(md5($str));
}

/*
|--------------------------------------------------------------------------
| Helper: Auto-create base tables (NO shonu_withdrawals/transactions)
|--------------------------------------------------------------------------
*/
function ensureTableAndColumns($conn) {
    // 1. shonu_turntable
    $conn->query("CREATE TABLE IF NOT EXISTS shonu_turntable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        invited_wheel_amount DECIMAL(10,2) DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. shonu_kaichila
    $conn->query("CREATE TABLE IF NOT EXISTS shonu_kaichila (
        id INT AUTO_INCREMENT PRIMARY KEY,
        balakedara INT NOT NULL,
        motta DECIMAL(10,2) DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. tbl_config
    $conn->query("CREATE TABLE IF NOT EXISTS tbl_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE,
        value VARCHAR(100)
    )");
}

// ----------------- Call helper -----------------
ensureTableAndColumns($conn);

// ----------------- Headers -----------------
header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');

// Preflight (optional)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

date_default_timezone_set("Asia/Dhaka");
$now = date("Y-m-d H:i:s");

// ----------------- Default Response -----------------
$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $now,
];

// ----------------- Only POST allowed -----------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode($res);
    exit();
}

// ----------------- Read body -----------------
$rawBody = file_get_contents("php://input");
$post    = json_decode($rawBody, true);

// ----------------- Basic param check -----------------
if (!isset($post['language'], $post['random'], $post['signature'], $post['timestamp'])) {
    $res['code'] = 7;
    $res['msg']  = 'Param is Invalid';
    $res['msgCode'] = 6;
    echo json_encode($res);
    exit();
}

// ----------------- Signature handling -----------------
$languageRaw  = trim((string)$post['language']);
$randomRaw    = trim((string)$post['random']);
$timestampRaw = trim((string)$post['timestamp']);
$clientSig    = strtoupper(trim((string)$post['signature']));

// Server-side sign (current guess)
$serverSig = build_server_signature($languageRaw, $randomRaw, $timestampRaw);

// Log for debugging
error_log("SIGN_DEBUG_SIMPLE: client={$clientSig} | server={$serverSig} | language={$languageRaw} | random={$randomRaw} | ts={$timestampRaw}");

if ($ENABLE_SIGNATURE_CHECK && $serverSig !== $clientSig) {
    $res['code'] = 5;
    $res['msg']  = 'Wrong signature';
    $res['msgCode'] = 3;
    echo json_encode($res);
    exit();
}

// ----------------- Authorization Header Check -----------------
if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $res['code'] = 4;
    $res['msg']  = 'Authorization header missing';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit();
}

$bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
$token  = $bearer[1] ?? '';

if (empty($token)) {
    $res['code'] = 4;
    $res['msg']  = 'Token missing';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit();
}

// JWT validate
$is_jwt_valid = is_jwt_valid($token);
$data_auth    = json_decode($is_jwt_valid, true);

if (!$data_auth || !isset($data_auth['status']) || $data_auth['status'] !== 'Success') {
    $res['code'] = 4;
    $res['msg']  = 'No operation permission';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit();
}

// Extra session validation
$sesquery  = "SELECT akshinak FROM shonu_subjects WHERE akshinak = '".$conn->real_escape_string($token)."'";
$sesresult = $conn->query($sesquery);

if (!$sesresult || mysqli_num_rows($sesresult) !== 1) {
    $res['code'] = 4;
    $res['msg']  = 'No operation permission';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit();
}

// User ID
$userId = (int)$data_auth['payload']['id'];

// Amount
$withdrawAmount = isset($post['amount']) ? (float)$post['amount'] : 0.0;

if ($withdrawAmount <= 0) {
    $res['code'] = 7;
    $res['msg']  = 'Invalid amount';
    $res['msgCode'] = 6;
    echo json_encode($res);
    exit();
}

// ----------------- Get current turntable balance -----------------
$balanceQuery  = "SELECT invited_wheel_amount FROM shonu_turntable WHERE user_id = $userId";
$balanceResult = $conn->query($balanceQuery);
$currentBalance = 0.0;

if ($balanceResult && mysqli_num_rows($balanceResult) > 0) {
    $balanceRow     = mysqli_fetch_array($balanceResult);
    $currentBalance = (float)$balanceRow['invited_wheel_amount'];
}

if ($currentBalance < $withdrawAmount) {
    $res['code'] = 8;
    $res['msg']  = 'Insufficient balance';
    $res['msgCode'] = 7;
    echo json_encode($res);
    exit();
}

// ----------------- Minimum withdrawal -----------------
$minWithdrawAmount = 500.0;
$minQuery = "SELECT value FROM tbl_config WHERE name='min_withdraw_amount' LIMIT 1";
$minRes   = $conn->query($minQuery);
if ($minRes && mysqli_num_rows($minRes) > 0) {
    $row = mysqli_fetch_array($minRes);
    $minWithdrawAmount = (float)$row['value'];
}

if ($withdrawAmount < $minWithdrawAmount) {
    $res['code'] = 9;
    $res['msg']  = "Minimum withdrawal amount is $minWithdrawAmount";
    $res['msgCode'] = 8;
    echo json_encode($res);
    exit();
}

// ----------------- DB Transaction -----------------
$conn->begin_transaction();
try {

    // 1. Deduct from shonu_turntable
    $updateTurnQuery = "
        UPDATE shonu_turntable 
        SET invited_wheel_amount = invited_wheel_amount - $withdrawAmount,
            updated_at = NOW()
        WHERE user_id = $userId
    ";
    $conn->query($updateTurnQuery);

    // 2. Insert into hyper_withdraw
    $withdrawTime = date('Y-m-d H:i:s');

    if (function_exists('random_bytes')) {
        $rand = bin2hex(random_bytes(4));
    } else {
        $rand = substr(md5(mt_rand()), 0, 8);
    }

    $orderNo    = 'IW' . date('YmdHis') . $rand;
    $auditState = 0;
    $reason     = '';

    $insertWithdraw = "
        INSERT INTO hyper_withdraw 
            (order_no, user_id, withdraw_amount, audit_state, withdrawal_type, create_time, reason)
        VALUES 
            ('$orderNo', $userId, $withdrawAmount, $auditState, 'turntable', '$withdrawTime', '$reason')
    ";
    $conn->query($insertWithdraw);
    $withdrawalId = $conn->insert_id;

    // 3. Insert into hyper_transactions
    $insertTrans = "
        INSERT INTO hyper_transactions 
            (user_id, amount, transaction_type, description, status, created_at)
        VALUES 
            ($userId, $withdrawAmount, 'withdrawal', 'Turntable withdrawal', 'completed', '$withdrawTime')
    ";
    $conn->query($insertTrans);

    // Commit
    $conn->commit();

    $res['code'] = 0;
    $res['msg']  = 'Withdrawal request submitted successfully';
    $res['msgCode'] = 0;
    $res['data'] = [
        'withdrawalId' => (int)$withdrawalId,
        'orderNo'      => $orderNo,
        'userId'       => (int)$userId,
        'amount'       => (float)$withdrawAmount,
        'newBalance'   => (float)($currentBalance - $withdrawAmount),
        'auditState'   => (int)$auditState,
        'withdrawTime' => $withdrawTime,
        'reason'       => $reason
    ];
    $res['serviceNowTime'] = $now;

} catch (Exception $e) {
    $conn->rollback();
    $res['code'] = 500;
    $res['msg']  = 'Transaction failed: '.$e->getMessage();
    $res['msgCode'] = 13;
}

// Final output
echo json_encode($res);
exit();
?>
