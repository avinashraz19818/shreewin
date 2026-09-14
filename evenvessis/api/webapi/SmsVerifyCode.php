<?php
include "../../conn.php";

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

function generateOTP() {
	$characters = '123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < 6; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function sendOTPThroughHyperService($mobile, $otp) {
    // Your OTP Service API
    $api_key = "d41393aed809d773e11e11008ad46c70"; // Client's API key
    $api_url = "https://otp.hypersofts.in/api/send_otp.php";
    
    // Build URL with GET parameters (as per your API structure)
    $full_url = $api_url . "?api_key=" . urlencode($api_key) . 
               "&number=" . urlencode($mobile) . 
               "&otp=" . urlencode($otp);
    
    // Use cURL for better error handling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Client-OTP-Service/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Debug information
    $debug_info = [
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'api_url' => $full_url
    ];
    
    if ($response === false) {
        return [
            'success' => false,
            'error' => 'Connection failed: ' . $curlError,
            'debug' => $debug_info
        ];
    }
    
    // Decode JSON response
    $result = json_decode($response, true);
    
    if ($result === null) {
        $debug_info['json_error'] = json_last_error_msg();
        $debug_info['raw_response'] = $response;
        return [
            'success' => false,
            'error' => 'Invalid response from OTP service',
            'debug' => $debug_info
        ];
    }
    
    $debug_info['api_response'] = $result;
    
    // Check your API response structure
    if (isset($result['success'])) {
        if ($result['success'] === true) {
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'order_id' => $result['order_id'] ?? null,
                'remaining_balance' => $result['remaining_balance'] ?? null,
                'debug' => $debug_info
            ];
        } else {
            $errorMsg = $result['error'] ?? 'Unknown service error';
            $contactRequired = $result['show_contact'] ?? false;
            
            $response_data = [
                'success' => false,
                'error' => $errorMsg,
                'debug' => $debug_info
            ];
            
            // Add contact information if required
            if ($contactRequired) {
                $response_data['contact_developer'] = true;
                $response_data['contact_info'] = [
                    'telegram' => $result['telegram_username'] ?? '@Hyperdeveloperr',
                    'message' => 'Please contact developer to resolve API access issues'
                ];
            }
            
            return $response_data;
        }
    }
    
    return [
        'success' => false,
        'error' => 'Unexpected API response format',
        'debug' => $debug_info
    ];
}

$shonubody = file_get_contents("php://input");
$shonupost = json_decode($shonubody, true);

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
	if (isset($shonupost['codeType']) && isset($shonupost['language']) && isset($shonupost['phone']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp'])) {
		$codeType = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['codeType']));
		$language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
		$phone = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['phone']));
		$random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
		$signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
		$shonustr = '{"codeType":' . $codeType . ',"language":' . $language . ',"phone":"' . $phone . '","random":"' . $random . '"}';
		$shonusign = strtoupper(md5($shonustr));
		
		if ($shonusign == $signature) {
			// Handle phone number format - keep as is for your API
			if (substr($phone, 0, 2) == "91") {
				$mobile_for_api = $phone; // Keep 91 prefix
				$mobile_for_db = substr($phone, 2); // Remove 91 for database
			} else {
				$mobile_for_api = "91" . $phone; // Add 91 prefix
				$mobile_for_db = $phone; // Keep as is for database
			}
			
			// Validate mobile number (10 digits after removing 91)
			if (!preg_match('/^[0-9]{10}$/', $mobile_for_db)) {
				$res['code'] = 1;
				$res['msg'] = 'Invalid mobile number';
				$res['msgCode'] = 102;
				http_response_code(200);
				echo json_encode($res);
				exit;
			}
			
			// Check if user exists in database
			$samasye = "SELECT id FROM shonu_subjects WHERE mobile = '$mobile_for_db'";
			$samasyephalitansa = $conn->query($samasye);
			$user_exists = mysqli_num_rows($samasyephalitansa) == 1;
			
			// Determine if we should proceed based on codeType and user existence
			if ($user_exists || $codeType == 1) {
				$otp = generateOTP();
				$createdate = date("Y-m-d H:i:s");
				
				// Determine OTP type
				$otp_type = $user_exists ? 'Reset PSWD' : 'Registration';

				// Save OTP in database
				$sql = mysqli_query($conn, "INSERT INTO `otp_record` (`mobile`, `otp`, `type`, `createdate`) VALUES ('" . $mobile_for_db . "','" . $otp . "','" . $otp_type . "','" . $createdate . "')");

				// Send OTP via your service
				$smsResult = sendOTPThroughHyperService($mobile_for_api, $otp);
				
				if ($smsResult['success']) {
					$res['code'] = 0;
					$res['msg'] = 'OTP sent successfully';
					$res['msgCode'] = 0;
					
					// Add additional info if available
					if (isset($smsResult['order_id'])) {
						$res['order_id'] = $smsResult['order_id'];
					}
					if (isset($smsResult['remaining_balance'])) {
						$res['remaining_balance'] = $smsResult['remaining_balance'];
					}
				} else {
					$res['code'] = 1;
					$res['msg'] = $smsResult['error'];
					$res['msgCode'] = 140;
					
					// Include contact information if IP/domain not whitelisted
					if (isset($smsResult['contact_developer']) && $smsResult['contact_developer']) {
						$res['contact_developer'] = true;
						$res['contact_info'] = $smsResult['contact_info'];
					}
					
					// Include debug info for troubleshooting
					if (isset($smsResult['debug'])) {
						$res['debug'] = $smsResult['debug'];
					}
				}
			} else {
				$res['code'] = 1;
				$res['msg'] = 'User does not exist';
				$res['msgCode'] = 101;
			}
		} else {
			$res['code'] = 5;
			$res['msg'] = 'Wrong signature';
			$res['msgCode'] = 3;
		}
	} else {
		$res['code'] = 7;
		$res['msg'] = 'Missing required parameters';
		$res['msgCode'] = 6;
	}
} else {
	http_response_code(405);
}

http_response_code(200);
echo json_encode($res);
?>