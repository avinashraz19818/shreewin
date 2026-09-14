<?php
// ============================================
// FILE: GetDailyAwardList.php
// PURPOSE: Show daily rewards with REAL bet count from ALL tables
// ============================================

header('Content-Type: application/json; charset=utf-8');

include "../../conn.php";
include "../../functions2.php";

if (!$conn) {
    die(json_encode(["code" => 500, "msg" => "Database connection failed"]));
}

date_default_timezone_set("Asia/Dhaka");
$serviceNowTime = date('Y-m-d H:i:s');

// ========== GET USER ID ==========
$userId = 0;
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true);

if (is_array($body) && isset($body['userId'])) $userId = (int)$body['userId'];
if ($userId <= 0 && isset($_REQUEST['userId'])) $userId = (int)$_REQUEST['userId'];
if ($userId <= 0 && isset($_REQUEST['uid'])) $userId = (int)$_REQUEST['uid'];

// JWT token
if ($userId <= 0) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($auth && stripos($auth, 'Bearer ') === 0) {
        $jwt = trim(substr($auth, 7));
        $jwtRes = is_jwt_valid($jwt);
        $jwtCheck = is_array($jwtRes) ? $jwtRes : json_decode($jwtRes, true);
        
        if (!empty($jwtCheck['status']) && $jwtCheck['status'] === 'Success') {
            $payload = $jwtCheck['payload'];
            $userId = (int)($payload['userId'] ?? $payload['uid'] ?? $payload['id'] ?? 0);
        }
    }
}

// Session
if ($userId <= 0) {
    @session_start();
    $userId = (int)($_SESSION['userId'] ?? 0);
}

if ($userId <= 0) {
    die(json_encode(["code" => 400, "msg" => "Invalid userId"]));
}

// ========== GET LAST 24 HOURS DEPOSIT ==========
$depositSql = "SELECT COALESCE(SUM(amount), 0) as total 
               FROM user_transactions 
               WHERE user_id = ? AND transaction_type = 'deposit' 
               AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$depositStmt = $conn->prepare($depositSql);
$depositStmt->bind_param("i", $userId);
$depositStmt->execute();
$depositResult = app_stmt_result($depositStmt);
$depositRow = $depositResult->fetch_assoc();
$deposit24h = (float)($depositRow['total'] ?? 0);
$depositStmt->close();

// ========== GET LAST 24 HOURS BET FROM ALL TABLES ==========
$bet24h = 0;
$betTables = [
    'bajikattuttate',
    'bajikattuttate_drei', 
    'bajikattuttate_funf',
    'bajikattuttate_zehn'
];

foreach ($betTables as $table) {
    // Check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
    if ($checkTable->num_rows == 0) {
        continue;
    }
    
    // Get bets from this table
    $betSql = "SELECT COALESCE(SUM(ketebida), 0) as total 
               FROM $table 
               WHERE byabaharkarta = ? 
               AND tiarikala >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    
    $betStmt = $conn->prepare($betSql);
    $betStmt->bind_param("i", $userId);
    $betStmt->execute();
    $betResult = app_stmt_result($betStmt);
    $betRow = $betResult->fetch_assoc();
    $bet24h += (float)($betRow['total'] ?? 0);
    $betStmt->close();
}

// ========== GET CURRENT BALANCE ==========
$balanceSql = "SELECT motta FROM shonu_kaichila WHERE balakedara = ? LIMIT 1";
$balanceStmt = $conn->prepare($balanceSql);
$balanceStmt->bind_param("i", $userId);
$balanceStmt->execute();
$balanceResult = app_stmt_result($balanceStmt);

if ($balanceResult->num_rows > 0) {
    $balanceRow = $balanceResult->fetch_assoc();
    $currentBalance = (float)$balanceRow['motta'];
} else {
    $currentBalance = 0;
}
$balanceStmt->close();

// ========== REWARD CONFIGURATIONS ==========
$configs = [
    1160 => [
        'level' => 'HIGH',
        'deposit_target' => 80000,
        'bet_target' => 400000,
        'award' => 1000,
        'lId' => 92,
        'mainConfigId' => 24
    ],
    1159 => [
        'level' => 'MEDIUM',
        'deposit_target' => 30000,
        'bet_target' => 150000,
        'award' => 500,
        'lId' => 89,
        'mainConfigId' => 23
    ],
    1158 => [
        'level' => 'LOW',
        'deposit_target' => 10000,
        'bet_target' => 50000,
        'award' => 200,
        'lId' => 86,
        'mainConfigId' => 22
    ]
];

// ========== CHECK CLAIM STATUS ==========
$result = [];

foreach ($configs as $configId => $cfg) {
    // Check if already claimed in last 24 hours
    $claimSql = "SELECT createDate FROM daily_award_record 
                 WHERE userId = ? AND configId = ? 
                 AND createDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                 ORDER BY createDate DESC LIMIT 1";
    $claimStmt = $conn->prepare($claimSql);
    $claimStmt->bind_param("ii", $userId, $configId);
    $claimStmt->execute();
    $claimResult = app_stmt_result($claimStmt);
    
    $claimed = false;
    $hoursLeft = 0;
    
    if ($claimResult->num_rows > 0) {
        $claimRow = $claimResult->fetch_assoc();
        $claimed = true;
        $hoursLeft = 24 - ((time() - strtotime($claimRow['createDate'])) / 3600);
        if ($hoursLeft < 0) $hoursLeft = 0;
    }
    $claimStmt->close();
    
    $conditionsMet = ($deposit24h >= $cfg['deposit_target'] && $bet24h >= $cfg['bet_target']);
    
    if ($claimed) {
        $status = 3;
        $buttonStatus = "received";
        $canReceive = false;
        $reason = "Already claimed";
        if ($hoursLeft > 0) {
            $reason .= " (Reset in " . round($hoursLeft, 1) . " hours)";
        }
    } elseif ($conditionsMet) {
        $status = 2;
        $buttonStatus = "can_receive";
        $canReceive = true;
        $reason = "Ready to claim!";
    } else {
        $status = 1;
        $buttonStatus = "to_complete";
        $canReceive = false;
        
        if ($deposit24h < $cfg['deposit_target']) {
            $need = $cfg['deposit_target'] - $deposit24h;
            $reason = "Need ₹" . number_format($need, 2) . " more deposit";
        } elseif ($bet24h < $cfg['bet_target']) {
            $need = $cfg['bet_target'] - $bet24h;
            $reason = "Need ₹" . number_format($need, 2) . " more bet";
        } else {
            $reason = "Targets not met";
        }
    }
    
    $result[] = [
        "userId" => $userId,
        "mainConfigId" => $cfg['mainConfigId'],
        "configId" => $configId,
        "schedule" => $deposit24h,
        "scheduleTwo" => $bet24h,
        "status" => $status,
        "buttonStatus" => $buttonStatus,
        "canReceive" => $canReceive,
        "reason" => $reason,
        "taskTitle" => "DAILY DEPOSIT BETTING BONUS",
        "taskDescribe" => "DAILY DEPOSIT BETTING BONUS",
        "taskId" => "D20",
        "taskTarget" => $cfg['deposit_target'],
        "taskAwardAmount" => $cfg['award'],
        "createDate" => $serviceNowTime,
        "targetTwo" => $cfg['bet_target'],
        "targetItem" => 3,
        "lId" => $cfg['lId']
    ];
}

echo json_encode([
    "code" => 0,
    "msg" => "Success",
    "data" => $result,
    "user_stats" => [
        "user_id" => $userId,
        "current_balance" => $currentBalance,
        "deposit_24h" => $deposit24h,
        "bet_24h" => $bet24h
    ]
], JSON_PRETTY_PRINT);

exit;
?>