<?php
include("../serive/samparka.php");

// Debug mode
$debug_mode = true;
if($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Function to send SMS
function sendSMSToAdmin($amount, $gateway, $transaction_id, $user_id, $bonus = 0) {
    $fields = array(
        "variables_values" => "Payment: PKR $amount via $gateway, Bonus: $bonus, TID: $transaction_id, User: $user_id",
        "route" => "otp",
        "numbers" => "8917626217",
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($fields),
        CURLOPT_HTTPHEADER => array(
            "authorization: d57hfbr8ufco8KQcFhnS8JF0ZOQgLJp1x2p",
            "accept: */*",
            "cache-control: no-cache",
            "content-type: application/json"
        ),
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    return $response;
}

// ========== FUNCTION TO GET BONUS PERCENTAGE ==========
function getBonusPercentage($conn, $payID) {
    if($payID <= 0) return 0;
    
    $bonus_query = "SELECT maxRechargeRifts FROM payment_channels WHERE payID = '$payID' AND status = 'active' LIMIT 1";
    $bonus_result = mysqli_query($conn, $bonus_query);
    
    if($bonus_result && mysqli_num_rows($bonus_result) > 0) {
        $bonus_row = mysqli_fetch_assoc($bonus_result);
        return floatval($bonus_row['maxRechargeRifts']);
    }
    return 0;
}
// =====================================================

// ========== FUNCTION TO CHECK/ADD DATABASE COLUMNS ==========
function checkDatabaseStructure($conn) {
    // Check thevani table for bonus columns
    $check_thevani = mysqli_query($conn, "SHOW COLUMNS FROM thevani");
    $thevani_columns = [];
    while($row = mysqli_fetch_assoc($check_thevani)) {
        $thevani_columns[] = $row['Field'];
    }
    
    // Add bonus_amount column if not exists
    if(!in_array('bonus_amount', $thevani_columns)) {
        mysqli_query($conn, "ALTER TABLE `thevani` ADD COLUMN `bonus_amount` decimal(10,2) DEFAULT 0.00 AFTER `motta`");
    }
    
    // Add bonus_percentage column if not exists
    if(!in_array('bonus_percentage', $thevani_columns)) {
        mysqli_query($conn, "ALTER TABLE `thevani` ADD COLUMN `bonus_percentage` decimal(5,2) DEFAULT 0.00 AFTER `bonus_amount`");
    }
    
    // Add total_amount column if not exists
    if(!in_array('total_amount', $thevani_columns)) {
        mysqli_query($conn, "ALTER TABLE `thevani` ADD COLUMN `total_amount` decimal(10,2) DEFAULT 0.00 AFTER `bonus_percentage`");
    }
    
    // Check payment_pairs table
    $check_pairs = mysqli_query($conn, "SHOW COLUMNS FROM payment_pairs");
    $pairs_columns = [];
    if($check_pairs) {
        while($row = mysqli_fetch_assoc($check_pairs)) {
            $pairs_columns[] = $row['Field'];
        }
        
        if(!in_array('usage_count', $pairs_columns)) {
            mysqli_query($conn, "ALTER TABLE payment_pairs ADD COLUMN usage_count INT DEFAULT 0");
        }
        
        if(!in_array('last_used', $pairs_columns)) {
            mysqli_query($conn, "ALTER TABLE payment_pairs ADD COLUMN last_used TIMESTAMP NULL");
        }
    }
}
// ============================================================

// Main processing
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    date_default_timezone_set('Asia/Kolkata');
    
    // Check database structure first
    checkDatabaseStructure($conn);
    
    // Get POST data
    $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
    $token = isset($_POST['token']) ? mysqli_real_escape_string($conn, $_POST['token']) : '';
    $amt = isset($_POST['amt']) ? floatval($_POST['amt']) : 0;
    $ref_num = isset($_POST['refnum']) ? mysqli_real_escape_string($conn, trim($_POST['refnum'])) : '';
    $srl = isset($_POST['srl']) ? mysqli_real_escape_string($conn, $_POST['srl']) : '0';
    $source = isset($_POST['source']) ? mysqli_real_escape_string($conn, $_POST['source']) : 'PK-Payment';
    $gateway = isset($_POST['gateway']) ? mysqli_real_escape_string($conn, $_POST['gateway']) : 'easypaisa';
    $gatewayId = isset($_POST['gatewayId']) ? mysqli_real_escape_string($conn, $_POST['gatewayId']) : '';
    $pairId = isset($_POST['pairId']) ? intval($_POST['pairId']) : 0;
    
    // ========== MAP GATEWAY TO PAY ID FOR BONUS ==========
    $payID_for_bonus = 0;
    $gateway_lower = strtolower($gateway);
    
    if(strpos($gateway_lower, 'easypaisa') !== false || $gateway_lower == 'easypaisa') {
        $payID_for_bonus = 2;
        $gateway_display = 'Easypaisa';
    } elseif(strpos($gateway_lower, 'jazz') !== false || $gateway_lower == 'jazzcash') {
        $payID_for_bonus = 1;
        $gateway_display = 'JazzCash';
    } elseif(strpos($gateway_lower, 'usdt') !== false || $gateway_lower == 'usdt') {
        $payID_for_bonus = 11;
        $gateway_display = 'USDT';
    } elseif(strpos($gateway_lower, 'fast') !== false || $gateway_lower == 'fastpay') {
        $payID_for_bonus = 39;
        $gateway_display = 'FastPay';
    } elseif(strpos($gateway_lower, 'paytm') !== false || $gateway_lower == 'paytm') {
        $payID_for_bonus = 13;
        $gateway_display = 'PayTM';
    } else {
        $payID_for_bonus = 0;
        $gateway_display = $gateway;
    }
    
    // ========== CALCULATE BONUS ==========
    $bonus_rate = getBonusPercentage($conn, $payID_for_bonus);
    $bonus_amount = $amt * $bonus_rate;
    $bonus_amount = round($bonus_amount, 2);
    $total_amount = $amt + $bonus_amount;
    $total_amount = round($total_amount, 2);
    
    if($debug_mode) {
        error_log("=== BONUS CALCULATION ===");
        error_log("Gateway: $gateway_display (PayID: $payID_for_bonus)");
        error_log("Amount: $amt");
        error_log("Bonus Rate: $bonus_rate");
        error_log("Bonus Amount: $bonus_amount");
        error_log("Total Amount: $total_amount");
        error_log("=========================");
    }
    // =====================================
    
    $formatted_amt = number_format($amt, 2, '.', '');
    $createdate = date("Y-m-d H:i:s");
    
    // Validate required fields
    if($userId <= 0 || empty($token) || $amt <= 0 || empty($ref_num)) {
        echo "0~Missing required fields";
        exit;
    }
    
    // Verify user exists
    $user_query = "SELECT mobile, status FROM shonu_subjects WHERE id = '$userId' LIMIT 1";
    $user_result = mysqli_query($conn, $user_query);
    
    if(!$user_result || mysqli_num_rows($user_result) == 0) {
        echo "0~User not found";
        exit;
    }
    
    $user_data = mysqli_fetch_assoc($user_result);
    
    // Check user status
    if($user_data['status'] != 1) {
        echo "4~Your account is currently suspended";
        exit;
    }
    
    // Gateway specific validation - ONLY CHECK LENGTH AND NUMBERS
    $expected_length = ($gateway_display == 'Easypaisa') ? 11 : 12;
    
    if(strlen($ref_num) != $expected_length) {
        echo "5~Transaction ID must be $expected_length digits";
        exit;
    }
    
    if(!preg_match('/^[0-9]+$/', $ref_num)) {
        echo "6~Transaction ID must contain only numbers";
        exit;
    }
    
    // Check for duplicate transaction ID
    $dup_query = "SELECT shonu FROM thevani WHERE ullekha = '$ref_num' LIMIT 1";
    $dup_result = mysqli_query($conn, $dup_query);
    
    if($dup_result && mysqli_num_rows($dup_result) >= 1) {
        echo "2~This Transaction ID is already used";
        exit;
    }
    
    // Check time limit between payments
    $time_query = "SELECT dinankavannuracisi FROM thevani 
                  WHERE balakedara = '$userId' 
                  ORDER BY shonu DESC LIMIT 1";
    $time_result = mysqli_query($conn, $time_query);
    
    if($time_result && mysqli_num_rows($time_result) > 0) {
        $time_data = mysqli_fetch_assoc($time_result);
        $last_payment_time = strtotime($time_data['dinankavannuracisi']);
        $next_allowed_time = $last_payment_time + 60;
        
        if(time() < $next_allowed_time) {
            echo "3~Please wait 1 minute before next payment";
            exit;
        }
    }
    
    // Check payment method status
    $method_query = "SELECT kramasankhye FROM amanatugolisu 
                    WHERE byabaharkarta = '$userId' AND sthiti = '1' 
                    LIMIT 1";
    $method_result = mysqli_query($conn, $method_query);
    
    if($method_result && mysqli_num_rows($method_result) >= 1) {
        echo "4~Payment method is currently suspended";
        exit;
    }
    
    // Minimum amount check
    if($amt < 100) {
        echo "8~Minimum amount is PKR 100";
        exit;
    }
    
    // Convert amount if USDT
    if($source == 'usdt') {
        $formatted_amt = number_format($amt * 93, 2, '.', '');
        // Recalculate bonus for converted amount
        $bonus_amount = $formatted_amt * $bonus_rate;
        $bonus_amount = round($bonus_amount, 2);
        $total_amount = $formatted_amt + $bonus_amount;
        $total_amount = round($total_amount, 2);
    }
    
    // ========== BUILD INSERT QUERY WITH BONUS ==========
    $insert_sql = "INSERT INTO thevani(
        payid,
        balakedara, 
        motta, 
        bonus_amount,
        bonus_percentage,
        total_amount,
        dharavahi, 
        mula, 
        ullekha, 
        duravani, 
        ekikrtapavati, 
        dinankavannuracisi, 
        madari, 
        pavatiaidi, 
        sthiti,
        gateway,
        gateway_id,
        pair_id
    ) VALUES(
        '1',
        '$userId', 
        '$formatted_amt',
        '$bonus_amount',
        '$bonus_rate',
        '$total_amount',
        '$srl', 
        '$source',
        '$ref_num', 
        '{$user_data['mobile']}', 
        '$gatewayId', 
        '$createdate', 
        '1004', 
        '2', 
        '0',
        '$gateway',
        '$gatewayId',
        '$pairId'
    )";
    
    if($debug_mode) {
        error_log("Insert SQL: " . $insert_sql);
    }
    
    // Execute query
    if(mysqli_query($conn, $insert_sql)) {
        $insert_id = mysqli_insert_id($conn);
        
        // Send SMS to admin with bonus info
        if($bonus_amount > 0) {
            sendSMSToAdmin($formatted_amt, $gateway_display, $ref_num, $userId, $bonus_amount);
        } else {
            sendSMSToAdmin($formatted_amt, $gateway_display, $ref_num, $userId);
        }
        
        // Update pair usage count if pairId is valid
        if($pairId > 0) {
            $update_pair_sql = "UPDATE payment_pairs 
                               SET usage_count = COALESCE(usage_count, 0) + 1,
                                   last_used = NOW()
                               WHERE id = '$pairId'";
            mysqli_query($conn, $update_pair_sql);
        }
        
        // Return success with bonus message
        if($bonus_amount > 0) {
            echo "1~Payment submitted successfully! You will receive Rs " . number_format($bonus_amount, 0) . " bonus (Total: Rs " . number_format($total_amount, 0) . ")";
        } else {
            echo "1~Payment submitted successfully";
        }
        
        if($debug_mode) {
            error_log("Insert successful. ID: $insert_id");
        }
        
    } else {
        $error_msg = mysqli_error($conn);
        error_log("Insert failed: " . $error_msg);
        
        // Try alternative query without some columns
        $alt_sql = "INSERT INTO thevani(
            balakedara, 
            motta, 
            bonus_amount,
            bonus_percentage,
            total_amount,
            dharavahi, 
            mula, 
            ullekha, 
            duravani, 
            ekikrtapavati, 
            dinankavannuracisi, 
            madari, 
            pavatiaidi, 
            sthiti
        ) VALUES(
            '$userId', 
            '$formatted_amt',
            '$bonus_amount',
            '$bonus_rate',
            '$total_amount',
            '$srl', 
            '$source',
            '$ref_num', 
            '{$user_data['mobile']}', 
            '$gatewayId', 
            '$createdate', 
            '1004', 
            '2', 
            '0'
        )";
        
        if(mysqli_query($conn, $alt_sql)) {
            // Send SMS
            if($bonus_amount > 0) {
                sendSMSToAdmin($formatted_amt, $gateway_display, $ref_num, $userId, $bonus_amount);
            } else {
                sendSMSToAdmin($formatted_amt, $gateway_display, $ref_num, $userId);
            }
            
            if($bonus_amount > 0) {
                echo "1~Payment submitted successfully! You will receive Rs " . number_format($bonus_amount, 0) . " bonus (Total: Rs " . number_format($total_amount, 0) . ")";
            } else {
                echo "1~Payment submitted successfully (alternative method)";
            }
        } else {
            echo "0~Database error: " . mysqli_error($conn);
        }
    }
    
} else {
    echo "0~Invalid request method";
}
?>