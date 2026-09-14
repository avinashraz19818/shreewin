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
header('Access-Control-Allow-Methods: POST, OPTIONS');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

date_default_timezone_set("Asia/Kolkata");
$now = date("Y-m-d H:i:s");

/* =========================================================
   OPTIONAL MANUAL OVERRIDE FOR REFERRAL TABLE
   Agar auto-detect fail kare to ye values bhar dena.
========================================================= */
// define('MANUAL_REF_TABLE', 'users');
// define('MANUAL_REF_ID_COL', 'id');
// define('MANUAL_REF_PARENT_COL', 'referrer_id');
// define('MANUAL_REF_CREATED_COL', 'created_at');

define('MIN_QUALIFY_DEPOSIT', 300.00);
define('SUCCESS_RECHARGE_STATUS', 1);
define('CYCLE_HOURS', 72);
define('FREE_SPIN_AFTER_REVEAL', 1);

function out($http, $code, $msg, $data = null, $msgCode = 0) {
    http_response_code($http);
    echo json_encode([
        'code' => $code,
        'msg' => $msg,
        'msgCode' => $msgCode,
        'serviceNowTime' => date("Y-m-d H:i:s"),
        'data' => $data
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function buildSignature($language, $random) {
    $str = '{"language":'.$language.',"random":"'.$random.'"}';
    return strtoupper(md5($str));
}

function ensureTablesAndColumns($conn) {
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
            box_reveal_done TINYINT(1) NOT NULL DEFAULT 0,
            selected_box_no INT NULL,
            selected_box_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            box_reward_data_json LONGTEXT NULL,
            free_spin_awarded TINYINT(1) NOT NULL DEFAULT 0,
            invite_spins_awarded INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $checks = [
        "box_reveal_done" => "ALTER TABLE shonu_turntable_cycles ADD COLUMN box_reveal_done TINYINT(1) NOT NULL DEFAULT 0 AFTER cycle_end_at",
        "selected_box_no" => "ALTER TABLE shonu_turntable_cycles ADD COLUMN selected_box_no INT NULL AFTER box_reveal_done",
        "selected_box_amount" => "ALTER TABLE shonu_turntable_cycles ADD COLUMN selected_box_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER selected_box_no",
        "box_reward_data_json" => "ALTER TABLE shonu_turntable_cycles ADD COLUMN box_reward_data_json LONGTEXT NULL AFTER selected_box_amount",
        "free_spin_awarded" => "ALTER TABLE shonu_turntable_cycles ADD COLUMN free_spin_awarded TINYINT(1) NOT NULL DEFAULT 0 AFTER box_reward_data_json",
        "invite_spins_awarded" => "ALTER TABLE shonu_turntable_cycles ADD COLUMN invite_spins_awarded INT NOT NULL DEFAULT 0 AFTER free_spin_awarded"
    ];

    foreach ($checks as $col => $sql) {
        $res = $conn->query("SHOW COLUMNS FROM shonu_turntable_cycles LIKE '$col'");
        if ($res && $res->num_rows == 0) {
            $conn->query($sql);
        }
    }
}

function ensureTurntableUser($conn, $userId) {
    $stmt = $conn->prepare("
        INSERT INTO shonu_turntable (user_id, total_spins, invited_wheel_amount, created_at, updated_at)
        VALUES (?, 0, 0, NOW(), NOW())
        ON DUPLICATE KEY UPDATE updated_at = NOW()
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function detectReferralConfig($conn) {
    if (defined('MANUAL_REF_TABLE') && defined('MANUAL_REF_ID_COL') && defined('MANUAL_REF_PARENT_COL') && defined('MANUAL_REF_CREATED_COL')) {
        return [
            'table' => MANUAL_REF_TABLE,
            'id_col' => MANUAL_REF_ID_COL,
            'parent_col' => MANUAL_REF_PARENT_COL,
            'created_col' => MANUAL_REF_CREATED_COL
        ];
    }

    $dbRes = $conn->query("SELECT DATABASE() AS dbname");
    $dbRow = $dbRes ? $dbRes->fetch_assoc() : null;
    $dbName = $dbRow['dbname'] ?? '';

    if (!$dbName) {
        return null;
    }

    $candidateTables = [
        'users',
        'user',
        'shonu_subjects',
        'khatedara',
        'members',
        'member',
        'customer',
        'customers'
    ];

    $idCols = ['id', 'user_id', 'shonu'];
    $parentCols = ['referrer_id', 'referred_by', 'parent_id', 'sponsor_id', 'invite_user_id', 'madari', 'ref_by', 'upliner_id'];
    $createdCols = ['created_at', 'register_time', 'createdon', 'created_on', 'dinankavannuracisi', 'date'];

    foreach ($candidateTables as $table) {
        $colRes = $conn->query("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = '".$conn->real_escape_string($dbName)."'
              AND TABLE_NAME = '".$conn->real_escape_string($table)."'
        ");
        if (!$colRes || $colRes->num_rows == 0) {
            continue;
        }

        $cols = [];
        while ($r = $colRes->fetch_assoc()) {
            $cols[] = $r['COLUMN_NAME'];
        }

        $id = null;
        $parent = null;
        $created = null;

        foreach ($idCols as $c) {
            if (in_array($c, $cols, true)) { $id = $c; break; }
        }
        foreach ($parentCols as $c) {
            if (in_array($c, $cols, true)) { $parent = $c; break; }
        }
        foreach ($createdCols as $c) {
            if (in_array($c, $cols, true)) { $created = $c; break; }
        }

        if ($id && $parent && $created) {
            return [
                'table' => $table,
                'id_col' => $id,
                'parent_col' => $parent,
                'created_col' => $created
            ];
        }
    }

    return null;
}

function getUserRegistrationTime($conn, $userId, $refCfg) {
    if (!$refCfg) return null;

    $sql = "SELECT ".$refCfg['created_col']." AS reg_time FROM ".$refCfg['table']." WHERE ".$refCfg['id_col']." = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = app_stmt_result($stmt);
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row || empty($row['reg_time'])) return null;

    $ts = strtotime($row['reg_time']);
    if ($ts === false) return null;

    return date("Y-m-d H:i:s", $ts);
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

function createCycle($conn, $userId, $cycleStartAt = null) {
    $start = $cycleStartAt ?: date("Y-m-d H:i:s");
    $end = date("Y-m-d H:i:s", strtotime($start . " +".CYCLE_HOURS." hours"));

    $stmt = $conn->prepare("
        INSERT INTO shonu_turntable_cycles
        (user_id, cycle_no, cycle_start_at, cycle_end_at, box_reveal_done, selected_box_no, selected_box_amount, box_reward_data_json, free_spin_awarded, invite_spins_awarded, created_at, updated_at)
        VALUES (?, 1, ?, ?, 0, NULL, 0.00, NULL, 0, 0, NOW(), NOW())
    ");
    $stmt->bind_param("iss", $userId, $start, $end);
    $stmt->execute();
    $stmt->close();

    return getCycle($conn, $userId);
}

function getOrCreateCycle($conn, $userId, $regTime = null) {
    $row = getCycle($conn, $userId);
    if ($row) return $row;
    return createCycle($conn, $userId, $regTime);
}

function resetCycleIfExpired($conn, $userId, $cycleRow) {
    $endTs = strtotime($cycleRow['cycle_end_at']);
    if ($endTs !== false && time() <= $endTs) {
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
                box_reveal_done = 0,
                selected_box_no = NULL,
                selected_box_amount = 0.00,
                box_reward_data_json = NULL,
                free_spin_awarded = 0,
                invite_spins_awarded = 0,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt2->bind_param("issi", $newCycleNo, $newStart, $newEnd, $userId);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
    }

    return getCycle($conn, $userId);
}

function getQualifiedReferralCount($conn, $userId, $cycleStart, $cycleEnd, $refCfg, &$debug = []) {
    if (!$refCfg) {
        $debug['referral_error'] = 'Referral config not detected';
        return 0;
    }

    $sql = "
        SELECT COUNT(DISTINCT u.".$refCfg['id_col'].") AS total
        FROM ".$refCfg['table']." u
        INNER JOIN thevani t
            ON t.balakedara = u.".$refCfg['id_col']."
        WHERE u.".$refCfg['parent_col']." = ?
          AND u.".$refCfg['created_col']." BETWEEN ? AND ?
          AND CAST(t.motta AS DECIMAL(12,2)) >= ?
          AND STR_TO_DATE(t.dinankavannuracisi, '%Y-%m-%d %H:%i:%s') BETWEEN ? AND ?
          AND t.sthiti = ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $debug['referral_sql_error'] = $conn->error;
        return 0;
    }

    $min = MIN_QUALIFY_DEPOSIT;
    $success = SUCCESS_RECHARGE_STATUS;
    $stmt->bind_param("issdssi", $userId, $cycleStart, $cycleEnd, $min, $cycleStart, $cycleEnd, $success);
    $stmt->execute();
    $res = app_stmt_result($stmt);
    $row = $res ? $res->fetch_assoc() : ['total' => 0];
    $stmt->close();

    $debug['ref_table'] = $refCfg['table'];
    $debug['ref_id_col'] = $refCfg['id_col'];
    $debug['ref_parent_col'] = $refCfg['parent_col'];
    $debug['ref_created_col'] = $refCfg['created_col'];

    return (int)($row['total'] ?? 0);
}

function getTurntable($conn, $userId) {
    $stmt = $conn->prepare("SELECT total_spins, invited_wheel_amount FROM shonu_turntable WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = app_stmt_result($stmt);
    $row = $res ? $res->fetch_assoc() : ['total_spins' => 0, 'invited_wheel_amount' => 0];
    $stmt->close();
    return $row;
}

function getUserNameFromSubjects($conn, $userId) {
    $stmt = $conn->prepare("SELECT codechorkamukala FROM shonu_subjects WHERE id = ? LIMIT 1");
    if (!$stmt) return 'User';
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = app_stmt_result($stmt);
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row['codechorkamukala'] ?? 'User';
}

/* =========================================================
   REQUEST VALIDATION
========================================================= */
ensureTablesAndColumns($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(405, 11, 'Method not allowed', null, 12);
}

$body = json_decode(file_get_contents("php://input"), true);
if (!is_array($body)) out(200, 7, 'Param is Invalid', null, 6);
foreach (['language','random','signature','timestamp'] as $k) {
    if (!isset($body[$k])) out(200, 7, 'Param is Invalid', null, 6);
}

$language = $body['language'];
$random = $body['random'];
$clientSign = strtoupper((string)$body['signature']);
$serverSign = buildSignature($language, $random);

if ($clientSign !== $serverSign) {
    out(200, 5, 'Wrong signature', null, 3);
}

/* =========================================================
   AUTH
========================================================= */
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? '');
if (!$authHeader && function_exists('apache_request_headers')) {
    $ah = apache_request_headers();
    if (isset($ah['Authorization'])) $authHeader = $ah['Authorization'];
}
if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
    out(401, 4, 'No operation permission', null, 2);
}
$token = $m[1];

$jwt = json_decode(is_jwt_valid($token), true);
if (!$jwt || ($jwt['status'] ?? '') !== 'Success') {
    out(401, 4, 'No operation permission', null, 2);
}

$userId = (int)($jwt['payload']['id'] ?? 0);
if ($userId <= 0) out(401, 4, 'No operation permission', null, 2);

// token check in subjects
$q = mysqli_query($conn, "SELECT 1 FROM shonu_subjects WHERE akshinak = '".mysqli_real_escape_string($conn, $token)."'");
if (!$q || mysqli_num_rows($q) !== 1) {
    out(401, 4, 'No operation permission', null, 2);
}

$userName = getUserNameFromSubjects($conn, $userId);

/* =========================================================
   USER + ACTIVE CYCLE
========================================================= */
ensureTurntableUser($conn, $userId);

$debug = [];
$refCfg = detectReferralConfig($conn);
$regTime = getUserRegistrationTime($conn, $userId, $refCfg);

$cycleRow = getOrCreateCycle($conn, $userId, $regTime);
$cycleRow = resetCycleIfExpired($conn, $userId, $cycleRow);

// Count qualified referrals inside active cycle
$qualifiedReferralCount = getQualifiedReferralCount(
    $conn,
    $userId,
    $cycleRow['cycle_start_at'],
    $cycleRow['cycle_end_at'],
    $refCfg,
    $debug
);

/* =========================================================
   MODE 1: FIRST ACTION OF ACTIVE CYCLE = 4 BOX REVEAL
   - one time only in each 72h cycle
   - selected amount wallet me add
   - 1 free spin add
   - qualified referral spins add
========================================================= */
if ((int)$cycleRow['box_reveal_done'] === 0) {
    $clickedBoxNo = isset($body['boxNo']) ? (int)$body['boxNo'] : rand(1, 4);
    if ($clickedBoxNo < 1 || $clickedBoxNo > 4) {
        $clickedBoxNo = rand(1, 4);
    }

    // Same 4 box behavior as your working code
    $boxes = [
        ['amount'=>2957.40, 'isSelected'=>false],
        ['amount'=>274.80,  'isSelected'=>false],
        ['amount'=>1316.40, 'isSelected'=>false],
        ['amount'=>2996.10, 'isSelected'=>false],
    ];

    // Put selected box at clicked position with shuffled amounts
    $amounts = array_column($boxes, 'amount');
    shuffle($amounts);

    $firstBoxes = [];
    for ($i = 1; $i <= 4; $i++) {
        $firstBoxes[] = [
            'boxNo' => $i,
            'amount' => (float)$amounts[$i - 1],
            'isSelected' => ($i === $clickedBoxNo)
        ];
    }

    $selectedAmount = (float)$amounts[$clickedBoxNo - 1];
    $freeSpin = FREE_SPIN_AFTER_REVEAL;
    $referralSpinsToAdd = max(0, $qualifiedReferralCount); // first reveal pe saare earned referral spins add
    $totalSpinsToAdd = $freeSpin + $referralSpinsToAdd;

    mysqli_begin_transaction($conn);
    try {
        $amtDb = number_format($selectedAmount, 2, '.', '');
        $userNameEsc = mysqli_real_escape_string($conn, $userName);
        $jsonBoxes = mysqli_real_escape_string($conn, json_encode($firstBoxes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // lock cycle row to prevent double tap double reward
        $lockQ = mysqli_query($conn, "SELECT box_reveal_done FROM shonu_turntable_cycles WHERE user_id = '$userId' FOR UPDATE");
        $lockRow = $lockQ ? mysqli_fetch_assoc($lockQ) : null;

        if (!$lockRow) {
            throw new Exception("Cycle row not found");
        }

        if ((int)$lockRow['box_reveal_done'] === 1) {
            mysqli_commit($conn);
            $cycleRow = getCycle($conn, $userId);
            $saved = json_decode($cycleRow['box_reward_data_json'], true);
            $turn = getTurntable($conn, $userId);

            out(200, 0, 'Succeed', [
                'isFirstInvitedWheel' => true,
                'prizeAmount' => (float)$cycleRow['selected_box_amount'],
                'isWin' => ((float)$cycleRow['selected_box_amount'] > 0),
                'firstInvitedWheelDatas' => $saved,
                'userInvitedWheelCount' => (int)$turn['total_spins'],
                'qualifiedReferralCount' => (int)$qualifiedReferralCount,
                'expiredTime' => $cycleRow['cycle_end_at']
            ], 0);
        }

        // Insert reward record only once
        mysqli_query($conn, "
            INSERT INTO shonu_turntable_spins (user_id, user_name, prize_amount, spin_time)
            VALUES ('$userId', '$userNameEsc', '$amtDb', NOW())
        ");

        // Add selected amount + free spin + referral spins
        mysqli_query($conn, "
            UPDATE shonu_turntable
            SET invited_wheel_amount = invited_wheel_amount + $amtDb,
                total_spins = total_spins + $totalSpinsToAdd,
                updated_at = NOW()
            WHERE user_id = '$userId'
        ");

        // Lock reveal for this cycle
        mysqli_query($conn, "
            UPDATE shonu_turntable_cycles
            SET box_reveal_done = 1,
                selected_box_no = '$clickedBoxNo',
                selected_box_amount = '$amtDb',
                box_reward_data_json = '$jsonBoxes',
                free_spin_awarded = 1,
                invite_spins_awarded = '$qualifiedReferralCount',
                updated_at = NOW()
            WHERE user_id = '$userId' AND box_reveal_done = 0
        ");

        if (mysqli_affected_rows($conn) <= 0) {
            throw new Exception("Box already opened");
        }

        mysqli_commit($conn);

        $turn = getTurntable($conn, $userId);

        out(200, 0, 'Succeed', [
            'isFirstInvitedWheel' => true,
            'prizeAmount' => (float)$selectedAmount,
            'isWin' => ($selectedAmount > 0),
            'firstInvitedWheelDatas' => $firstBoxes,
            'userInvitedWheelCount' => (int)$turn['total_spins'],
            'qualifiedReferralCount' => (int)$qualifiedReferralCount,
            'referralSpinsAdded' => (int)$referralSpinsToAdd,
            'freeSpinAdded' => (int)$freeSpin,
            'expiredTime' => $cycleRow['cycle_end_at']
        ], 0);

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log("BOX_REVEAL_ERROR: ".$e->getMessage());
        out(500, 500, 'Internal Server Error', ['debug' => $debug], 500);
    }
}

/* =========================================================
   MODE 2: NORMAL SPIN
   - Before spin, newly qualified referrals ke extra spins sync karo
   - Then 1 spin consume
========================================================= */

// Add newly earned referral spins if any
$currentInviteAwarded = (int)$cycleRow['invite_spins_awarded'];
$newReferralSpins = max(0, $qualifiedReferralCount - $currentInviteAwarded);

if ($newReferralSpins > 0) {
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "
            UPDATE shonu_turntable
            SET total_spins = total_spins + $newReferralSpins,
                updated_at = NOW()
            WHERE user_id = '$userId'
        ");

        mysqli_query($conn, "
            UPDATE shonu_turntable_cycles
            SET invite_spins_awarded = '$qualifiedReferralCount',
                updated_at = NOW()
            WHERE user_id = '$userId'
        ");

        mysqli_commit($conn);
        $cycleRow = getCycle($conn, $userId);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
    }
}

$turn = getTurntable($conn, $userId);
$availableSpins = (int)$turn['total_spins'];

if ($availableSpins <= 0) {
    out(200, 8, 'No spins left', [
        'userInvitedWheelCount' => 0,
        'qualifiedReferralCount' => (int)$qualifiedReferralCount,
        'inviteSpinsAwarded' => (int)$cycleRow['invite_spins_awarded'],
        'expiredTime' => $cycleRow['cycle_end_at']
    ], 9);
}

// Normal wheel prizes
$prizes = [
    ['amount'=>0.12,'p'=>0.35,'win'=>true],
    ['amount'=>2.05,'p'=>0.25,'win'=>true],
    ['amount'=>2.10,'p'=>0.15,'win'=>true],
    ['amount'=>1.20,'p'=>0.10,'win'=>true],
    ['amount'=>3.50,'p'=>0.18,'win'=>true],
    ['amount'=>5.00,'p'=>0.13,'win'=>true],
    ['amount'=>2.00,'p'=>0.12,'win'=>true],
    ['amount'=>3.00,'p'=>0.25,'win'=>true],
    ['amount'=>1.00,'p'=>0.05,'win'=>true],
    ['amount'=>2.00,'p'=>0.09,'win'=>true],
    ['amount'=>1.00,'p'=>0.07,'win'=>true],
    ['amount'=>0.00,'p'=>0.00,'win'=>false],
];

$sum = array_sum(array_column($prizes, 'p'));
$r = mt_rand() / mt_getrandmax();
$cum = 0.0;
$pick = end($prizes);

foreach ($prizes as $p) {
    $cum += ($p['p'] / max($sum, 1.0));
    if ($r <= $cum) {
        $pick = $p;
        break;
    }
}

$prizeAmount = (float)$pick['amount'];
$isWin = (bool)$pick['win'];

// Commit normal spin
mysqli_begin_transaction($conn);

try {
    $prizeDb = number_format($prizeAmount, 2, '.', '');
    $userNameEsc = mysqli_real_escape_string($conn, $userName);

    mysqli_query($conn, "
        INSERT INTO shonu_turntable_spins (user_id, user_name, prize_amount, spin_time)
        VALUES ('$userId', '$userNameEsc', '$prizeDb', NOW())
    ");

    mysqli_query($conn, "
        UPDATE shonu_turntable
        SET total_spins = GREATEST(total_spins - 1, 0),
            invited_wheel_amount = invited_wheel_amount + $prizeDb,
            updated_at = NOW()
        WHERE user_id = '$userId'
    ");

    mysqli_commit($conn);

} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log("SPIN_ERROR: ".$e->getMessage());
    out(500, 500, 'Internal Server Error', ['debug' => $debug], 500);
}

// Final normal response
$turn = getTurntable($conn, $userId);

out(200, 0, 'Succeed', [
    'isFirstInvitedWheel' => false,
    'prizeAmount' => (float)$prizeAmount,
    'isWin' => $isWin,
    'firstInvitedWheelDatas' => null,
    'userInvitedWheelCount' => (int)$turn['total_spins'],
    'qualifiedReferralCount' => (int)$qualifiedReferralCount,
    'expiredTime' => $cycleRow['cycle_end_at'],
    'debug' => $debug
], 0);
?>