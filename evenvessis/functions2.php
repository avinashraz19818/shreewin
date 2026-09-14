<?php 
	function generate_jwt($headers, $payload, $secret = 'bdgshonuuncensored') {
		$headers_encoded = base64url_encode(json_encode($headers));
		
		$payload_encoded = base64url_encode(json_encode($payload));
		
		$signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
		$signature_encoded = base64url_encode($signature);
		
		$jwt = "$headers_encoded.$payload_encoded.$signature_encoded";
		
		return $jwt;
	}
	
	function is_jwt_valid($jwt, $secret = 'bdgshonuuncensored') {
		
		$res = [
			'status' => '',
			'payload' => '',
		];

		$tokenParts = explode('.', $jwt);
		$header = base64_decode($tokenParts[0]);
		$payload = base64_decode($tokenParts[1]);
		$signature_provided = $tokenParts[2];

		$base64_url_header = base64url_encode($header);
		$base64_url_payload = base64url_encode($payload);
		$signature = hash_hmac('SHA256', $base64_url_header . "." . $base64_url_payload, $secret, true);
		$base64_url_signature = base64url_encode($signature);

		$is_signature_valid = ($base64_url_signature === $signature_provided);
		
		if (!$is_signature_valid) {
			$res['status']='Failed';
		} else {
			$res['status']='Success';
			$res['payload']=json_decode($payload, 1);
		}
		
		$allvalue = json_encode($res);
		
		return $allvalue;
	}
	
	function base64url_encode($str) {
		return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
	}

define('SESSION_EXPIRE_SECONDS', 86400); // Set to 24 hours for testing

function create_session_table_if_not_exists($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token TEXT NOT NULL,
        login_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        UNIQUE KEY unique_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->query($sql);
}

function expire_old_sessions($conn) {
    $sql = "DELETE FROM user_sessions WHERE expires_at < NOW()";
    $conn->query($sql);
}

function save_user_session($conn, $userId, $token, $loginAt, $expiresAt) {
    $stmt = $conn->prepare("
        INSERT INTO user_sessions (user_id, token, login_at, expires_at)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), login_at = VALUES(login_at), expires_at = VALUES(expires_at)
    ");
    if($stmt) {
        $stmt->bind_param("isss", $userId, $token, $loginAt, $expiresAt);
        $stmt->execute();
        $stmt->close();
    }
}

function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
?>