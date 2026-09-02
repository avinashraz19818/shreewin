<?php
include("conn.php");
include("silkpay_config.php");

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
$secretKey = $config['secretKey'];

// Helper function to verify signature
function verifySignature($data, $receivedSign, $secret) {
    $signString = $data['mId'] . $data['mOrderId'] . $data['amount'] . $data['timestamp'] . $secret;
    $expectedSign = md5($signString);
    logError("Callback Signature Input: $signString | Expected Sign: $expectedSign | Received Sign: $receivedSign");
    return $expectedSign === $receivedSign;
}

// Read callback data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

logError("Callback Received: " . ($input ?: 'No input'));

if ($data && isset($data['mId'], $data['mOrderId'], $data['amount'], $data['timestamp'], $data['sign'], $data['status'], $data['payOrderId'])) {
    // Verify signature
    if (verifySignature($data, $data['sign'], $secretKey)) {
        // Update database based on status
        $mOrderId = mysqli_real_escape_string($conn, $data['mOrderId']);
        $payOrderId = mysqli_real_escape_string($conn, $data['payOrderId']);
        $status = (int)$data['status']; // 2: success, 3: failed
        $utr = isset($data['utr']) ? mysqli_real_escape_string($conn, $data['utr']) : null;
        $sthiti = ($status == 2) ? '2' : '3'; // Map to database status

        $updateQuery = mysqli_query($conn, "UPDATE hintegedukolli 
            SET sthiti = '$sthiti', utr = " . ($utr ? "'$utr'" : 'NULL') . " 
            WHERE mOrderId = '$mOrderId' AND payOrderId = '$payOrderId'");
        
        if ($updateQuery) {
            logError("Callback processed successfully for mOrderId=$mOrderId, payOrderId=$payOrderId, status=$sthiti");
            echo "OK";
        } else {
            logError("Database update failed for mOrderId=$mOrderId, payOrderId=$payOrderId");
            http_response_code(500);
            echo "Database update failed";
        }
    } else {
        logError("Invalid callback signature for mOrderId={$data['mOrderId']}");
        http_response_code(403);
        echo "Invalid signature";
    }
} else {
    logError("Invalid callback data: " . ($input ?: 'Empty input'));
    http_response_code(400);
    echo "Invalid callback data";
}
exit;
?>