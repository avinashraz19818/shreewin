<?php 
@ob_start();
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
if ($payTypeID == 1023 || $payTypeID == 1124 || $payTypeID == 1030 || $payTypeID == 1029 || $payTypeID == 1021) {
    $payID_for_bonus = 2; // Easypaisa
} elseif ($payTypeID == 1010 || $payTypeID == 1012 || $payTypeID == 1013 || $payTypeID == 1014 || $payTypeID == 1015) {
    $payID_for_bonus = 1; // JazzCash
} elseif ($payTypeID == 2123 || $payTypeID == 2190) {
    $payID_for_bonus = 11; // USDT
} elseif ($payTypeID == 2191 || $payTypeID == 2192) {
    $payID_for_bonus = 13; // PayTM
}


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

$demoQuery = "SELECT 1 FROM demo WHERE balakedara = '$uid'";
$demoResult = $conn->query($demoQuery);

if ($demoResult->num_rows > 0) {
    $createdate = date("Y-m-d H:i:s");
    
    $insertQuery = "
        INSERT INTO `thevani` (`balakedara`, `motta`, `dharavahi`, `mula`, `ullekha`, `duravani`, `ekikrtapavati`, `dinankavannuracisi`, `madari`, `pavatiaidi`, `sthiti`) 
        VALUES ('$uid', '$ramt', '$serial', '$payName', 'N/A', 'N/A', 'N/A', '$createdate', '1005', '2', '1')
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
        $name = 'TestPay';
        $email = 'Boompays@gmail.com';
        $mobile = $numarr['mobile'];
        $remark = 'remark';
        $type = 2;
        

    
            include 'bmconfig.php';
             
            // Set up parameters
            $notify_url = "https://luckywin28.buzz/pay/bmcall.php";
            
            if (!$ramt || !$serial) {
                die("Error: Amount or order ID not provided.");
            }
            
            $apiConfigUrl = "https://pkapi.boompays.online/v1/paynew";
            
            $data = [
                "mchID" => $bm_mch,
                "mchorderid" => $serial,
                "channel_code" => $bm_ch,
                "api_key" => $bm_ky,
                "amount" => $amount,
                "return_url" => $myurl,
                "notify_url" => $notify_url
            ];
            
            // Initialize cURL session
            $ch = curl_init();
            
            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $apiConfigUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            
            // Set headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/x-www-form-urlencoded"
            ]);
            
            // Execute cURL request
            $response = curl_exec($ch);
            
            // Check for errors
            if ($response === false) {
                die("cURL Error: " . curl_error($ch));
            }
            
         
            // Decode the JSON response
            $responseData = json_decode($response, true);
            
         
            // Check if the response contains the payment URL
            if ($responseData && $responseData['msg'] == "success" && isset($responseData['payurl'])) {
                $amt = $amount;
                $srl = $serial;
                $source = 'Boompays';
                $ref_num = $serial;					
                $emailQ = mysqli_query($conn , "SELECT mobile FROM `shonu_subjects` WHERE `id` = '".$uid."'");
                $emailA = mysqli_fetch_array($emailQ);
                $email = $emailA['mobile'];
                $upi = 'Boompays';
                $createdate = date("Y-m-d H:i:s");

                // Insert payment record into `thevani`
                $deposit1 = mysqli_query($conn, "INSERT INTO `thevani`(`payid`,`balakedara`, `motta`, `dharavahi`, `mula`, `ullekha`, `duravani`, `ekikrtapavati`, `dinankavannuracisi`, `madari`, `pavatiaidi`, `sthiti`) 
                VALUES('2','$uid', '$amount', '$srl', '$payName','$ref_num', '$email', '$upi', '$createdate', '1005', '2', '0')");
                
                header('Location: ' . $responseData['payurl']);
                exit;
            } else {
                // If there's an error, display an appropriate message
                echo "Error: Unable to process payment";
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
