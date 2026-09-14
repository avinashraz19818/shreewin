<?php
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

date_default_timezone_set("Asia/Kolkata");
$serviceNowTime = date("Y-m-d H:i:s");

$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $serviceNowTime,
];

/* =========================================================
   CONFIG
========================================================= */

// Referral table — ISKO APNE DB KE HISAAB SE SAHI KARNA PADEGA
define('REF_USER_TABLE', 'users');
define('REF_USER_ID_COL', 'id');
define('REF_USER_PARENT_COL', 'referrer_id');
define('REF_USER_CREATED_COL', 'created_at');

// Recharge table (confirmed)
define('RECHARGE_TABLE', 'thevani');
define('RECHARGE_USER_ID_COL', 'balakedara');
define('RECHARGE_AMOUNT_COL', 'motta');
define('RECHARGE_STATUS_COL', 'sthiti');
define('RECHARGE_CREATED_COL', 'dinankavannuracisi');

define('SUCCESS_RECHARGE_STATUS', 1);
define('MIN_QUALIFY_DEPOSIT', 300.00);
define('CYCLE_HOURS', 72);
define('TOTAL_BOXES', 4);
define('FREE_SPIN_AFTER_BOXES', 1);

/* =========================================================
   HELPERS
========================================================= */
function sendJson($res, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($res);
    exit();
}

function buildSignature($language, $random) {
    $str = '{"language":' . $language . ',"random":"' . $random . '"}';
    return strtoupper(md5($str));
}

function ensureTables($conn) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS shonu_turntable (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            total_spins INT NOT NULL DEFAULT 0,
            invited_wheel_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS shonu_turntable_spins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            user_name VARCHAR(255) NULL,
            prize_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            spin_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS shonu_turntable_cycles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            cycle_no INT NOT NULL DEFAULT 1,
            cycle_start_at DATETIME NOT NULL,
            cycle_end_at DATETIME NOT NULL,
            box1_open TINYINT(1) NOT NULL DEFAULT 0,
            box2_open TINYINT(1) NOT NULL DEFAULT 0,
            box3_open TINYINT(1) NOT NULL DEFAULT 0,
            box4_open TINYINT(1) NOT NULL DEFAULT 0,
            boxes_opened_count INT NOT NULL DEFAULT 0,
            free_spin_awarded TINYINT(1) NOT NULL DEFAULT 0,
            invite_spins_awarded INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
}

function ensureTurntableUser($conn, $userId) {
    $sql = "
        INSERT INTO shonu_turntable (user_id, total_spins, invited_wheel_amount, created_at, updated_at)
        VALUES (?, 0, 0, NOW(), NOW())
        ON DUPLICATE KEY UPDATE updated_at = NOW()
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function getCycle($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM shonu_turntable_cycles WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = app_stmt_result($stmt);
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

function createCycle($conn, $userId) {
    $start = date("Y-m-d H:i:s");
    $end = date("Y-m-d H:i:s", strtotime("+".CYCLE_HOURS." hours"));

    $sql = "
        INSERT INTO shonu_turntable_cycles
        (user_id, cycle_no, cycle_start_at, cycle_end_at, created_at, updated_at)
        VALUES (?, 1, ?, ?, NOW(), NOW())
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $start, $end);
    $stmt->execute();
    $stmt->close();

    return getCycle($conn, $userId);
}

function getOrCreateCycle($conn, $userId) {
    $row = getCycle($conn, $userId);
    if ($row) return $row;
    return createCycle($conn, $userId);
}

function resetCycleIfExpired($conn, $userId, $cycleRow) {
    $nowTs = time();
    $endTs = strtotime($cycleRow['cycle_end_at']);

    if ($endTs !== false && $nowTs <= $endTs) {
        return $cycleRow;
    }

    $newCycleNo = ((int)$cycleRow['cycle_no']) + 1;
    $newStart = date("Y-m-d H:i:s");
    $newEnd = date("Y-m-d H:i:s", strtotime("+".CYCLE_HOURS." hours"));

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("UPDATE shonu_turntable SET total_spins = 0, updated_at = NOW() WHERE user_id = ?");
        $stmt1->bind_param("i", $userId);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("
            UPDATE shonu_turntable_cycles
            SET cycle_no = ?,
                cycle_start_at = ?,
                cycle_end_at = ?,
                box1_open = 0,
                box2_open = 0,
                box3_open = 0,
                box4_open = 0,
                boxes_opened_count = 0,
                free_spin_awarded = 0,
                invite_spins_awarded = 0,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt2->bind_param("issi", $newCycleNo, $newStart, $newEnd, $userId);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
    }

    return getCycle($conn, $userId);
}

function recountBoxes($row) {
    $count = 0;
    for ($i = 1; $i <= 4; $i++) {
        if ((int)$row['box'.$i.'_open'] === 1) $count++;
    }
    return $count;
}

function markSingleBoxOpen($conn, $userId, $boxNo) {
    $boxNo = (int)$boxNo;
    if ($boxNo < 1 || $boxNo > 4) return;

    $col = "box{$boxNo}_open";
    $sql = "UPDATE shonu_turntable_cycles SET $col = 1, updated_at = NOW() WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function syncPostedBoxes($conn, $userId, $post) {
    // Supported formats:
    // action=open_box + boxNo
    // openBox=1
    // openedBox=1
    // boxNo=1
    // box1_open=1 ... box4_open=1

    if (isset($post['action']) && $post['action'] === 'open_box' && isset($post['boxNo'])) {
        markSingleBoxOpen($conn, $userId, (int)$post['boxNo']);
    }

    if (isset($post['openBox'])) {
        markSingleBoxOpen($conn, $userId, (int)$post['openBox']);
    }

    if (isset($post['openedBox'])) {
        markSingleBoxOpen($conn, $userId, (int)$post['openedBox']);
    }

    if (!isset($post['action']) && isset($post['boxNo'])) {
        markSingleBoxOpen($conn, $userId, (int)$post['boxNo']);
    }

    for ($i = 1; $i <= 4; $i++) {
        $k = 'box'.$i.'_open';
        if (isset($post[$k]) && (int)$post[$k] === 1) {
            markSingleBoxOpen($conn, $userId, $i);
        }
    }

    $row = getCycle($conn, $userId);
    $count = recountBoxes($row);

    $stmt = $conn->prepare("UPDATE shonu_turntable_cycles SET boxes_opened_count = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->bind_param("ii", $count, $userId);
    $stmt->execute();
    $stmt->close();

    return getCycle($conn, $userId);
}

function getQualifiedReferralCount($conn, $userId, $startAt, $endAt, &$debug = []) {
    $sql = "
        SELECT COUNT(DISTINCT u." . REF_USER_ID_COL . ") AS total
        FROM " . REF_USER_TABLE . " u
        INNER JOIN " . RECHARGE_TABLE . " r
            ON r." . RECHARGE_USER_ID_COL . " = u." . REF_USER_ID_COL . "
        WHERE u." . REF_USER_PARENT_COL . " = ?
          AND u." . REF_USER_CREATED_COL . " BETWEEN ? AND ?
          AND CAST(r." . RECHARGE_AMOUNT_COL . " AS DECIMAL(12,2)) >= ?
          AND STR_TO_DATE(r." . RECHARGE_CREATED_COL . ", '%Y-%m-%d %H:%i:%s') BETWEEN ? AND ?
          AND r." . RECHARGE_STATUS_COL . " = ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $debug['referral_sql_error'] = $conn->error;
        return 0;
    }

    $min = MIN_QUALIFY_DEPOSIT;
    $successStatus = SUCCESS_RECHARGE_STATUS;
    $stmt->bind_param("issdssi", $userId, $startAt, $endAt, $min, $startAt, $endAt, $successStatus);
    $stmt->execute();
    $res = app_stmt_result($stmt);

    $count = 0;
    if ($res && ($row = $res->fetch_assoc())) {
        $count = (int)$row['total'];
    }
    $stmt->close();

    // debug sample rows
    $debug['referral_table'] = REF_USER_TABLE;
    $debug['referral_parent_col'] = REF_USER_PARENT_COL;
    $debug['recharge_table'] = RECHARGE_TABLE;
    $debug['cycle_start'] = $startAt;
    $debug['cycle_end'] = $endAt;

    return $count;
}

function awardMissingSpins($conn, $userId, $cycleRow, $qualifiedReferralCount) {
    $boxesOpened = (int)$cycleRow['boxes_opened_count'];
    $freeSpinAwarded = (int)$cycleRow['free_spin_awarded'];
    $inviteSpinsAwarded = (int)$cycleRow['invite_spins_awarded'];

    $conn->begin_transaction();
    try {
        // free spin after all 4 boxes
        if ($boxesOpened >= TOTAL_BOXES && $freeSpinAwarded === 0) {
            $free = FREE_SPIN_AFTER_BOXES;
            $stmt1 = $conn->prepare("UPDATE shonu_turntable SET total_spins = total_spins + ?, updated_at = NOW() WHERE user_id = ?");
            $stmt1->bind_param("ii", $free, $userId);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conn->prepare("UPDATE shonu_turntable_cycles SET free_spin_awarded = 1, updated_at = NOW() WHERE user_id = ?");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $stmt2->close();
        }

        // referral extra spins
        if ($boxesOpened >= TOTAL_BOXES && $qualifiedReferralCount > $inviteSpinsAwarded) {
            $diff = $qualifiedReferralCount - $inviteSpinsAwarded;

            $stmt3 = $conn->prepare("UPDATE shonu_turntable SET total_spins = total_spins + ?, updated_at = NOW() WHERE user_id = ?");
            $stmt3->bind_param("ii", $diff, $userId);
            $stmt3->execute();
            $stmt3->close();

            $stmt4 = $conn->prepare("UPDATE shonu_turntable_cycles SET invite_spins_awarded = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt4->bind_param("ii", $qualifiedReferralCount, $userId);
            $stmt4->execute();
            $stmt4->close();
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
    }

    return getCycle($conn, $userId);
}

/* =========================================================
   INIT
========================================================= */
ensureTables($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendJson($res, 405);
}

$rawBody = file_get_contents("php://input");
$post = json_decode($rawBody, true);

if (!isset($post['language'], $post['random'], $post['signature'], $post['timestamp'])) {
    $res['code'] = 7;
    $res['msg'] = 'Param is Invalid';
    $res['msgCode'] = 6;
    sendJson($res, 200);
}

/* =========================================================
   SIGNATURE
========================================================= */
$language = htmlspecialchars(mysqli_real_escape_string($conn, $post['language']));
$random = htmlspecialchars(mysqli_real_escape_string($conn, $post['random']));
$signature = htmlspecialchars(mysqli_real_escape_string($conn, $post['signature']));
$serverSign = buildSignature($language, $random);

if ($serverSign !== $signature) {
    $res['code'] = 5;
    $res['msg'] = 'Wrong signature';
    $res['msgCode'] = 3;
    sendJson($res, 200);
}

/* =========================================================
   AUTH
========================================================= */
if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    sendJson($res, 401);
}

$bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
$author = $bearer[1] ?? '';

$is_jwt_valid = is_jwt_valid($author);
$data_auth = json_decode($is_jwt_valid, true);

if (!$data_auth || !isset($data_auth['status']) || $data_auth['status'] !== 'Success') {
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    sendJson($res, 401);
}

$stmtSes = $conn->prepare("SELECT akshinak FROM shonu_subjects WHERE akshinak = ? LIMIT 1");
$stmtSes->bind_param("s", $author);
$stmtSes->execute();
$sesresult = app_stmt_result($stmtSes);
$sesnum = $sesresult ? $sesresult->num_rows : 0;
$stmtSes->close();

if ($sesnum !== 1) {
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    sendJson($res, 401);
}

$userId = (int)$data_auth['payload']['id'];

/* =========================================================
   USER + CYCLE
========================================================= */
ensureTurntableUser($conn, $userId);
$cycleRow = getOrCreateCycle($conn, $userId);
$cycleRow = resetCycleIfExpired($conn, $userId, $cycleRow);

// Sync any posted box state
$cycleRow = syncPostedBoxes($conn, $userId, $post);

/* =========================================================
   QUALIFIED REFERRAL COUNT
========================================================= */
$debugInfo = [];
$qualifiedReferralCount = getQualifiedReferralCount(
    $conn,
    $userId,
    $cycleRow['cycle_start_at'],
    $cycleRow['cycle_end_at'],
    $debugInfo
);

/* =========================================================
   AWARD SPINS
========================================================= */
$cycleRow = awardMissingSpins($conn, $userId, $cycleRow, $qualifiedReferralCount);

/* =========================================================
   CURRENT TURN TABLE
========================================================= */
$stmtTurn = $conn->prepare("SELECT total_spins, invited_wheel_amount FROM shonu_turntable WHERE user_id = ? LIMIT 1");
$stmtTurn->bind_param("i", $userId);
$stmtTurn->execute();
$turnRes = app_stmt_result($stmtTurn);
$turnRow = $turnRes ? $turnRes->fetch_assoc() : [];
$stmtTurn->close();

$userInvitedWheelCount = (int)($turnRow['total_spins'] ?? 0);
$userInvitedWheelAmount = (float)($turnRow['invited_wheel_amount'] ?? 0.00);

// total prize amount
$stmtPrize = $conn->prepare("SELECT COALESCE(SUM(prize_amount),0) AS total_prize FROM shonu_turntable_spins WHERE user_id = ?");
$stmtPrize->bind_param("i", $userId);
$stmtPrize->execute();
$prizeRes = app_stmt_result($stmtPrize);
$prizeRow = $prizeRes ? $prizeRes->fetch_assoc() : [];
$stmtPrize->close();

$invitedWheelTotalPrizeAmount = (float)($prizeRow['total_prize'] ?? 0.00);

// spin history count
$stmtSpinCount = $conn->prepare("SELECT COUNT(*) AS total_spins FROM shonu_turntable_spins WHERE user_id = ?");
$stmtSpinCount->bind_param("i", $userId);
$stmtSpinCount->execute();
$spinCountRes = app_stmt_result($stmtSpinCount);
$spinCountRow = $spinCountRes ? $spinCountRes->fetch_assoc() : [];
$stmtSpinCount->close();

$isFirstInvitedWheel = ((int)($spinCountRow['total_spins'] ?? 0) === 0);

// last 10 records
$stmtHistory = $conn->prepare("
    SELECT user_id, user_name, prize_amount, spin_time AS createTime
    FROM shonu_turntable_spins
    WHERE user_id = ?
    ORDER BY spin_time DESC
    LIMIT 10
");
$stmtHistory->bind_param("i", $userId);
$stmtHistory->execute();
$historyRes = app_stmt_result($stmtHistory);
$lastWheelRecordList = [];

if ($historyRes && $historyRes->num_rows > 0) {
    while ($row = $historyRes->fetch_assoc()) {
        $lastWheelRecordList[] = [
            'userId' => (int)$row['user_id'],
            'userName' => $row['user_name'] ?? 'User',
            'invitedWheelAmount' => (float)$userInvitedWheelAmount,
            'prizeAmount' => (float)$row['prize_amount'],
            'createTime' => $row['createTime']
        ];
    }
}
$stmtHistory->close();

if (empty($lastWheelRecordList)) {
    $lastWheelRecordList[] = [
        'userId' => (int)$userId,
        'userName' => 'User',
        'invitedWheelAmount' => (float)$userInvitedWheelAmount,
        'prizeAmount' => 0.00,
        'createTime' => $serviceNowTime
    ];
}

/* =========================================================
   RESPONSE
========================================================= */
$data = [];
$data['isOpenInvitedWheel'] = ((int)$cycleRow['boxes_opened_count'] >= TOTAL_BOXES);
$data['isFirstInvitedWheel'] = $isFirstInvitedWheel;
$data['userInvitedWheelCount'] = $userInvitedWheelCount;
$data['userInvitedWheelAmount'] = $userInvitedWheelAmount;
$data['invitedWheelTotalPrizeAmount'] = $invitedWheelTotalPrizeAmount;
$data['invitedWheelAmountofcodeAmount'] = (float)MIN_QUALIFY_DEPOSIT;
$data['expiredTime'] = $cycleRow['cycle_end_at'];
$data['diskDisplayAmount'] = [500.0, 5.0, 10.0, 20.0, 30.0, 50.0, 80.0];
$data['noWinningRandomAmount'] = [0.0, 10.0];
$data['lastWheelRecordList'] = $lastWheelRecordList;

$data['cycleNo'] = (int)$cycleRow['cycle_no'];
$data['cycleStartTime'] = $cycleRow['cycle_start_at'];
$data['boxesOpenedCount'] = (int)$cycleRow['boxes_opened_count'];
$data['boxesState'] = [
    1 => (int)$cycleRow['box1_open'],
    2 => (int)$cycleRow['box2_open'],
    3 => (int)$cycleRow['box3_open'],
    4 => (int)$cycleRow['box4_open'],
];
$data['freeSpinAwarded'] = (int)$cycleRow['free_spin_awarded'];
$data['qualifiedReferralCount'] = (int)$qualifiedReferralCount;
$data['inviteSpinsAwarded'] = (int)$cycleRow['invite_spins_awarded'];
$data['ruleText'] = 'Open all 4 boxes to get 1 free spin. Each referred user with minimum ₹300 successful recharge within 72 hours gives 1 extra spin.';

// debug fields to identify issue
$data['debug'] = [
    'receivedAction' => $post['action'] ?? '',
    'receivedBoxNo' => $post['boxNo'] ?? ($post['openBox'] ?? ($post['openedBox'] ?? '')),
    'boxesOpenedCountAfterSync' => (int)$cycleRow['boxes_opened_count'],
    'freeSpinAwardedAfterSync' => (int)$cycleRow['free_spin_awarded'],
    'qualifiedReferralCount' => (int)$qualifiedReferralCount,
    'inviteSpinsAwardedAfterSync' => (int)$cycleRow['invite_spins_awarded'],
    'referralDebug' => $debugInfo
];

$res['data'] = $data;
$res['code'] = 0;
$res['msg'] = 'Succeed';
$res['msgCode'] = 0;
$res['serviceNowTime'] = $serviceNowTime;

sendJson($res, 200);
?>