<?php
include "../serive/samparka.php";
$config      = require __DIR__ . '/novapayconfig.php';
$merchantKey = $config['merchantKey'];

$DEBUG_MODE = true;
function logError($msg) {
    global $DEBUG_MODE;
    if ($DEBUG_MODE) {
        file_put_contents('webhook_log.txt', date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
    }
}

// 1) Read & parse
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
logError('Raw input: ' . json_encode($data));
if (!$data || empty($data['sign'])) {
    logError("Invalid callback or missing sign");
    echo 'fail(sign missing)';
    exit;
}

// 2) Verify signature
$received = $data['sign'];
unset($data['sign']);

// Filter out null or empty-string values:
// PHP 8+:
// $data = array_filter($data, fn($v) => $v !== null && $v !== '');
// PHP 7+:
$data = array_filter($data, function($v) { return $v !== null && $v !== ''; });

ksort($data);
$qs   = http_build_query($data) . '&secret=' . $merchantKey;
$calc = strtoupper(md5($qs));

if (!hash_equals($calc, $received)) {
    logError("Signature mismatch: got {$received}, expected {$calc}");
    echo 'fail(sign mismatch)';
    exit;
}

// 3) Process only successful deposits
if ((string)$data['status'] === '10') {
    $orderId = $data['orderId'];

    $chk = mysqli_query($conn,
        "SELECT motta, balakedara
         FROM thevani
         WHERE dharavahi = '{$orderId}'
           AND sthiti    = '0'"
    );
    if ($chk && mysqli_num_rows($chk)) {
        $row = mysqli_fetch_assoc($chk);
        mysqli_query($conn,
            "UPDATE shonu_kaichila
             SET motta = ROUND(motta + '{$row['motta']}', 2)
             WHERE balakedara = '{$row['balakedara']}'"
        );
        mysqli_query($conn,
            "UPDATE thevani
             SET sthiti = '1'
             WHERE dharavahi = '{$orderId}'"
        );
    }
}
logError("Signature: got {$received}, expected {$calc}");
logError("echo success");
echo 'success';
exit;
