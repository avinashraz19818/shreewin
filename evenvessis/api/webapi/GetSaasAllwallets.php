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
						$balquery = "SELECT motta
						  FROM shonu_kaichila
						  WHERE balakedara = ".$data_auth['payload']['id'];
						$balresult = $conn->query($balquery);
						$balarr = mysqli_fetch_array($balresult);
						// Fetch ARGame third-party balance from argame_balances
						$argame_balance = 0;
						$user_id = $data_auth['payload']['id'];
						error_log("GetAllwallets: Checking ARGame balance for user {$user_id}");
						
						// Check if table exists first
						$tableCheck = $conn->query("SHOW TABLES LIKE 'argame_balances'");
						if ($tableCheck && $tableCheck->num_rows == 0) {
							error_log("GetAllwallets: argame_balances table does not exist!");
							// Create table
							$createTable = "CREATE TABLE argame_balances (
								id INT AUTO_INCREMENT PRIMARY KEY,
								user_id VARCHAR(50) NOT NULL,
								balance DECIMAL(10,2) DEFAULT 0.00,
								created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
								updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
								UNIQUE KEY unique_user (user_id)
							)";
							$conn->query($createTable);
							error_log("GetAllwallets: Created argame_balances table");
						}
						
						$argstmt = $conn->prepare("SELECT balance FROM argame_balances WHERE user_id = ?");
						if ($argstmt) {
							$argstmt->bind_param("s", $user_id);
							$argstmt->execute();
							$argres = app_stmt_result($argstmt);
							if ($argres && $argres->num_rows > 0) {
							    $argrow = $argres->fetch_assoc();
							    $argame_balance = floatval($argrow['balance']);
							    error_log("GetAllwallets: Found ARGame balance {$argame_balance} for user {$user_id}");
							} else {
							    error_log("GetAllwallets: No ARGame balance found for user {$user_id}");
							}
						} else {
						    error_log("GetAllwallets: Failed to prepare ARGame balance query - " . $conn->error);
						}

                        // Just read balances without auto-recovery
                        $motta_val = isset($balarr['motta']) ? floatval($balarr['motta']) : 0.0;
                        error_log("GetAllwallets: Showing balances - Main: {$motta_val}, ARGame: {$argame_balance} for user {$user_id}");

                        $data['thidGameBalanceList'][0]['vendorCode'] = 'Lottery';
                        $data['thidGameBalanceList'][0]['balance'] = (int)$motta_val;
						$data['thidGameBalanceList'][1]['vendorCode'] = 'TB_Chess';
						$data['thidGameBalanceList'][1]['balance'] = 0;
						$data['thidGameBalanceList'][2]['vendorCode'] = 'Wickets9';
						$data['thidGameBalanceList'][2]['balance'] = 0;
						$data['thidGameBalanceList'][3]['vendorCode'] = 'CQ9';
						$data['thidGameBalanceList'][3]['balance'] = 0;
						$data['thidGameBalanceList'][4]['vendorCode'] = 'MG';
						$data['thidGameBalanceList'][4]['balance'] = 0;
						$data['thidGameBalanceList'][5]['vendorCode'] = 'JDB';
						$data['thidGameBalanceList'][5]['balance'] = 0;
						$data['thidGameBalanceList'][6]['vendorCode'] = 'DG';
						$data['thidGameBalanceList'][6]['balance'] = 0;
						$data['thidGameBalanceList'][7]['vendorCode'] = 'CMD';
						$data['thidGameBalanceList'][7]['balance'] = 0;
						$data['thidGameBalanceList'][8]['vendorCode'] = 'SaBa';
						$data['thidGameBalanceList'][8]['balance'] = 0;
						$data['thidGameBalanceList'][9]['vendorCode'] = 'EVO_Video';
						$data['thidGameBalanceList'][9]['balance'] = 0;
						$data['thidGameBalanceList'][10]['vendorCode'] = 'JILI';
						$data['thidGameBalanceList'][10]['balance'] = 0;
						$data['thidGameBalanceList'][11]['vendorCode'] = 'Card365';
						$data['thidGameBalanceList'][11]['balance'] = 0;
						$data['thidGameBalanceList'][12]['vendorCode'] = 'V8Card';
						$data['thidGameBalanceList'][12]['balance'] = 0;
						$data['thidGameBalanceList'][13]['vendorCode'] = 'AG_Video';
						$data['thidGameBalanceList'][13]['balance'] = 0;
						$data['thidGameBalanceList'][14]['vendorCode'] = 'PG';
						$data['thidGameBalanceList'][14]['balance'] = 0;
						$data['thidGameBalanceList'][15]['vendorCode'] = 'TB';
						$data['thidGameBalanceList'][15]['balance'] = 0;
						$data['thidGameBalanceList'][16]['vendorCode'] = 'WM_Video';
						$data['thidGameBalanceList'][16]['balance'] = 0;
						// Append Third-Party (ARGame) balance entry (no auto-transfer)
						$data['thidGameBalanceList'][17]['vendorCode'] = 'ARGame';
						$data['thidGameBalanceList'][17]['balance'] = (int)$argame_balance;
						$data['thidGameBalanceList'][18]['vendorCode'] = 'SEXY_Video';
						$data['thidGameBalanceList'][18]['balance'] = 0;
						$data['totalWithdraw'] = 0;
						$data['totalRecharge'] = 0;
						
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