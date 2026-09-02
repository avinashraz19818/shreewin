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
					$sesquery = "SELECT akshinak FROM shonu_subjects WHERE akshinak = '$author'";
					$sesresult = $conn->query($sesquery);
					$sesnum = mysqli_num_rows($sesresult);
					if($sesnum == 1){
					    $userId = $data_auth['payload']['id'];
						
						// Get rules content from database or static content
						$rulesContent = getTurntableRulesContent($language);
						
						// Prepare response data
						$data['rulesContent'] = $rulesContent;
						$data['lastUpdateTime'] = date('Y-m-d H:i:s');
						$data['version'] = '1.0';
						
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
	}
	else{
		$res['code'] = 11;
		$res['msg'] = 'Method not allowed';
		$res['msgCode'] = 12;
		http_response_code(405);
		echo json_encode($res);		
	}

	// Function to get turntable rules content
	function getTurntableRulesContent($language = 'en') {
		global $conn;
		
		// Try to get rules from database first
		$rulesQuery = "SELECT content FROM wheel_rules WHERE language = '$language' AND is_active = 1 ORDER BY id DESC LIMIT 1";
		$rulesResult = $conn->query($rulesQuery);
		
		if($rulesResult && mysqli_num_rows($rulesResult) > 0) {
			$rulesRow = mysqli_fetch_array($rulesResult);
			return $rulesRow['content'];
		}
		
		// Default rules content if not found in database
		if($language == 'hi') {
			// Hindi rules
			return '
			<h3>लकी व्हील नियम और शर्तें</h3>
			<ul>
				<li><strong>प्रतिदिन स्पिन सीमा:</strong> प्रत्येक उपयोगकर्ता प्रतिदिन अधिकतम 10 बार स्पिन कर सकता है</li>
				<li><strong>पुरस्कार राशि:</strong> स्पिन पर 450, 478, 430, या 488 का पुरस्कार जीतें</li>
				<li><strong>निकासी न्यूनतम:</strong> निकासी के लिए न्यूनतम 1.00 की आवश्यकता है</li>
				<li><strong>निकासी प्रक्रिया:</strong> निकासी अनुरोध 24-48 घंटों के भीतर संसाधित किए जाते हैं</li>
				<li><strong>पुरस्कार वैधता:</strong> जीते गए पुरस्कार 30 दिनों के लिए वैध होते हैं</li>
			</ul>
			<p>किसी भी धोखाधड़ी या दुरुपयोग के मामले में, खाता निलंबित किया जा सकता है</p>
			';
		} else {
			// English rules (default)
			return '
			<h3>Lucky Wheel Rules and Terms</h3>
			<ul>
				<li><strong>Daily Spin Limit:</strong> Each user can spin maximum 10 times per day</li>
				<li><strong>Prize Amounts:</strong> Win prizes of 450, 478, 430, or 488 on spin</li>
				<li><strong>Withdrawal Minimum:</strong> Minimum 1.00 required for withdrawal</li>
				<li><strong>Withdrawal Process:</strong> Withdrawal requests are processed within 24-48 hours</li>
				<li><strong>Prize Validity:</strong> Won prizes are valid for 30 days</li>
				<li><strong>Fair Play:</strong> Each spin is completely random and fair</li>
				<li><strong>Account Requirement:</strong> Must have verified account to withdraw</li>
			</ul>
			<p>In case of any fraud or misuse, account may be suspended</p>
			';
		}
	}
?>