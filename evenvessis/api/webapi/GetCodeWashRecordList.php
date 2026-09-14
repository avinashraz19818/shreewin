<?php 
include "../../functions2.php";
include "../../conn.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');

date_default_timezone_set("Asia/Kolkata");
$shnunc = date("Y-m-d H:i:s");

try {
    $shonubody = file_get_contents("php://input");
    $shonupost = json_decode($shonubody, true);

    if ($_SERVER['REQUEST_METHOD'] != 'GET') {
        
        if (isset($shonupost['language'], $shonupost['random'], $shonupost['timestamp'])) {
            
            // Check Authorization header
            if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $res = [
                    'code' => 4,
                    'msg' => 'Authorization header missing',
                    'msgCode' => 2,
                    'serviceNowTime' => $shnunc
                ];
                http_response_code(401);
                echo json_encode($res);
                exit;
            }
            
            $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
            $author = $bearer[1] ?? '';
            
            if (empty($author)) {
                $res = [
                    'code' => 4,
                    'msg' => 'Token missing',
                    'msgCode' => 2,
                    'serviceNowTime' => $shnunc
                ];
                http_response_code(401);
                echo json_encode($res);
                exit;
            }
            
            $is_jwt_valid = is_jwt_valid($author);
            $data_auth = json_decode($is_jwt_valid, true);

            if ($data_auth && isset($data_auth['status']) && $data_auth['status'] === 'Success') {
                
                $userId = $data_auth['payload']['id'];
                
                // Get parameters
                $language = $shonupost['language'];
                $random = $shonupost['random'];
                $timestamp = $shonupost['timestamp'];
                $codeType = isset($shonupost['codeType']) ? $shonupost['codeType'] : -1;
                $pageNo = isset($shonupost['pageNo']) ? max(1, (int)$shonupost['pageNo']) : 1;
                $pageSize = 10;
                $offset = ($pageNo - 1) * $pageSize;
                $state = isset($shonupost['state']) ? (int)$shonupost['state'] : -1;

                // Get user's VIP level
                $lvlquery = "SELECT lvl FROM vip WHERE userid = '$userId'";
                $lvlresult = $conn->query($lvlquery);
                
                $lvl = 0;
                if ($lvlresult && $lvlresult->num_rows > 0) {
                    $lvlData = $lvlresult->fetch_assoc();
                    $lvl = (int)$lvlData['lvl'];
                }

                // Get rebet amount from balance
                $reamountq = "SELECT rebet FROM shonu_kaichila WHERE balakedara = '$userId'";
                $reamount = $conn->query($reamountq);
                $u = 0;
                if ($reamount && $reamount->num_rows > 0) {
                    $rc = $reamount->fetch_assoc();
                    $u = (float)($rc['rebet'] ?? 0);
                }

                // Determine wash rate based on VIP level
                $washRate = 0.05;
                if (in_array($lvl, [0, 1, 2])) {
                    $washRate = 0.05;
                } elseif (in_array($lvl, [3, 4, 5])) {
                    $washRate = 0.1;
                } elseif (in_array($lvl, [6, 7, 8])) {
                    $washRate = 0.15;
                } elseif ($lvl == 9) {
                    $washRate = 0.2;
                } elseif ($lvl == 10) {
                    $washRate = 0.3;
                }

                // CodeType mapping
                $ctRaw = $codeType;
                if (!is_numeric($ctRaw)) {
                    $ctKey = strtolower(trim((string)$ctRaw));
                    $ctMap = [
                        'casino' => 1, 'rummy' => 2, 'slots' => 3, 'slot' => 3,
                        'cq9' => 2, 'microgaming' => 4, 'mg' => 4, 'jdb' => 6,
                        'jili' => 18, 'evo' => 17, 'ag_playace' => 12, 'ag' => 12,
                        'km' => 9, 'eg' => 57, 'idg' => 101, 'v8' => 21,
                        'v8card' => 21, 'pg' => 22, 'inout' => 40
                    ];
                    $codeType = isset($ctMap[$ctKey]) ? (int)$ctMap[$ctKey] : -1;
                }

                if ($state == -1) {
                    
                    // Today midnight
                    $todayMidnight = date("Y-m-d") . " 00:00:00";
                    
                    // GET TODAY'S REBET RECORDS
                    $samasye = "SELECT rebet, rate, motta, lvl, created_at 
                               FROM rebetrec 
                               WHERE user_id = $userId 
                               AND created_at >= '$todayMidnight'
                               ORDER BY id DESC 
                               LIMIT $pageSize OFFSET $offset";
                    
                    $samasyephalitansa = $conn->query($samasye);
                    
                    $washList = [];
                    $dayRebate = 0.0;
                    $totalCount = 0;

                    if ($samasyephalitansa && $samasyephalitansa->num_rows > 0) {
                        while ($row = $samasyephalitansa->fetch_assoc()) {
                            $washVolume = (float)($row['rebet'] ?? 0);
                            $rebateAmount = (float)($row['motta'] ?? 0);
                            $rate = (float)($row['rate'] ?? 0);
                            
                            $washList[] = [
                                'washVolume' => $washVolume,
                                'washRate' => (float)number_format($rate, 3, '.', ''),
                                'rebateAmount' => (float)number_format($rebateAmount, 2, '.', ''),
                                'vipLevel' => (int)($row['lvl'] ?? 0),
                                'date' => $row['created_at'] ?? ''
                            ];
                            $dayRebate += $rebateAmount;
                        }

                        // Get total count
                        $countQuery = "SELECT COUNT(*) as total FROM rebetrec WHERE user_id = $userId AND created_at >= '$todayMidnight'";
                        $countResult = $conn->query($countQuery);
                        if ($countResult && $countResult->num_rows > 0) {
                            $countRow = $countResult->fetch_assoc();
                            $totalCount = (int)($countRow['total'] ?? 0);
                        }
                        
                        $totalPage = $pageSize > 0 ? ceil($totalCount / $pageSize) : 1;
                    } else {
                        $washList = [];
                        $totalCount = 0;
                        $totalPage = 0;
                    }

                    // GET TOTAL REBATE (ALL TIME)
                    $totalRebateQuery = "SELECT COALESCE(SUM(motta), 0) as totalRebate FROM rebetrec WHERE user_id = $userId";
                    $totalRebateResult = $conn->query($totalRebateQuery);
                    $totalRebate = 0.0;
                    if ($totalRebateResult && $totalRebateResult->num_rows > 0) {
                        $totalRebateRow = $totalRebateResult->fetch_assoc();
                        $totalRebate = (float)($totalRebateRow['totalRebate'] ?? 0.0);
                    }

                    // RESPONSE
                    $res = [
                        'data' => [
                            'codeWashAmount' => (float)number_format($u, 2, '.', ''),
                            'dayRebate' => (float)number_format($dayRebate, 2, '.', ''),
                            'totalRebate' => (float)number_format($totalRebate, 2, '.', ''),
                            'washRate' => (float)$washRate,
                            'washList' => $washList,
                            'pageNo' => (int)$pageNo,
                            'totalPage' => (int)$totalPage,
                            'totalCount' => (int)$totalCount,
                            'vipLevel' => $lvl
                        ],
                        'code' => 0,
                        'msg' => 'Succeed',
                        'msgCode' => 0,
                        'serviceNowTime' => $shnunc
                    ];

                    http_response_code(200);
                    echo json_encode($res, JSON_PRETTY_PRINT);
                    exit;
                }
                
            } else {                    
                $res = [
                    'code' => 4,
                    'msg' => 'Invalid token',
                    'msgCode' => 2,
                    'serviceNowTime' => $shnunc
                ];
                http_response_code(401);
                echo json_encode($res);                    
            }
        } else {
            $res = [
                'code' => 7,
                'msg' => 'Param is Invalid',
                'msgCode' => 6,
                'serviceNowTime' => $shnunc
            ];
            http_response_code(200);
            echo json_encode($res);            
        }
    } else {
        http_response_code(405);
        echo json_encode($res);
    }
} catch (Exception $e) {
    $res = [
        'code' => 10,
        'msg' => 'Internal server error',
        'msgCode' => 5,
        'serviceNowTime' => $shnunc
    ];
    http_response_code(500);
    echo json_encode($res);
} 
?>