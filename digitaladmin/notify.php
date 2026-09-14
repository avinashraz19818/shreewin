<?php
// Payout Callback Handler (Section 6)
// Must return "SUCCESS" for successful processing

require_once('payoutapi_config.php');
include("api/conn.php");

// Function to generate signature
function generateSignature($params, $secretKey) {
    ksort($params);
    $string = '';
    foreach ($params as $key => $value) {
        if ($key !== 'sign' && $value !== '' && $value !== null && $value !== false) {
            $string .= "$key=" . urlencode($value) . "&";
        }
    }
    $string = rtrim($string, '&');
    $string .= "&key=$secretKey";
    return strtoupper(md5($string));
}

// Read raw POST data
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input || !isset($input['sign'])) {
    http_response_code(400);
    exit('Invalid callback data');
}

// Verify callback IP
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($clientIp, ALLOWED_CALLBACK_IPS)) {
    http_response_code(403);
    exit('Unauthorized IP');
}

$receivedSign = $input['sign'];
$params = $input;
unset($params['sign']);

// Verify signature
$calculatedSign = generateSignature($params, SECRET_KEY);
if ($calculatedSign !== $receivedSign) {
    http_response_code(400);
    exit('Invalid signature');
}

// Valid callback
$merNo = $input['merNo'] ?? '';
$payOrderNo = $input['payOrderNo'] ?? '';
$outTradeNo = $input['outTradeNo'] ?? '';
$totalAmount = $input['totalAmount'] ?? '';
$orderStatus = $input['orderStatus'] ?? '';
$sthiti = ($orderStatus == '1') ? '1' : (($orderStatus == '2') ? '2' : '3');

// Update database
$updateQuery = mysqli_query($conn, "UPDATE hintegedukolli 
    SET sthiti = '$sthiti', 
        pay_order_no = '$payOrderNo',
        callback_status = '$orderStatus',
        updated_at = NOW() 
    WHERE out_trade_no = '$outTradeNo'");

if ($updateQuery && mysqli_affected_rows($conn) > 0) {
    echo 'SUCCESS';
} else {
    http_response_code(500);
    echo 'Database update failed';
}
?>