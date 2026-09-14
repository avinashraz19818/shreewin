<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    if (isset($shonupost['date']) && isset($shonupost['language']) && isset($shonupost['pageNo']) && isset($shonupost['pageSize']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp']) && isset($shonupost['type'])) {
        $date = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['date']));
        $language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
        $pageNo = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['pageNo']));
        $pageSize = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['pageSize']));			
        $random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
        $signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
        $type = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['type']));
        
        if($date == ''){
            $shonustr = '{"language":'.$language.',"pageNo":'.$pageNo.',"pageSize":'.$pageSize.',"random":"'.$random.'","type":'.$type.'}';	
        }
        else{
            $shonustr = '{"date":"'.$date.'","language":'.$language.',"pageNo":'.$pageNo.',"pageSize":'.$pageSize.',"random":"'.$random.'","type":'.$type.'}';	
        }						
        $shonusign = strtoupper(md5($shonustr));
        
        if($shonusign == $signature){
            $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
            $author = $bearer[1];				
            $is_jwt_valid = is_jwt_valid($author);
            $data_auth = json_decode($is_jwt_valid, 1);
            
            if($data_auth['status'] === 'Success') {
                $sesquery = "SELECT akshinak FROM shonu_subjects WHERE akshinak = '$author'";
                $sesresult=$conn->query($sesquery);
                $sesnum = mysqli_num_rows($sesresult);
                
                if($sesnum == 1){
                    $samatolana = ($pageNo - 1) * $pageSize;
                    $shonuid = $data_auth['payload']['id'];
                    
                    // Initialize data array
                    $data = [];
                    
                    if($date == ''){
                        // ============================================
                        // WITHOUT DATE FILTER
                        // ============================================
                        if($type == -1){
                            // ALL TRANSACTIONS - Including agent commission
                            $samasye = "SELECT * FROM (
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT kramasankhye as parichaya, bonus as ketebida, 'sb' as phalaphala, bonus as sesabida, dinankavannuracisi as tiarikala 
                                FROM shonu_kaichila WHERE balakedara = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT macau as parichaya, salary as ketebida, 'ds' as phalaphala, salary as sesabida, createdate as tiarikala 
                                FROM dailysalary WHERE userid = $shonuid
                                UNION ALL
                                SELECT shonu as parichaya, motta as ketebida, 'rc' as phalaphala, motta as sesabida, dinankavannuracisi as tiarikala 
                                FROM thevani WHERE balakedara = $shonuid AND sthiti = 1
                                UNION ALL
                                SELECT id as parichaya, sturgis as ketebida, 'frc' as phalaphala, sturgis as sesabida, time as tiarikala 
                                FROM egrahcer_sonub WHERE dr = $shonuid AND status = 1
                                UNION ALL
                                SELECT id as parichaya, prize as ketebida, 'rb' as phalaphala, prize as sesabida, time as tiarikala 
                                FROM spinrec WHERE user_id = $shonuid
                                UNION ALL
                                SELECT dearlord as parichaya, todayblessings as ketebida, 'atb' as phalaphala, todayblessings as sesabida, amen as tiarikala 
                                FROM cihne WHERE identity = $shonuid
                                UNION ALL
                                SELECT shonu as parichaya, motta as ketebida, 'wd' as phalaphala, remarks as sesabida, dinankavannuracisi as tiarikala 
                                FROM hintegedukolli WHERE balakedara = $shonuid 
                                UNION ALL
                                SELECT id as parichaya, motta as ketebida, 'orb' as phalaphala, motta as sesabida, created_at as tiarikala 
                                FROM rebetrec WHERE user_id = $shonuid
                                UNION ALL
                                SELECT id as parichaya, motta as ketebida, 'lvlup' as phalaphala, type as sesabida, created_at as tiarikala 
                                FROM viprec WHERE user_id = $shonuid
                                UNION ALL
                                SELECT id as parichaya, rebateAmount_Last as ketebida, 'cmd' as phalaphala, rebateAmount_Last as sesabida, created_timestamp as tiarikala 
                                FROM commission WHERE user_id = $shonuid
                                UNION ALL
                                SELECT id as parichaya, motta as ketebida, 'reftask' as phalaphala, motta as sesabida, time as tiarikala 
                                FROM noitativni_sonub WHERE arthur = $shonuid AND status = 1 
                                UNION ALL
                                SELECT kani as parichaya, price as ketebida, 're' as phalaphala, remark as sesabida, shonu as tiarikala 
                                FROM hodike_balakedara WHERE userkani = $shonuid 
                                UNION ALL
                                SELECT id as parichaya, amount as ketebida, 'bonus' as phalaphala, CONCAT(bonus_type, '|', remark) as sesabida, transaction_date as tiarikala 
                                FROM all_bonus_transactions WHERE user_id = $shonuid
                                UNION ALL
                                -- 🔥 AGENT COMMISSION ADDED
                                SELECT id as parichaya, total_amount as ketebida, 'agent_salary' as phalaphala, 
                                       CONCAT('Agent Salary|', salary_type) as sesabida, created_at as tiarikala 
                                FROM agent_salary_log WHERE userid = $shonuid
                            ) AS all_transactions
                            ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
                            
                            $samasyephalitansa = $conn->query($samasye);
                            
                            // Count query with agent commission
                            $samasye_ondu = "SELECT COUNT(*) as total FROM (
                                SELECT parichaya FROM bajikattuttate WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT kramasankhye as parichaya FROM shonu_kaichila WHERE balakedara = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT macau as parichaya FROM dailysalary WHERE userid = $shonuid
                                UNION ALL
                                SELECT shonu as parichaya FROM thevani WHERE balakedara = $shonuid AND sthiti = 1
                                UNION ALL
                                SELECT id as parichaya FROM egrahcer_sonub WHERE dr = $shonuid AND status = 1
                                UNION ALL
                                SELECT id as parichaya FROM spinrec WHERE user_id = $shonuid
                                UNION ALL
                                SELECT dearlord as parichaya FROM cihne WHERE identity = $shonuid
                                UNION ALL
                                SELECT shonu as parichaya FROM hintegedukolli WHERE balakedara = $shonuid 
                                UNION ALL
                                SELECT id as parichaya FROM rebetrec WHERE user_id = $shonuid
                                UNION ALL
                                SELECT id as parichaya FROM viprec WHERE user_id = $shonuid
                                UNION ALL
                                SELECT id as parichaya FROM commission WHERE user_id = $shonuid
                                UNION ALL
                                SELECT id as parichaya FROM noitativni_sonub WHERE arthur = $shonuid AND status = 1 
                                UNION ALL
                                SELECT kani as parichaya FROM hodike_balakedara WHERE userkani = $shonuid 
                                UNION ALL
                                SELECT id as parichaya FROM all_bonus_transactions WHERE user_id = $shonuid
                                UNION ALL
                                SELECT id as parichaya FROM agent_salary_log WHERE userid = $shonuid
                            ) AS count_all";
                            
                            $count_result = $conn->query($samasye_ondu);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 0){
                            // Bet transactions
                            $samasye = "SELECT * FROM (
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid
                            ) AS bet_transactions
                            ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
                            
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $samasye_ondu = "SELECT COUNT(*) as total FROM (
                                SELECT parichaya FROM bajikattuttate WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid
                            ) AS bet_count";
                            
                            $count_result = $conn->query($samasye_ondu);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 1){
                            // Salary
                            $samasye = "SELECT macau, salary, createdate
                                      FROM dailysalary WHERE userid = $shonuid								  
                                      ORDER BY createdate DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM dailysalary WHERE userid = $shonuid";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 4){
                            // Deposit
                            $samasye = "SELECT shonu, motta, dinankavannuracisi
                                      FROM thevani WHERE balakedara = $shonuid AND sthiti = 1
                                      ORDER BY dinankavannuracisi DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM thevani WHERE balakedara = $shonuid AND sthiti = 1";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if ($type == 119) {
                            // Spin
                            $samasye = "SELECT id, prize, time
                                      FROM spinrec WHERE user_id = $shonuid
                                      ORDER BY time DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM spinrec WHERE user_id = $shonuid";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        } 
                        else if ($type == 12) {
                            // Signup bonus
                            $samasye = "SELECT kramasankhye, bonus, dinankavannuracisi
                                      FROM shonu_kaichila WHERE balakedara = $shonuid
                                      ORDER BY dinankavannuracisi DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM shonu_kaichila WHERE balakedara = $shonuid";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 5) {
                            // Withdraw
                            $samasye = "SELECT shonu, motta, dinankavannuracisi, remarks
                                        FROM hintegedukolli WHERE balakedara = $shonuid
                                        ORDER BY dinankavannuracisi DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM hintegedukolli WHERE balakedara = $shonuid";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 2){
                            // Jackpot
                            $samasye = "SELECT * FROM (
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                            ) AS jackpot_transactions
                            ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
                            
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $samasye_ondu = "SELECT COUNT(*) as total FROM (
                                SELECT parichaya FROM bajikattuttate WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
                            ) AS jackpot_count";
                            
                            $count_result = $conn->query($samasye_ondu);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 3){
                            // Red envelope
                            $samasye = "SELECT kani, price, shonu, remark
                                      FROM hodike_balakedara WHERE userkani = $shonuid
                                      ORDER BY shonu DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM hodike_balakedara WHERE userkani = $shonuid";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 14){
                            // First deposit bonus
                            $samasye = "SELECT id, sturgis, time
                                      FROM egrahcer_sonub WHERE dr = $shonuid
                                      ORDER BY time DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM egrahcer_sonub WHERE dr = $shonuid";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if(in_array($type, [8, 10, 13, 20, 25, 107, 115, 117, 118, 124])) {
                            // Bonus transactions
                            $samasye = "SELECT id as parichaya, amount as ketebida, 'bonus' as phalaphala, 
                                           CONCAT(bonus_type, '|', remark) as sesabida, transaction_date as tiarikala 
                                           FROM all_bonus_transactions 
                                           WHERE user_id = $shonuid AND bonus_type = $type
                                           ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM all_bonus_transactions WHERE user_id = $shonuid AND bonus_type = $type";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                    }
                    else{
                        // ============================================
                        // WITH DATE FILTER
                        // ============================================
                        if($type == -1){
                            $samasye = "SELECT * FROM (
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT kramasankhye as parichaya, bonus as ketebida, 'sb' as phalaphala, bonus as sesabida, dinankavannuracisi as tiarikala 
                                FROM shonu_kaichila WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT macau as parichaya, salary as ketebida, 'ds' as phalaphala, salary as sesabida, createdate as tiarikala 
                                FROM dailysalary WHERE userid = $shonuid AND date(createdate) = date('".$date."')
                                UNION ALL
                                SELECT shonu as parichaya, motta as ketebida, 'rc' as phalaphala, motta as sesabida, dinankavannuracisi as tiarikala 
                                FROM thevani WHERE balakedara = $shonuid AND sthiti = 1 AND date(dinankavannuracisi) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya, sturgis as ketebida, 'frc' as phalaphala, sturgis as sesabida, time as tiarikala 
                                FROM egrahcer_sonub WHERE dr = $shonuid AND status = 1 AND date(time) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya, prize as ketebida, 'rb' as phalaphala, prize as sesabida, time as tiarikala 
                                FROM spinrec WHERE user_id = $shonuid AND date(time) = date('".$date."')
                                UNION ALL
                                SELECT dearlord as parichaya, todayblessings as ketebida, 'atb' as phalaphala, todayblessings as sesabida, amen as tiarikala 
                                FROM cihne WHERE identity = $shonuid AND date(amen) = date('".$date."')
                                UNION ALL
                                SELECT shonu as parichaya, motta as ketebida, 'wd' as phalaphala, remarks as sesabida, dinankavannuracisi as tiarikala 
                                FROM hintegedukolli WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya, motta as ketebida, 'orb' as phalaphala, motta as sesabida, created_at as tiarikala 
                                FROM rebetrec WHERE user_id = $shonuid AND date(created_at) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya, motta as ketebida, 'lvlup' as phalaphala, type as sesabida, created_at as tiarikala 
                                FROM viprec WHERE user_id = $shonuid AND date(created_at) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya, rebateAmount_Last as ketebida, 'cmd' as phalaphala, rebateAmount_Last as sesabida, created_timestamp as tiarikala 
                                FROM commission WHERE user_id = $shonuid AND date(created_timestamp) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya, motta as ketebida, 'reftask' as phalaphala, motta as sesabida, time as tiarikala 
                                FROM noitativni_sonub WHERE arthur = $shonuid AND status = 1 AND date(time) = date('".$date."')
                                UNION ALL
                                SELECT kani as parichaya, price as ketebida, 're' as phalaphala, remark as sesabida, shonu as tiarikala 
                                FROM hodike_balakedara WHERE userkani = $shonuid AND date(shonu) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya, amount as ketebida, 'bonus' as phalaphala, CONCAT(bonus_type, '|', remark) as sesabida, transaction_date as tiarikala 
                                FROM all_bonus_transactions WHERE user_id = $shonuid AND date(transaction_date) = date('".$date."')
                                UNION ALL
                                -- 🔥 AGENT COMMISSION WITH DATE FILTER
                                SELECT id as parichaya, total_amount as ketebida, 'agent_salary' as phalaphala, 
                                       CONCAT('Agent Salary|', salary_type) as sesabida, created_at as tiarikala 
                                FROM agent_salary_log WHERE userid = $shonuid AND date(created_at) = date('".$date."')
                            ) AS all_transactions_date
                            ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
                            
                            $samasyephalitansa = $conn->query($samasye);
                            
                            // Count query with date filter
                            $samasye_ondu = "SELECT COUNT(*) as total FROM (
                                SELECT parichaya FROM bajikattuttate WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT kramasankhye as parichaya FROM shonu_kaichila WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT macau as parichaya FROM dailysalary WHERE userid = $shonuid AND date(createdate) = date('".$date."')
                                UNION ALL
                                SELECT shonu as parichaya FROM thevani WHERE balakedara = $shonuid AND sthiti = 1 AND date(dinankavannuracisi) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya FROM egrahcer_sonub WHERE dr = $shonuid AND status = 1 AND date(time) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya FROM spinrec WHERE user_id = $shonuid AND date(time) = date('".$date."')
                                UNION ALL
                                SELECT dearlord as parichaya FROM cihne WHERE identity = $shonuid AND date(amen) = date('".$date."')
                                UNION ALL
                                SELECT shonu as parichaya FROM hintegedukolli WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya FROM rebetrec WHERE user_id = $shonuid AND date(created_at) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya FROM viprec WHERE user_id = $shonuid AND date(created_at) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya FROM commission WHERE user_id = $shonuid AND date(created_timestamp) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya FROM noitativni_sonub WHERE arthur = $shonuid AND status = 1 AND date(time) = date('".$date."')
                                UNION ALL
                                SELECT kani as parichaya FROM hodike_balakedara WHERE userkani = $shonuid AND date(shonu) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya FROM all_bonus_transactions WHERE user_id = $shonuid AND date(transaction_date) = date('".$date."')
                                UNION ALL
                                SELECT id as parichaya FROM agent_salary_log WHERE userid = $shonuid AND date(created_at) = date('".$date."')
                            ) AS count_all";
                            
                            $count_result = $conn->query($samasye_ondu);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 0){
                            // Bet transactions with date
                            $samasye = "SELECT * FROM (
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                            ) AS bet_transactions_date
                            ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
                            
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $samasye_ondu = "SELECT COUNT(*) as total FROM (
                                SELECT parichaya FROM bajikattuttate WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
                            ) AS bet_count";
                            
                            $count_result = $conn->query($samasye_ondu);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 1){
                            $samasye = "SELECT macau, salary, createdate
                                      FROM dailysalary WHERE userid = $shonuid AND date(createdate) = date('".$date."')
                                      ORDER BY createdate DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM dailysalary WHERE userid = $shonuid AND date(createdate) = date('".$date."')";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 4){
                            $samasye = "SELECT shonu, motta, dinankavannuracisi
                                      FROM thevani WHERE balakedara = $shonuid AND sthiti = 1 AND date(dinankavannuracisi) = date('".$date."')
                                      ORDER BY dinankavannuracisi DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM thevani WHERE balakedara = $shonuid AND sthiti = 1 AND date(dinankavannuracisi) = date('".$date."')";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if ($type == 119) {
                            $samasye = "SELECT id, prize, time
                                      FROM spinrec WHERE user_id = $shonuid AND date(time) = date('" . $date . "')
                                      ORDER BY time DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM spinrec WHERE user_id = $shonuid AND date(time) = date('".$date."')";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        } 
                        else if ($type == 12) {
                            $samasye = "SELECT kramasankhye, bonus, dinankavannuracisi
                                      FROM shonu_kaichila WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('" . $date . "')
                                      ORDER BY dinankavannuracisi DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM shonu_kaichila WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('".$date."')";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 5) {
                            $samasye = "SELECT shonu, motta, dinankavannuracisi, remarks 
                                        FROM hintegedukolli 
                                        WHERE balakedara = $shonuid 
                                        AND date(dinankavannuracisi) = date('".$date."') 
                                        ORDER BY dinankavannuracisi DESC 
                                        LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM hintegedukolli WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('".$date."')";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 2){
                            $samasye = "SELECT * FROM (
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
                                FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                            ) AS jackpot_transactions_date
                            ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
                            
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $samasye_ondu = "SELECT COUNT(*) as total FROM (
                                SELECT parichaya FROM bajikattuttate WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                                UNION ALL
                                SELECT parichaya FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
                            ) AS jackpot_count";
                            
                            $count_result = $conn->query($samasye_ondu);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 3){
                            $samasye = "SELECT kani, price, shonu, remark
                                      FROM hodike_balakedara WHERE userkani = $shonuid AND date(shonu) = date('".$date."')
                                      ORDER BY shonu DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM hodike_balakedara WHERE userkani = $shonuid AND date(shonu) = date('".$date."')";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if($type == 14){
                            $samasye = "SELECT id, sturgis, time
                                      FROM egrahcer_sonub WHERE dr = $shonuid AND date(time) = date('".$date."')
                                      ORDER BY time DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM egrahcer_sonub WHERE dr = $shonuid AND date(time) = date('".$date."')";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                        else if(in_array($type, [8, 10, 13, 20, 25, 107, 115, 117, 118, 124])) {
                            $samasye = "SELECT id as parichaya, amount as ketebida, 'bonus' as phalaphala, 
                                           CONCAT(bonus_type, '|', remark) as sesabida, transaction_date as tiarikala 
                                           FROM all_bonus_transactions 
                                           WHERE user_id = $shonuid AND bonus_type = $type 
                                           AND date(transaction_date) = date('".$date."')
                                           ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
                            $samasyephalitansa = $conn->query($samasye);
                            
                            $count_sql = "SELECT COUNT(*) as total FROM all_bonus_transactions WHERE user_id = $shonuid AND bonus_type = $type AND date(transaction_date) = date('".$date."')";
                            $count_result = $conn->query($count_sql);
                            $count_row = $count_result->fetch_assoc();
                            $samasyephalitansa_sankhye = $count_row['total'];
                        }
                    }
                    
                    // Process results with proper error handling
                    if(isset($samasyephalitansa) && $samasyephalitansa && $samasyephalitansa->num_rows > 0) {
                        $i = 0;
                        while ($row = $samasyephalitansa->fetch_assoc()) {
                            // Handle different transaction types
                            if($type == 1){
                                $data['list'][$i]['amount'] = $row['salary'];
                                $data['list'][$i]['type'] = 1;
                                $data['list'][$i]['typeName'] = 'Salary';
                                $data['list'][$i]['typeNameCode'] = '8001';
                                $data['list'][$i]['orderNum'] = $row['macau'];
                                $data['list'][$i]['addTime'] = $row['createdate'];
                                $data['list'][$i]['remark'] = '';
                                $data['list'][$i]['timestamp'] = strtotime($row['createdate']);
                            }
                            else if($type == 4){
                                $data['list'][$i]['amount'] = $row['motta'];
                                $data['list'][$i]['type'] = 4;
                                $data['list'][$i]['typeName'] = 'Deposit';
                                $data['list'][$i]['typeNameCode'] = '8004';
                                $data['list'][$i]['orderNum'] = $row['shonu'];
                                $data['list'][$i]['addTime'] = $row['dinankavannuracisi'];
                                $data['list'][$i]['remark'] = '';
                                $data['list'][$i]['timestamp'] = strtotime($row['dinankavannuracisi']);
                            }
                            else if($type == 119){
                                $data['list'][$i]['amount'] = $row['prize'];
                                $data['list'][$i]['type'] = 119;
                                $data['list'][$i]['typeName'] = 'spin';
                                $data['list'][$i]['typeNameCode'] = '8119';
                                $data['list'][$i]['orderNum'] = $row['id'];
                                $data['list'][$i]['addTime'] = $row['time'];
                                $data['list'][$i]['remark'] = '';
                                $data['list'][$i]['timestamp'] = strtotime($row['time']);
                            }
                            else if($type == 12){
                                $data['list'][$i]['amount'] = $row['bonus'];
                                $data['list'][$i]['type'] = 12;
                                $data['list'][$i]['typeName'] = 'Signup Bonus';
                                $data['list'][$i]['typeNameCode'] = '8012';
                                $data['list'][$i]['orderNum'] = $row['kramasankhye'];
                                $data['list'][$i]['addTime'] = $row['dinankavannuracisi'];
                                $data['list'][$i]['remark'] = '';
                                $data['list'][$i]['timestamp'] = strtotime($row['dinankavannuracisi']);
                            }
                            else if($type == 5){
                                $data['list'][$i]['amount'] = $row['motta'];
                                $data['list'][$i]['type'] = 5;
                                $data['list'][$i]['typeName'] = 'Withdraw';
                                $data['list'][$i]['typeNameCode'] = '8005';
                                $data['list'][$i]['orderNum'] = $row['shonu'];
                                $data['list'][$i]['addTime'] = $row['dinankavannuracisi'];
                                $data['list'][$i]['remark'] = $row['remarks'] ?? '';
                                $data['list'][$i]['timestamp'] = strtotime($row['dinankavannuracisi']);
                            }
                            else if($type == 2){
                                $data['list'][$i]['amount'] = $row['sesabida'];
                                $data['list'][$i]['type'] = 2;
                                $data['list'][$i]['typeName'] = 'Jackpot increase';
                                $data['list'][$i]['typeNameCode'] = '8002';
                                $data['list'][$i]['orderNum'] = $row['parichaya'];
                                $data['list'][$i]['addTime'] = $row['tiarikala'];
                                $data['list'][$i]['remark'] = '';
                                $data['list'][$i]['timestamp'] = strtotime($row['tiarikala']);
                            }
                            else if($type == 3){
                                $data['list'][$i]['amount'] = $row['price'];
                                $data['list'][$i]['type'] = 3;
                                $data['list'][$i]['typeName'] = 'Red Envelope';
                                $data['list'][$i]['typeNameCode'] = '8003';
                                $data['list'][$i]['orderNum'] = $row['kani'];
                                $data['list'][$i]['addTime'] = $row['shonu'];
                                $data['list'][$i]['remark'] = $row['remark'] ?? '';
                                $data['list'][$i]['timestamp'] = strtotime($row['shonu']);
                            }
                            else if($type == 14){
                                // First deposit bonus with updated amounts
                                $bonusAmount = 0;
                                if($row['sturgis'] == 1) $bonusAmount = 38;      // 200 recharge
                                else if($row['sturgis'] == 2) $bonusAmount = 28;  // 100 recharge
                                else if($row['sturgis'] == 3) $bonusAmount = 68;  // 500 recharge
                                else if($row['sturgis'] == 4) $bonusAmount = 118; // 1000 recharge
                                else if($row['sturgis'] == 5) $bonusAmount = 228; // 3000 recharge
                                else if($row['sturgis'] == 6) $bonusAmount = 588; // 10000 recharge
                                else if($row['sturgis'] == 7) $bonusAmount = 2388; // 50000 recharge
                                
                                $data['list'][$i]['amount'] = $bonusAmount;
                                $data['list'][$i]['type'] = 14;
                                $data['list'][$i]['typeName'] = 'First deposit bonus';
                                $data['list'][$i]['typeNameCode'] = '8014';
                                $data['list'][$i]['orderNum'] = $row['id'];
                                $data['list'][$i]['addTime'] = $row['time'];
                                $data['list'][$i]['remark'] = '';
                                $data['list'][$i]['timestamp'] = strtotime($row['time']);
                            }
                            else if($row['phalaphala'] == 'bonus') {
                                $sesabida = $row['sesabida'];
                                $parts = explode('|', $sesabida, 2);
                                $bonusType = $parts[0];
                                $actualRemark = $parts[1] ?? '';
                                
                                $bonusTypeMapping = [
                                    3   => ['type' => 3, 'typeName' => 'Red envelope', 'typeNameCode' => '8003'],
                                    8   => ['type' => 8, 'typeName' => 'Agent red envelope recharge', 'typeNameCode' => '8008'],
                                    10  => ['type' => 10, 'typeName' => 'Recharge gift', 'typeNameCode' => '8010'],
                                    13  => ['type' => 13, 'typeName' => 'Bonus recharge', 'typeNameCode' => '8013'],
                                    14  => ['type' => 14, 'typeName' => 'First full gift', 'typeNameCode' => '8014'],
                                    20  => ['type' => 20, 'typeName' => 'Invite bonus', 'typeNameCode' => '8020'],
                                    25  => ['type' => 25, 'typeName' => 'Card binding gift', 'typeNameCode' => '8025'],
                                    107 => ['type' => 107, 'typeName' => 'Weekly Awards', 'typeNameCode' => '8107'],
                                    115 => ['type' => 115, 'typeName' => 'Return Awards', 'typeNameCode' => '8115'],
                                    117 => ['type' => 117, 'typeName' => 'New members get bonuses by playing games', 'typeNameCode' => '8117'],
                                    118 => ['type' => 118, 'typeName' => 'Daily Awards', 'typeNameCode' => '8118'],
                                    124 => ['type' => 124, 'typeName' => 'Agent Bonus', 'typeNameCode' => '8124']
                                ];
                                
                                if(isset($bonusTypeMapping[$bonusType])) {
                                    $data['list'][$i]['type'] = $bonusTypeMapping[$bonusType]['type'];
                                    $data['list'][$i]['typeName'] = $bonusTypeMapping[$bonusType]['typeName'];
                                    $data['list'][$i]['typeNameCode'] = $bonusTypeMapping[$bonusType]['typeNameCode'];
                                } else {
                                    $data['list'][$i]['type'] = $bonusType;
                                    $data['list'][$i]['typeName'] = 'Bonus';
                                    $data['list'][$i]['typeNameCode'] = '8000';
                                }
                                $data['list'][$i]['amount'] = $row['ketebida'];
                                $data['list'][$i]['orderNum'] = $row['parichaya'];
                                $data['list'][$i]['addTime'] = $row['tiarikala'];
                                $data['list'][$i]['remark'] = $actualRemark;
                                $data['list'][$i]['timestamp'] = strtotime($row['tiarikala']);
                            }
                            // 🔥 AGENT COMMISSION HANDLER
                            else if($row['phalaphala'] == 'agent_salary'){
                                $data['list'][$i]['amount'] = $row['ketebida'];
                                $data['list'][$i]['type'] = 50;
                                $data['list'][$i]['typeName'] = 'Agent Commission';
                                $data['list'][$i]['typeNameCode'] = '8050';
                                $data['list'][$i]['orderNum'] = $row['parichaya'];
                                $data['list'][$i]['addTime'] = $row['tiarikala'];
                                $parts = explode('|', $row['sesabida'], 2);
                                $data['list'][$i]['remark'] = $parts[1] ?? 'Daily Salary';
                                $data['list'][$i]['timestamp'] = strtotime($row['tiarikala']);
                            }
                            else{
                                // Default transaction types
                                if($row['phalaphala'] == 'gagner'){
                                    $data['list'][$i]['amount'] = $row['sesabida'];
                                    $data['list'][$i]['type'] = 2;
                                    $data['list'][$i]['typeName'] = 'Jackpot increase';
                                    $data['list'][$i]['typeNameCode'] = '8002';
                                }
                                else if($row['phalaphala'] == 'ds'){
                                    $data['list'][$i]['amount'] = $row['ketebida'];
                                    $data['list'][$i]['type'] = 1;
                                    $data['list'][$i]['typeName'] = 'Salary';
                                    $data['list'][$i]['typeNameCode'] = '8001';
                                }
                                else if($row['phalaphala'] == 'rc'){
                                    $data['list'][$i]['amount'] = $row['ketebida'];
                                    $data['list'][$i]['type'] = 4;
                                    $data['list'][$i]['typeName'] = 'Deposit';
                                    $data['list'][$i]['typeNameCode'] = '8004';
                                }
                                else if($row['phalaphala'] == 'rb'){
                                    $data['list'][$i]['amount'] = (int)$row['ketebida'];
                                    $data['list'][$i]['type'] = 119;
                                    $data['list'][$i]['typeName'] = 'spin';
                                    $data['list'][$i]['typeNameCode'] = '8119';
                                }
                                else if($row['phalaphala'] == 'sb'){
                                    $data['list'][$i]['amount'] = (int)$row['ketebida'];
                                    $data['list'][$i]['type'] = 12;
                                    $data['list'][$i]['typeName'] = 'Signup Bonus';
                                    $data['list'][$i]['typeNameCode'] = '8012';
                                }
                                else if($row['phalaphala'] == 'orb'){
                                    $data['list'][$i]['amount'] = $row['ketebida'];
                                    $data['list'][$i]['type'] = 102;
                                    $data['list'][$i]['typeName'] = 'Rebate';
                                    $data['list'][$i]['typeNameCode'] = '8102';
                                }
                                else if($row['phalaphala'] == 'reftask'){
                                    $data['list'][$i]['amount'] = $row['ketebida'];
                                    $data['list'][$i]['type'] = 20;
                                    $data['list'][$i]['typeName'] = 'Referral';
                                    $data['list'][$i]['typeNameCode'] = '8020';
                                }
                                else if($row['phalaphala'] == 'lvlup'){
                                    if ($row['sesabida'] == 1) {
                                        $data['list'][$i]['type'] = 29;
                                        $data['list'][$i]['typeNameCode'] = '8029';
                                    } else if ($row['sesabida'] == 2) {
                                        $data['list'][$i]['type'] = 30; 
                                        $data['list'][$i]['typeNameCode'] = '8030';
                                    } else {
                                        $data['list'][$i]['type'] = null;
                                    }
                                    $data['list'][$i]['amount'] = $row['ketebida']; 
                                    $data['list'][$i]['typeName'] = 'VIP'; 
                                }
                                else if($row['phalaphala'] == 'atb'){
                                    $data['list'][$i]['amount'] = $row['ketebida'];
                                    $data['list'][$i]['type'] = 7;
                                    $data['list'][$i]['typeName'] = 'Attendance';
                                    $data['list'][$i]['typeNameCode'] = '8007';
                                }
                                else if($row['phalaphala'] == 'cmd'){
                                    $data['list'][$i]['amount'] = $row['ketebida'];
                                    $data['list'][$i]['type'] = 1;
                                    $data['list'][$i]['typeName'] = 'Commission';
                                    $data['list'][$i]['typeNameCode'] = '8001';
                                }
                                else if($row['phalaphala'] == 'wd'){
                                    $data['list'][$i]['amount'] = $row['ketebida'];
                                    $data['list'][$i]['type'] = 5;
                                    $data['list'][$i]['typeName'] = 'Withdraw';
                                    $data['list'][$i]['typeNameCode'] = '8005';
                                    $data['list'][$i]['remark'] = $row['sesabida'];
                                }
                                else if($row['phalaphala'] == 're'){
                                    $data['list'][$i]['amount'] = $row['ketebida'];
                                    $data['list'][$i]['type'] = 3;
                                    $data['list'][$i]['typeName'] = 'Red Envelope';
                                    $data['list'][$i]['typeNameCode'] = '8003';
                                }
                                else if($row['phalaphala'] == 'frc'){
                                    // First deposit bonus from egrahcer_sonub
                                    $bonusAmount = 0;
                                    if($row['ketebida'] == 1) $bonusAmount = 38;      // 200 recharge
                                    else if($row['ketebida'] == 2) $bonusAmount = 28;  // 100 recharge
                                    else if($row['ketebida'] == 3) $bonusAmount = 68;  // 500 recharge
                                    else if($row['ketebida'] == 4) $bonusAmount = 118; // 1000 recharge
                                    else if($row['ketebida'] == 5) $bonusAmount = 228; // 3000 recharge
                                    else if($row['ketebida'] == 6) $bonusAmount = 588; // 10000 recharge
                                    else if($row['ketebida'] == 7) $bonusAmount = 2388; // 50000 recharge
                                    
                                    $data['list'][$i]['amount'] = $bonusAmount;
                                    $data['list'][$i]['type'] = 14;
                                    $data['list'][$i]['typeName'] = 'First Recharge';
                                    $data['list'][$i]['typeNameCode'] = '8014';
                                }
                                else{
                                    $data['list'][$i]['amount'] = $row['ketebida'];
                                    $data['list'][$i]['type'] = 0;
                                    $data['list'][$i]['typeName'] = 'Bet amount reduced';
                                    $data['list'][$i]['typeNameCode'] = '8000';
                                }
                                $data['list'][$i]['orderNum'] = $row['parichaya'] ?? '';
                                $data['list'][$i]['addTime'] = $row['tiarikala'] ?? '';
                                $data['list'][$i]['remark'] = $row['remark'] ?? '';
                                $data['list'][$i]['timestamp'] = isset($row['tiarikala']) ? strtotime($row['tiarikala']) : 0;
                            }
                            $i++;
                        }
                        
                        // Sort by timestamp (newest first) for mixed transaction types
                        if($type == -1 && isset($data['list'])) {
                            usort($data['list'], function($a, $b) {
                                return $b['timestamp'] - $a['timestamp'];
                            });
                        }
                        
                        $data['pageNo'] = (int)$pageNo;
                        $data['totalPage'] = $samasyephalitansa_sankhye > 0 ? ceil($samasyephalitansa_sankhye / $pageSize) : 0;
                        $data['totalCount'] = (int)$samasyephalitansa_sankhye;						
                    }
                    else{
                        $data['list'] = [];
                        $data['pageNo'] = (int)$pageNo;
                        $data['totalPage'] = 0;
                        $data['totalCount'] = 0;
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