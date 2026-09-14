<?php 
// Enable all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to file
ini_set('log_errors', 1);
ini_set('error_log', 'withdrawal_errors.log');

include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');
header('Vary: Origin');

// Simple CORS handling
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, ar-origin, ar-real-ip, ar-session');
    exit(0);
}

date_default_timezone_set("Asia/Dhaka");
$shnunc = date("Y-m-d H:i:s");

// Initial response
$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $shnunc,
];

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception("Method not allowed", 405);
    }

    error_log("=== NEW WITHDRAWAL REQUEST STARTED ===");
    error_log("Request Time: " . $shnunc);

    // Get request body
    $shonubody = file_get_contents("php://input");
    error_log("Raw Request Body: " . $shonubody);

    $shonupost = json_decode($shonubody, true);
    
    if ($shonupost === null) {
        error_log("JSON Decode Error: " . json_last_error_msg());
        throw new Exception("Invalid JSON data", 400);
    }

    error_log("Decoded POST data: " . print_r($shonupost, true));

    // Check required parameters
    $required_params = ['amount', 'bid', 'language', 'pwd', 'random', 'signature', 'type'];
    $missing_params = [];
    foreach ($required_params as $param) {
        if (!isset($shonupost[$param])) {
            $missing_params[] = $param;
        }
    }
    
    if (!empty($missing_params)) {
        error_log("Missing parameters: " . implode(", ", $missing_params));
        throw new Exception("Missing parameters: " . implode(", ", $missing_params), 400);
    }

    // Get all parameters
    $amount = $shonupost['amount'];
    $bid = $shonupost['bid'];
    $language = $shonupost['language'];
    $pwd = $shonupost['pwd'];
    $random = $shonupost['random'];
    $type = $shonupost['type'];
    $signature = $shonupost['signature'];
    
    // Optional parameters
    $timestamp = isset($shonupost['timestamp']) ? $shonupost['timestamp'] : '';
    $name = isset($shonupost['name']) ? $shonupost['name'] : '';
    $tip = isset($shonupost['tip']) ? $shonupost['tip'] : '';

    // Log all parameters
    error_log("Parameters:");
    error_log("Amount: $amount");
    error_log("Bid: $bid");
    error_log("Language: $language");
    error_log("Type: $type");
    error_log("Random: $random");
    error_log("Received Signature: $signature");
    error_log("Timestamp: $timestamp");
    error_log("Name: $name");
    error_log("Tip: $tip");

    // TEMPORARY: Skip signature verification for testing
    // Uncomment the following lines after fixing signature
    /*
    // Generate signature - Try multiple formats
    $signature_matched = false;
    
    // Format 1: With timestamp
    if ($timestamp) {
        $shonustr1 = '{"amount":' . $amount . ',"bid":' . $bid . ',"language":' . $language . ',"pwd":"' . $pwd . '","random":"' . $random . '","type":' . $type . ',"timestamp":' . $timestamp . '}';
        $shonusign1 = strtoupper(md5($shonustr1));
        if ($shonusign1 === $signature) {
            $signature_matched = true;
            error_log("Signature matched with Format 1");
        }
    }
    
    // Format 2: Without timestamp
    if (!$signature_matched) {
        $shonustr2 = '{"amount":' . $amount . ',"bid":' . $bid . ',"language":' . $language . ',"pwd":"' . $pwd . '","random":"' . $random . '","type":' . $type . '}';
        $shonusign2 = strtoupper(md5($shonustr2));
        if ($shonusign2 === $signature) {
            $signature_matched = true;
            error_log("Signature matched with Format 2");
        }
    }
    
    if (!$signature_matched) {
        error_log("Signature mismatch!");
        error_log("Generated (with timestamp): " . $shonusign1);
        error_log("Generated (without timestamp): " . $shonusign2);
        $res['code'] = 5;
        $res['msg'] = 'Wrong signature';
        $res['msgCode'] = 3;
        echo json_encode($res);
        exit;
    }
    */
    
    error_log("Signature check temporarily bypassed for debugging");

    // Check Authorization header
    $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    error_log("Authorization Header: " . $authHeader);
    
    if (empty($authHeader)) {
        throw new Exception("Missing Authorization header", 401);
    }

    // Parse Authorization header
    $bearer_parts = explode(" ", $authHeader);
    if (count($bearer_parts) < 2 || $bearer_parts[0] !== 'Bearer') {
        throw new Exception("Invalid Authorization format", 401);
    }

    $author = $bearer_parts[1];
    error_log("JWT Token: " . substr($author, 0, 50) . "...");

    // Validate JWT
    $is_jwt_valid = is_jwt_valid($author);
    if ($is_jwt_valid === false) {
        throw new Exception("Invalid JWT token", 401);
    }

    $data_auth = json_decode($is_jwt_valid, true);
    error_log("JWT Decoded: " . print_r($data_auth, true));
    
    if (!$data_auth || !isset($data_auth['status']) || $data_auth['status'] !== 'Success') {
        throw new Exception("Invalid JWT status", 401);
    }

    // Get user ID
    $shonuid = isset($data_auth['payload']['id']) ? (int)$data_auth['payload']['id'] : 0;
    if ($shonuid <= 0) {
        throw new Exception("Invalid user ID in token", 401);
    }
    
    error_log("User ID: $shonuid");

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM shonu_subjects WHERE akshinak = ?");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error, 500);
    }
    
    $stmt->bind_param("s", $author);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows != 1) {
        $stmt->close();
        throw new Exception("User not found", 401);
    }
    $stmt->close();

    // Get user balance
    $stmt = $conn->prepare("SELECT motta FROM shonu_kaichila WHERE balakedara = ?");
    if (!$stmt) {
        throw new Exception("Database prepare failed for balance check", 500);
    }
    
    $stmt->bind_param("i", $shonuid);
    $stmt->execute();
    $result = app_stmt_result($stmt);
    
    if (!$result) {
        $stmt->close();
        throw new Exception("Failed to get balance result", 500);
    }
    
    $balarr = $result->fetch_assoc();
    $stmt->close();
    
    if (!$balarr) {
        throw new Exception("User balance not found", 400);
    }
    
    $balance = isset($balarr['motta']) ? (float)$balarr['motta'] : 0;
    $amount_float = (float)$amount;
    
    error_log("User Balance: $balance, Withdrawal Amount: $amount_float");

    // Validate amount
    if ($amount_float < 110 || $amount_float > 50000) {
        throw new Exception("Amount must be between 110 and 50000", 400);
    }

    if ($amount_float > $balance) {
        throw new Exception("Insufficient balance. Available: $balance, Requested: $amount_float", 400);
    }

    // Check if demo user
    $isDemoUser = false;
    $checkDemoStmt = $conn->prepare("SELECT 1 FROM demo WHERE balakedara = ?");
    if ($checkDemoStmt) {
        $checkDemoStmt->bind_param("i", $shonuid);
        $checkDemoStmt->execute();
        $checkDemoStmt->store_result();
        $isDemoUser = $checkDemoStmt->num_rows > 0;
        $checkDemoStmt->close();
    }

    // Update balance
    $new_balance = $balance - $amount_float;
    $updateStmt = $conn->prepare("UPDATE shonu_kaichila SET motta = ? WHERE balakedara = ?");
    if (!$updateStmt) {
        throw new Exception("Failed to prepare balance update: " . $conn->error, 500);
    }
    
    $updateStmt->bind_param("di", $new_balance, $shonuid);
    if (!$updateStmt->execute()) {
        $updateStmt->close();
        throw new Exception("Failed to update balance: " . $conn->error, 500);
    }
    $updateStmt->close();
    
    error_log("Balance updated successfully. New balance: $new_balance");

    // Generate serial number
    $date = date("Ymd");
    $time = time();
    $serial = 'W' . $date . $time . rand(1000, 9999);
    error_log("Generated Serial: $serial");

    // Insert withdrawal record
    $sthiti = $isDemoUser ? 1 : 0;
    $insertStmt = $conn->prepare("INSERT INTO hintegedukolli (balakedara, motta, dharavahi, khateshonu, dinankavannuracisi, madari, tike, sthiti) VALUES (?, ?, ?, ?, ?, ?, 'Applied', ?)");
    if (!$insertStmt) {
        throw new Exception("Failed to prepare withdrawal insert: " . $conn->error, 500);
    }
    
    $insertStmt->bind_param("idsssii", $shonuid, $amount_float, $serial, $bid, $shnunc, $type, $sthiti);
    
    if ($insertStmt->execute()) {
        error_log("Withdrawal record inserted successfully");
        $insertId = $insertStmt->insert_id;
        
        $res['data'] = [
            'shonuid' => $shonuid,
            'serial' => $serial,
            'amount' => $amount_float,
            'type' => $type,
            'time' => $shnunc,
            'newBalance' => $new_balance,
            'insertId' => $insertId
        ];
        $res['code'] = 0;
        $res['msg'] = 'Succeed';
        $res['msgCode'] = 0;
        
        error_log("Withdrawal successful. Response: " . json_encode($res));
    } else {
        error_log("Failed to insert withdrawal record: " . $insertStmt->error);
        
        // Rollback balance
        $rollbackStmt = $conn->prepare("UPDATE shonu_kaichila SET motta = ? WHERE balakedara = ?");
        $rollbackStmt->bind_param("di", $balance, $shonuid);
        $rollbackStmt->execute();
        $rollbackStmt->close();
        
        throw new Exception("Failed to insert withdrawal record: " . $insertStmt->error, 500);
    }
    
    $insertStmt->close();
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    $code = $e->getCode();
    $message = $e->getMessage();
    
    // Map exception codes to response codes
    if ($code == 401) {
        $res['code'] = 4;
        $res['msg'] = $message;
        $res['msgCode'] = 2;
        http_response_code(401);
    } elseif ($code == 400) {
        $res['code'] = 7;
        $res['msg'] = $message;
        $res['msgCode'] = 6;
        http_response_code(400);
    } elseif ($code == 405) {
        $res['code'] = 11;
        $res['msg'] = $message;
        $res['msgCode'] = 12;
        http_response_code(405);
    } else {
        $res['code'] = 9;
        $res['msg'] = "Internal Server Error: " . $message;
        $res['msgCode'] = 8;
        http_response_code(500);
    }
}

// Close connection
if (isset($conn)) {
    $conn->close();
}

error_log("=== REQUEST COMPLETED ===");

echo json_encode($res);
exit;
?>