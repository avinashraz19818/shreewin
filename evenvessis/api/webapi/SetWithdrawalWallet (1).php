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

// Validate signature (if needed)
// You can add signature validation logic here

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

// Check if the combination of userid and type already exists
$checkStmt = $conn->prepare("SELECT id FROM bankcard WHERE userid = ? AND type = ?");
if (!$checkStmt) {
    $res['code'] = 9;
    $res['msg'] = 'Database prepare failed';
    $res['msgCode'] = 8;
    http_response_code(500);
    echo json_encode($res);
    exit;
}

$checkStmt->bind_param("is", $shonuid, $bankId);
$checkStmt->execute();
$checkResult = app_stmt_result($checkStmt);
if ($checkResult->num_rows > 0) {
    $res['code'] = 1;
    $res['msg'] = 'You have already bound the e-wallet, please contact customer service to modify';
    $res['msgCode'] = 208;
    http_response_code(400);
    echo json_encode($res);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Insert new bank card
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
    $res['data'] = null;
    $res['code'] = 0;
    $res['msg'] = 'Succeed';
    $res['msgCode'] = 0;
    http_response_code(200);
    echo json_encode($res);                    
} else {
    $res['code'] = 8;
    $res['msg'] = 'Failed to insert data: ' . $insertStmt->error;
    $res['msgCode'] = 7;
    http_response_code(500);
    echo json_encode($res);                
}

$insertStmt->close();
$conn->close();
exit;
?>