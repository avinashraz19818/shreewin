<?php

$config = require __DIR__ . '/../../pay/config.php';


$appSecret = $config['nopay_secret_key2'];
$appId = $config['nopay_appid2'];

// Signature function
function generateSignature($params, $appSecret) {
    ksort($params);
    $queryString = urldecode(http_build_query($params)) . "&key=" . $appSecret;
    return hash('sha256', $queryString);
}

// Request parameters
$data = [
    "appId"    => $appId,
    "timestamp"=> time(),
];

// Optional: coin add karna ho to yahan likh sakte ho
// $data['coin'] = "USDT";

// Sign generate karo
$data["sign"] = generateSignature($data, $appSecret);

// API endpoint
$url = "https://icw891o.nopay.app/order/balanceCoinQuery";

// Curl request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "appId: $appId",
    "version: v1",
    "language: en"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Response dekhlo
$result = json_decode($response, true);

// Print for debug
print_r($result);

?>
