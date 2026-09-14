<?php 
// Sabse pehle error reporting on karo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log'); // Apne server ka path do

include "../../conn.php";
include "../../functions2.php";

// Buffer clean karo
ob_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');

// CORS properly handle karo
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('vary: Origin');
}

date_default_timezone_set("Asia/Kolkata");
$shnunc = date("Y-m-d H:i:s");
$today = date("Y-m-d");

// Response structure
$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $shnunc,
];

try {
    // Request body read karo
    $shonubody = file_get_contents("php://input");
    if (!$shonubody) {
        throw new Exception("Empty request body");
    }
    
    $shonupost = json_decode($shonubody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON: " . json_last_error_msg());
    }

    // Log request for debugging
    error_log("Gift Code Request: " . print_r($shonupost, true));

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        http_response_code(405);
        echo json_encode($res);
        exit;
    }

    // Validate required parameters
    $required = ['giftCode', 'language', 'random', 'signature', 'timestamp'];
    foreach ($required as $field) {
        if (!isset($shonupost[$field])) {
            throw new Exception("Missing parameter: " . $field);
        }
    }

    // Clean inputs
    $giftCode = mysqli_real_escape_string($conn, trim($shonupost['giftCode']));
    $language = mysqli_real_escape_string($conn, trim($shonupost['language']));
    $random = mysqli_real_escape_string($conn, trim($shonupost['random']));
    $signature = mysqli_real_escape_string($conn, trim($shonupost['signature']));
    
    // Verify signature
    $shonustr = '{"giftCode":"' . $giftCode . '","language":' . $language . ',"random":"' . $random . '"}';
    $shonusign = strtoupper(md5($shonustr));
    
    if ($shonusign != $signature) {
        $res['code'] = 5;
        $res['msg'] = 'Wrong signature';
        $res['msgCode'] = 3;
        echo json_encode($res);
        exit;
    }

    // Validate JWT
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        throw new Exception("No authorization header");
    }
    
    $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
    if (count($bearer) < 2) {
        throw new Exception("Invalid authorization format");
    }
    
    $author = $bearer[1];
    $is_jwt_valid = is_jwt_valid($author);
    $data_auth = json_decode($is_jwt_valid, true);
    
    if ($data_auth['status'] !== 'Success') {
        $res['code'] = 4;
        $res['msg'] = 'No operation permission';
        $res['msgCode'] = 2;
        echo json_encode($res);
        exit;
    }

    // Check user session
    $sesquery = "SELECT akshinak, id FROM shonu_subjects WHERE akshinak = '$author'";
    $sesresult = $conn->query($sesquery);
    
    if (!$sesresult) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    if ($sesresult->num_rows == 0) {
        $res['code'] = 4;
        $res['msg'] = 'User not found';
        $res['msgCode'] = 2;
        echo json_encode($res);
        exit;
    }
    
    $sesrow = $sesresult->fetch_assoc();
    $shonuid = $data_auth['payload']['id'];
    
    // ============================================
    // GIFT CODE VALIDATION
    // ============================================
    
    // Check if hodike_nirvahaka table exists and has required columns
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'hodike_nirvahaka'");
    if (mysqli_num_rows($tableCheck) == 0) {
        throw new Exception("Gift code table not found");
    }
    
    // Get gift code details
    $checkcode = mysqli_query($conn, "
        SELECT 
            `utilisateurmax`, 
            `prix`, 
            `nombredutilisateurs`,
            `required_deposit`,
            `valid_upto`,
            `gift_type`
        FROM `hodike_nirvahaka` 
        WHERE `enserie` = '".$giftCode."' 
        AND `shonu` = '1'
    ");
    
    if (!$checkcode) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    if (mysqli_num_rows($checkcode) == 0) {
        $res['code'] = 1;
        $res['msg'] = 'Invalid gift code';
        $res['msgCode'] = 230;
        echo json_encode($res);
        exit;
    }
    
    $giftData = mysqli_fetch_array($checkcode);
    
    // Extract values
    $utilisateurmax = $giftData['utilisateurmax'];
    $nombredutilisateurs = $giftData['nombredutilisateurs'];
    $prix = $giftData['prix'];
    $requiredDeposit = isset($giftData['required_deposit']) ? floatval($giftData['required_deposit']) : 0;
    $validUpto = isset($giftData['valid_upto']) ? $giftData['valid_upto'] : null;
    
    // Check expiry
    if ($validUpto && strtotime($validUpto) < strtotime($shnunc)) {
        $res['code'] = 1;
        $res['msg'] = 'Gift code has expired';
        $res['msgCode'] = 231;
        echo json_encode($res);
        exit;
    }
    
    // Check max users
    if ($nombredutilisateurs >= $utilisateurmax) {
        $res['code'] = 1;
        $res['msg'] = 'Gift code usage limit reached';
        $res['msgCode'] = 232;
        echo json_encode($res);
        exit;
    }
    
    // Check if already redeemed
    $checkuser = mysqli_query($conn, "
        SELECT `kani` FROM `hodike_balakedara` 
        WHERE `serial` = '".$giftCode."' 
        AND `userkani` = '".$shonuid."'
    ");
    
    if (!$checkuser) {
        throw new Exception("Redemption check failed: " . $conn->error);
    }
    
    if (mysqli_num_rows($checkuser) > 0) {
        $res['code'] = 1;
        $res['msg'] = 'You have already redeemed this code';
        $res['msgCode'] = 233;
        echo json_encode($res);
        exit;
    }
    
    // ============================================
    // DEPOSIT VALIDATION
    // ============================================
    
    if ($requiredDeposit > 0) {
        // Check main deposit table
        $depositCheck = mysqli_query($conn, "
            SELECT COALESCE(SUM(motta), 0) as total_deposit_today 
            FROM `thevani` 
            WHERE `balakedara` = '".$shonuid."' 
            AND `sthiti` = 1 
            AND DATE(`dinankavannuracisi`) = '".$today."'
        ");
        
        if (!$depositCheck) {
            throw new Exception("Deposit check failed: " . $conn->error);
        }
        
        $depositData = mysqli_fetch_array($depositCheck);
        $todayDeposit = floatval($depositData['total_deposit_today']);
        
        // Check other deposit tables
        $depositTables = ['bajikattuttate_trx', 'bajikattuttate_trx3', 'bajikattuttate_trx5', 'bajikattuttate_trx10'];
        
        foreach ($depositTables as $table) {
            $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE '".$table."'");
            if (mysqli_num_rows($tableCheck) > 0) {
                $extraDeposit = mysqli_query($conn, "
                    SELECT COALESCE(SUM(ketebida), 0) as deposit 
                    FROM `".$table."` 
                    WHERE `byabaharkarta` = '".$shonuid."' 
                    AND `phalaphala` = 'rc' 
                    AND DATE(`tiarikala`) = '".$today."'
                ");
                
                if ($extraDeposit && mysqli_num_rows($extraDeposit) > 0) {
                    $extraData = mysqli_fetch_array($extraDeposit);
                    $todayDeposit += floatval($extraData['deposit']);
                }
            }
        }
        
        // Check if deposit requirement met
        if ($todayDeposit < $requiredDeposit) {
            $res['code'] = 1;
            $res['msg'] = 'Minimum deposit of ₹' . number_format($requiredDeposit, 2) . 
                         ' required today to redeem this code';
            $res['msgCode'] = 234;
            $res['data'] = [
                'requiredDeposit' => $requiredDeposit,
                'yourDeposit' => $todayDeposit
            ];
            echo json_encode($res);
            exit;
        }
    }
    
    // ============================================
    // PROCESS REDEMPTION
    // ============================================
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Update gift code usage
    $newCount = $nombredutilisateurs + 1;
    $updateGift = mysqli_query($conn, "
        UPDATE `hodike_nirvahaka` 
        SET `nombredutilisateurs` = '".$newCount."' 
        WHERE `enserie` = '".$giftCode."'
    ");
    
    if (!$updateGift) {
        throw new Exception("Failed to update gift code: " . $conn->error);
    }
    
    // Insert redemption record
    $crdt = date("Y-m-d H:i:s");
    $insertRedemption = mysqli_query($conn, "
        INSERT INTO `hodike_balakedara` 
        (`userkani`, `serial`, `price`, `shonu`, `remark`) 
        VALUES (
            '".$shonuid."',
            '".$giftCode."',
            '".$prix."',
            '".$crdt."',
            'Gift code redemption'
        )
    ");
    
    if (!$insertRedemption) {
        throw new Exception("Failed to insert redemption: " . $conn->error);
    }
    
    // Update user balance
    $updateBalance = mysqli_query($conn, "
        UPDATE shonu_kaichila
        SET motta = ROUND(COALESCE(motta, 0) + '".$prix."', 2)
        WHERE balakedara = '".$shonuid."'
    ");
    
    if (!$updateBalance) {
        throw new Exception("Failed to update balance: " . $conn->error);
    }
    
    // Insert bonus transaction
    $insertBonus = mysqli_query($conn, "
        INSERT INTO `all_bonus_transactions` 
        (`user_id`, `amount`, `bonus_type`, `remark`, `transaction_date`, `reference_code`) 
        VALUES (
            '".$shonuid."',
            '".$prix."',
            '3',
            'Gift code: ".$giftCode."',
            '".$crdt."',
            '".$giftCode."'
        )
    ");
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Get new balance
    $balanceQuery = mysqli_query($conn, "SELECT motta FROM shonu_kaichila WHERE balakedara = '$shonuid'");
    $balanceRow = $balanceQuery->fetch_assoc();
    $newBalance = floatval($balanceRow['motta']);
    
    // Success response
    $res['code'] = 0;
    $res['msg'] = 'Succeed';
    $res['msgCode'] = 0;
    $res['data'] = [
        'redeemed_amount' => floatval($prix),
        'gift_code' => $giftCode,
        'remaining_uses' => $utilisateurmax - $newCount,
        'new_balance' => $newBalance
    ];
    
    echo json_encode($res);
    
} catch (Exception $e) {
    // Rollback if transaction active
    if (isset($conn) && !mysqli_connect_errno()) {
        mysqli_rollback($conn);
    }
    
    // Log error
    error_log("Gift Code API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Send error response
    $res['code'] = 2;
    $res['msg'] = 'Internal server error';
    $res['msgCode'] = 500;
    $res['debug'] = $e->getMessage(); // Remove this in production
    
    // Clear any output buffers
    ob_clean();
    
    http_response_code(200); // Keep 200 for API responses
    echo json_encode($res);
}

// End and flush
ob_end_flush();
?>