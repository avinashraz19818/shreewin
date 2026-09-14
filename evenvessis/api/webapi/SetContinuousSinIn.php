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
                    // Ye ID shonu_subjects ki ID hai
                    $shonu_id = $data_auth['payload']['id'];
                    
                    // Define current datetime
                    $crdt = date("Y-m-d H:i:s");
                    
                    // ===== USER MAPPING - FIND OR CREATE USER IN USERS TABLE =====
                    
                    // Get user details from shonu_subjects
                    $shonu_query = mysqli_query($conn, "SELECT `id`, `mobile`, `owncode` FROM `shonu_subjects` WHERE `id` = '$shonu_id'");
                    if(mysqli_num_rows($shonu_query) == 0) {
                        $res['code'] = 1;
                        $res['msg'] = 'User not found in shonu_subjects';
                        $res['msgCode'] = 404;
                        http_response_code(200);
                        echo json_encode($res);
                        exit;
                    }
                    $shonu_data = mysqli_fetch_array($shonu_query);
                    $mobile = $shonu_data['mobile'];
                    $owncode = $shonu_data['owncode'];
                    
                    // Find user in users table
                    $actual_user_id = null;
                    
                    // Method 1: Try by account (owncode)
                    if($owncode && $owncode != '0' && $owncode != '') {
                        $user_query = mysqli_query($conn, "SELECT `uid` FROM `users` WHERE `account` = '$owncode'");
                        if(mysqli_num_rows($user_query) > 0) {
                            $user_data = mysqli_fetch_array($user_query);
                            $actual_user_id = $user_data['uid'];
                        }
                    }
                    
                    // Method 2: Try by mobile
                    if(!$actual_user_id && $mobile && $mobile != '0' && $mobile != '') {
                        $user_query = mysqli_query($conn, "SELECT `uid` FROM `users` WHERE `mobile` = '$mobile'");
                        if(mysqli_num_rows($user_query) > 0) {
                            $user_data = mysqli_fetch_array($user_query);
                            $actual_user_id = $user_data['uid'];
                        }
                    }
                    
                    // Method 3: Try by username from JWT
                    if(!$actual_user_id) {
                        $username = $data_auth['payload']['username'] ?? '';
                        if($username) {
                            $user_query = mysqli_query($conn, "SELECT `uid` FROM `users` WHERE `username` = '$username'");
                            if(mysqli_num_rows($user_query) > 0) {
                                $user_data = mysqli_fetch_array($user_query);
                                $actual_user_id = $user_data['uid'];
                            }
                        }
                    }
                    
                    // Method 4: Create new user if not found
                    if(!$actual_user_id) {
                        $account_name = 'user_' . $shonu_id;
                        $username = 'user_' . $shonu_id;
                        $nickname = 'User ' . $shonu_id;
                        
                        $insert_user = mysqli_query($conn, "INSERT INTO `users` 
                            (`mobile`, `account`, `username`, `nickname`, `wallet`, `status`, `created_at`) 
                            VALUES 
                            ('$mobile', '$account_name', '$username', '$nickname', 0.00, 1, '$crdt')");
                        
                        if($insert_user) {
                            $actual_user_id = mysqli_insert_id($conn);
                        } else {
                            $res['code'] = 1;
                            $res['msg'] = 'Failed to create user in users table: ' . mysqli_error($conn);
                            $res['msgCode'] = 500;
                            http_response_code(200);
                            echo json_encode($res);
                            exit;
                        }
                    }
                    
                    // ===== END OF USER MAPPING =====
                    
                    // Get total approved recharge (balakedara uses shonu_id)
                    $recharge = mysqli_query($conn, "SELECT SUM(`motta`) as allrech FROM `thevani` WHERE `balakedara`='".$shonu_id."' AND `sthiti`='1'");
                    if (!$recharge) {
                        $res['code'] = 1;
                        $res['msg'] = 'Database error: ' . mysqli_error($conn);
                        $res['msgCode'] = 500;
                        http_response_code(200);
                        echo json_encode($res);
                        exit;
                    }
                    $rechargear = mysqli_fetch_array($recharge);
                    $allrech = $rechargear['allrech'] ?? 0;
                    
                    // Check existing sign-ins (cihne uses shonu_id as identity)
                    $existance = mysqli_query($conn, "SELECT `dearlord` FROM `cihne` WHERE `identity`='".$shonu_id."'");
                    $existanceno = mysqli_num_rows($existance);
                    
                    if($existanceno == 0){
                        // First sign-in
                        if($allrech >= 200){
                            // Start Transaction
                            mysqli_begin_transaction($conn);
                            
                            try {
                                // Insert into cihne (using shonu_id)
                                $sql = mysqli_query($conn, "INSERT INTO `cihne` (`identity`, `daysonearth`, `todayblessings`, `totalblessings`, `amen`) 
                                                            VALUES ('".$shonu_id."', '1', '7', '7', '".$crdt."')");
                                
                                if(!$sql){
                                    throw new Exception("Failed to insert into cihne: " . mysqli_error($conn));
                                }
                                
                                // ===== UPDATE BOTH WALLETS =====
                                
                                // 1. Update users table (backup wallet)
                                $update_users = mysqli_query($conn, "UPDATE `users` SET `wallet` = `wallet` + 7 WHERE `uid` = '$actual_user_id'");
                                
                                if(!$update_users){
                                    throw new Exception("Failed to update users wallet: " . mysqli_error($conn));
                                }
                                
                                // 2. Update shonu_kaichila table (main wallet used by app)
                                $check_kaichila = mysqli_query($conn, "SELECT * FROM `shonu_kaichila` WHERE `balakedara` = '$shonu_id'");
                                
                                if(mysqli_num_rows($check_kaichila) > 0) {
                                    // Update existing
                                    $update_kaichila = mysqli_query($conn, "UPDATE `shonu_kaichila` SET `motta` = `motta` + 7 WHERE `balakedara` = '$shonu_id'");
                                    if(!$update_kaichila){
                                        throw new Exception("Failed to update shonu_kaichila: " . mysqli_error($conn));
                                    }
                                } else {
                                    // Insert new
                                    $insert_kaichila = mysqli_query($conn, "INSERT INTO `shonu_kaichila` (`balakedara`, `motta`) VALUES ('$shonu_id', 7)");
                                    if(!$insert_kaichila){
                                        throw new Exception("Failed to insert into shonu_kaichila: " . mysqli_error($conn));
                                    }
                                }
                                
                                // Commit transaction
                                mysqli_commit($conn);
                                
                                $res['code'] = 0;
                                $res['msg'] = 'Succeed - First sign-in completed';
                                $res['msgCode'] = 0;
                                
                            } catch (Exception $e) {
                                // Rollback on error
                                mysqli_rollback($conn);
                                $res['code'] = 1;
                                $res['msg'] = 'Transaction failed: ' . $e->getMessage();
                                $res['msgCode'] = 500;
                            }
                        }
                        else{
                            $res['code'] = 1;
                            $res['msg'] = 'The recharge amount is not up to the standard. Required: 200, Available: ' . $allrech;
                            $res['msgCode'] = 502;
                        }
                    }
                    else if($existanceno > 0 && $existanceno < 7){
                        // Check if already signed in today
                        $todayCheck = mysqli_query($conn, "SELECT `dearlord` FROM `cihne` 
                                                          WHERE `identity`='".$shonu_id."' 
                                                          AND DATE(`amen`) = DATE('".$crdt."')");
                        $todaySigned = mysqli_num_rows($todayCheck);
                        
                        if($todaySigned == 0){
                            // Get current max day
                            $maxDayQuery = mysqli_query($conn, "SELECT MAX(`daysonearth`) as maxday FROM `cihne` WHERE `identity`='".$shonu_id."'");
                            $maxDayResult = mysqli_fetch_array($maxDayQuery);
                            $currentMaxDay = $maxDayResult['maxday'] ?? 0;
                            $nextDay = $currentMaxDay + 1;
                            
                            // Get total blessings so far
                            $totalQuery = mysqli_query($conn, "SELECT SUM(`todayblessings`) as total FROM `cihne` WHERE `identity`='".$shonu_id."'");
                            $totalResult = mysqli_fetch_array($totalQuery);
                            $totalSoFar = $totalResult['total'] ?? 0;
                            
                            // Set rewards based on next day
                            switch($nextDay){
                                case 2:
                                    $todayblessings = 20;
                                    $totalblessings = $totalSoFar + 20;
                                    $rechtobe = 1000;
                                    break;
                                case 3:
                                    $todayblessings = 100;
                                    $totalblessings = $totalSoFar + 100;
                                    $rechtobe = 3000;
                                    break;
                                case 4:
                                    $todayblessings = 200;
                                    $totalblessings = $totalSoFar + 200;
                                    $rechtobe = 8000;
                                    break;
                                case 5:
                                    $todayblessings = 450;
                                    $totalblessings = $totalSoFar + 450;
                                    $rechtobe = 20000;
                                    break;
                                case 6:
                                    $todayblessings = 2400;
                                    $totalblessings = $totalSoFar + 2400;
                                    $rechtobe = 80000;
                                    break;
                                case 7:
                                    $todayblessings = 6400;
                                    $totalblessings = $totalSoFar + 6400;
                                    $rechtobe = 200000;
                                    break;
                                default:
                                    $res['code'] = 1;
                                    $res['msg'] = 'Invalid day: ' . $nextDay;
                                    $res['msgCode'] = 500;
                                    http_response_code(200);
                                    echo json_encode($res);
                                    exit;
                            }
                            
                            // Check recharge requirement
                            if($allrech >= $rechtobe){
                                // Start Transaction
                                mysqli_begin_transaction($conn);
                                
                                try {
                                    // Insert into cihne (using shonu_id)
                                    $sql = mysqli_query($conn, "INSERT INTO `cihne` (`identity`, `daysonearth`, `todayblessings`, `totalblessings`, `amen`) 
                                                                VALUES ('".$shonu_id."', '".$nextDay."', '".$todayblessings."', '".$totalblessings."', '".$crdt."')");
                                    
                                    if(!$sql){
                                        throw new Exception("Failed to insert into cihne: " . mysqli_error($conn));
                                    }
                                    
                                    // ===== UPDATE BOTH WALLETS =====
                                    
                                    // 1. Update users table (backup wallet)
                                    $update_users = mysqli_query($conn, "UPDATE `users` SET `wallet` = `wallet` + $todayblessings WHERE `uid` = '$actual_user_id'");
                                    
                                    if(!$update_users){
                                        throw new Exception("Failed to update users wallet: " . mysqli_error($conn));
                                    }
                                    
                                    // 2. Update shonu_kaichila table (main wallet used by app)
                                    $check_kaichila = mysqli_query($conn, "SELECT * FROM `shonu_kaichila` WHERE `balakedara` = '$shonu_id'");
                                    
                                    if(mysqli_num_rows($check_kaichila) > 0) {
                                        // Update existing
                                        $update_kaichila = mysqli_query($conn, "UPDATE `shonu_kaichila` SET `motta` = `motta` + $todayblessings WHERE `balakedara` = '$shonu_id'");
                                        if(!$update_kaichila){
                                            throw new Exception("Failed to update shonu_kaichila: " . mysqli_error($conn));
                                        }
                                    } else {
                                        // Insert new
                                        $insert_kaichila = mysqli_query($conn, "INSERT INTO `shonu_kaichila` (`balakedara`, `motta`) VALUES ('$shonu_id', $todayblessings)");
                                        if(!$insert_kaichila){
                                            throw new Exception("Failed to insert into shonu_kaichila: " . mysqli_error($conn));
                                        }
                                    }
                                    
                                    // Commit transaction
                                    mysqli_commit($conn);
                                    
                                    $res['code'] = 0;
                                    $res['msg'] = 'Succeed - Day ' . $nextDay . ' completed';
                                    $res['msgCode'] = 0;
                                    
                                } catch (Exception $e) {
                                    // Rollback on error
                                    mysqli_rollback($conn);
                                    $res['code'] = 1;
                                    $res['msg'] = 'Transaction failed: ' . $e->getMessage();
                                    $res['msgCode'] = 500;
                                }
                            }
                            else{
                                $res['code'] = 1;
                                $res['msg'] = 'The recharge amount is not up to the standard for day ' . $nextDay;
                                $res['msgCode'] = 502;
                            }
                        }
                        else{
                            $res['code'] = 1;
                            $res['msg'] = 'Received Today';
                            $res['msgCode'] = 501;
                        }
                    }
                    else{
                        $res['code'] = 1;
                        $res['msg'] = 'Sign-in cycle completed';
                        $res['msgCode'] = 502;
                    }
                    
                    $res['data'] = null;
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