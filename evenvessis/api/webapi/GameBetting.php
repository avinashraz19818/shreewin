<?php 
include "../../conn.php";
require_once __DIR__ . '/../../veegame_legacy_gate.php';
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

// ============================================
// FUNCTION: Update Need to Bet on Bet Placement
// ============================================
function updateNeedToBetOnBet($conn, $user_id, $bet_amount) {
    // Get all pending deposits (oldest first)
    $pendingQuery = mysqli_query($conn, "SELECT id, required_bet, completed_bet 
                                        FROM user_deposit_bet_track 
                                        WHERE user_id = '$user_id' AND status = 'pending'
                                        ORDER BY created_at ASC");
    
    if (!$pendingQuery || mysqli_num_rows($pendingQuery) == 0) {
        return 0; // No pending requirements
    }
    
    $remaining_bet = $bet_amount;
    $total_applied = 0;
    
    while ($row = mysqli_fetch_assoc($pendingQuery)) {
        if ($remaining_bet <= 0) break;
        
        $track_id = $row['id'];
        $required = floatval($row['required_bet']);
        $completed = floatval($row['completed_bet']);
        $remaining_required = $required - $completed;
        
        if ($remaining_required > 0) {
            $apply_amount = min($remaining_bet, $remaining_required);
            $new_completed = $completed + $apply_amount;
            $new_status = ($new_completed >= $required) ? 'completed' : 'pending';
            
            // Update tracking
            mysqli_query($conn, "UPDATE user_deposit_bet_track 
                                SET completed_bet = '$new_completed', status = '$new_status' 
                                WHERE id = '$track_id'");
            
            $remaining_bet -= $apply_amount;
            $total_applied += $apply_amount;
        }
    }
    
    return $total_applied; // Return how much was applied
}
	
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
	if (isset($shonupost['amount']) && isset($shonupost['betCount']) && isset($shonupost['gameType']) && isset($shonupost['issuenumber']) && 
		isset($shonupost['language']) && isset($shonupost['random']) && isset($shonupost['selectType']) && isset($shonupost['signature']) && 
		isset($shonupost['timestamp']) && isset($shonupost['typeId'])) {
		$amount = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['amount']));
		$betCount = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['betCount']));
		$gameType = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['gameType']));
		$issuenumber = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['issuenumber']));
		$language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
		$random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
		$selectType = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['selectType']));
		$signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
		$typeId = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['typeId']));
		$shonustr = '{"amount":'.$amount.',"betCount":'.$betCount.',"gameType":'.$gameType.',"issuenumber":"'.$issuenumber.'","language":'.$language.',"random":"'.$random.'","selectType":'.$selectType.',"typeId":'.$typeId.'}';
		$shonusign = strtoupper(md5($shonustr));
		if($shonusign == $signature){
			$bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
			$author = $bearer[1];				
			$is_jwt_valid = is_jwt_valid($author);
			$data_auth = json_decode($is_jwt_valid, 1);
			if($data_auth['status'] === 'Success') {
				/* =====================================================
				   🔒 RESTRICT USER CHECK (YOUR EXISTING CODE)
				===================================================== */
				$user_id = (int)$data_auth['payload']['id'];
				
				// Check if user is restricted from betting
				$restrict_query = "SELECT restrictbet, codechorkamukala, mobile 
								  FROM shonu_subjects 
								  WHERE id = '$user_id' 
								  LIMIT 1";
				$restrict_result = $conn->query($restrict_query);
				
				if ($restrict_result && $restrict_result->num_rows === 1) {
					$user_data = $restrict_result->fetch_assoc();
					if ((int)$user_data['restrictbet'] === 1) {
						$res['code'] = 1009;
						$res['msg'] = "You are restricted from betting";
						$res['msgCode'] = 1009;
						$res['restricted'] = true;
						$res['data'] = [
							"userId" => $user_id,
							"username" => $user_data['codechorkamukala'],
							"mobile" => $user_data['mobile']
						];
						http_response_code(200);
						echo json_encode($res);
						exit;
					}
				}
				/* ================= END RESTRICT ================= */
				
				$sesquery = "SELECT akshinak
				  FROM shonu_subjects
				  WHERE akshinak = '$author'";
				$sesresult=$conn->query($sesquery);
				$sesnum = mysqli_num_rows($sesresult);
				if($sesnum == 1){
					if($typeId == 1){
						$lordjesus = 'bajikattuttate';
						$sonofgod = 'gelluonduhogu';
					}
					else if($typeId == 2){
						$lordjesus = 'bajikattuttate_drei';
						$sonofgod = 'gelluonduhogu_drei';
					}
					else if($typeId == 3){
						$lordjesus = 'bajikattuttate_funf';
						$sonofgod = 'gelluonduhogu_funf';
					}
					else if($typeId == 4){
						$lordjesus = 'bajikattuttate_zehn';
						$sonofgod = 'gelluonduhogu_zehn';
					}
					if($betCount >= 1){
						if($amount >= 1){
							$samasye = "SELECT atadaaidi
							  FROM ".$sonofgod."
							  ORDER BY kramasankhye DESC LIMIT 1";
							$samasyephalitansa=$conn->query($samasye);
							$samasyesreni = mysqli_fetch_array($samasyephalitansa);
							if($samasyesreni['atadaaidi'] == $issuenumber){
								$totalamount = $amount * $betCount;								
								$balquery = "SELECT motta
								  FROM shonu_kaichila
								  WHERE balakedara = ".$data_auth['payload']['id'];
								$balresult = $conn->query($balquery);
								$balarr = mysqli_fetch_array($balresult);									
								$shonubalance = $balarr['motta'];								
								if($shonubalance >= $totalamount){
									$byabaharkarta = $data_auth['payload']['id'];
									
									// 🔒 FIRST DEPOSIT CHECK SYSTEM (YOUR EXISTING CODE)
									$first_recharge_query = "SELECT * FROM thevani WHERE balakedara = '$byabaharkarta' AND sthiti = 1";
									$first_recharge_result = $conn->query($first_recharge_query);
									
									if ($first_recharge_result && $first_recharge_result->num_rows > 0) {
										// ✅ First recharge completed - allow betting
										$sesabida = sprintf("%.2f", $totalamount * 0.98);
										$tathya = mysqli_query($conn,"INSERT INTO `".$lordjesus."` (`byabaharkarta`,`kalaparichaya`,`prakar`,`ojana`,`menge`,`wettanzahl`,`ketebida`,`phalaphala`,`sesabida`,`tiarikala`) VALUES ('".$byabaharkarta."','".$issuenumber."','".$gameType."','".$selectType."','".$amount."','".$betCount."','".$totalamount."','perte','".$sesabida."','".$shnunc."')");
										
										if($tathya){
											$mottanutan = $shonubalance - $totalamount;
											$nabikarana = "UPDATE shonu_kaichila set motta='$mottanutan' where balakedara='$byabaharkarta'";
											$conn->query($nabikarana);
											
											// ============================================
											// 🎯 UPDATE NEED TO BET - BET LAGATE HI KAM HOGA (YEH LINE ADD HO RAHI)
											// ============================================
											updateNeedToBetOnBet($conn, $byabaharkarta, $totalamount);
											
											// VIP experience update (YOUR EXISTING CODE)
											$vip_experience_amount = $amount * $betCount;
											include "commission.php";
											include "vip.php";
											
											$res['data'] = null;
											$res['code'] = 0;
											$res['msg'] = 'Succeed';
											$res['msgCode'] = 0;
											http_response_code(200);
											echo json_encode($res);
										} else {
											$res['code'] = 7;
											$res['msg'] = 'Bet placement failed';
											$res['msgCode'] = 500;
											http_response_code(200);
											echo json_encode($res);
										}
									} else {
										// ❌ First recharge not completed (YOUR EXISTING CODE)
										$res['code'] = 7;
										$res['msg'] = 'Please complete your first deposit to start betting';
										$res['msgCode'] = 502;
										$res['depositRequired'] = true;
										http_response_code(200);
										echo json_encode($res);
									}
								} else {
									$res['code'] = 1;
									$res['msg'] = 'Balance is not enough';
									$res['msgCode'] = 142;
									http_response_code(200);
									echo json_encode($res);
								}
							} else {
								$res['code'] = 1;
								$res['msg'] = 'The current period is settled';
								$res['msgCode'] = 404;
								http_response_code(200);
								echo json_encode($res);
							}																																				
						} else {
							$res['code'] = 7;
							$res['msg'] = "Invalid value for parameter 'Amount'";
							unset($res['msgCode']);
							unset($res['serviceNowTime']);
							http_response_code(200);
							echo json_encode($res);
						}
					} else {
						$res['code'] = 7;
						$res['msg'] = "Invalid value for parameter 'BetCount'";
						unset($res['msgCode']);
						unset($res['serviceNowTime']);
						http_response_code(200);
						echo json_encode($res);
					}
				} else {
					$res['code'] = 4;
					$res['msg'] = 'No operation permission';
					$res['msgCode'] = 2;
					http_response_code(401);
					echo json_encode($res);
				}					
			} else {					
				$res['code'] = 4;
				$res['msg'] = 'No operation permission';
				$res['msgCode'] = 2;
				http_response_code(401);
				echo json_encode($res);					
			}
		} else {
			$res['code'] = 5;
			$res['msg'] = 'Wrong signature';
			$res['msgCode'] = 1;
			http_response_code(403);
			echo json_encode($res);
		}
	} else {
		$res['code'] = 9;
		$res['msg'] = 'Missing parameters';
		unset($res['msgCode']);
		unset($res['serviceNowTime']);
		http_response_code(200);
		echo json_encode($res);
	}
}
else{
	http_response_code(405);
	echo json_encode($res);
}	
?>