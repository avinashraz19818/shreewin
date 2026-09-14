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
	$currentTime = date("Y-m-d H:i:s");

	$response = [
		'code' => 11,
		'msg' => 'Method not allowed',
		'msgCode' => 12,
		'serviceNowTime' => $currentTime,
	];

	$body = file_get_contents("php://input");
	$postData = json_decode($body, true);

	if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
		if (
			isset($postData['language']) &&
			isset($postData['random']) &&
			isset($postData['signature']) &&
			isset($postData['timestamp']) &&
			isset($postData['vipLevel'])
		) {
			$language = htmlspecialchars(mysqli_real_escape_string($conn, $postData['language']));
			$random = htmlspecialchars(mysqli_real_escape_string($conn, $postData['random']));
			$signature = htmlspecialchars(mysqli_real_escape_string($conn, $postData['signature']));
			$vipLevel = intval($postData['vipLevel']);

			// Signature verification
			$verifyStr = '{"language":'.$language.',"random":"'.$random.'","vipLevel":'.$vipLevel.'}';
			$verifySign = strtoupper(md5($verifyStr));

			if ($verifySign === $signature) {
				$bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
				$token = $bearer[1];				
				$jwtData = json_decode(is_jwt_valid($token), true);

				if ($jwtData['status'] === 'Success') {
					// Verify token is valid in DB
					$query = "SELECT akshinak FROM shonu_subjects WHERE akshinak = '$token'";
					$result = $conn->query($query);

					if ($result && $result->num_rows === 1) {
						// Fetch dynamic rewards based on vipLevel
						$vipQuery = "SELECT reward_id AS id, name, description, integral, balance, rate 
									 FROM vip_levels 
									 WHERE vip_level = $vipLevel 
									 ORDER BY reward_id";
						$vipResult = $conn->query($vipQuery);

						$data = [];
						if ($vipResult && $vipResult->num_rows > 0) {
							while ($row = $vipResult->fetch_assoc()) {
								$data[] = $row;
							}
						}

						$response['data'] = $data;
						$response['code'] = 0;
						$response['msg'] = 'Succeed';
						$response['msgCode'] = 0;
						http_response_code(200);
						echo json_encode($response);
					} else {
						$response['code'] = 4;
						$response['msg'] = 'No operation permission';
						$response['msgCode'] = 2;
						http_response_code(401);
						echo json_encode($response);
					}
				} else {
					$response['code'] = 4;
					$response['msg'] = 'No operation permission';
					$response['msgCode'] = 2;
					http_response_code(401);
					echo json_encode($response);	
				}
			} else {
				$response['code'] = 5;
				$response['msg'] = 'Wrong signature';
				$response['msgCode'] = 3;
				http_response_code(200);
				echo json_encode($response);		
			}
		} else {
			$response['code'] = 7;
			$response['msg'] = 'Param is Invalid';
			$response['msgCode'] = 6;
			http_response_code(200);
			echo json_encode($response);
		}
	} else {
		http_response_code(405);
		echo json_encode($response);
	}
?>
