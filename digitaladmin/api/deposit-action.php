<?php
session_start();
if (empty($_SESSION['unohs'])) {
    http_response_code(403);
    echo '2';
    exit;
}

require_once __DIR__ . '/conn.php';
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: text/plain; charset=utf-8');

$approve = isset($_POST['app']);
$reject = isset($_POST['rej']);
$sid = isset($_POST['sid']) ? (int)$_POST['sid'] : 0;
if ($sid < 1 || (!$approve && !$reject)) {
    echo '2';
    exit;
}

try {
    $conn->begin_transaction();

    // The row id is the source of truth. Do not trust amount/user/date posted by
    // the browser, and lock the pending request so repeated clicks cannot credit
    // the same recharge twice.
    $stmt = $conn->prepare('SELECT balakedara,motta,ullekha,sthiti FROM thevani WHERE shonu=? FOR UPDATE');
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    // Shared/cPanel hosting may not include mysqlnd, so get_result() is not
    // available. bind_result()/fetch() works with the standard mysqli driver.
    $depositUid = null;
    $depositAmount = null;
    $depositReference = null;
    $depositStatus = null;
    $stmt->bind_result($depositUid, $depositAmount, $depositReference, $depositStatus);
    $row = $stmt->fetch() ? [
        'balakedara' => $depositUid,
        'motta' => $depositAmount,
        'ullekha' => $depositReference,
        'sthiti' => $depositStatus,
    ] : null;
    $stmt->close();

    if (!$row || (string)$row['sthiti'] !== '0') {
        $conn->rollback();
        echo '2';
        exit;
    }

    $uid = (int)$row['balakedara'];
    $deposit = round((float)$row['motta'], 2);
    $refNum = (string)($row['ullekha'] ?? '');
    if ($uid < 1 || $deposit <= 0) {
        throw new RuntimeException('Invalid pending deposit');
    }

    if ($reject) {
        $stmt = $conn->prepare("UPDATE thevani SET sthiti='2' WHERE shonu=? AND sthiti='0'");
        $stmt->bind_param('i', $sid);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new RuntimeException('Deposit status changed before rejection');
        }
        $stmt->close();
        $conn->commit();
        echo '1~' . $sid;
        exit;
    }

    // Self-heal old accounts that are missing a wallet row.
    $ensureWallet = $conn->prepare('INSERT IGNORE INTO shonu_kaichila (balakedara,motta,bonus,dinankavannuracisi) VALUES (?,0,0,NOW())');
    if ($ensureWallet) { $ensureWallet->bind_param('i',$uid); $ensureWallet->execute(); $ensureWallet->close(); }
    $wallet = $conn->prepare('SELECT motta,turnover FROM shonu_kaichila WHERE balakedara=? FOR UPDATE');
    $wallet->bind_param('i', $uid);
    $wallet->execute();
    $walletBalance = null;
    $walletTurnover = null;
    $wallet->bind_result($walletBalance, $walletTurnover);
    $walletRow = $wallet->fetch() ? [
        'motta' => $walletBalance,
        'turnover' => $walletTurnover,
    ] : null;
    $wallet->close();
    if (!$walletRow) {
        throw new RuntimeException('Wallet row not found');
    }

    $bonusAmount = 0.0;
    try {
        $bonusQ = $conn->query('SELECT is_enabled,bonus_percent FROM bonus_settings WHERE id=1 LIMIT 1');
        if ($bonusQ && ($bonus = $bonusQ->fetch_assoc()) && (int)$bonus['is_enabled'] === 1) {
            $percent = max(0.0, min(100.0, (float)$bonus['bonus_percent']));
            $bonusAmount = round($deposit * $percent / 100.0, 2);
        }
    } catch (Throwable $ignored) {
        $bonusAmount = 0.0;
    }

    $credit = round($deposit + $bonusAmount, 2);
    $updateWallet = $conn->prepare('UPDATE shonu_kaichila SET motta=ROUND(motta+?,2), turnover=ROUND(turnover+?,2) WHERE balakedara=?');
    $updateWallet->bind_param('ddi', $credit, $credit, $uid);
    $updateWallet->execute();
    if ($updateWallet->affected_rows < 1) {
        $updateWallet->close();
        throw new RuntimeException('Wallet update failed');
    }
    $updateWallet->close();

    // Mark successful only after the wallet mutation succeeds.
    $done = $conn->prepare("UPDATE thevani SET sthiti='1' WHERE shonu=? AND sthiti='0'");
    $done->bind_param('i', $sid);
    $done->execute();
    if ($done->affected_rows !== 1) {
        $done->close();
        throw new RuntimeException('Deposit status update failed');
    }
    $done->close();

    if ($bonusAmount > 0) {
        try {
            $log = $conn->prepare('INSERT IGNORE INTO bonus_log(user_id,bonus_amount,refnum,created_at) VALUES (?,?,?,NOW())');
            if ($log) {
                $log->bind_param('ids', $uid, $bonusAmount, $refNum);
                $log->execute();
                $log->close();
            }
        } catch (Throwable $ignored) {
            // Bonus logging is auxiliary; the atomic recharge itself remains valid.
        }
    }

    $conn->commit();
    echo '1~' . $sid;
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('[deposit-action] ' . $e->getMessage());
    echo '2';
}
