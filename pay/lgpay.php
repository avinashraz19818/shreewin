<?php 
    header('Content-type: text/plain; charset=utf-8');
    include ("../serive/samparka.php");
?>

<?php 
if(isset($_GET['amount'])){
    $ramt = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['amount']));
    $payTypeID = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
} else{
    $ramt = 0;
}
    $payName = 'LG-Pay';

$dot_pos = strpos($ramt, '.');
if ($dot_pos === false) {
    $ramt = $ramt . '.00';
} else {
    $after_dot = substr($ramt, $dot_pos + 1);
    $after_dot_length = strlen($after_dot);
    if ($after_dot_length > 2) {
        $after_dot = substr($after_dot, 0, 2);
        $ramt = substr($ramt, 0, $dot_pos + 1) . $after_dot;
    } elseif ($after_dot_length < 2) {
        $zeros_to_add = 2 - $after_dot_length;
        $ramt = $ramt . str_repeat('0', $zeros_to_add);
    }
}

$date = date("Ymd");
$time = time();
$serial = $date . $time . rand(100000, 999900);

$tyid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
$uid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['uid']));
$sign = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['sign']));
$urlInfo = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['urlInfo']));

// Check if `uid` exists in the `demo` table
$demoQuery = "SELECT 1 FROM demo WHERE balakedara = '$uid'";
$demoResult = $conn->query($demoQuery);

if ($demoResult->num_rows > 0) {
    // If uid exists in demo table, insert into `thevani` table and update `motta`
    $createdate = date("Y-m-d H:i:s");
    $emailQ = mysqli_query($conn , "SELECT mobile FROM `shonu_subjects` WHERE `id` = '".$uid."'");
	$emailA = mysqli_fetch_array($emailQ);
	$userm = $emailA['mobile'];
    // Insert into `thevani`
    $insertQuery = "
        INSERT INTO `thevani` (`payid`, `balakedara`, `motta`, `dharavahi`, `mula`, `ullekha`, `duravani`, `ekikrtapavati`, `dinankavannuracisi`, `madari`, `pavatiaidi`, `sthiti`) 
        VALUES ('2', '$uid', '$ramt', '$serial', '$payName', 'N/A', '$userm', 'N/A', '$createdate', '1005', '2', '1')
    ";
    $conn->query($insertQuery);

    // Update `motta` field in `shonu_kaichila`
    $updateQuery = "
        UPDATE `shonu_kaichila`
        SET `motta` = `motta` + $ramt
        WHERE `balakedara` = '$uid'
    ";
    $conn->query($updateQuery);

    // Redirect to recharge history
    header('Location: https://luckywin28.buzz/#/main');
    exit;
}

// If not found in `demo` table, proceed with the rest of the existing logic

$res = [
    'code' => 405,
    'message' => 'Illegal access!',
];
if (isset($_GET['tyid']) && isset($_GET['amount']) && isset($_GET['uid']) && isset($_GET['sign']) && isset($_GET['urlInfo'])) {
    $userId = $uid;
    $userPhoto = '1';

    $numquery = "SELECT mobile, codechorkamukala
        FROM shonu_subjects
        WHERE id = ".$userId;
    $numresult = $conn->query($numquery);
    $numarr = mysqli_fetch_array($numresult);

    $userName = '91'.$numarr['mobile'];
    $nickName = $numarr['codechorkamukala'];

    $creaquery = "SELECT createdate
        FROM shonu_subjects
        WHERE id = ".$userId;
    $crearesult = $conn->query($creaquery);
    $creaarr = mysqli_fetch_array($crearesult);

    $knbdstr = '{"userId":'.$userId.',"userPhoto":"'.$userPhoto.'","userName":'.$userName.',"nickName":"'.$nickName.'","createdate":"'.$creaarr['createdate'].'"}';
    $shonusign = strtoupper(hash('sha256', $knbdstr));

    $urlarr = explode (",", $urlInfo);
    $theirurl = $urlarr[0];
    $myurl = 'https://luckywin28.buzz';

    if($myurl){

        $orderid = $serial;
        $amount = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['amount']));
        $name = 'TestName';
        $email = 'testemail@gmail.com';
        $mobile = $numarr['mobile'];
        $remark = 'remark';
        $type = 2;
        

        // API configuration
        $config = require 'lgpayconfig.php';
        $apiUrl = $config['api_url'];
        $secretKey = $config['secret_key'];
        $app_id = $config['app_id'];

        // Set up parameters
        $notify_url = "https://luckywin28.buzz/pay/lgpaywebhook.php";

        if (!$ramt || !$serial) {
            die("Error: Amount or order ID not provided.");
        }

        $params = [
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
            'remark' => 'web pay',
            'notify_url' => $notify_url,
            'money' => $amount*100,
            'app_id' => $app_id,
            'trade_type' => 'PKRPH',
            'order_sn' => $serial,
            'return_url' => 'https://luckywin28.buzz/#/wallet/RechargeHistory',
        ];

        // Sort parameters
        ksort($params);
        $signatureString = '';
        foreach ($params as $key => $value) {
            $signatureString .= "$key=$value&";
        }
        $signatureString .= "key=$secretKey";
        $signature = strtoupper(md5($signatureString));

        $params['sign'] = $signature;

        $postData = http_build_query($params);

        // Send the request to the payment API
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo "Error: " . curl_error($ch);
        } else {
            // Decode the JSON response
            $responseData = json_decode($response, true);

            // Check if the response contains the payment URL
            if ($responseData && $responseData['status'] == 1 && isset($responseData['data']['pay_url'])) {
                $amt = $amount;
                $srl = $serial;
                $source = 'IndianPay';
                $ref_num = $un['order_id'];					
                $emailQ = mysqli_query($conn , "SELECT mobile FROM `shonu_subjects` WHERE `id` = '".$uid."'");
                $emailA = mysqli_fetch_array($emailQ);
                $email = $emailA['mobile'];
                $upi = 'IndianPayUPI';
                $createdate = date("Y-m-d H:i:s");

                // Insert payment record into `thevani`
                $deposit1 = mysqli_query($conn, "INSERT INTO `thevani`(`payid`,`balakedara`, `motta`, `dharavahi`, `mula`, `ullekha`, `duravani`, `ekikrtapavati`, `dinankavannuracisi`, `madari`, `pavatiaidi`, `sthiti`) 
                VALUES('2','$uid', '$amount', '$srl', '$payName','$ref_num', '$email', '$upi', '$createdate', '1005', '2', '0')");
                
                header('Location: ' . $responseData['data']['pay_url']);
                exit;
            } else {
                // If there's an error, display an appropriate message
                echo "Error: Unable to process payment.";
                var_dump($response);
            }
        }

        curl_close($ch);

    } else {
        $res['code'] = 10000;
        $res['success'] = 'false';
        $res['message'] = 'Sorry, The system is busy, please try again later!';

        header('Content-Type: text/html; charset=utf-8');
        http_response_code(200);
        echo json_encode($res);
    }
}
	else {
		header('Content-Type: application/json; charset=utf-8');
		http_response_code(200);
		echo json_encode($res);	
	}
?>