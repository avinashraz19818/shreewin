<?php
// ============================================
// FILE: ReceiveDailyAward.php
// PURPOSE: Claim daily reward - PREVENTS DOUBLE CLAIM
// ============================================

header('Content-Type: application/json; charset=utf-8');

include "../../conn.php";
include "../../functions2.php";

if (!$conn) {
    die(json_encode(["code" => 500, "msg" => "Database connection failed"]));
}

date_default_timezone_set("Asia/Dhaka");
$serviceNowTime = date('Y-m-d H:i:s');

// ========== CREATE CLAIM RECORD TABLE IF NOT EXISTS ==========
$conn->query("CREATE TABLE IF NOT EXISTS `daily_award_record` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `userId` INT NOT NULL,
    `configId` INT NOT NULL,
    `award_amount` DECIMAL(10,2) NOT NULL,
    `createDate` DATETIME NOT NULL,
    INDEX(userId),
    INDEX(configId),
    INDEX(createDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// ========== GET USER ID FROM JWT TOKEN ==========
$userId = 0;
$configId = 0;

// Get JSON input
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true);

// Get configId from JSON (dailyAwardId)
if (is_array($body) && isset($body['dailyAwardId'])) {
    $configId = (int)$body['dailyAwardId'];
}

// Get userId from Authorization header (JWT)
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

// Validate
if ($userId <= 0) {
    die(json_encode([
        "code" => 400,
        "msg" => "Invalid userId",
        "debug" => "Could not extract from JWT"
    ]));
}

if ($configId <= 0) {
    die(json_encode([
        "code" => 400,
        "msg" => "Invalid configId"
    ]));
}

// ========== REWARD CONFIGURATIONS ==========
$configs = [
    1160 => ['level' => 'HIGH', 'award' => 1000],
    1159 => ['level' => 'MEDIUM', 'award' => 500],
    1158 => ['level' => 'LOW', 'award' => 200]
];

if (!isset($configs[$configId])) {
    die(json_encode(["code" => 400, "msg" => "Invalid configId"]));
}

$cfg = $configs[$configId];

// ========== CHECK IF ALREADY CLAIMED IN LAST 24 HOURS ==========
$checkSql = "SELECT id FROM daily_award_record 
             WHERE userId = ? AND configId = ? 
             AND createDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("ii", $userId, $configId);
$checkStmt->execute();
$checkResult = app_stmt_result($checkStmt);

if ($checkResult->num_rows > 0) {
    // Already claimed - get claim time
    $claimRow = $checkResult->fetch_assoc();
    $claimId = $claimRow['id'];
    
    // Get claim time for debugging
    $timeSql = "SELECT createDate FROM daily_award_record WHERE id = ?";
    $timeStmt = $conn->prepare($timeSql);
    $timeStmt->bind_param("i", $claimId);
    $timeStmt->execute();
    $timeResult = app_stmt_result($timeStmt);
    $timeRow = $timeResult->fetch_assoc();
    $claimTime = $timeRow['createDate'];
    $timeStmt->close();
    
    $checkStmt->close();
    
    die(json_encode([
        "code" => 409,
        "msg" => "Already claimed in last 24 hours",
        "data" => [
            "level" => $cfg['level'],
            "claim_time" => $claimTime,
            "next_claim_available" => date('Y-m-d H:i:s', strtotime($claimTime . ' +24 hours'))
        ]
    ]));
}
$checkStmt->close();

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

// ========== GET LAST 24 HOURS BET ==========
$bet24h = 0;
$betTables = ['bajikattuttate', 'bajikattuttate_drei', 'bajikattuttate_funf', 'bajikattuttate_zehn'];

foreach ($betTables as $table) {
    $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
    if ($checkTable->num_rows == 0) continue;
    
    $betSql = "SELECT COALESCE(SUM(ketebida), 0) as total 
               FROM $table 
               WHERE byabaharkarta = ? 
               AND tiarikala >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $betStmt = $conn->prepare($betSql);
    if ($betStmt) {
        $betStmt->bind_param("i", $userId);
        $betStmt->execute();
        $betResult = app_stmt_result($betStmt);
        $betRow = $betResult->fetch_assoc();
        $bet24h += (float)($betRow['total'] ?? 0);
        $betStmt->close();
    }
}

// Define targets based on configId
$targets = [
    1160 => ['deposit' => 80000, 'bet' => 400000],
    1159 => ['deposit' => 30000, 'bet' => 150000],
    1158 => ['deposit' => 10000, 'bet' => 50000]
];

$target = $targets[$configId];

// Check if targets met
if ($deposit24h < $target['deposit'] || $bet24h < $target['bet']) {
    die(json_encode([
        "code" => 403,
        "msg" => "Targets not met",
        "data" => [
            "required" => [
                "deposit" => $target['deposit'],
                "bet" => $target['bet']
            ],
            "your" => [
                "deposit" => $deposit24h,
                "bet" => $bet24h
            ]
        ]
    ]));
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
    $insertWallet = "INSERT INTO shonu_kaichila (balakedara, motta) VALUES (?, 0)";
    $insertStmt = $conn->prepare($insertWallet);
    $insertStmt->bind_param("i", $userId);
    $insertStmt->execute();
    $insertStmt->close();
}
$balanceStmt->close();

$newBalance = $currentBalance + $cfg['award'];

// ========== START TRANSACTION ==========
$conn->begin_transaction();

try {
    // 1. Update user balance
    $updateSql = "UPDATE shonu_kaichila SET motta = ? WHERE balakedara = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("di", $newBalance, $userId);
    $updateStmt->execute();
    $updateStmt->close();
    
    // 2. Insert into award record - YAHI SE RECEIVED HOGA
    $awardSql = "INSERT INTO daily_award_record (userId, configId, award_amount, createDate) 
                 VALUES (?, ?, ?, NOW())";
    $awardStmt = $conn->prepare($awardSql);
    $awardStmt->bind_param("iid", $userId, $configId, $cfg['award']);
    $awardStmt->execute();
    $awardId = $awardStmt->insert_id;
    $awardStmt->close();
    
    // 3. Insert into transactions as reward
    $transSql = "INSERT INTO user_transactions (user_id, amount, transaction_type, balance_after, remarks, created_at) 
                 VALUES (?, ?, 'reward', ?, ?, NOW())";
    $transStmt = $conn->prepare($transSql);
    $remarks = "Daily reward - " . $cfg['level'] . " level";
    $transStmt->bind_param("idds", $userId, $cfg['award'], $newBalance, $remarks);
    $transStmt->execute();
    $transStmt->close();
    
    $conn->commit();
    
    echo json_encode([
        "code" => 0,
        "msg" => "Reward claimed successfully",
        "data" => [
            "level" => $cfg['level'],
            "award" => $cfg['award'],
            "new_balance" => $newBalance,
            "previous_balance" => $currentBalance,
            "claim_id" => $awardId,
            "claim_time" => $serviceNowTime
        ]
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "code" => 500,
        "msg" => "Server error: " . $e->getMessage()
    ]);
}

exit;
?>