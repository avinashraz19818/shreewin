<?php
session_start();
if (!isset($_SESSION['unohs'])) {
    header("location: login.php?msg=unauthorized");
    exit;
}

include("conn.php");
include("silkpay_config.php");
require_once __DIR__ . '/manual-withdraw-helper.php';

// Ensure logs directory exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/error.log';

// Helper function to log errors
function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Load SilkPay configuration
$config = include("silkpay_config.php");
$merchantId = trim($config['merchantId']);
$secretKey = trim($config['secretKey']);
$payoutApiUrl = trim($config['payoutApiUrl']);
$balanceApiUrl = trim($config['balanceApiUrl']);
$notifyUrl = trim($config['notifyUrl']);

// Log environment details
logError("Environment: merchantId=$merchantId, payoutApiUrl=$payoutApiUrl, notifyUrl=$notifyUrl");

// Helper function to generate MD5 signature
function generateSignature($mId, $mOrderId, $amount, $timestamp, $secret) {
    // Ensure no whitespace or hidden characters
    $mId = trim($mId);
    $mOrderId = trim($mOrderId);
    $amount = trim($amount);
    $timestamp = trim($timestamp);
    $secret = trim($secret);
    
    $signString = $mId . $mOrderId . $amount . $timestamp . $secret;
    $sign = md5($signString);
    logError("Signature Parameters: mId=$mId, mOrderId=$mOrderId, amount=$amount, timestamp=$timestamp, secret=$secret");
    logError("Signature Input: $signString | Generated Sign: $sign");
    return $sign;
}

// Helper function to call SilkPay API
function callSilkPayApi($data, $url) {
    global $logFile;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log API request details
    logError("API Request to $url: " . json_encode($data));
    logError("API Response: HTTP $httpCode | Body: " . ($response ?: 'No response'));
    if ($curlError) {
        logError("cURL Error: $curlError");
    }
    
    return ['httpCode' => $httpCode, 'response' => json_decode($response, true)];
}

// Process the request
$response = ['status' => 0, 'message' => 'Unknown error'];

if (isset($_POST['id']) && isset($_POST['type']) && isset($_POST['remark'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $type = strtolower(mysqli_real_escape_string($conn, $_POST['type']));
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);
    
    // Log incoming request
    logError("Incoming Request: id=$id, type=$type, remark=$remark");

    // Fetch withdrawal details
    $query = mysqli_query($conn, "SELECT shonu_subjects.mobile, shonu_subjects.email, shonu_subjects.owncode, 
        khate.phalanubhavi, khate.kod, khate.khatehesaru, khate.khatesankhye, 
        hintegedukolli.shonu, hintegedukolli.motta, hintegedukolli.khateshonu, 
        hintegedukolli.sthiti, hintegedukolli.dinankavannuracisi 
        FROM hintegedukolli 
        INNER JOIN shonu_subjects ON shonu_subjects.id = hintegedukolli.balakedara 
        INNER JOIN khate ON khate.shonu = hintegedukolli.khateshonu 
        WHERE hintegedukolli.shonu = '$id'");
    
    if ($result = mysqli_fetch_array($query)) {
        if ($type == 'accept' && !in_array((int)$result['sthiti'], [0, 3], true)) {
            $response = ['status' => 0, 'message' => 'Withdrawal request is already processed'];
        } elseif ($type == 'accept') {
            // Check merchant balance
            $timestamp = round(microtime(true) * 1000);
            $balanceSign = generateSignature($merchantId, '', '', (string)$timestamp, $secretKey);
            $balanceData = [
                'mId' => $merchantId,
                'timestamp' => (string)$timestamp,
                'sign' => $balanceSign
            ];
            $balanceResponse = callSilkPayApi($balanceData, $balanceApiUrl);

            if ($balanceResponse['httpCode'] == 200 && isset($balanceResponse['response']['status']) && $balanceResponse['response']['status'] == '200') {
                $availableBalance = floatval($balanceResponse['response']['data']['availableAmount']);
                $requestAmount = floatval($result['motta']);
                
                if ($availableBalance < $requestAmount) {
                    $response = ['status' => 0, 'message' => 'Insufficient merchant balance'];
                    logError("Insufficient balance: Available=$availableBalance, Requested=$requestAmount");
                } else {
                    // Prepare Payout API request
                    $amount = number_format($result['motta'], 2, '.', '');
                    $mOrderId = "WD" . time() . rand(1000, 9999); // Unique merchant order ID
                    $timestamp = (string)round(microtime(true) * 1000);
                    $sign = generateSignature($merchantId, $mOrderId, $amount, $timestamp, $secretKey);

                    $payoutData = [
                        'amount' => $amount,
                        'mId' => $merchantId,
                        'mOrderId' => $mOrderId,
                        'timestamp' => $timestamp,
                        'notifyUrl' => $notifyUrl,
                        'upi' => '', // Assuming bank transfer
                        'bankNo' => trim($result['khatesankhye']),
                        'ifsc' => trim($result['kod']),
                        'name' => trim($result['phalanubhavi']),
                        'sign' => $sign
                    ];

                    // Call SilkPay Payout API
                    $apiResponse = callSilkPayApi($payoutData, $payoutApiUrl);

                    if ($apiResponse['httpCode'] == 200 && isset($apiResponse['response']['status']) && $apiResponse['response']['status'] == '200') {
                        // Update database with payout details
                        $payOrderId = mysqli_real_escape_string($conn, $apiResponse['response']['data']['payOrderId']);
                        $updateQuery = mysqli_query($conn, "UPDATE hintegedukolli 
                            SET sthiti = '1', tike = 'Completed', remarks = '$remark', pay_order_no = '$payOrderId', out_trade_no = '$mOrderId', updated_at = NOW() 
                            WHERE shonu = '$id' AND sthiti IN ('0','3')");
                        if ($updateQuery) {
                            $response = ['status' => 1, 'message' => 'Payout initiated successfully. Awaiting callback confirmation.'];
                        } else {
                            $response = ['status' => 0, 'message' => 'Database update failed'];
                            logError("Database update failed for shonu=$id");
                        }
                    } else {
                        $errorCode = isset($apiResponse['response']['status']) ? $apiResponse['response']['status'] : 'Unknown';
                        $errorMessage = isset($apiResponse['response']['message']) ? $apiResponse['response']['message'] : 'Payout API error';
                        $response = ['status' => 0, 'message' => "Payout failed: [$errorCode] $errorMessage"];
                        logError("Payout failed: [$errorCode] $errorMessage");
                    }
                }
            } else {
                $errorMessage = isset($balanceResponse['response']['message']) ? $balanceResponse['response']['message'] : 'Balance check failed';
                $response = ['status' => 0, 'message' => "Balance check failed: $errorMessage"];
                logError("Balance check failed: $errorMessage");
            }
        } elseif ($type == 'reject') {
            [$ok, $message] = admin_manual_withdraw_action($conn, (int)$id, 'reject', $remark);
            $response = ['status' => $ok ? 2 : 0, 'message' => $message];
        } elseif ($type == 'processing') {
            [$ok, $message] = admin_manual_withdraw_action($conn, (int)$id, 'processing', $remark);
            $response = ['status' => $ok ? 3 : 0, 'message' => $message];
        }
    } else {
        $response = ['status' => 0, 'message' => 'Invalid withdrawal ID'];
        logError("Invalid withdrawal ID: $id");
    }
} else {
    $response = ['status' => 0, 'message' => 'Missing parameters'];
    logError("Missing parameters in request");
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>