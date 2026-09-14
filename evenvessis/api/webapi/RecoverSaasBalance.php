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
	
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$shonupost = $_GET;
	}
	if ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($shonupost['language']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp'])) {
			$language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
			$random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
			$signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
			$shonustr = '{"language":'.$language.',"random":"'.$random.'"}';
			$shonusign = strtoupper(md5($shonustr));
			if($shonusign == $signature){
				// Robust JWT extraction: Authorization header or author/token fallback
				$bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION'] ?? '');
				$author = isset($bearer[1]) ? $bearer[1] : null;
				if (empty($author) && isset($shonupost['author'])) { $author = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['author'])); }
				if (empty($author) && isset($shonupost['token'])) { $author = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['token'])); }
				$is_jwt_valid = is_jwt_valid($author);
				$data_auth = json_decode($is_jwt_valid, 1);
				if($data_auth['status'] === 'Success') {
					$sesquery = "SELECT akshinak
					  FROM shonu_subjects
					  WHERE akshinak = '$author'";
					$sesresult=$conn->query($sesquery);
					$sesnum = mysqli_num_rows($sesresult);
					if($sesnum == 1){
						// Fetch balances consistently
						$userId = intval($data_auth['payload']['id']);
						// Main wallet (motta)
						$motta = 0.0;
						$balStmt = $conn->prepare("SELECT motta FROM shonu_kaichila WHERE balakedara = ?");
						if ($balStmt) {
							$balStmt->bind_param("i", $userId);
							$balStmt->execute();
							$balRes = app_stmt_result($balStmt);
							if ($balRes && $balRes->num_rows > 0) {
								$balRow = $balRes->fetch_assoc();
								$motta = floatval($balRow['motta']);
							}
						}
						// Third-party (ARGame)
						$argBalance = 0.0;
						error_log("GetUserAmount: Checking ARGame balance for user {$userId}");
						
						// Check if table exists first
						$tableCheck = $conn->query("SHOW TABLES LIKE 'argame_balances'");
						if ($tableCheck && $tableCheck->num_rows == 0) {
							error_log("GetUserAmount: argame_balances table does not exist!");
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
							error_log("GetUserAmount: Created argame_balances table");
						}
						
						$argStmt = $conn->prepare("SELECT balance FROM argame_balances WHERE user_id = ?");
						if ($argStmt) {
							$argStmt->bind_param("i", $userId);
							$argStmt->execute();
							$argRes = app_stmt_result($argStmt);
							if ($argRes && $argRes->num_rows > 0) {
								$argRow = $argRes->fetch_assoc();
								$argBalance = floatval($argRow['balance']);
								error_log("GetUserAmount: Found ARGame balance {$argBalance} for user {$userId}");
							} else {
								error_log("GetUserAmount: No ARGame balance found for user {$userId}");
							}
						} else {
							error_log("GetUserAmount: Failed to prepare ARGame query for user {$userId} - " . $conn->error);
						}
						
						// Auto-recover trigger: Enable when user clicks transfer button
						$doRecover = true; // Default to true for manual transfer
						if (isset($shonupost['skipRecover']) && intval($shonupost['skipRecover']) == 1) { $doRecover = false; }
						$transferred = 0.0;
						if ($doRecover && $argBalance > 0) {
							$argBalanceBefore = $argBalance;
							$conn->begin_transaction();
							// Update main wallet
							$mainRows = 0;
							$updMain = $conn->prepare("UPDATE shonu_kaichila SET motta = motta + ? WHERE balakedara = ?");
							if ($updMain) {
								$updMain->bind_param("di", $argBalanceBefore, $userId);
								$updMain->execute();
								$mainRows = $updMain->affected_rows;
								if ($mainRows <= 0) {
									$insMain = $conn->prepare("INSERT INTO shonu_kaichila (balakedara, motta) VALUES (?, ?)");
									if ($insMain) {
										$insMain->bind_param("id", $userId, $argBalanceBefore);
										$insMain->execute();
										$mainRows = $insMain->affected_rows;
									}
								}
							}
							// Zero-out ARGame
							$arRows = 0;
							$updAR = $conn->prepare("UPDATE argame_balances SET balance = 0, updated_at = NOW() WHERE user_id = ?");
							if ($updAR) {
								$updAR->bind_param("i", $userId);
								$updAR->execute();
								$arRows = $updAR->affected_rows;
							}
							if ($mainRows <= 0 || $arRows <= 0) {
								$conn->rollback();
							} else {
								$conn->commit();
								$transferred = $argBalanceBefore;
								$argBalance = 0.0; // reflect zero after transfer
								// Refresh motta
								$balStmt = $conn->prepare("SELECT motta FROM shonu_kaichila WHERE balakedara = ?");
								if ($balStmt) {
									$balStmt->bind_param("i", $userId);
									$balStmt->execute();
									$balRes = app_stmt_result($balStmt);
									if ($balRes && $balRes->num_rows > 0) {
										$balRow = $balRes->fetch_assoc();
										$motta = floatval($balRow['motta']);
									}
								}
							}
						}
						
						// Prepare response
						error_log("GetUserAmount: Final balances - Main: {$motta}, ARGame: {$argBalance}, Transferred: {$transferred}, User: {$userId}");
						$data['amount'] = (int)$motta;
						$data['uRate'] = 93;
						$data['uGold'] = 0;
						$data['thirdPartyBalance'] = [ 'ARGame' => $argBalance ];
						if ($transferred > 0) {
							$data['recovered'] = 1;
							$data['transferred'] = $transferred;
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