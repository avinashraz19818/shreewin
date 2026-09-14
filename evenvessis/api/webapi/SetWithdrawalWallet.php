<?php 
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');
header('Vary: Origin');

// Handle CORS origin
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allow_origin = '';
if ($origin) {
    $stmt = $conn->prepare("SELECT domain FROM allowed_origins WHERE domain=? AND status=1");
    if ($stmt) {
        $stmt->bind_param("s", $origin);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $allow_origin = $origin;
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($allow_origin) header("Access-Control-Allow-Origin: $allow_origin");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, ar-origin, ar-real-ip, ar-session');
    exit(0);
}

if ($allow_origin) {
    header("Access-Control-Allow-Origin: $allow_origin");
}

date_default_timezone_set("Asia/Dhaka");
$shnunc = date("Y-m-d H:i:s");
$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $shnunc,
];

// Check request method
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode($res);
    exit;
}

// Get request body
$shonubody = file_get_contents("php://input");
$shonupost = json_decode($shonubody, true);

if ($shonupost === null) {
    $res['code'] = 7;
    $res['msg'] = 'Invalid JSON data';
    $res['msgCode'] = 6;
    http_response_code(400);
    echo json_encode($res);
    exit;
}

// Check required parameters
$required_params = ['withdrawId', 'mobileNo', 'bankId', 'beneficiaryName', 'type', 'codeType', 'language', 'random', 'signature', 'timestamp'];
foreach ($required_params as $param) {
    if (!isset($shonupost[$param])) {
        $res['code'] = 7;
        $res['msg'] = 'Missing parameter: ' . $param;
        $res['msgCode'] = 6;
        http_response_code(400);
        echo json_encode($res);
        exit;
    }
}

// Sanitize inputs
$withdrawId = mysqli_real_escape_string($conn, $shonupost['withdrawId']);
$mobileNo = mysqli_real_escape_string($conn, $shonupost['mobileNo']);
$bankId = mysqli_real_escape_string($conn, $shonupost['bankId']);
$beneficiaryName = mysqli_real_escape_string($conn, $shonupost['beneficiaryName']);
$type = mysqli_real_escape_string($conn, $shonupost['type']);
$codeType = mysqli_real_escape_string($conn, $shonupost['codeType']);
$language = mysqli_real_escape_string($conn, $shonupost['language']);
$random = mysqli_real_escape_string($conn, $shonupost['random']);
$timestamp = mysqli_real_escape_string($conn, $shonupost['timestamp']);
$signature = mysqli_real_escape_string($conn, $shonupost['signature']);

// Check Authorization header
$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
if (empty($authHeader) || !preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    $res['code'] = 4;
    $res['msg'] = 'Missing or invalid Authorization header';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit;
}

$author = $matches[1];
$is_jwt_valid = is_jwt_valid($author);

if ($is_jwt_valid === false) {
    $res['code'] = 4;
    $res['msg'] = 'Invalid JWT';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit;
}

$data_auth = json_decode($is_jwt_valid, true);
if (!$data_auth || !isset($data_auth['status']) || $data_auth['status'] !== 'Success') {
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit;
}

// Get user ID from token
$shonuid = isset($data_auth['payload']['id']) ? (int)$data_auth['payload']['id'] : 0;
if ($shonuid <= 0) {
    $res['code'] = 4;
    $res['msg'] = 'Invalid user ID';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit;
}

// Verify user exists in shonu_subjects
$stmt = $conn->prepare("SELECT akshinak, id FROM shonu_subjects WHERE akshinak = ?");
if (!$stmt) {
    $res['code'] = 9;
    $res['msg'] = 'Database prepare failed';
    $res['msgCode'] = 8;
    http_response_code(500);
    echo json_encode($res);
    exit;
}

$stmt->bind_param("s", $author);
$stmt->execute();
$sesresult = app_stmt_result($stmt);
$sesnum = $sesresult->num_rows;
if ($sesnum != 1) {
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit;
}

$sesrow = $sesresult->fetch_assoc();
$stmt->close();

// ============================================
// CHECK IF ACCOUNT NUMBER ALREADY EXISTS FOR ANY USER
// ============================================
$checkDuplicateStmt = $conn->prepare("SELECT id, userid FROM bankcard WHERE account = ? AND type = ?");
if (!$checkDuplicateStmt) {
    $res['code'] = 9;
    $res['msg'] = 'Database prepare failed';
    $res['msgCode'] = 8;
    http_response_code(500);
    echo json_encode($res);
    exit;
}

$checkDuplicateStmt->bind_param("ss", $mobileNo, $bankId);
$checkDuplicateStmt->execute();
$duplicateResult = app_stmt_result($checkDuplicateStmt);

if ($duplicateResult->num_rows > 0) {
    $duplicateRow = $duplicateResult->fetch_assoc();
    
    // Check if it's the same user
    if ($duplicateRow['userid'] == $shonuid) {
        // Same user - they already added this account
        $res['code'] = 1;
        $res['msg'] = 'You have already added this wallet';
        $res['msgCode'] = 208; // 208 = already added by self
        http_response_code(200);
        echo json_encode($res);
        $checkDuplicateStmt->close();
        $conn->close();
        exit;
    } else {
        // Different user - account already in use
        $res['code'] = 1;
        $res['msg'] = 'This account number is already linked to another user';
        $res['msgCode'] = 12123; // 209 = linked to another user
        http_response_code(200);
        echo json_encode($res);
        $checkDuplicateStmt->close();
        $conn->close();
        exit;
    }
}
$checkDuplicateStmt->close();

// ============================================
// CHECK IF USER ALREADY HAS THIS TYPE OF WALLET (OPTIONAL)
// ============================================
$checkUserWalletStmt = $conn->prepare("SELECT id FROM bankcard WHERE userid = ? AND type = ?");
if (!$checkUserWalletStmt) {
    $res['code'] = 9;
    $res['msg'] = 'Database prepare failed';
    $res['msgCode'] = 8;
    http_response_code(500);
    echo json_encode($res);
    exit;
}

$checkUserWalletStmt->bind_param("is", $shonuid, $bankId);
$checkUserWalletStmt->execute();
$userWalletResult = app_stmt_result($checkUserWalletStmt);

// Optional: Uncomment if you want to limit one wallet per type
if ($userWalletResult->num_rows > 0) {
    $walletName = ($bankId == '23') ? 'EasyPaisa' : 'JazzCash';
    $res['code'] = 1;
    $res['msg'] = 'You can only add one ' . $walletName . ' wallet';
    $res['msgCode'] = 210; // 210 = only one wallet per type
    http_response_code(200);
    echo json_encode($res);
    $checkUserWalletStmt->close();
    $conn->close();
    exit;
}
$checkUserWalletStmt->close();

// ============================================
// INSERT NEW BANK CARD
// ============================================
$insertStmt = $conn->prepare("INSERT INTO bankcard (userid, account, name, type) VALUES (?, ?, ?, ?)");
if (!$insertStmt) {
    $res['code'] = 9;
    $res['msg'] = 'Database prepare failed: ' . $conn->error;
    $res['msgCode'] = 8;
    http_response_code(500);
    echo json_encode($res);
    exit;
}

$insertStmt->bind_param("issi", $shonuid, $mobileNo, $beneficiaryName, $bankId);
if ($insertStmt->execute()) {
    $inserted_id = $insertStmt->insert_id;
    
    // ============================================
    // RETURN SUCCESS RESPONSE WITH CORRECT FORMAT
    // ============================================
    $walletName = ($bankId == '23') ? 'EasyPaisa' : 'JazzCash';
    
    $responseData = [
        'bid' => $inserted_id,
        'bankName' => $walletName,
        'walletName' => $walletName,
        'beneficiaryName' => $beneficiaryName,
        'accountNo' => substr($mobileNo, 0, 3) . '***' . substr($mobileNo, -2),
        'ifsCode' => 'ifsc',
        'withType' => (int)$bankId,
        'mobileNO' => substr($mobileNo, 0, 3) . '***' . substr($mobileNo, -2),
        'bankProvince' => 'a',
        'bankCity' => 'b',
        'bankAddress' => 'c'
    ];
    
    $res['data'] = $responseData;
    $res['code'] = 0;
    $res['msg'] = 'Succeed';
    $res['msgCode'] = 0;
    http_response_code(200);
    echo json_encode($res);                    
} else {
    // ============================================
    // HANDLE INSERT ERRORS
    // ============================================
    if (strpos($insertStmt->error, 'Duplicate entry') !== false) {
        // This should not happen because we already checked, but just in case
        $res['code'] = 1;
        $res['msg'] = 'This account number is already registered';
        $res['msgCode'] = 208;
        http_response_code(200);
    } else {
        $res['code'] = 8;
        $res['msg'] = 'Failed to insert data: ' . $insertStmt->error;
        $res['msgCode'] = 7;
        http_response_code(500);
    }
    echo json_encode($res);                
}

$insertStmt->close();
$conn->close();
exit;
?>