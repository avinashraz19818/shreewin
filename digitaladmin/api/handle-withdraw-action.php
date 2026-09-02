<?php
session_start();
if (empty($_SESSION['unohs'])) {
    http_response_code(403);
    echo '0';
    exit;
}

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/manual-withdraw-helper.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$type = strtolower(trim((string)($_POST['type'] ?? '')));
$remark = trim((string)($_POST['remark'] ?? ''));
if ($id < 1 || !in_array($type, ['accept', 'reject', 'processing'], true)) {
    echo '0';
    exit;
}

if ($type === 'reject' || $type === 'processing') {
    [$ok, $message] = admin_manual_withdraw_action($conn, $id, $type, $remark);
    if (!$ok) {
        error_log('[handle-withdraw-action] ' . $message);
        echo '0';
        exit;
    }
    echo $type === 'reject' ? '2' : '3';
    exit;
}

// Resolve active gateway. If no gateway is configured, manual approval remains usable.
$gateway = 'manual';
$gatewayResult = $conn->query("SELECT value FROM tbl_pg WHERE status='1' ORDER BY id DESC LIMIT 1");
if ($gatewayResult && ($gatewayRow = $gatewayResult->fetch_assoc()) && !empty($gatewayRow['value'])) {
    $gateway = strtolower(trim((string)$gatewayRow['value']));
}

if ($gateway !== 'indianpay') {
    [$ok, $message] = admin_manual_withdraw_action($conn, $id, 'accept', $remark);
    if (!$ok) {
        error_log('[handle-withdraw-action] ' . $message);
        echo '0';
        exit;
    }
    echo '1';
    exit;
}

// IndianPay approval: validate the request is still pending/processing before the remote payout.
$stmt = $conn->prepare('SELECT balakedara,motta,dharavahi,khateshonu,sthiti FROM hintegedukolli WHERE shonu=? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$withdraw = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$withdraw || !in_array((int)$withdraw['sthiti'], [0, 3], true)) {
    echo '0';
    exit;
}

$userid = (int)$withdraw['balakedara'];
$amount = round((float)$withdraw['motta'], 2);
$serial = (string)$withdraw['dharavahi'];
$bid = (int)$withdraw['khateshonu'];

$accountStmt = $conn->prepare('SELECT khatehesaru,khatesankhye,kod,phalanubhavi FROM khate WHERE byabaharkarta=? AND shonu=? LIMIT 1');
$accountStmt->bind_param('ii', $userid, $bid);
$accountStmt->execute();
$account = $accountStmt->get_result()->fetch_assoc();
$accountStmt->close();
if (!$account) {
    echo '0';
    exit;
}

$mobile = '';
$mobileStmt = $conn->prepare('SELECT mobile FROM shonu_subjects WHERE id=? LIMIT 1');
$mobileStmt->bind_param('i', $userid);
$mobileStmt->execute();
if ($row = $mobileStmt->get_result()->fetch_assoc()) {
    $mobile = (string)$row['mobile'];
}
$mobileStmt->close();

$url = 'https://indianpay.co.in/admin/single_transaction';
$merchantId = 'INDIANPAY10045';
$merchantToken = 'KFDZW5BHD2y8kzj8cW3t6yFV3jAX3iNS';
$payload = [
    'merchant_id' => $merchantId,
    'merchant_token' => $merchantToken,
    'account_no' => (string)$account['khatesankhye'],
    'ifsccode' => (string)$account['kod'],
    'amount' => $amount,
    'bankname' => (string)$account['khatehesaru'],
    'remark' => $remark !== '' ? $remark : 'withdrawal',
    'orderid' => $serial,
    'name' => (string)$account['phalanubhavi'],
    'contact' => $mobile,
    'email' => 'withgateway@localhost.invalid',
];
$payload['salt'] = base64_encode(json_encode($payload));

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);
$decoded = json_decode((string)$response, true);
if ($curlError !== '' || !is_array($decoded) || (int)($decoded['status'] ?? 0) !== 200) {
    error_log('[handle-withdraw-action] IndianPay failed: ' . ($curlError ?: (string)$response));
    echo '0';
    exit;
}

[$ok, $message] = admin_manual_withdraw_action($conn, $id, 'accept', $remark);
if (!$ok) {
    // Remote provider accepted but local state could not be finalized; log prominently for reconciliation.
    error_log('[handle-withdraw-action] REMOTE SUCCESS / LOCAL UPDATE FAILED withdrawal ' . $id . ': ' . $message);
    echo '0';
    exit;
}

echo '1';
?>
