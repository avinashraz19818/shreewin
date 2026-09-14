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
    
    function replaceWithAsterisks($inputString) {
        if (strlen($inputString) < 10) {
            return $inputString;
        }
        $before = substr($inputString, 0, 6);
        $toReplace = substr($inputString, 6, 4);
        $after = substr($inputString, 10);
        $replaced = str_repeat('*', strlen($toReplace));
        $resultString = $before . $replaced . $after;
        return $resultString;
    }
    
    if ($_SERVER['REQUEST_METHOD'] != 'GET') {
        if (isset($shonupost['language']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp']) && isset($shonupost['withdrawid'])) {
            $language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
            $random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
            $signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
            $withdrawid = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['withdrawid']));
            $shonustr = '{"language":'.$language.',"random":"'.$random.'","withdrawid":'.$withdrawid.'}';
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
                        $shonuid = (int)$data_auth['payload']['id'];
                        
                        // ===========================================
                        // ADMIN PANEL RULES FETCH
                        // ===========================================
                        
                        // Determine withdraw type based on withdrawid
                        if($withdrawid == 1 || $withdrawid == 2) {
                            $withdraw_type = 0; // Bank & UPI
                        } elseif($withdrawid == 3) {
                            $withdraw_type = 3; // TRC/USDT
                        } else {
                            $withdraw_type = 0; // Default
                        }
                        
                        // Fetch withdrawal rules from admin panel
                        $rules_query = $conn->query("
                            SELECT * FROM withdrawal_rules 
                            WHERE withdraw_type = '$withdraw_type' 
                            LIMIT 1
                        ");
                        
                        if($rules_query && $rules_query->num_rows > 0) {
                            $rules = $rules_query->fetch_assoc();
                            
                            // Use admin panel values
                            $daily_limit = isset($rules['daily_withdraw_limit']) ? (int)$rules['daily_withdraw_limit'] : 3;
                            $min_price = isset($rules['minPrice']) ? (float)$rules['minPrice'] : 110;
                            $max_price = isset($rules['maxPrice']) ? (float)$rules['maxPrice'] : 50000;
                            $fee = isset($rules['fee']) ? (float)$rules['fee'] : 0;
                            $start_time = isset($rules['startTime']) ? substr($rules['startTime'], 0, 5) : "00:00";
                            $end_time = isset($rules['endTime']) ? substr($rules['endTime'], 0, 5) : "23:59";
                            $bet_multiplier = isset($rules['bet_multiplier']) ? (float)$rules['bet_multiplier'] : 3.00;
                            $need_to_bet_enabled = isset($rules['need_to_bet_enabled']) ? (bool)$rules['need_to_bet_enabled'] : true;
                        } else {
                            // Default values if no rules found
                            $daily_limit = 3;
                            $min_price = ($withdrawid == 3) ? 930 : 110;
                            $max_price = 50000;
                            $fee = 0;
                            $start_time = "00:00";
                            $end_time = "23:59";
                            $bet_multiplier = 3.00;
                            $need_to_bet_enabled = true;
                        }
                        
                        // Initialize need to bet variables
                        $needToBetAmount = 0;
                        $withdrawableAmount = 0;
                        
                        if($need_to_bet_enabled) {
                            // 1. Get total deposits
                            $deposit_query = $conn->query("
                                SELECT COALESCE(SUM(motta), 0) as total_deposit 
                                FROM thevani 
                                WHERE balakedara = $shonuid 
                                AND sthiti = '1'
                            ");
                            $deposit_data = $deposit_query ? $deposit_query->fetch_assoc() : ['total_deposit' => 0];
                            $total_deposit = floatval($deposit_data['total_deposit']);
                            
                            // 2. Get total bonuses from extra funds
                            $bonus_query = $conn->query("
                                SELECT COALESCE(SUM(amount), 0) as total_bonus 
                                FROM user_extra_funds 
                                WHERE userid = $shonuid 
                                AND transaction_type = 'credit'
                            ");
                            $bonus_data = $bonus_query ? $bonus_query->fetch_assoc() : ['total_bonus' => 0];
                            $total_bonus = floatval($bonus_data['total_bonus']);
                            
                            // 3. Calculate base required bet
                            $base_required_bet = ($total_deposit + $total_bonus) * $bet_multiplier;
                            
                            // 4. Get user's adjustments
                            $adj_query = $conn->query("
                                SELECT 
                                    COALESCE(SUM(CASE WHEN adjust_type = 'increase' THEN adjust_amount ELSE 0 END), 0) as total_increase,
                                    COALESCE(SUM(CASE WHEN adjust_type = 'decrease' THEN adjust_amount ELSE 0 END), 0) as total_decrease
                                FROM user_bet_adjust 
                                WHERE user_id = $shonuid
                            ");
                            
                            $adj_data = $adj_query ? $adj_query->fetch_assoc() : ['total_increase' => 0, 'total_decrease' => 0];
                            $total_increase = floatval($adj_data['total_increase']);
                            $total_decrease = floatval($adj_data['total_decrease']);
                            
                            // 5. Calculate final required bet with adjustments
                            $final_required_bet = $base_required_bet + $total_increase - $total_decrease;
                            
                            // Ensure final_required_bet is not negative
                            if ($final_required_bet < 0) {
                                $final_required_bet = 0;
                            }
                            
                            // 6. Get user's total bet amount from all tables
                            $bet_tables = [
                                'bajikattuttate_trx',
                                'bajikattuttate_trx3', 
                                'bajikattuttate_trx5',
                                'bajikattuttate_trx10',
                                'bajikattuttate',
                                'bajikattuttate_drei',
                                'bajikattuttate_funf',
                                'bajikattuttate_zehn',
                                'bajikattuttate_kemuru',
                                'bajikattuttate_kemuru_drei',
                                'bajikattuttate_kemuru_funf',
                                'bajikattuttate_kemuru_zehn',
                                'bajikattuttate_aidudi',
                                'bajikattuttate_aidudi_drei',
                                'bajikattuttate_aidudi_funf',
                                'bajikattuttate_aidudi_zehn'
                            ];
                            
                            $total_bet = 0;
                            foreach($bet_tables as $table) {
                                // Check if table exists before querying
                                $check_table = $conn->query("SHOW TABLES LIKE '$table'");
                                if($check_table && $check_table->num_rows > 0) {
                                    $bet_query = $conn->query("SELECT COALESCE(SUM(ketebida), 0) as total FROM $table WHERE byabaharkarta = '$shonuid'");
                                    if($bet_query) {
                                        $bet_data = $bet_query->fetch_assoc();
                                        $total_bet += floatval($bet_data['total']);
                                    }
                                }
                            }
                            
                            // 7. Calculate NEED TO BET
                            $needToBetAmount = max(0, $final_required_bet - $total_bet);
                        }
                        
                        // 8. Get user's total balance
                        $balanceQuery = $conn->query("
                            SELECT COALESCE(motta, 0) as total_balance 
                            FROM shonu_kaichila 
                            WHERE balakedara = $shonuid
                        ");
                        $balanceData = $balanceQuery ? $balanceQuery->fetch_assoc() : ['total_balance' => 0];
                        $totalBalance = floatval($balanceData['total_balance']);
                        
                        // 9. Calculate withdrawable amount
                        if($need_to_bet_enabled && $needToBetAmount > 0) {
                            $withdrawableAmount = 0;
                        } else {
                            $withdrawableAmount = $totalBalance;
                        }
                        
                        // Check if withdrawable amount is within limits
                        if($withdrawableAmount < $min_price) {
                            $withdrawableAmount = 0;
                        } elseif($withdrawableAmount > $max_price) {
                            $withdrawableAmount = $max_price;
                        }
                        
                        // Initialize data array
                        $data = [];
                        $data['withdrawalslist'] = [];
                        
                        // WithdrawID specific handling
                        if($withdrawid == 1 || $withdrawid == 2 || $withdrawid == 3){
                            // Common withdrawal rules from admin panel
                            $data["withdrawalsrule"]["withdrawCount"] = $daily_limit; // Daily limit from admin
                            $data["withdrawalsrule"]["withdrawRemainingCount"] = $withdrawalsRemaining; // Remaining withdrawals
                            $data["withdrawalsrule"]["startTime"] = $start_time; // From admin
                            $data["withdrawalsrule"]["endTime"] = $end_time; // From admin
                            $data["withdrawalsrule"]["fee"] = (int)$fee; // From admin
                            $data["withdrawalsrule"]["maxPrice"] = (int)$max_price; // From admin
                            $data["withdrawalsrule"]["minPrice"] = (int)$min_price; // From admin
                            $data["withdrawalsrule"]["amount"] = $totalBalance; // Total balance
                            $data["withdrawalsrule"]["amountofCode"] = $needToBetAmount; // NEED TO BET amount
                            $data["withdrawalsrule"]["canWithdrawAmount"] = $withdrawableAmount; // Actual amount user can withdraw
                            $data["withdrawalsrule"]["c2cUnitAmount"] = 0;
                            $data["withdrawalsrule"]["uRate"] = 97;
                            $data["withdrawalsrule"]["uGold"] = 0;
                            
                            if($withdrawid == 1){
                                // BANK WITHDRAWAL
                                $samasye = "SELECT phalanubhavi FROM khate WHERE byabaharkarta = $shonuid AND khatehesaru != 'TRC' ORDER BY shonu DESC LIMIT 1";
                                $samasyephalitansa = $conn->query($samasye);
                                $samasyephalitansa_dhadi = $samasyephalitansa ? mysqli_num_rows($samasyephalitansa) : 0;
                                
                                if($samasyephalitansa_dhadi >= 1){
                                    $samasyephalitansa_sreni = mysqli_fetch_array($samasyephalitansa);                        
                                    $data['lastBandCarkName'] = $samasyephalitansa_sreni['phalanubhavi'];
                                    
                                    $samasye = "SELECT shonu, khatehesaru, khatesankhye, kod, duravani FROM khate WHERE byabaharkarta = $shonuid AND khatehesaru != 'TRC' ORDER BY shonu DESC";
                                    $samasyephalitansa = $conn->query($samasye);
                                    $i = 0;
                                    $data['withdrawalslist'] = [];
                                    if($samasyephalitansa) {
                                        while($row = mysqli_fetch_array($samasyephalitansa)){
                                            $data['withdrawalslist'][$i]['bid'] = $row['shonu'];
                                            $data['withdrawalslist'][$i]['bankName'] = $row['khatehesaru'];
                                            $data['withdrawalslist'][$i]['beneficiaryName'] = '';
                                            $data['withdrawalslist'][$i]['accountNo'] = replaceWithAsterisks($row['khatesankhye']);
                                            $data['withdrawalslist'][$i]['ifsCode'] = $row['kod'];
                                            $data['withdrawalslist'][$i]['withType'] = 1;
                                            $data['withdrawalslist'][$i]['mobileNo'] = replaceWithAsterisks($row['duravani']);
                                            $data['withdrawalslist'][$i]['bankProvince'] = '';
                                            $data['withdrawalslist'][$i]['bankCity'] = '';
                                            $data['withdrawalslist'][$i]['bankAddress'] = '';
                                            $i++;
                                        }
                                    }
                                } else {
                                    $data['lastBandCarkName'] = null;
                                    $data['withdrawalslist'] = [];
                                }
                            }
                            else if($withdrawid == 2){
                                // UPI WITHDRAWAL
                                // First check if upi_withdrawal table exists
                                $checkTable = $conn->query("SHOW TABLES LIKE 'upi_withdrawal'");
                                if($checkTable && $checkTable->num_rows > 0) {
                                    // Get UPI details
                                    $samasye = "SELECT * FROM upi_withdrawal WHERE user_id = $shonuid ORDER BY id DESC LIMIT 1";
                                    $samasyephalitansa = $conn->query($samasye);
                                    $samasyephalitansa_dhadi = $samasyephalitansa ? mysqli_num_rows($samasyephalitansa) : 0;
                                    
                                    if($samasyephalitansa_dhadi >= 1){
                                        $samasyephalitansa_sreni = mysqli_fetch_assoc($samasyephalitansa);                        
                                        $data['lastBandCarkName'] = $samasyephalitansa_sreni['name'] ?? null;
                                        
                                        // Get all UPI records
                                        $samasye = "SELECT * FROM upi_withdrawal WHERE user_id = $shonuid ORDER BY id DESC";
                                        $samasyephalitansa = $conn->query($samasye);
                                        $i = 0;
                                        $data['withdrawalslist'] = [];
                                        if($samasyephalitansa) {
                                            while($row = mysqli_fetch_assoc($samasyephalitansa)){
                                                // Check if this is UPI response structure
                                                $data['withdrawalslist'][$i]['bid'] = $row['id'];
                                                $data['withdrawalslist'][$i]['bankName'] = $row['name'] ?? '';
                                                $data['withdrawalslist'][$i]['beneficiaryName'] = '';
                                                $data['withdrawalslist'][$i]['accountNo'] = $row['upi_id'] ?? '';
                                                $data['withdrawalslist'][$i]['ifsCode'] = '';
                                                $data['withdrawalslist'][$i]['withType'] = 2; // UPI type
                                                $data['withdrawalslist'][$i]['mobileNo'] = $row['mobile'] ?? '';
                                                $data['withdrawalslist'][$i]['bankProvince'] = '';
                                                $data['withdrawalslist'][$i]['bankCity'] = '';
                                                $data['withdrawalslist'][$i]['bankAddress'] = '';
                                                $data['withdrawalslist'][$i]['upiName'] = $row['name'] ?? ''; // Extra field for UPI
                                                $data['withdrawalslist'][$i]['upiAccount'] = $row['upi_id'] ?? ''; // Extra field for UPI
                                                $data['withdrawalslist'][$i]['bankCode'] = '';
                                                $data['withdrawalslist'][$i]['isKycOnline'] = false;
                                                $i++;
                                            }
                                        }
                                    } else {
                                        $data['lastBandCarkName'] = null;
                                        $data['withdrawalslist'] = [];
                                    }
                                } else {
                                    // If UPI table doesn't exist, check in khate table for UPI records
                                    $samasye = "SELECT phalanubhavi FROM khate WHERE byabaharkarta = $shonuid AND (khatehesaru LIKE '%UPI%' OR khatehesaru LIKE '%UPI') ORDER BY shonu DESC LIMIT 1";
                                    $samasyephalitansa = $conn->query($samasye);
                                    $samasyephalitansa_dhadi = $samasyephalitansa ? mysqli_num_rows($samasyephalitansa) : 0;
                                    
                                    if($samasyephalitansa_dhadi >= 1){
                                        $samasyephalitansa_sreni = mysqli_fetch_assoc($samasyephalitansa);                        
                                        $data['lastBandCarkName'] = $samasyephalitansa_sreni['phalanubhavi'];
                                        
                                        $samasye = "SELECT shonu, khatehesaru, khatesankhye, kod, duravani FROM khate WHERE byabaharkarta = $shonuid AND (khatehesaru LIKE '%UPI%' OR khatehesaru LIKE '%UPI') ORDER BY shonu DESC";
                                        $samasyephalitansa = $conn->query($samasye);
                                        $i = 0;
                                        $data['withdrawalslist'] = [];
                                        if($samasyephalitansa) {
                                            while($row = mysqli_fetch_assoc($samasyephalitansa)){
                                                $data['withdrawalslist'][$i]['bid'] = $row['shonu'];
                                                $data['withdrawalslist'][$i]['bankName'] = $row['khatehesaru'];
                                                $data['withdrawalslist'][$i]['beneficiaryName'] = '';
                                                $data['withdrawalslist'][$i]['accountNo'] = $row['khatesankhye'];
                                                $data['withdrawalslist'][$i]['ifsCode'] = $row['kod'];
                                                $data['withdrawalslist'][$i]['withType'] = 2; // UPI type
                                                $data['withdrawalslist'][$i]['mobileNo'] = $row['duravani'];
                                                $data['withdrawalslist'][$i]['bankProvince'] = '';
                                                $data['withdrawalslist'][$i]['bankCity'] = '';
                                                $data['withdrawalslist'][$i]['bankAddress'] = '';
                                                $data['withdrawalslist'][$i]['upiName'] = ''; // Will be extracted from account number if needed
                                                $data['withdrawalslist'][$i]['upiAccount'] = $row['khatesankhye'];
                                                $data['withdrawalslist'][$i]['bankCode'] = '';
                                                $data['withdrawalslist'][$i]['isKycOnline'] = false;
                                                $i++;
                                            }
                                        }
                                    } else {
                                        $data['lastBandCarkName'] = null;
                                        $data['withdrawalslist'] = [];
                                    }
                                }
                            }
                            else if($withdrawid == 3){
                                // TRC WITHDRAWAL
                                $samasye = "SELECT phalanubhavi FROM khate WHERE byabaharkarta = $shonuid AND khatehesaru = 'TRC' ORDER BY shonu DESC LIMIT 1";
                                $samasyephalitansa = $conn->query($samasye);
                                $samasyephalitansa_dhadi = $samasyephalitansa ? mysqli_num_rows($samasyephalitansa) : 0;
                                
                                if($samasyephalitansa_dhadi >= 1){
                                    $samasyephalitansa_sreni = mysqli_fetch_array($samasyephalitansa);                        
                                    $data['lastBandCarkName'] = $samasyephalitansa_sreni['phalanubhavi'];
                                    
                                    $samasye = "SELECT shonu, khatehesaru, khatesankhye, kod, duravani FROM khate WHERE byabaharkarta = $shonuid AND khatehesaru = 'TRC' ORDER BY shonu DESC";
                                    $samasyephalitansa = $conn->query($samasye);
                                    $i = 0;
                                    $data['withdrawalslist'] = [];
                                    if($samasyephalitansa) {
                                        while($row = mysqli_fetch_array($samasyephalitansa)){
                                            $data['withdrawalslist'][$i]['bid'] = $row['shonu'];
                                            $data['withdrawalslist'][$i]['bankName'] = $row['khatehesaru'];
                                            $data['withdrawalslist'][$i]['beneficiaryName'] = '';
                                            $data['withdrawalslist'][$i]['accountNo'] = replaceWithAsterisks($row['khatesankhye']);
                                            $data['withdrawalslist'][$i]['ifsCode'] = $row['kod'];
                                            $data['withdrawalslist'][$i]['withType'] = 1;
                                            $data['withdrawalslist'][$i]['mobileNo'] = replaceWithAsterisks($row['duravani']);
                                            $data['withdrawalslist'][$i]['bankProvince'] = '';
                                            $data['withdrawalslist'][$i]['bankCity'] = '';
                                            $data['withdrawalslist'][$i]['bankAddress'] = '';
                                            $i++;
                                        }
                                    }
                                } else {
                                    $data['lastBandCarkName'] = null;
                                    $data['withdrawalslist'] = [];
                                }
                            }

                            $res['data'] = $data;
                            $res['code'] = 0;
                            $res['msg'] = 'Succeed';
                            $res['msgCode'] = 0;
                            http_response_code(200);
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