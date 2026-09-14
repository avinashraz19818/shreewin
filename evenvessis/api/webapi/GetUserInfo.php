<?php
// GetUserInfo.php - COMPLETE FIXED VERSION
error_reporting(0); // Production के लिए

// Headers पहले
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');

date_default_timezone_set("Asia/Kolkata");
$now = date("Y-m-d H:i:s");

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Default response
$response = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $now
];

try {
    // Step 1: Include files
    $rootDir = realpath(dirname(__FILE__) . '/../..');
    
    // conn.php
    $connFile = $rootDir . '/conn.php';
    if (!file_exists($connFile)) {
        throw new Exception('DB config not found');
    }
    require_once $connFile;
    
    // functions2.php
    $funcFile = $rootDir . '/functions2.php';
    if (!file_exists($funcFile)) {
        throw new Exception('Functions file not found');
    }
    require_once $funcFile;
    
    // Step 2: Check DB connection
    if (!isset($conn) || !$conn || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Step 3: Check request method
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        http_response_code(405);
        echo json_encode($response);
        exit;
    }
    
    // Step 4: Get and parse input
    $rawInput = file_get_contents("php://input");
    $input = [];
    
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $input = $_POST;
        }
    } else {
        $input = $_POST;
    }
    
    // Step 5: Check required parameters
    if (!isset($input['language'], $input['random'], $input['signature'], $input['timestamp'])) {
        echo json_encode([
            'code' => 7,
            'msg' => 'Required parameters missing',
            'msgCode' => 6,
            'serviceNowTime' => $now,
            'debug' => array_keys($input)
        ]);
        exit;
    }
    
    // Step 6: Extract parameters
    $language = trim((string)$input['language']);
    $random = trim((string)$input['random']);
    $timestamp = trim((string)$input['timestamp']);
    $clientSig = strtoupper(trim((string)$input['signature']));
    
    // Step 7: SIGNATURE VERIFICATION
    $signatureVariants = [];
    
    // Format 1: language as number, no timestamp (MAIN)
    $signatureVariants[] = '{"language":' . $language . ',"random":"' . $random . '"}';
    
    // Format 2: language as string, no timestamp
    $signatureVariants[] = '{"language":"' . $language . '","random":"' . $random . '"}';
    
    // Format 3: with timestamp as number
    $signatureVariants[] = '{"language":' . $language . ',"random":"' . $random . '","timestamp":' . $timestamp . '}';
    
    // Format 4: with timestamp as string
    $signatureVariants[] = '{"language":' . $language . ',"random":"' . $random . '","timestamp":"' . $timestamp . '"}';
    
    // Format 5: language as string with timestamp
    $signatureVariants[] = '{"language":"' . $language . '","random":"' . $random . '","timestamp":"' . $timestamp . '"}';
    
    // Calculate and compare
    $signatureValid = false;
    foreach ($signatureVariants as $preimage) {
        $calculatedSig = strtoupper(md5($preimage));
        if ($calculatedSig === $clientSig) {
            $signatureValid = true;
            break;
        }
    }
    
    if (!$signatureValid) {
        // For debugging
        $mainPreimage = '{"language":' . $language . ',"random":"' . $random . '"}';
        $expectedSig = strtoupper(md5($mainPreimage));
        
        echo json_encode([
            'code' => 5,
            'msg' => 'Wrong signature',
            'msgCode' => 3,
            'serviceNowTime' => $now,
            'debug' => [
                'received' => $clientSig,
                'expected' => $expectedSig
            ]
        ]);
        exit;
    }
    
    // Step 8: Get Authorization token
    $authHeader = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? '';
    }
    
    if (empty($authHeader)) {
        echo json_encode([
            'code' => 4,
            'msg' => 'No authorization token',
            'msgCode' => 2,
            'serviceNowTime' => $now
        ]);
        exit;
    }
    
    // Extract Bearer token
    $token = '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
    }
    
    if (empty($token)) {
        echo json_encode([
            'code' => 4,
            'msg' => 'Invalid token format',
            'msgCode' => 2,
            'serviceNowTime' => $now
        ]);
        exit;
    }
    
    // Step 9: Validate JWT
    if (!function_exists('is_jwt_valid')) {
        throw new Exception('JWT function missing');
    }
    
    $jwtResult = is_jwt_valid($token);
    $authData = @json_decode($jwtResult, true);
    
    if (!$authData || !isset($authData['status']) || $authData['status'] !== 'Success') {
        echo json_encode([
            'code' => 4,
            'msg' => 'Invalid or expired token',
            'msgCode' => 2,
            'serviceNowTime' => $now
        ]);
        exit;
    }
    
    $userId = (int)($authData['payload']['id'] ?? 0);
    if ($userId <= 0) {
        echo json_encode([
            'code' => 4,
            'msg' => 'Invalid user ID',
            'msgCode' => 2,
            'serviceNowTime' => $now
        ]);
        exit;
    }
    
    $userMobile = $authData['payload']['mobile'] ?? '';
    
    // Step 10: Get user data from database
    $userQuery = $conn->prepare("
        SELECT 
            id,
            COALESCE(codechorkamukala, '') AS codechorkamukala,
            COALESCE(user_photo, '1') AS user_photo,
            createdate,
            shonullgnt
        FROM shonu_subjects 
        WHERE akshinak = ? AND id = ?
        LIMIT 1
    ");
    
    if (!$userQuery) {
        throw new Exception('DB prepare error: ' . $conn->error);
    }
    
    $userQuery->bind_param("si", $token, $userId);
    $userQuery->execute();
    $userResult = app_stmt_result($userQuery);
    $userRow = $userResult->fetch_assoc();
    $userQuery->close();
    
    if (!$userRow) {
        echo json_encode([
            'code' => 4,
            'msg' => 'User session not found',
            'msgCode' => 2,
            'serviceNowTime' => $now
        ]);
        exit;
    }
    
    // Step 11: Prepare response data
    $data = [];
    $data['userId'] = (int)$userRow['id'];
    $data['userPhoto'] = (string)$userRow['user_photo'];
    $data['userName'] = '91' . $userMobile;
    $data['nickName'] = (string)$userRow['codechorkamukala'];
    
    // Get balance
    $balanceQuery = $conn->prepare("SELECT motta FROM shonu_kaichila WHERE balakedara = ? LIMIT 1");
    $balanceQuery->bind_param("i", $userId);
    $balanceQuery->execute();
    $balanceResult = app_stmt_result($balanceQuery);
    $balanceRow = $balanceResult->fetch_assoc();
    $balanceQuery->close();
    
    $data['amount'] = (float)($balanceRow['motta'] ?? 0);
    
    // Get USDT rate
    $rateQuery = $conn->query("SELECT rate FROM tbl_pg WHERE value = 'usdt' LIMIT 1");
    $rateRow = $rateQuery ? $rateQuery->fetch_assoc() : ['rate' => 0];
    $data['uRate'] = (float)($rateRow['rate'] ?? 0);
    
    // Dates
    $createdDate = (string)$userRow['createdate'];
    $lastLogin = (string)$userRow['shonullgnt'];
    
    // Unread notifications count
    $unreadCount = 0;
    $notifQuery = $conn->prepare("SELECT COUNT(*) as total FROM notification WHERE user_id = ? AND state = 0");
    $notifQuery->bind_param("i", $userId);
    $notifQuery->execute();
    $notifResult = app_stmt_result($notifQuery);
    $notifRow = $notifResult->fetch_assoc();
    $notifQuery->close();
    
    if ($notifRow) {
        $unreadCount = (int)$notifRow['total'];
    }
    
    // Generate sign
    $signString = '{"userId":' . $data['userId'] . ',"userPhoto":"' . $data['userPhoto'] . '","userName":' . $data['userName'] . ',"nickName":"' . $data['nickName'] . '","createdate":"' . $createdDate . '"}';
    $data['sign'] = strtoupper(hash('sha256', $signString));
    
    // Other static data
    $data['amountofCode'] = 0.00;
    $data['isWithdraw'] = null;
    $data['message'] = null;
    $data['withdrawCount'] = 0;
    $data['addTime'] = '2024-04-17 14:10:50';
    $data['userLoginDate'] = $lastLogin;
    $data['startTime'] = null;
    $data['endTime'] = null;
    $data['fee'] = 0.0;
    $data['unRead'] = $unreadCount;
    $data['facebookAppID'] = null;
    $data['googleAppID'] = null;
    $data['twitterAppID'] = null;
    $data['keyCode'] = null;
    $data['trxRate'] = 10.0;
    $data['uGold'] = 0.00;
    $data['googleVerify'] = 0;
    $data['isvalidator'] = 0;
    $data['isRePwd'] = '1';
    $data['integral'] = 0;
    $data['isOpenPointMall'] = '0';
    $data['isOpenAmountOfCode'] = '1';
    $data['isOpenOfficialRechargeInputDialog'] = '1';
    $data['isAllowUserAddUSDT'] = '1';
    $data['isShowWalletTotalCT'] = '0';
    $data['isShowRechargeBankList'] = '0';
    $data['isPopupCommissionSwitch'] = '0';
    
    // Group data
    $data['groupDataShowAuth'] = [
        ['id' => 11, 'isShow' => true],
        ['id' => 12, 'isShow' => true],
        ['id' => 15, 'isShow' => true],
        ['id' => 16, 'isShow' => true],
        ['id' => 17, 'isShow' => true],
        ['id' => 18, 'isShow' => true],
        ['id' => 19, 'isShow' => true],
        ['id' => 20, 'isShow' => true]
    ];
    
    // Verify methods
    $data['verifyMethods'] = [
        'mobile' => '91' . $userMobile,
        'email' => '',
        'google' => '0'
    ];
    
    $data['regType'] = 1;
    
    // User group auth
    $data['userGroupAuth'] = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    $data['bindReward'] = 0.0;
    $data['isGoogle'] = '0';
    $data['isOpenChampion'] = '0';
    $data['isAllowWithdraw'] = 1;
    
    // Step 12: Success response
    $response = [
        'code' => 0,
        'msg' => 'Succeed',
        'msgCode' => 0,
        'serviceNowTime' => $now,
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error
    error_log("GetUserInfo Error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'code' => 500,
        'msg' => 'Internal server error',
        'msgCode' => 500,
        'serviceNowTime' => $now
    ]);
}

// Close connection
if (isset($conn) && $conn) {
    $conn->close();
}
?>