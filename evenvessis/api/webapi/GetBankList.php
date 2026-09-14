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
		if (isset($shonupost['language']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp']) && isset($shonupost['withdrawid'])) {
			$language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
			$random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
			$signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
			$withdrawid = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['withdrawid']));
			$shonustr = '{"language":'.$language.',"random":"'.$random.'","withdrawid":'.$withdrawid.'}';
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
						http_response_code(200);
						if($withdrawid == 23){
							echo '{
                                    "data": {
                                        "banklist": [
                                            {
                                                "bankID": 23,
                                                "bankLogo": "https://ossimg.92rpay.com/92r",
                                                "bankName": "EASYPAISA",
                                                "reserved": "23"
                                            }
                                        ]
                                    },
                                    "code": 0,
                                    "msg": "Succeed",
                                    "msgCode": 0,
                                    "serviceNowTime": "2025-09-15 21:44:38"
                                }
							';				
						} else if($withdrawid == 24){
							echo '{
                                    "data": {
                                        "banklist": [
                                            {
                                                "bankID": 24,
                                                "bankLogo": "https://ossimg.92rpay.com/92r",
                                                "bankName": "JazzCash",
                                                "reserved": "24"
                                            }
                                        ]
                                    },
                                    "code": 0,
                                    "msg": "Succeed",
                                    "msgCode": 0,
                                    "serviceNowTime": "2025-09-15 21:44:38"
                                }
							';				
						}
						else if($withdrawid == 3){
							echo '
								{
								  "data": {
									"banklist": [
									  {
										"bankID": 55,
										"bankLogo": "https://ossimg.bdg123456.com/BDGWin",
										"bankName": "TRC",
										"reserved": "3"
									  }
									]
								  },
								  "code": 0,
								  "msg": "Succeed",
								  "msgCode": 0,
								  "serviceNowTime": "$shnunc"
								}
							';	
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