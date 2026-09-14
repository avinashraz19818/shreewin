<?php 
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, ar-origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Credentials: true');
date_default_timezone_set('Asia/Kolkata');

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
        
        // ============================================
        // TRY ALL POSSIBLE SIGNATURE FORMATS
        // ============================================
        
        $formats = [
            // Format 1: Language with quotes (standard JSON) - AAP WALE MEIN YEH USE HO RAHA
            '{"language":"' . $language . '","random":"' . $random . '"}',
            
            // Format 2: Language without quotes (if numeric)
            '{"language":' . $language . ',"random":"' . $random . '"}',
            
            // Format 3: With spaces after colon
            '{"language": "' . $language . '", "random": "' . $random . '"}',
            
            // Format 4: Language without quotes and no spaces
            '{"language":' . $language . ',"random":"' . $random . '"}',
            
            // Format 5: Random as number (without quotes)
            '{"language":"' . $language . '","random":' . $random . '}',
            
            // Format 6: Both as numbers
            '{"language":' . $language . ',"random":' . $random . '}',
            
            // Format 7: With single quotes (unlikely but check)
            "{'language':'" . $language . "','random':'" . $random . "'}",
            
            // Format 8: URL encoded format
            'language=' . $language . '&random=' . $random
        ];
        
        $signature_valid = false;
        $matched_format = '';
        $matched_calculated = '';
        
        foreach($formats as $index => $format) {
            $calculated = strtoupper(md5($format));
            if($calculated == $signature) {
                $signature_valid = true;
                $matched_format = $format;
                $matched_calculated = $calculated;
                break;
            }
        }
        
        if($signature_valid){
            
            // Authorization check
            if(!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $res['code'] = 4;
                $res['msg'] = 'Authorization header missing';
                $res['msgCode'] = 2;
                http_response_code(401);
                echo json_encode($res);
                exit;
            }
            
            $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
            $author = isset($bearer[1]) ? $bearer[1] : '';
            
            if(empty($author)) {
                $res['code'] = 4;
                $res['msg'] = 'Invalid authorization token';
                $res['msgCode'] = 2;
                http_response_code(401);
                echo json_encode($res);
                exit;
            }
            
            $is_jwt_valid = is_jwt_valid($author);
            $data_auth = json_decode($is_jwt_valid, true);
            
            if(isset($data_auth['status']) && $data_auth['status'] === 'Success') {
                
                // Check user permission
                $sesquery = "SELECT akshinak FROM shonu_subjects WHERE akshinak = '$author'";
                $sesresult = $conn->query($sesquery);
                $sesnum = mysqli_num_rows($sesresult);
                
                if($sesnum == 1){
                    $shonuid = $data_auth['payload']['id'];
                    
                    // ============================================
                    // 📊 TOTAL WEEKLY BET CALCULATION
                    // ============================================
                    
                    $betTables = [
                        'bajikattuttate',
                        'bajikattuttate_drei',
                        'bajikattuttate_funf',
                        'bajikattuttate_zehn',
                        'wagers',
                        'bet_transactions',
                        'user_bets',
                        'crashbetrecord'
                    ];
                    
                    $totalWeeklyBet = 0;
                    $foundTables = [];
                    
                    foreach($betTables as $table) {
                        $checkTable = "SHOW TABLES LIKE '$table'";
                        $tableResult = $conn->query($checkTable);
                        
                        if ($tableResult && $tableResult->num_rows > 0) {
                            
                            $columnsResult = $conn->query("SHOW COLUMNS FROM $table");
                            $columns = [];
                            while($col = $columnsResult->fetch_assoc()) {
                                $columns[] = $col['Field'];
                            }
                            
                            $userIdCol = 'byabaharkarta';
                            $amountCol = 'menge';
                            $dateCol = 'tiarikala';
                            
                            if(in_array($userIdCol, $columns) && in_array($amountCol, $columns) && in_array($dateCol, $columns)) {
                                
                                $query = "SELECT SUM($amountCol) as total 
                                         FROM $table 
                                         WHERE $userIdCol = '$shonuid' 
                                         AND YEARWEEK($dateCol, 1) = YEARWEEK(CURDATE(), 1)";
                                
                                $result = $conn->query($query);
                                
                                if ($result) {
                                    $row = $result->fetch_assoc();
                                    if ($row['total'] > 0) {
                                        $totalWeeklyBet += floatval($row['total']);
                                        $foundTables[] = $table . ": " . $row['total'];
                                    }
                                }
                            }
                        }
                    }

                    // ============================================
                    // 🎯 TASKS LIST
                    // ============================================
                    $tasks = [
                        [
                            "userId" => (int)$shonuid,
                            "mainConfigId" => 1,
                            "configId" => 251,
                            "schedule" => $totalWeeklyBet,
                            "status" => 1,
                            "taskTitle" => "Weekly Betting rewards",
                            "taskDescribe" => "To receive your weekly reward, place a bet in the game with the specified amount and claim your bonus rewards with LEHWIN.",
                            "taskId" => "B5",
                            "taskTarget" => 1000.00,
                            "taskAwardAmount" => 9.00,
                            "createDate" => "2025-11-24 00:00:01",
                            "targetTwo" => 0.00,
                            "targetItem" => 0,
                            "targetSubItem" => null,
                            "rechargeCategories" => null,
                            "scheduleTwo" => 0.0,
                            "lId" => 57
                        ],
                        [
                            "userId" => (int)$shonuid,
                            "mainConfigId" => 2,
                            "configId" => 252,
                            "schedule" => $totalWeeklyBet,
                            "status" => 1,
                            "taskTitle" => "Weekly Betting rewards",
                            "taskDescribe" => "To receive your weekly reward, place a bet in the game with the specified amount and claim your bonus rewards with LEHWIN.",
                            "taskId" => "B5",
                            "taskTarget" => 5000.00,
                            "taskAwardAmount" => 19.00,
                            "createDate" => "2025-11-24 00:00:01",
                            "targetTwo" => 0.00,
                            "targetItem" => 0,
                            "targetSubItem" => null,
                            "rechargeCategories" => null,
                            "scheduleTwo" => 0.0,
                            "lId" => 61
                        ],
                        [
                            "userId" => (int)$shonuid,
                            "mainConfigId" => 3,
                            "configId" => 253,
                            "schedule" => $totalWeeklyBet,
                            "status" => 1,
                            "taskTitle" => "Weekly Betting rewards",
                            "taskDescribe" => "To receive your weekly reward, place a bet in the game with the specified amount and claim your bonus rewards with LEHWIN.",
                            "taskId" => "B5",
                            "taskTarget" => 10000.00,
                            "taskAwardAmount" => 29.00,
                            "createDate" => "2025-11-24 00:00:01",
                            "targetTwo" => 0.00,
                            "targetItem" => 0,
                            "targetSubItem" => null,
                            "rechargeCategories" => null,
                            "scheduleTwo" => 0.0,
                            "lId" => 65
                        ],
                        [
                            "userId" => (int)$shonuid,
                            "mainConfigId" => 4,
                            "configId" => 254,
                            "schedule" => $totalWeeklyBet,
                            "status" => 1,
                            "taskTitle" => "Weekly Betting rewards",
                            "taskDescribe" => "To receive your weekly reward, place a bet in the game with the specified amount and claim your bonus rewards with LEHWIN.",
                            "taskId" => "B5",
                            "taskTarget" => 50000.00,
                            "taskAwardAmount" => 99.00,
                            "createDate" => "2025-11-24 00:00:01",
                            "targetTwo" => 0.00,
                            "targetItem" => 0,
                            "targetSubItem" => null,
                            "rechargeCategories" => null,
                            "scheduleTwo" => 0.0,
                            "lId" => 69
                        ],
                        [
                            "userId" => (int)$shonuid,
                            "mainConfigId" => 5,
                            "configId" => 255,
                            "schedule" => $totalWeeklyBet,
                            "status" => 1,
                            "taskTitle" => "Weekly Betting rewards",
                            "taskDescribe" => "To receive your weekly reward, place a bet in the game with the specified amount and claim your bonus rewards with LEHWIN.",
                            "taskId" => "B5",
                            "taskTarget" => 100000.00,
                            "taskAwardAmount" => 199.00,
                            "createDate" => "2025-11-24 00:00:01",
                            "targetTwo" => 0.00,
                            "targetItem" => 0,
                            "targetSubItem" => null,
                            "rechargeCategories" => null,
                            "scheduleTwo" => 0.0,
                            "lId" => 49
                        ],
                        [
                            "userId" => (int)$shonuid,
                            "mainConfigId" => 6,
                            "configId" => 256,
                            "schedule" => $totalWeeklyBet,
                            "status" => 1,
                            "taskTitle" => "Weekly Betting rewards",
                            "taskDescribe" => "To receive your weekly reward, place a bet in the game with the specified amount and claim your bonus rewards with LEHWIN.",
                            "taskId" => "B5",
                            "taskTarget" => 500000.00,
                            "taskAwardAmount" => 299.00,
                            "createDate" => "2025-11-24 00:00:01",
                            "targetTwo" => 0.00,
                            "targetItem" => 0,
                            "targetSubItem" => null,
                            "rechargeCategories" => null,
                            "scheduleTwo" => 0.0,
                            "lId" => 53
                        ]
                    ];
                    
                    // Check claim status
                    $checkTable = "SHOW TABLES LIKE 'weekly_award_claims'";
                    $tableResult = $conn->query($checkTable);
                    $claimsTableExists = ($tableResult && $tableResult->num_rows > 0);
                    
                    $data = [];
                    foreach ($tasks as $task) {
                        
                        $isClaimed = false;
                        
                        if ($claimsTableExists) {
                            $checkClaim = "SELECT * FROM weekly_award_claims 
                                         WHERE user_id = '$shonuid' 
                                         AND award_amount = '{$task['taskAwardAmount']}' 
                                         AND YEARWEEK(claimed_at, 1) = YEARWEEK(CURDATE(), 1)";
                            
                            $claimResult = $conn->query($checkClaim);
                            
                            if ($claimResult && $claimResult->num_rows > 0) {
                                $isClaimed = true;
                            }
                        }
                        
                        if ($isClaimed) {
                            $task['status'] = 2;
                        } elseif ($totalWeeklyBet >= $task['taskTarget']) {
                            $task['status'] = 3;
                        } else {
                            $task['status'] = 1;
                        }
                        
                        $data[] = $task;
                    }
                    
                    $res['data'] = $data;
                    $res['code'] = 0;
                    $res['msg'] = 'Succeed';
                    $res['msgCode'] = 0;
                    $res['debug'] = [
                        'total_weekly_bet' => $totalWeeklyBet,
                        'tables_found' => $foundTables,
                        'user_id' => $shonuid,
                        'signature_matched' => true,
                        'matched_format' => $matched_format
                    ];
                    http_response_code(200);
                    echo json_encode($res, JSON_PRETTY_PRINT);			
                    
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
            // Koi bhi format match nahi hua
            $res['code'] = 5;
            $res['msg'] = 'Wrong signature';
            $res['msgCode'] = 3;
            
            // Debug info - saare formats ke calculated signatures dikhao
            $debug_formats = [];
            foreach($formats as $index => $format) {
                $debug_formats['format_' . ($index+1)] = [
                    'string' => $format,
                    'md5' => strtoupper(md5($format))
                ];
            }
            
            $res['debug'] = [
                'received' => $signature,
                'your_calculated' => strtoupper(md5('{"language":"' . $language . '","random":"' . $random . '"}')),
                'all_formats' => $debug_formats,
                'language' => $language,
                'random' => $random
            ];
            
            http_response_code(200);
            echo json_encode($res, JSON_PRETTY_PRINT);
        }
    } else {
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