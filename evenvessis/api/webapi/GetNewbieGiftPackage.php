<?php
// GetNewbieGiftPackage.php (final, uses functions2 helpers)

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../conn.php";
include "../../functions2.php";

header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set("Asia/Dhaka");
$now   = date("Y-m-d H:i:s");
$today = date("Y-m-d");

// ---------- AUTH CHECK ----------
if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    http_response_code(401);
    echo json_encode(['code'=>4,'msg'=>'Authorization missing','serviceNowTime'=>$now]);
    exit;
}
$bearer = explode(' ', trim($_SERVER['HTTP_AUTHORIZATION']));
$token  = $bearer[1] ?? '';
if ($token === '') {
    http_response_code(401);
    echo json_encode(['code'=>4,'msg'=>'Token missing','serviceNowTime'=>$now]);
    exit;
}
$jwtResult = is_jwt_valid($token);
$auth      = is_array($jwtResult) ? $jwtResult : json_decode($jwtResult, true);
if (!$auth || ($auth['status'] ?? '') !== 'Success') {
    http_response_code(401);
    echo json_encode(['code'=>4,'msg'=>'Invalid token','serviceNowTime'=>$now]);
    exit;
}
$balakedara = (int)($auth['payload']['id'] ?? ($auth['payload']['user_id'] ?? ($auth['payload']['userId'] ?? 0)));
if ($balakedara <= 0) {
    http_response_code(400);
    echo json_encode(['code'=>4,'msg'=>'User id missing in token','serviceNowTime'=>$now]);
    exit;
}

// ---------- DATABASE CONNECTION CHECK ----------
if (!$conn) {
    echo json_encode([
        "code" => 500,
        "msg" => "Database connection failed",
        "serviceNowTime" => $now
    ]);
    exit;
}

// ---------- FIX: MISSING FUNCTION DEFINITION ----------
/**
 * Get first successful recharge date for user
 * @param int $userId
 * @return string|null Returns datetime string or null if no recharge found
 */
if (!function_exists('getFirstSuccessfulRechargeDate')) {
    function getFirstSuccessfulRechargeDate($userId) {
        global $conn;
        
        // पहले सही टेबल ढूंढें
        $possibleTables = ['recharge', 'deposit', 'user_recharge', 'add_money', 'transactions', 'payment_records'];
        $userColumns = ['userId', 'user_id', 'uid', 'userid', 'balakedara'];
        $dateColumns = ['createDate', 'created_at', 'createdAt', 'date', 'payment_date', 'addtime', 'add_time'];
        $statusColumns = ['status', 'payment_status', 'order_status', 'pay_status'];
        
        foreach ($possibleTables as $table) {
            // चेक करें टेबल मौजूद है
            $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
            if (!$checkTable || $checkTable->num_rows == 0) {
                continue;
            }
            
            // टेबल के कॉलम देखें
            $columns = [];
            $colResult = $conn->query("SHOW COLUMNS FROM $table");
            if (!$colResult) continue;
            
            while ($col = $colResult->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            
            // यूजर कॉलम ढूंढें
            $userCol = null;
            foreach ($userColumns as $uc) {
                if (in_array($uc, $columns)) {
                    $userCol = $uc;
                    break;
                }
            }
            if (!$userCol) continue;
            
            // डेट कॉलम ढूंढें
            $dateCol = null;
            foreach ($dateColumns as $dc) {
                if (in_array($dc, $columns)) {
                    $dateCol = $dc;
                    break;
                }
            }
            if (!$dateCol) continue;
            
            // SQL बनाएं
            $sql = "SELECT $dateCol as first_date FROM $table WHERE $userCol = ?";
            
            // स्टेटस कॉलम है तो जोड़ें
            foreach ($statusColumns as $sc) {
                if (in_array($sc, $columns)) {
                    $sql .= " AND $sc IN ('success', 'completed', '1', 'paid', 'SUCCESS', 'succeed', 'SUCCEED')";
                    break;
                }
            }
            
            $sql .= " ORDER BY $dateCol ASC LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) continue;
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = app_stmt_result($stmt);
            
            if ($row = $result->fetch_assoc()) {
                $firstDate = $row['first_date'];
                $stmt->close();
                return $firstDate;
            }
            $stmt->close();
        }
        
        return null; // कोई रिचार्ज नहीं मिला
    }
}

// ---------- FIX: GET FIRST RECHARGE DATE ----------
$firstRechargeDT = getFirstSuccessfulRechargeDate($balakedara);

// ---------- CONFIG ----------
$totalDays    = 7;
$rewardPerDay = 2.00;
$totalAmount  = $totalDays * $rewardPerDay;

// ---------- IF USER NEVER RECHARGED ----------
if (!$firstRechargeDT) {
    // user never recharged
    echo json_encode([
        "data" => [
            "id" => 0,
            "title" => "Newbie Gift Pack",
            "description" => "Newbie Gift Pack",
            "amount" => (float)$totalAmount,
            "status" => 0,
            "receivedNumber" => 0,
            "totalNumber" => $totalDays,
            "userId" => $balakedara,
            "walletBalance" => 0.00,
            "canReceive" => false,
            "reason" => "Not recharged",
            "nextClaim" => null,
            "dailyRewardId" => null,
            "history" => []
        ],
        "code" => 0,
        "msg" => "Succeed",
        "serviceNowTime" => $now
    ]);
    exit;
}

$firstRechargeDate = date("Y-m-d", strtotime($firstRechargeDT));
$endDate = date("Y-m-d", strtotime("$firstRechargeDate +7 days"));

// ---------- ENSURE DAILY REWARD ROW EXISTS ----------
$dailyRewardId = null;
$received = 0;
$nextClaimAt = $now;
$status = 0;

// चेक करें daily_reward टेबल मौजूद है
$checkDailyTable = $conn->query("SHOW TABLES LIKE 'daily_reward'");
if (!$checkDailyTable || $checkDailyTable->num_rows == 0) {
    // टेबल नहीं है तो create करें
    $conn->query("
        CREATE TABLE IF NOT EXISTS daily_reward (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            deposit_amount DECIMAL(10,2) DEFAULT 0,
            reward_per_day DECIMAL(10,2) DEFAULT 2.00,
            received_days INT DEFAULT 0,
            status TINYINT DEFAULT 0,
            next_claim_at DATETIME,
            created_at DATETIME,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

// daily_reward रिकॉर्ड ढूंढें या बनाएं
$chk = $conn->prepare("SELECT * FROM daily_reward WHERE user_id = ? LIMIT 1");
if ($chk) {
    $chk->bind_param("i", $balakedara);
    $chk->execute();
    $res = app_stmt_result($chk);
    $chk->close();

    if ($res->num_rows == 0) {
        // नया रिकॉर्ड बनाएं
        $ins = $conn->prepare("INSERT INTO daily_reward (user_id, deposit_amount, reward_per_day, received_days, status, next_claim_at, created_at) VALUES (?, 0, ?, 0, 0, NOW(), NOW())");
        if ($ins) {
            $ins->bind_param("id", $balakedara, $rewardPerDay);
            $ins->execute();
            $dailyRewardId = (int)$conn->insert_id;
            $ins->close();

            // नया रिकॉर्ड लोड करें
            $chk2 = $conn->prepare("SELECT * FROM daily_reward WHERE id = ? LIMIT 1");
            $chk2->bind_param("i", $dailyRewardId);
            $chk2->execute();
            $res = app_stmt_result($chk2);
            $chk2->close();
        }
    }
    
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        $dailyRewardId = (int)$r['id'];
        $received = isset($r['received_days']) ? (int)$r['received_days'] : 0;
        $nextClaimAt = $r['next_claim_at'] ?? $now;
    }
}

if ($received < 0) $received = 0;
if ($received > $totalDays) $received = $totalDays;

// ---------- WALLET BALANCE ----------
$walletBalance = 0.00;
// पहले वॉलेट टेबल ढूंढें
$walletTables = ['shonu_kaichila', 'user_wallet', 'wallet', 'user_balance', 'accounts'];
foreach ($walletTables as $wt) {
    $checkWT = $conn->query("SHOW TABLES LIKE '$wt'");
    if ($checkWT && $checkWT->num_rows > 0) {
        // टेबल के कॉलम देखें
        $cols = [];
        $colRes = $conn->query("SHOW COLUMNS FROM $wt");
        while ($col = $colRes->fetch_assoc()) {
            $cols[] = $col['Field'];
        }
        
        // बैलेंस कॉलम ढूंढें
        $balanceCol = null;
        foreach (['motta', 'balance', 'amount', 'coins', 'points'] as $bc) {
            if (in_array($bc, $cols)) {
                $balanceCol = $bc;
                break;
            }
        }
        
        // यूजर कॉलम ढूंढें
        $userCol = null;
        foreach (['balakedara', 'user_id', 'userId', 'uid'] as $uc) {
            if (in_array($uc, $cols)) {
                $userCol = $uc;
                break;
            }
        }
        
        if ($balanceCol && $userCol) {
            $wq = $conn->prepare("SELECT $balanceCol as balance FROM $wt WHERE $userCol = ? LIMIT 1");
            if ($wq) {
                $wq->bind_param("i", $balakedara);
                $wq->execute();
                $wrow = app_stmt_result($wq)->fetch_assoc();
                $wq->close();
                if ($wrow) {
                    $walletBalance = (float)($wrow['balance'] ?? 0);
                    break;
                }
            }
        }
    }
}

// ---------- STATUS LOGIC ----------
$isCompleted = ($received >= $totalDays);
$isExpiredTime = ($today > $endDate);
$canReceive = false;
$reason = null;

if ($isExpiredTime) {
    $canReceive = false; 
    $reason = "Reward period expired"; 
    $status = 2;
} elseif ($isCompleted) {
    $canReceive = false; 
    $reason = "Completed"; 
    $status = 2;
} else {
    if ($now < $nextClaimAt) {
        $canReceive = false; 
        $reason = "Already claimed, wait for next claim time"; 
        $status = 0;
    } else {
        $canReceive = true; 
        $reason = "Can receive"; 
        $status = 1;
    }
}

// ---------- FETCH HISTORY ----------
$history = [];

// चेक करें हिस्ट्री टेबल मौजूद है
$checkHistoryTable = $conn->query("SHOW TABLES LIKE 'hyper_newbie_claim_record'");
if (!$checkHistoryTable || $checkHistoryTable->num_rows > 0) {
    $hstmt = $conn->prepare("SELECT id, userId, dailyRewardId, dayNumber, awardAmount, createdAt, source FROM hyper_newbie_claim_record WHERE userId = ? ORDER BY createdAt DESC LIMIT 50");
    if ($hstmt) {
        $hstmt->bind_param("i", $balakedara);
        $hstmt->execute();
        $hres = app_stmt_result($hstmt);
        while ($hr = $hres->fetch_assoc()) {
            $history[] = [
                'id' => (int)$hr['id'],
                'userId' => (int)$hr['userId'],
                'dailyRewardId' => isset($hr['dailyRewardId']) ? (int)$hr['dailyRewardId'] : null,
                'dayNumber' => (int)$hr['dayNumber'],
                'awardAmount' => (float)$hr['awardAmount'],
                'createdAt' => $hr['createdAt'],
                'source' => $hr['source'] ?? 'newbie'
            ];
        }
        $hstmt->close();
    }
}

// ---------- FINAL RESPONSE ----------
echo json_encode([
    "data" => [
        "id" => $dailyRewardId ?? 0,
        "title" => "Newbie Gift Pack",
        "description" => "Newbie Gift Pack",
        "amount" => (float)$totalAmount,
        "status" => $status,
        "receivedNumber" => $received,
        "totalNumber" => $totalDays,
        "userId" => $balakedara,
        "walletBalance" => $walletBalance,
        "canReceive" => $canReceive,
        "reason" => $reason,
        "nextClaim" => $isExpiredTime ? null : $nextClaimAt,
        "dailyRewardId" => $dailyRewardId,
        "history" => $history
    ],
    "code" => 0,
    "msg" => "Succeed",
    "serviceNowTime" => $now
]);
exit;
?>