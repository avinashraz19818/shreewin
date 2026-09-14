<?php 
	include "../../conn.php";
	include "../../functions2.php";
	
	header('Content-Type: application/json; charset=utf-8');
	header('Strict-Transport-Security: max-age=31536000');
	header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
	header('Access-Control-Allow-Credentials: true');
	$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
	header('Access-Control-Allow-Origin: ' . $origin);
	header('vary: Origin');
	
	date_default_timezone_set("Asia/Kolkata");
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

		if (isset($shonupost['date']) && isset($shonupost['language']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp'])) {

			$dateRaw   = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['date']));
			$language  = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));		
			$random    = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
			$signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));

			// ✅ FIX: dateOnly to avoid "double time specification"
			$dateOnly = substr($dateRaw, 0, 10); // YYYY-MM-DD

			$shonustr = '{"date":"'.$dateRaw.'","language":'.$language.',"random":"'.$random.'"}';							
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

						// =========================================================
						// ✅ IMPORTANT FIX:
						// User sends EARNING DATE (e.g. 2026-03-18)
						// Cron pays at 1AM next day and sets process_date = TODAY (e.g. 2026-03-19)
						// So settlementDay = dateOnly + 1 day
						// =========================================================
						$settlementDay = date('Y-m-d', strtotime($dateOnly . ' +1 day'));

						$ist = new DateTimeZone('Asia/Kolkata');
						$startIST = new DateTime($settlementDay . ' 00:00:00', $ist);
						$endIST   = new DateTime($settlementDay . ' 00:00:00', $ist);
						$endIST->modify('+1 day');

						$startStr = $startIST->format('Y-m-d H:i:s');
						$endStr   = $endIST->format('Y-m-d H:i:s');

						$data = null;

						$prakaraFilter = "('LVLCOMM1','LVLCOMM2','LVLCOMM3','LVLCOMM4','LVLCOMM5','LVLCOMM6')";

						// ✅ Users count based on process_date (settlement day)
						$samasye = "SELECT COUNT(DISTINCT koduvavanu) AS total_users
						  FROM vyavahara 
						  WHERE balakedara = $shonuid
						  AND commission_status = 1
						  AND process_date >= '$startStr'
						  AND process_date < '$endStr'
						  AND prakara IN $prakaraFilter";
						$samasyephalitansa = $conn->query($samasye);
						$samasyesreniUsers = mysqli_fetch_array($samasyephalitansa);
						$samasyedhadi = intval($samasyesreniUsers['total_users']);

						if($samasyedhadi == 0){
							$data = null;
						}
						else{
							$data = [];

							// settlementTime = next day 00:00:00 of settlement day
							$data['settlementTime'] = $endIST->format("Y-m-d H:i:s");
							$data['children_LotteryAmount_Users'] = $samasyedhadi;

							// ✅ Totals based on process_date (settlement day)
							$samasye2 = "SELECT COALESCE(SUM(ketebida),0) as tot_k, COALESCE(SUM(ayoga),0) as tot_a
							  FROM vyavahara 
							  WHERE balakedara = $shonuid
							  AND commission_status = 1
							  AND process_date >= '$startStr'
							  AND process_date < '$endStr'
							  AND prakara IN $prakaraFilter";
							$samasyephalitansa2 = $conn->query($samasye2);
							$samasyesreni = mysqli_fetch_array($samasyephalitansa2);

							$data['children_LotteryAmount'] = floatval($samasyesreni['tot_k']);
							$data['rebateAmount_Last'] = floatval($samasyesreni['tot_a']); // ✅ matches NewPromotion yesterday_commission batch

							// keep original input date (earning date)
							$data['time'] = $dateOnly;
						}						
																														
						$res['data'] = $data;
						$res['code'] = 0;
						$res['msg'] = 'Succeed';
						$res['msgCode'] = 0;
						http_response_code(200);
						echo json_encode($res);					
					}
					else{
						$res['code'] = 4;
						$res['msg'] = 'No operation permission';
						$res['msgCode'] = 2;
						http_response_code(401);
						echo json_encode($res);
					}					
				}
				else{					
					$res['code'] = 4;
					$res['msg'] = 'No operation permission';
					$res['msgCode'] = 2;
					http_response_code(401);
					echo json_encode($res);					
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