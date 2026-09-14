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
                $sesresult = $conn->query($sesquery);
                $sesnum = mysqli_num_rows($sesresult);
                
                if($sesnum == 1){
                    // Get user ID from token
                    $token_parts = explode('.', $author);
                    $payload = json_decode(base64_decode($token_parts[1]), true);
                    $user_id = isset($payload['id']) ? $payload['id'] : 0;
                    
                    if($user_id > 0) {
                        $today_date = date('Y-m-d');
                        $today_rewards = 0;
                        $total_rewards = 0;
                        
                        // DEBUGGING: Log user ID
                        error_log("Calculating rewards for user ID: " . $user_id);
                        
                        // 1. SPIN RECORDS (spinrec table)
                        $spin_query = "SELECT COALESCE(SUM(prize), 0) as today_total, 
                                      COALESCE(SUM(prize), 0) as total 
                                      FROM spinrec WHERE user_id = '$user_id'";
                        $spin_result = $conn->query($spin_query);
                        if($spin_result) {
                            $spin_row = $spin_result->fetch_assoc();
                            $spin_total = (float)$spin_row['total'];
                            $total_rewards += $spin_total;
                            
                            // Today's spin
                            $spin_today_query = "SELECT COALESCE(SUM(prize), 0) as today 
                                                FROM spinrec WHERE user_id = '$user_id' 
                                                AND DATE(time) = '$today_date'";
                            $spin_today_result = $conn->query($spin_today_query);
                            if($spin_today_result) {
                                $spin_today_row = $spin_today_result->fetch_assoc();
                                $today_rewards += (float)$spin_today_row['today'];
                            }
                            error_log("Spin rewards - Today: " . $spin_today_row['today'] . ", Total: " . $spin_total);
                        }
                        
                        // 2. SIGNUP BONUS (shonu_kaichila)
                        $signup_query = "SELECT COALESCE(SUM(bonus), 0) as total 
                                        FROM shonu_kaichila WHERE balakedara = '$user_id'";
                        $signup_result = $conn->query($signup_query);
                        if($signup_result) {
                            $signup_row = $signup_result->fetch_assoc();
                            $total_rewards += (float)$signup_row['total'];
                            
                            // Today's signup bonus
                            $signup_today_query = "SELECT COALESCE(SUM(bonus), 0) as today 
                                                  FROM shonu_kaichila WHERE balakedara = '$user_id' 
                                                  AND DATE(dinankavannuracisi) = '$today_date'";
                            $signup_today_result = $conn->query($signup_today_query);
                            if($signup_today_result) {
                                $signup_today_row = $signup_today_result->fetch_assoc();
                                $today_rewards += (float)$signup_today_row['today'];
                            }
                            error_log("Signup rewards - Today: " . $signup_today_row['today'] . ", Total: " . $signup_row['total']);
                        }
                        
                        // 3. FIRST RECHARGE BONUS (egrahcer_sonub)
                        $recharge_query = "SELECT COALESCE(SUM(sturgis), 0) as total 
                                          FROM egrahcer_sonub WHERE dr = '$user_id' AND status = 1";
                        $recharge_result = $conn->query($recharge_query);
                        if($recharge_result) {
                            $recharge_row = $recharge_result->fetch_assoc();
                            $total_rewards += (float)$recharge_row['total'];
                            
                            // Today's recharge bonus
                            $recharge_today_query = "SELECT COALESCE(SUM(sturgis), 0) as today 
                                                    FROM egrahcer_sonub WHERE dr = '$user_id' 
                                                    AND DATE(time) = '$today_date' AND status = 1";
                            $recharge_today_result = $conn->query($recharge_today_query);
                            if($recharge_today_result) {
                                $recharge_today_row = $recharge_today_result->fetch_assoc();
                                $today_rewards += (float)$recharge_today_row['today'];
                            }
                            error_log("Recharge rewards - Today: " . $recharge_today_row['today'] . ", Total: " . $recharge_row['total']);
                        }
                        
                        // 4. ATTENDANCE BONUS (cihne)
                        $attendance_query = "SELECT COALESCE(SUM(todayblessings), 0) as total 
                                            FROM cihne WHERE identity = '$user_id'";
                        $attendance_result = $conn->query($attendance_query);
                        if($attendance_result) {
                            $attendance_row = $attendance_result->fetch_assoc();
                            $total_rewards += (float)$attendance_row['total'];
                            
                            // Today's attendance
                            $attendance_today_query = "SELECT COALESCE(SUM(todayblessings), 0) as today 
                                                      FROM cihne WHERE identity = '$user_id' 
                                                      AND DATE(amen) = '$today_date'";
                            $attendance_today_result = $conn->query($attendance_today_query);
                            if($attendance_today_result) {
                                $attendance_today_row = $attendance_today_result->fetch_assoc();
                                $today_rewards += (float)$attendance_today_row['today'];
                            }
                            error_log("Attendance rewards - Today: " . $attendance_today_row['today'] . ", Total: " . $attendance_row['total']);
                        }
                        
                        // 5. COMMISSION (commission table)
                        $commission_query = "SELECT COALESCE(SUM(rebateAmount_Last), 0) as total 
                                            FROM commission WHERE user_id = '$user_id'";
                        $commission_result = $conn->query($commission_query);
                        if($commission_result) {
                            $commission_row = $commission_result->fetch_assoc();
                            $total_rewards += (float)$commission_row['total'];
                            
                            // Today's commission
                            $commission_today_query = "SELECT COALESCE(SUM(rebateAmount_Last), 0) as today 
                                                      FROM commission WHERE user_id = '$user_id' 
                                                      AND DATE(created_timestamp) = '$today_date'";
                            $commission_today_result = $conn->query($commission_today_query);
                            if($commission_today_result) {
                                $commission_today_row = $commission_today_result->fetch_assoc();
                                $today_rewards += (float)$commission_today_row['today'];
                            }
                            error_log("Commission - Today: " . $commission_today_row['today'] . ", Total: " . $commission_row['total']);
                        }
                        
                        // 6. REBET BONUS (rebetrec)
                        $rebet_query = "SELECT COALESCE(SUM(motta), 0) as total 
                                       FROM rebetrec WHERE user_id = '$user_id'";
                        $rebet_result = $conn->query($rebet_query);
                        if($rebet_result) {
                            $rebet_row = $rebet_result->fetch_assoc();
                            $total_rewards += (float)$rebet_row['total'];
                            
                            // Today's rebet
                            $rebet_today_query = "SELECT COALESCE(SUM(motta), 0) as today 
                                                 FROM rebetrec WHERE user_id = '$user_id' 
                                                 AND DATE(created_at) = '$today_date'";
                            $rebet_today_result = $conn->query($rebet_today_query);
                            if($rebet_today_result) {
                                $rebet_today_row = $rebet_today_result->fetch_assoc();
                                $today_rewards += (float)$rebet_today_row['today'];
                            }
                            error_log("Rebet - Today: " . $rebet_today_row['today'] . ", Total: " . $rebet_row['total']);
                        }
                        
                        // 7. VIP BONUS (viprec)
                        $vip_query = "SELECT COALESCE(SUM(motta), 0) as total 
                                     FROM viprec WHERE user_id = '$user_id'";
                        $vip_result = $conn->query($vip_query);
                        if($vip_result) {
                            $vip_row = $vip_result->fetch_assoc();
                            $total_rewards += (float)$vip_row['total'];
                            
                            // Today's VIP
                            $vip_today_query = "SELECT COALESCE(SUM(motta), 0) as today 
                                               FROM viprec WHERE user_id = '$user_id' 
                                               AND DATE(created_at) = '$today_date'";
                            $vip_today_result = $conn->query($vip_today_query);
                            if($vip_today_result) {
                                $vip_today_row = $vip_today_result->fetch_assoc();
                                $today_rewards += (float)$vip_today_row['today'];
                            }
                            error_log("VIP - Today: " . $vip_today_row['today'] . ", Total: " . $vip_row['total']);
                        }
                        
                        // 8. REFERRAL TASK (noitativni_sonub)
                        $referral_query = "SELECT COALESCE(SUM(motta), 0) as total 
                                          FROM noitativni_sonub WHERE arthur = '$user_id' AND status = 1";
                        $referral_result = $conn->query($referral_query);
                        if($referral_result) {
                            $referral_row = $referral_result->fetch_assoc();
                            $total_rewards += (float)$referral_row['total'];
                            
                            // Today's referral
                            $referral_today_query = "SELECT COALESCE(SUM(motta), 0) as today 
                                                    FROM noitativni_sonub WHERE arthur = '$user_id' 
                                                    AND DATE(time) = '$today_date' AND status = 1";
                            $referral_today_result = $conn->query($referral_today_query);
                            if($referral_today_result) {
                                $referral_today_row = $referral_today_result->fetch_assoc();
                                $today_rewards += (float)$referral_today_row['today'];
                            }
                            error_log("Referral - Today: " . $referral_today_row['today'] . ", Total: " . $referral_row['total']);
                        }
                        
                        // 9. AGENT COMMISSION (agent_salary_log)
                        $agent_query = "SELECT COALESCE(SUM(total_amount), 0) as total 
                                       FROM agent_salary_log WHERE userid = '$user_id'";
                        $agent_result = $conn->query($agent_query);
                        if($agent_result) {
                            $agent_row = $agent_result->fetch_assoc();
                            $total_rewards += (float)$agent_row['total'];
                            
                            // Today's agent commission
                            $agent_today_query = "SELECT COALESCE(SUM(total_amount), 0) as today 
                                                 FROM agent_salary_log WHERE userid = '$user_id' 
                                                 AND DATE(created_at) = '$today_date'";
                            $agent_today_result = $conn->query($agent_today_query);
                            if($agent_today_result) {
                                $agent_today_row = $agent_today_result->fetch_assoc();
                                $today_rewards += (float)$agent_today_row['today'];
                            }
                            error_log("Agent - Today: " . $agent_today_row['today'] . ", Total: " . $agent_row['total']);
                        }
                        
                        // 10. DAILY SALARY (dailysalary)
                        $salary_query = "SELECT COALESCE(SUM(salary), 0) as total 
                                        FROM dailysalary WHERE userid = '$user_id'";
                        $salary_result = $conn->query($salary_query);
                        if($salary_result) {
                            $salary_row = $salary_result->fetch_assoc();
                            $total_rewards += (float)$salary_row['total'];
                            
                            // Today's salary
                            $salary_today_query = "SELECT COALESCE(SUM(salary), 0) as today 
                                                  FROM dailysalary WHERE userid = '$user_id' 
                                                  AND DATE(createdate) = '$today_date'";
                            $salary_today_result = $conn->query($salary_today_query);
                            if($salary_today_result) {
                                $salary_today_row = $salary_today_result->fetch_assoc();
                                $today_rewards += (float)$salary_today_row['today'];
                            }
                            error_log("Salary - Today: " . $salary_today_row['today'] . ", Total: " . $salary_row['total']);
                        }
                        
                        // 11. DEPOSIT BONUS (thevani)
                        $deposit_query = "SELECT COALESCE(SUM(motta), 0) as total 
                                         FROM thevani WHERE balakedara = '$user_id' AND sthiti = 1";
                        $deposit_result = $conn->query($deposit_query);
                        if($deposit_result) {
                            $deposit_row = $deposit_result->fetch_assoc();
                            $total_rewards += (float)$deposit_row['total'];
                            
                            // Today's deposit
                            $deposit_today_query = "SELECT COALESCE(SUM(motta), 0) as today 
                                                   FROM thevani WHERE balakedara = '$user_id' 
                                                   AND DATE(dinankavannuracisi) = '$today_date' AND sthiti = 1";
                            $deposit_today_result = $conn->query($deposit_today_query);
                            if($deposit_today_result) {
                                $deposit_today_row = $deposit_today_result->fetch_assoc();
                                $today_rewards += (float)$deposit_today_row['today'];
                            }
                            error_log("Deposit - Today: " . $deposit_today_row['today'] . ", Total: " . $deposit_row['total']);
                        }
                        
                        // 12. RED ENVELOPE (hodike_balakedara)
                        $red_query = "SELECT COALESCE(SUM(price), 0) as total 
                                     FROM hodike_balakedara WHERE userkani = '$user_id'";
                        $red_result = $conn->query($red_query);
                        if($red_result) {
                            $red_row = $red_result->fetch_assoc();
                            $total_rewards += (float)$red_row['total'];
                            
                            // Today's red envelope
                            $red_today_query = "SELECT COALESCE(SUM(price), 0) as today 
                                               FROM hodike_balakedara WHERE userkani = '$user_id' 
                                               AND DATE(shonu) = '$today_date'";
                            $red_today_result = $conn->query($red_today_query);
                            if($red_today_result) {
                                $red_today_row = $red_today_result->fetch_assoc();
                                $today_rewards += (float)$red_today_row['today'];
                            }
                            error_log("Red Envelope - Today: " . $red_today_row['today'] . ", Total: " . $red_row['total']);
                        }
                        
                        // 13. ALL BONUS TRANSACTIONS (all_bonus_transactions)
                        $all_bonus_query = "SELECT COALESCE(SUM(amount), 0) as total 
                                           FROM all_bonus_transactions WHERE user_id = '$user_id'";
                        $all_bonus_result = $conn->query($all_bonus_query);
                        if($all_bonus_result) {
                            $all_bonus_row = $all_bonus_result->fetch_assoc();
                            $total_rewards += (float)$all_bonus_row['total'];
                            
                            // Today's all bonus
                            $all_bonus_today_query = "SELECT COALESCE(SUM(amount), 0) as today 
                                                     FROM all_bonus_transactions WHERE user_id = '$user_id' 
                                                     AND DATE(transaction_date) = '$today_date'";
                            $all_bonus_today_result = $conn->query($all_bonus_today_query);
                            if($all_bonus_today_result) {
                                $all_bonus_today_row = $all_bonus_today_result->fetch_assoc();
                                $today_rewards += (float)$all_bonus_today_row['today'];
                            }
                            error_log("All Bonus - Today: " . $all_bonus_today_row['today'] . ", Total: " . $all_bonus_row['total']);
                        }
                        
                        // 14. JACKPOT WINNINGS (bajikattuttate tables with phalaphala = 'gagner')
                        $jackpot_query = "SELECT COALESCE(SUM(sesabida), 0) as total 
                                         FROM bajikattuttate WHERE byabaharkarta = '$user_id' AND phalaphala = 'gagner'";
                        $jackpot_result = $conn->query($jackpot_query);
                        if($jackpot_result) {
                            $jackpot_row = $jackpot_result->fetch_assoc();
                            $total_rewards += (float)$jackpot_row['total'];
                            
                            // Today's jackpot
                            $jackpot_today_query = "SELECT COALESCE(SUM(sesabida), 0) as today 
                                                   FROM bajikattuttate WHERE byabaharkarta = '$user_id' 
                                                   AND phalaphala = 'gagner' AND DATE(tiarikala) = '$today_date'";
                            $jackpot_today_result = $conn->query($jackpot_today_query);
                            if($jackpot_today_result) {
                                $jackpot_today_row = $jackpot_today_result->fetch_assoc();
                                $today_rewards += (float)$jackpot_today_row['today'];
                            }
                            error_log("Jackpot - Today: " . $jackpot_today_row['today'] . ", Total: " . $jackpot_row['total']);
                        }
                        
                        error_log("FINAL - Today Rewards: " . $today_rewards . ", Total Rewards: " . $total_rewards);
                    } else {
                        $today_rewards = 0;
                        $total_rewards = 0;
                    }
                    
                    $data = [
                        "isTaskState" => "1",
                        "isOpenJackpotReward" => "0",
                        "isOpenWashCode" => "1",
                        "unJackpotCount" => 0,
                        "isOpenActivityAward" => "1",
                        "unWeeklyAwardCount" => 0,
                        "isFinishUserGuidelines" => true,
                        "isFirstUserDayRequest" => false,
                        "isOpenChampion" => "0",
                        "newbieGiftPackCount" => 0,
                        "newMemberGiftPackageSwitch" => 0,
                        "todayRewards" => (int) 0,
                        "totalRewards" => (int) 0 
                    ];
                    
                    $res['data'] = $data;
                    $res['code'] = 0;
                    $res['msg'] = 'Succeed';
                    $res['msgCode'] = 0;
                    http_response_code(200);
                    echo json_encode($res);			
                }
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