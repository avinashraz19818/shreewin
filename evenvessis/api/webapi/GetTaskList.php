<?php 
	include "../../conn.php";
	include "../../functions2.php";

	
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');
header('Vary: Origin');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allow_origin = '';
if ($origin) {
    $stmt = $conn->prepare("SELECT domain FROM allowed_origins WHERE domain=? AND status=1");
    $stmt->bind_param("s", $origin);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $allow_origin = $origin;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($allow_origin) header("Access-Control-Allow-Origin: $allow_origin");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, ar-origin, ar-real-ip, ar-session');
    exit(0);
}

if ($allow_origin) {
    header("Access-Control-Allow-Origin: $allow_origin");
}
	
	date_default_timezone_set("Asia/Dhaka");
	$shnunc = date("Y-m-d H:i:s");
	$res = [
		'code' => 11,
		'msg' => 'Method not allowed',
		'msgCode' => 12,
		'serviceNowTime' => $shnunc,
	];
	$shonubody = file_get_contents("php://input");
	$shonupost = json_decode($shonubody, true);
	if ($_SERVER['REQUEST_METHOD'] != 'GET') {
		if (isset($shonupost['language']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp'])) {
			$language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
			$random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
			$signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
			$shonustr = '{"language":'.$language.',"random":"'.$random.'"}';
			$shonusign = strtoupper(md5($shonustr));
			if($shonusign == $signature){
				$bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
				$author = $bearer[1];				
				$is_jwt_valid = is_jwt_valid($author);
				$data_auth = json_decode($is_jwt_valid, 1);
				if($data_auth['status'] === 'Success') {
					$sesquery = "SELECT akshinak
					  FROM shonu_subjects
					  WHERE akshinak = '$author'";
					$sesresult=$conn->query($sesquery);
					$sesnum = mysqli_num_rows($sesresult);
					if($sesnum == 1){
						$shonuid = $data_auth['payload']['id'];
						$findowncode = "SELECT owncode FROM shonu_subjects WHERE id = ".$shonuid;
						$owncodeqr = $conn->query($findowncode);
						$owncodear = mysqli_fetch_array($owncodeqr);
						$owncode = $owncodear['owncode'];
						
						$data['totalPeople'] = 50000;
						$data['totalAmount'] = null;
						
						
						// SQL query to fetch data for taskID from 0 to 13
$sql = "SELECT taskID, taskAmount, rechargeAmount, taskPeople 
        FROM tbl_invitebonus 
        WHERE taskID BETWEEN 0 AND 13";

// Execute the query
$result = $conn->query($sql);

// Check if there are results
if ($result->num_rows > 0) {
    // Loop through the result and assign to individual variables for each taskID
    while ($row = $result->fetch_assoc()) {
        $taskID = $row['taskID'];
        $taskAmount = $row['taskAmount'];
        $rechargeAmount = $row['rechargeAmount'];
        $taskPeople = $row['taskPeople'];

        // Create dynamic variable names based on taskID
        ${'taskAmount' . $taskID} = $taskAmount;
        ${'rechargeAmount' . $taskID} = $rechargeAmount;
        ${'taskPeople' . $taskID} = $taskPeople;

        
    }
} else {
    echo "No data found";
}
						
						//SELECT * FROM thevani GROUP BY (balakedara) HAVING SUM(motta) >= 300;
						$tiramisu = "SELECT SUM(`motta`) as total_motta
						FROM `thevani`
						WHERE `sthiti` = 1 AND `balakedara` IN (SELECT `id` FROM `shonu_subjects` WHERE `code` = '$owncode')
						GROUP BY `balakedara`
						HAVING total_motta >= $rechargeAmount0";
						$mascarpone = $conn->query($tiramisu);
						$pannacotta = mysqli_num_rows($mascarpone);
						
						
						
						
						$data['taskList'][0]['taskID'] = 1;
						$data['taskList'][0]['taskAmount'] = $taskAmount0;
						$data['taskList'][0]['rechargeAmount'] = $rechargeAmount0;
						$data['taskList'][0]['rechargeAmount_All'] = null;
						$data['taskList'][0]['taskPeople'] = $taskPeople0;
						$data['taskList'][0]['rechargePeople'] = $pannacotta;
						$data['taskList'][0]['taskRechargePeople'] = $taskPeople0;
						
						$effqry = "SELECT `id` FROM `shonu_subjects` WHERE `code` = '$owncode'";
						$effrn = $conn->query($effqry);
						$effrw = mysqli_num_rows($effrn);
						
						$data['taskList'][0]['efficientPeople'] = $effrw;
						$data['taskList'][0]['title'] = null;
						$data['taskList'][0]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 1 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][0]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][0]['isFinshed'] = $pannacotta >= 1 ? true : false;
						$data['taskList'][0]['beginDate'] = '2025-01-16';
						$data['taskList'][0]['endDate'] = '2099-01-16';
						
						$data['taskList'][1]['taskID'] = 2;
						$data['taskList'][1]['taskAmount'] = $taskAmount2;
						$data['taskList'][1]['rechargeAmount'] = $rechargeAmount2;
						$data['taskList'][1]['rechargeAmount_All'] = null;
						$data['taskList'][1]['taskPeople'] = $taskPeople2;
						$data['taskList'][1]['rechargePeople'] = $pannacotta;
						$data['taskList'][1]['taskRechargePeople'] = $taskPeople2;
						$data['taskList'][1]['efficientPeople'] = $effrw;
						$data['taskList'][1]['title'] = null;
						$data['taskList'][1]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 2 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][1]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][1]['isFinshed'] = $pannacotta >= 3 ? true : false;
						$data['taskList'][1]['beginDate'] = '2025-01-16';
						$data['taskList'][1]['endDate'] = '2099-01-16';
						
						$data['taskList'][2]['taskID'] = 3;
						$data['taskList'][2]['taskAmount'] = $taskAmount3;
						$data['taskList'][2]['rechargeAmount'] = $rechargeAmount3;
						$data['taskList'][2]['rechargeAmount_All'] = null;
						$data['taskList'][2]['taskPeople'] = $taskPeople3;
						$data['taskList'][2]['rechargePeople'] = $pannacotta;
						$data['taskList'][2]['taskRechargePeople'] = $taskPeople3;
						$data['taskList'][2]['efficientPeople'] = $effrw;
						$data['taskList'][2]['title'] = null;
						$data['taskList'][2]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 3 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][2]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][2]['isFinshed'] = $pannacotta >= 10 ? true : false;
						$data['taskList'][2]['beginDate'] = '2025-01-16';
						$data['taskList'][2]['endDate'] = '2099-01-16';
						
						$data['taskList'][3]['taskID'] = 4;
						$data['taskList'][3]['taskAmount'] = $taskAmount4;
						$data['taskList'][3]['rechargeAmount'] = $rechargeAmount4;
						$data['taskList'][3]['rechargeAmount_All'] = null;
						$data['taskList'][3]['taskPeople'] = $taskPeople4;
						$data['taskList'][3]['rechargePeople'] = $pannacotta;
						$data['taskList'][3]['taskRechargePeople'] = $taskPeople4;
						$data['taskList'][3]['efficientPeople'] = $effrw;
						$data['taskList'][3]['title'] = null;
						$data['taskList'][3]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 4 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][3]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][3]['isFinshed'] = $pannacotta >= 30 ? true : false;
						$data['taskList'][3]['beginDate'] = '2025-01-16';
						$data['taskList'][3]['endDate'] = '2099-01-16';
						
						$data['taskList'][4]['taskID'] = 5;
						$data['taskList'][4]['taskAmount'] = $taskAmount5;
						$data['taskList'][4]['rechargeAmount'] = $rechargeAmount5;
						$data['taskList'][4]['rechargeAmount_All'] = null;
						$data['taskList'][4]['taskPeople'] = $taskPeople5;
						$data['taskList'][4]['rechargePeople'] = $pannacotta;
						$data['taskList'][4]['taskRechargePeople'] = $taskPeople5;
						$data['taskList'][4]['efficientPeople'] = $effrw;
						$data['taskList'][4]['title'] = null;
						$data['taskList'][4]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 5 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][4]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][4]['isFinshed'] = $pannacotta >= 60 ? true : false;
						$data['taskList'][4]['beginDate'] = '2025-01-16';
						$data['taskList'][4]['endDate'] = '2099-01-16';
						
						$data['taskList'][5]['taskID'] = 6;
						$data['taskList'][5]['taskAmount'] = $taskAmount6;
						$data['taskList'][5]['rechargeAmount'] = $rechargeAmount6;
						$data['taskList'][5]['rechargeAmount_All'] = null;
						$data['taskList'][5]['taskPeople'] = $taskPeople6;
						$data['taskList'][5]['rechargePeople'] = $pannacotta;
						$data['taskList'][5]['taskRechargePeople'] = $taskPeople6;
						$data['taskList'][5]['efficientPeople'] = $effrw;
						$data['taskList'][5]['title'] = null;
						$data['taskList'][5]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 6 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][5]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][5]['isFinshed'] = $pannacotta >= 100 ? true : false;
						$data['taskList'][5]['beginDate'] = '2025-01-16';
						$data['taskList'][5]['endDate'] = '2099-01-16';
						
						$data['taskList'][6]['taskID'] = 7;
						$data['taskList'][6]['taskAmount'] = $taskAmount7;
						$data['taskList'][6]['rechargeAmount'] = $rechargeAmount7;
						$data['taskList'][6]['rechargeAmount_All'] = null;
						$data['taskList'][6]['taskPeople'] = $taskPeople7;
						$data['taskList'][6]['rechargePeople'] = $pannacotta;
						$data['taskList'][6]['taskRechargePeople'] = $taskPeople7;
						$data['taskList'][6]['efficientPeople'] = $effrw;
						$data['taskList'][6]['title'] = null;
						$data['taskList'][6]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 7 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][6]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][6]['isFinshed'] = $pannacotta >= 200 ? true : false;
						$data['taskList'][6]['beginDate'] = '2025-01-16';
						$data['taskList'][6]['endDate'] = '2099-01-16';
						
						$data['taskList'][7]['taskID'] = 8;
						$data['taskList'][7]['taskAmount'] = $taskAmount8;
						$data['taskList'][7]['rechargeAmount'] = $rechargeAmount8;
						$data['taskList'][7]['rechargeAmount_All'] = null;
						$data['taskList'][7]['taskPeople'] = $taskPeople8;
						$data['taskList'][7]['rechargePeople'] = $pannacotta;
						$data['taskList'][7]['taskRechargePeople'] = $taskPeople8;
						$data['taskList'][7]['efficientPeople'] = $effrw;
						$data['taskList'][7]['title'] = null;
						$data['taskList'][7]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 8 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][7]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][7]['isFinshed'] = $pannacotta >= 500 ? true : false;
						$data['taskList'][7]['beginDate'] = '2025-01-16';
						$data['taskList'][7]['endDate'] = '2099-01-26';
						
						$data['taskList'][8]['taskID'] = 9;
						$data['taskList'][8]['taskAmount'] = $taskAmount9;
						$data['taskList'][8]['rechargeAmount'] = $rechargeAmount9;
						$data['taskList'][8]['rechargeAmount_All'] = null;
						$data['taskList'][8]['taskPeople'] = $taskPeople9;
						$data['taskList'][8]['rechargePeople'] = $pannacotta;
						$data['taskList'][8]['taskRechargePeople'] = $taskPeople9;
						$data['taskList'][8]['efficientPeople'] = $effrw;
						$data['taskList'][8]['title'] = null;
						$data['taskList'][8]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 9 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][8]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][8]['isFinshed'] = $pannacotta >= 1000 ? true : false;
						$data['taskList'][8]['beginDate'] = '2025-01-16';
						$data['taskList'][8]['endDate'] = '2099-01-16';
						
						$data['taskList'][9]['taskID'] = 10;
						$data['taskList'][9]['taskAmount'] = $taskAmount10;
						$data['taskList'][9]['rechargeAmount'] = $rechargeAmount10;
						$data['taskList'][9]['rechargeAmount_All'] = null;
						$data['taskList'][9]['taskPeople'] = $taskPeople10;
						$data['taskList'][9]['rechargePeople'] = $pannacotta;
						$data['taskList'][9]['taskRechargePeople'] = $taskPeople10;
						$data['taskList'][9]['efficientPeople'] = $effrw;
						$data['taskList'][9]['title'] = null;
						$data['taskList'][9]['title2'] = null;
						
						$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 10 AND status = 1";
						$cassata = $conn->query($cannoli);
						$risotto = mysqli_num_rows($cassata);
						
						$data['taskList'][9]['isReceive'] = $risotto >= 1 ? 1 : 0;
						$data['taskList'][9]['isFinshed'] = $pannacotta >= 5000 ? true : false;
						$data['taskList'][9]['beginDate'] = '2025-01-16';
						$data['taskList'][9]['endDate'] = '2099-01-16';
						
				// 		$data['taskList'][10]['taskID'] = 11;
				// 		$data['taskList'][10]['taskAmount'] = $taskAmount11;
				// 		$data['taskList'][10]['rechargeAmount'] = $rechargeAmount11;
				// 		$data['taskList'][10]['rechargeAmount_All'] = null;
				// 		$data['taskList'][10]['taskPeople'] = $taskPeople11;
				// 		$data['taskList'][10]['rechargePeople'] = $pannacotta;
				// 		$data['taskList'][10]['taskRechargePeople'] = $taskPeople11;
				// 		$data['taskList'][10]['efficientPeople'] = $effrw;
				// 		$data['taskList'][10]['title'] = null;
				// 		$data['taskList'][10]['title2'] = null;
						
				// 		$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 11 AND status = 1";
				// 		$cassata = $conn->query($cannoli);
				// 		$risotto = mysqli_num_rows($cassata);
						
				// 		$data['taskList'][10]['isReceive'] = $risotto >= 1 ? 1 : 0;
				// 		$data['taskList'][10]['isFinshed'] = $pannacotta >= 10000 ? true : false;
				// 		$data['taskList'][10]['beginDate'] = '2025-01-16';
				// 		$data['taskList'][10]['endDate'] = '2099-01-16';
						
				// 		$data['taskList'][11]['taskID'] = 12;
				// 		$data['taskList'][11]['taskAmount'] = $taskAmount12;
				// 		$data['taskList'][11]['rechargeAmount'] = $rechargeAmount12;
				// 		$data['taskList'][11]['rechargeAmount_All'] = null;
				// 		$data['taskList'][11]['taskPeople'] = $taskPeople12;
				// 		$data['taskList'][11]['rechargePeople'] = $pannacotta;
				// 		$data['taskList'][11]['taskRechargePeople'] = $taskPeople12;
				// 		$data['taskList'][11]['efficientPeople'] = $effrw;
				// 		$data['taskList'][11]['title'] = null;
				// 		$data['taskList'][11]['title2'] = null;
						
				// 		$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 12 AND status = 1";
				// 		$cassata = $conn->query($cannoli);
				// 		$risotto = mysqli_num_rows($cassata);
						
				// 		$data['taskList'][11]['isReceive'] = $risotto >= 1 ? 1 : 0;
				// 		$data['taskList'][11]['isFinshed'] = $pannacotta >= 20000 ? true : false;
				// 		$data['taskList'][11]['beginDate'] = '2025-01-16';
				// 		$data['taskList'][11]['endDate'] = '2099-01-16';
						
				// 		$data['taskList'][12]['taskID'] = 13;
				// 		$data['taskList'][12]['taskAmount'] = $taskAmount13;
				// 		$data['taskList'][12]['rechargeAmount'] = $rechargeAmount13;
				// 		$data['taskList'][12]['rechargeAmount_All'] = null;
				// 		$data['taskList'][12]['taskPeople'] = $taskPeople13;
				// 		$data['taskList'][12]['rechargePeople'] = $pannacotta;
				// 		$data['taskList'][12]['taskRechargePeople'] = $taskPeople13;
				// 		$data['taskList'][12]['efficientPeople'] = $effrw;
				// 		$data['taskList'][12]['title'] = null;
				// 		$data['taskList'][12]['title2'] = null;
						
				// 		$cannoli = "SELECT id FROM noitativni_sonub WHERE arthur = '".$shonuid."' AND fleck = 13 AND status = 1";
				// 		$cassata = $conn->query($cannoli);
				// 		$risotto = mysqli_num_rows($cassata);
						
				// 		$data['taskList'][12]['isReceive'] = $risotto >= 1 ? 1 : 0;
				// 		$data['taskList'][12]['isFinshed'] = $pannacotta >= $taskPeople13 ? true : false;
				// 		$data['taskList'][12]['beginDate'] = '2025-01-16';
				// 		$data['taskList'][12]['endDate'] = '2099-01-16';
						
						
						$data['chirldrenListDataList'] = null;
						
						$res['data'] = $data;
						$res['code'] = 0;
						$res['msg'] = 'Succeed';
						$res['msgCode'] = 0;
						http_response_code(200);
						echo json_encode($res);			
					}
				}
			}
			else{
				$res['code'] = 5;
				$res['msg'] = 'Wrong signature';
				$res['msgCode'] = 3;
				http_response_code(200);
				echo json_encode($res);
			}
		}
		else{
			$res['code'] = 7;
			$res['msg'] = 'Param is Invalid';
			$res['msgCode'] = 6;
			http_response_code(200);
			echo json_encode($res);
		}		
	} else {		
		http_response_code(405);
		echo json_encode($res);
	}	
?>