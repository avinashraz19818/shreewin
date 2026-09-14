<?php 
header('Content-type: text/plain; charset=utf-8');
include ("../serive/samparka.php");

if(isset($_GET['amount'])){
    $ramt = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['amount']));
    $payTypeID = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
} else {
    $ramt = 0;
}

$payName = 'LG-Pay-USDT';
$ramt = number_format((float)$ramt, 2, '.', ''); // normalize

$date = date("Ymd");
$time = time();
$serial = $date . $time . rand(100000, 999900);

$tyid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
$uid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['uid']));
$sign = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['sign']));
$urlInfo = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['urlInfo']));

// demo user check
$demoQuery = "SELECT 1 FROM demo WHERE balakedara = '$uid'";
$demoResult = $conn->query($demoQuery);

if ($demoResult->num_rows > 0) {
    $createdate = date("Y-m-d H:i:s");
    $emailQ = mysqli_query($conn , "SELECT mobile FROM `shonu_subjects` WHERE `id` = '".$uid."'");
    $emailA = mysqli_fetch_array($emailQ);
    $userm = $emailA['mobile'];
    
    $insertQuery = "
        INSERT INTO `thevani` (`payid`, `balakedara`, `motta`, `dharavahi`, `mula`, `ullekha`, `duravani`, `ekikrtapavati`, `dinankavannuracisi`, `madari`, `pavatiaidi`, `sthiti`) 
        VALUES ('2', '$uid', '$ramt', '$serial', '$payName', 'N/A', '$userm', 'N/A', '$createdate', '1005', '2', '1')
    ";
    $conn->query($insertQuery);

    $updateQuery = "
        UPDATE `shonu_kaichila`
        SET `motta` = `motta` + $ramt
        WHERE `balakedara` = '$uid'
    ";
    $conn->query($updateQuery);

    header('Location: https://luckywin28.buzz/#/main');
    exit;
}

if (isset($_GET['tyid']) && isset($_GET['amount']) && isset($_GET['uid']) && isset($_GET['sign']) && isset($_GET['urlInfo'])) {

    $userId = $uid;
    $numquery = "SELECT mobile, codechorkamukala FROM shonu_subjects WHERE id = ".$userId;
    $numresult = $conn->query($numquery);
    $numarr = mysqli_fetch_array($numresult);

    $config = require 'lgpayconfig.php';
    $apiUrl = $config['api_url'];
    $secretKey = $config['secret_key'];
    $app_id = $config['app_id'];

    $usdtRate = isset($config['usdt_rate']) ? floatval($config['usdt_rate']) : 290.0;
    $gatewayScale = isset($config['gateway_scale']) ? intval($config['gateway_scale']) : 100; // default 100

    $notify_url = "https://1indiaclub.com/pay/lgpaywebhook.php";

    $rawUSDT = floatval($ramt);

    // ✅ DB / history value (INR) → no scaling
    $displayAmount = number_format($rawUSDT * $usdtRate, 2, '.', ''); // e.g. 10 USDT * 92 = 920

    // ✅ Gateway value → scaled
    $sendAmountFormatted = number_format($displayAmount * $gatewayScale, 2, '.', ''); // e.g. 920 * 100 = 92000

    // Build gateway params
    $params = [
        'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
        'remark' => 'USDT pay',
        'notify_url' => $notify_url,
        'money' => $sendAmountFormatted,
        'app_id' => $app_id,
        'trade_type' => 'usdt',
        'currency' => 'PKR',
        'order_sn' => $serial,
        'return_url' => 'https://luckywin28.buzz/#/wallet/RechargeHistory',
    ];

    ksort($params);
    $signatureString = '';
    foreach ($params as $key => $value) {
        $signatureString .= "$key=$value&";
    }
    $signatureString .= "key=$secretKey";
    $signature = strtoupper(md5($signatureString));
    $params['sign'] = $signature;
    $postData = http_build_query($params);

    // Debug log
    $debugLog = date('Y-m-d H:i:s') 
        . " | order_sn={$serial} | rawUSDT={$rawUSDT} | displayAmount={$displayAmount} | sendAmount={$sendAmountFormatted} | post=" . $postData . "\n";
    file_put_contents('/tmp/lgpay_debug.log', $debugLog, FILE_APPEND);

    // Send request
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if ($responseData && isset($responseData['status']) && $responseData['status'] == 1 && isset($responseData['data']['pay_url'])) {
        $ref_num = $responseData['data']['order_id'] ?? 'N/A';
        $mobile = $numarr['mobile'];
        $upi = 'LGPayUSDT';
        $createdate = date("Y-m-d H:i:s");

        // ✅ Insert into DB: use displayAmount (scaled for game, not gateway)
        mysqli_query($conn, "INSERT INTO `thevani`
            (`payid`,`balakedara`, `motta`, `dharavahi`, `mula`, `ullekha`, `duravani`, `ekikrtapavati`, `dinankavannuracisi`, `madari`, `pavatiaidi`, `sthiti`) 
            VALUES('2','$uid', '$displayAmount', '$serial', '$payName','$ref_num', '$mobile', '$upi', '$createdate', '1005', '2', '0')"
        );

        header('Location: ' . $responseData['data']['pay_url']);
        exit;
    } else {
        echo "Error: Unable to process USDT payment.";
        var_dump($response);
    }

} else {
    $res = [
        'code' => 405,
        'message' => 'Illegal access!',
    ];
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode($res);	
}
?>
