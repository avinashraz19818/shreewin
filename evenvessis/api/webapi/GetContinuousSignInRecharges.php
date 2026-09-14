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
                    $shonuid = $data_auth['payload']['id'];
                    
                    // DEBUG: Check if user exists in cihne
                    $check_user = mysqli_query($conn, "SELECT COUNT(*) as total FROM `cihne` WHERE `identity`='".$shonuid."'");
                    $check_result = mysqli_fetch_array($check_user);
                    
                    if($check_result['total'] == 0) {
                        $res['debug'] = 'No records found in cihne for user: ' . $shonuid;
                        $data['signIn']['signCount'] = 0;
                        $data['signIn']['isCycle'] = 0;
                        $data['signIn']['signInSum'] = 0;
                    } else {
                        // Get latest record
                        $existance = mysqli_query($conn, "SELECT `daysonearth`, `totalblessings` FROM `cihne` 
                                                          WHERE `identity`='".$shonuid."' 
                                                          ORDER BY `dearlord` DESC LIMIT 1");
                        
                        if(mysqli_num_rows($existance) > 0) {
                            $existance_ar = mysqli_fetch_array($existance);
                            $data['signIn']['signCount'] = $existance_ar['daysonearth'];
                            $data['signIn']['isCycle'] = $existance_ar['daysonearth'];
                            $data['signIn']['signInSum'] = $existance_ar['totalblessings'];
                            
                            $res['debug'] = [
                                'daysonearth' => $existance_ar['daysonearth'],
                                'totalblessings' => $existance_ar['totalblessings']
                            ];
                        } else {
                            $res['debug'] = 'Query returned 0 rows';
                            $data['signIn']['signCount'] = 0;
                            $data['signIn']['isCycle'] = 0;
                            $data['signIn']['signInSum'] = 0;
                        }
                    }
                    
                    // Get rewards list
                    $rewards = mysqli_query($conn, "SELECT * FROM signin_recharge_rewards WHERE status = 1 ORDER BY day ASC");
                    $i = 0;
                    while ($reward = mysqli_fetch_assoc($rewards)) {
                        $data['signInRechargesList'][$i]['rechargesID'] = $reward['rechargesID'];
                        $data['signInRechargesList'][$i]['amount'] = $reward['amount'];
                        $data['signInRechargesList'][$i]['day'] = $reward['day'];
                        $data['signInRechargesList'][$i]['bouns'] = $reward['bonus'];
                        
                        // Calculate isReceive based on actual records
                        $check_day = mysqli_query($conn, "SELECT COUNT(*) as claimed 
                                                          FROM `cihne` 
                                                          WHERE `identity`='".$shonuid."' 
                                                          AND `daysonearth` = '".$reward['day']."'");
                        $day_check = mysqli_fetch_array($check_day);
                        $data['signInRechargesList'][$i]['isReceive'] = ($day_check['claimed'] > 0) ? 1 : 0;
                        
                        $i++;
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