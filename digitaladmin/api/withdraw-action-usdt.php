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
[$ok, $message] = admin_manual_withdraw_action($conn, $id, $type, $remark);
if (!$ok) {
    error_log('[withdraw-action-usdt] ' . $message);
    echo '0';
    exit;
}
echo $type === 'accept' ? '1' : ($type === 'reject' ? '2' : ($type === 'processing' ? '3' : '0'));
?>
