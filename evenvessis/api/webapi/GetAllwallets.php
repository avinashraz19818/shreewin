<?php 
	include "../../conn.php";
	include "../../functions2.php";
	
	header('Content-Type: application/json; charset=utf-8');
	header('Strict-Transport-Security: max-age=31536000');
	header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
	header('Access-Control-Allow-Credentials: true');
	$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
	header('Access-Control-Allow-Origin: ' . $origin);
	header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
				// Robust Authorization token extraction (Bearer header or fallbacks)
				$author = '';
				if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
					$parts = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
					if (count($parts) === 2) {
						$author = $parts[1];
					} else {
						$author = $_SERVER['HTTP_AUTHORIZATION'];
					}
				}
				if (empty($author)) {
					if (isset($shonupost['author'])) { $author = $shonupost['author']; }
					elseif (isset($shonupost['token'])) { $author = $shonupost['token']; }
					elseif (isset($_GET['author'])) { $author = $_GET['author']; }
					elseif (isset($_GET['token'])) { $author = $_GET['token']; }
				}
				$author = htmlspecialchars(mysqli_real_escape_string($conn, $author));
				
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
						  WHERE balakedara = ".intval($data_auth['payload']['id']);
						$balresult = $conn->query($balquery);
						$balarr = mysqli_fetch_array($balresult);
						// Fetch ARGame third-party balance from argame_balances (avoid get_result for compatibility)
						$argame_balance = 0;
						$argstmt = $conn->prepare("SELECT balance FROM argame_balances WHERE user_id = ?");
						$userId = intval($data_auth['payload']['id']);
						$argstmt->bind_param("i", $userId);
						if ($argstmt->execute()) {
							$argstmt->bind_result($balanceVal);
							if ($argstmt->fetch()) {
								$argame_balance = floatval($balanceVal);
							}
						}
						$argstmt->close();

                        // Auto-recover block removed per requirement; just read balances without transferring
                        $motta_val = isset($balarr['motta']) ? floatval($balarr['motta']) : 0.0;

                        // Only keep Lottery and ARGame vendors
                        $data['thidGameBalanceList'] = [];
                        $data['thidGameBalanceList'][0]['vendorCode'] = 'Lottery';
                        $data['thidGameBalanceList'][0]['balance'] = (int)$motta_val;
                        $data['thidGameBalanceList'][1]['vendorCode'] = 'ARGame';
                        $data['thidGameBalanceList'][1]['balance'] = $argame_balance;
                        
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