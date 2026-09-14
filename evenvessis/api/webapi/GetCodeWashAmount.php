<?php 
include "../../conn.php";
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
$shonubody = file_get_contents("php://input");
$shonupost = json_decode($shonubody, true);

// ============================================
// ========== REBET RECORD API ================
// ============================================
if (isset($shonupost['codeType']) && isset($shonupost['language']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp'])) {
    
    $language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
    $random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
    $signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
    $timestamp = $shonupost['timestamp'];
    $codeType = isset($shonupost['codeType']) ? $shonupost['codeType'] : -1;
    
    if(true) { // Temporarily disabled signature check
        
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

            if ($state == -1) {
                
                // Today's date range
                $todayStart = date("Y-m-d") . " 00:00:00";
                $todayEnd = date("Y-m-d") . " 23:59:59";
                
                // GET TODAY'S REBET RECORDS
                $samasye = "SELECT rebet, rate, motta, lvl, created_at 
                           FROM rebetrec 
                           WHERE user_id = '$userId' 
                           AND created_at BETWEEN '$todayStart' AND '$todayEnd'
                           ORDER BY id DESC 
                           LIMIT $pageSize OFFSET $offset";
                
                $samasyephalitansa = $conn->query($samasye);
                
                $washList = [];
                $dayRebate = 0.0;
                $totalCount = 0;

                if ($samasyephalitansa && $samasyephalitansa->num_rows > 0) {
                    while ($row = $samasyephalitansa->fetch_assoc()) {
                        $betAmount = (float)($row['rebet'] ?? 0);
                        $rebateAmount = (float)($row['motta'] ?? 0);
                        $rate = (float)($row['rate'] ?? 0);
                        
                        $washList[] = [
                            'washVolume' => round($betAmount, 2),
                            'rebateAmount' => round($rebateAmount, 2),
                            'washRate' => round($rate, 5),
                            'addTime' => $row['created_at'] ?? '',
                            'codeType' => 3
                        ];
                        $dayRebate += $rebateAmount;
                    }

                    // Get total count for pagination
                    $countQuery = "SELECT COUNT(*) as total FROM rebetrec 
                                  WHERE user_id = '$userId' 
                                  AND created_at BETWEEN '$todayStart' AND '$todayEnd'";
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
                $totalRebateQuery = "SELECT COALESCE(SUM(motta), 0) as totalRebate FROM rebetrec WHERE user_id = '$userId'";
                $totalRebateResult = $conn->query($totalRebateQuery);
                $totalRebate = 0.0;
                if ($totalRebateResult && $totalRebateResult->num_rows > 0) {
                    $totalRebateRow = $totalRebateResult->fetch_assoc();
                    $totalRebate = (float)($totalRebateRow['totalRebate'] ?? 0.0);
                }

                // RESPONSE
                $res = [
                    'data' => [
                        'codeWashAmount' => round($u, 2),
                        'dayRebate' => round($dayRebate, 2),
                        'totalRebate' => round($totalRebate, 2),
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
            'code' => 5,
            'msg' => 'Wrong signature',
            'msgCode' => 3,
            'serviceNowTime' => $shnunc
        ];
        http_response_code(200);
        echo json_encode($res);
    }
    exit;
}

// ============================================
// ========== INVALID REQUEST =================
// ============================================
else {
    $res = [
        'code' => 9,
        'msg' => 'Invalid request - missing required parameters',
        'msgCode' => 6,
        'serviceNowTime' => $shnunc,
        'received' => array_keys($shonupost ?? [])
    ];
    http_response_code(200);
    echo json_encode($res);
}
?>