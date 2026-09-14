<?php
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Vary: Origin');

date_default_timezone_set("Asia/Kolkata");
$shnunc = date("Y-m-d H:i:s");

$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $shnunc,
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ---- Auth header check ----
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $res = [
            'code' => 4,
            'msg' => 'Authorization header missing',
            'msgCode' => 2,
            'serviceNowTime' => $shnunc
        ];
        http_response_code(401);
        echo json_encode($res);
        exit();
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
        exit();
    }

    $is_jwt_valid = is_jwt_valid($author);
    $data_auth = json_decode($is_jwt_valid, true);

    if ($data_auth && isset($data_auth['status']) && $data_auth['status'] === 'Success') {

        // JWT se userId
        $userId = (int)$data_auth['payload']['id'];

        // Body read + pagination
        $shonubody = file_get_contents("php://input");
        $shonupost = json_decode($shonubody, true);

        $pageNo = isset($shonupost['pageNo']) ? max(1, (int)$shonupost['pageNo']) : 1;
        $pageSize = isset($shonupost['pageSize']) ? max(1, (int)$shonupost['pageSize']) : 10;
        $offset = ($pageNo - 1) * $pageSize;

        try {
            
            // ============================================
            // STEP 1: CHECK AND UPDATE DAILY TASKS
            // ============================================
            
            // Get today's deposit (thevani table - successful deposits)
            $depositQuery = "SELECT COALESCE(SUM(motta), 0) as total 
                            FROM thevani 
                            WHERE balakedara = ? 
                            AND sthiti = 1 
                            AND DATE(dinankavannuracisi) = CURDATE()";
            $depositStmt = $conn->prepare($depositQuery);
            $depositStmt->bind_param("i", $userId);
            $depositStmt->execute();
            $depositResult = app_stmt_result($depositStmt);
            $depositRow = $depositResult->fetch_assoc();
            $todayDeposit = (float)($depositRow['total'] ?? 0);
            $depositStmt->close();
            
            // Get today's bet (from all betting tables)
            $betTables = ['bajikattuttate', 'bajikattuttate_drei', 'bajikattuttate_funf', 'bajikattuttate_zehn'];
            $todayBet = 0;
            
            foreach($betTables as $table) {
                $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
                if ($checkTable && $checkTable->num_rows > 0) {
                    $betQuery = "SELECT COALESCE(SUM(ketebida), 0) as total 
                                FROM $table 
                                WHERE byabaharkarta = ? 
                                AND DATE(tiarikala) = CURDATE()";
                    $betStmt = $conn->prepare($betQuery);
                    if ($betStmt) {
                        $betStmt->bind_param("i", $userId);
                        $betStmt->execute();
                        $betResult = app_stmt_result($betStmt);
                        $betRow = $betResult->fetch_assoc();
                        $todayBet += (float)($betRow['total'] ?? 0);
                        $betStmt->close();
                    }
                }
            }
            
            // ============================================
            // STEP 2: GET DAILY AWARD RECORDS
            // ============================================
            
            // First check if table exists
            $checkTable = $conn->query("SHOW TABLES LIKE 'hyper_daily_award_record'");
            if (!$checkTable || $checkTable->num_rows == 0) {
                // Create table if not exists
                $createTable = "CREATE TABLE IF NOT EXISTS `hyper_daily_award_record` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `userId` int(11) NOT NULL,
                    `configId` int(11) NOT NULL,
                    `awardAmount` decimal(10,2) NOT NULL,
                    `configDetailId` int(11) DEFAULT NULL,
                    `taskId` varchar(50) DEFAULT NULL,
                    `taskTitle` varchar(255) DEFAULT NULL,
                    `taskDescribe` text,
                    `taskTarget` decimal(10,2) DEFAULT NULL,
                    `createDate` datetime NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `userId` (`userId`),
                    KEY `createDate` (`createDate`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $conn->query($createTable);
            }
            
            // ---- TOTAL COUNT (today's records only) ----
            $countQuery = "SELECT COUNT(*) AS total 
                          FROM hyper_daily_award_record 
                          WHERE userId = ? 
                          AND DATE(createDate) = CURDATE()";  // Only today's records
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bind_param("i", $userId);
            $countStmt->execute();
            $countResult = app_stmt_result($countStmt);
            $totalCount = ($countResult && $row = $countResult->fetch_assoc()) ? (int)$row['total'] : 0;
            $totalPage = $pageSize > 0 ? ceil($totalCount / $pageSize) : 1;

            $list = [];

            if ($totalCount > 0) {
                // ---- LIST QUERY (today's records only) ----
                $query = "SELECT 
                            id,
                            configId,
                            userId,
                            awardAmount,
                            configDetailId,
                            taskId,
                            taskTitle,
                            taskDescribe,
                            taskTarget,
                            createDate
                          FROM hyper_daily_award_record
                          WHERE userId = ? 
                          AND DATE(createDate) = CURDATE()  // Only today's records
                          ORDER BY createDate DESC
                          LIMIT ?, ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", $userId, $offset, $pageSize);
                $stmt->execute();
                $result = app_stmt_result($stmt);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Add today's deposit and bet for reference
                        $row['todayDeposit'] = $todayDeposit;
                        $row['todayBet'] = $todayBet;
                        $list[] = $row;
                    }
                }
                $stmt->close();
            } else {
                // No records found, but still return today's progress
                $list = [
                    [
                        'userId' => $userId,
                        'todayDeposit' => $todayDeposit,
                        'todayBet' => $todayBet,
                        'message' => 'No rewards claimed today'
                    ]
                ];
            }

            // ============================================
            // STEP 3: AUTO-RESET LOGIC (Midnight reset)
            // ============================================
            
            // Create a track table for last reset time
            $trackTable = "CREATE TABLE IF NOT EXISTS `daily_reset_track` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `last_reset_date` date NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $conn->query($trackTable);
            
            // Check if user needs reset
            $checkReset = "SELECT last_reset_date FROM daily_reset_track WHERE user_id = ?";
            $resetStmt = $conn->prepare($checkReset);
            $resetStmt->bind_param("i", $userId);
            $resetStmt->execute();
            $resetResult = app_stmt_result($resetStmt);
            $today = date('Y-m-d');
            
            if ($resetResult->num_rows > 0) {
                $resetRow = $resetResult->fetch_assoc();
                if ($resetRow['last_reset_date'] != $today) {
                    // Reset needed - new day
                    $updateReset = "UPDATE daily_reset_track SET last_reset_date = ? WHERE user_id = ?";
                    $updateStmt = $conn->prepare($updateReset);
                    $updateStmt->bind_param("si", $today, $userId);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    $resetMessage = "New day started - reset at midnight";
                } else {
                    $resetMessage = "Same day - no reset";
                }
            } else {
                // First time user
                $insertReset = "INSERT INTO daily_reset_track (user_id, last_reset_date) VALUES (?, ?)";
                $insertStmt = $conn->prepare($insertReset);
                $insertStmt->bind_param("is", $userId, $today);
                $insertStmt->execute();
                $insertStmt->close();
                $resetMessage = "First time - initialized";
            }
            $resetStmt->close();

            $res = [
                'data' => [
                    'list' => $list,
                    'pageNo' => $pageNo,
                    'totalPage' => $totalPage,
                    'totalCount' => $totalCount,
                    'todayProgress' => [
                        'deposit' => $todayDeposit,
                        'bet' => $todayBet,
                        'date' => date('Y-m-d')
                    ]
                ],
                'code' => 0,
                'msg' => 'Succeed',
                'msgCode' => 0,
                'serviceNowTime' => $shnunc,
                'reset' => $resetMessage
            ];

            echo json_encode($res, JSON_PRETTY_PRINT);
            exit();

        } catch (Exception $e) {
            // Log error for debugging
            error_log("Daily Award Record Error: " . $e->getMessage());
            
            $res = [
                'code' => 500,
                'msg' => 'Database error: ' . $e->getMessage(),
                'msgCode' => 500,
                'serviceNowTime' => $shnunc
            ];
            http_response_code(500);
            echo json_encode($res);
            exit();
        }

    } else {
        $res = [
            'code' => 4,
            'msg' => 'Invalid or expired token',
            'msgCode' => 2,
            'serviceNowTime' => $shnunc
        ];
        http_response_code(401);
        echo json_encode($res);
        exit();
    }

} else {
    // OPTIONS preflight
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    http_response_code(405);
    echo json_encode($res);
    exit();
}
?>