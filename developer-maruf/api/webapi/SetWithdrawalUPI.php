<?php
require_once __DIR__ . '/_common.php';
api_require_post();
$body = api_input();
api_require_signature($body);
$user = api_user();
$upiId = trim((string)($body['accountNo'] ?? $body['accountno'] ?? ''));
$name = trim((string)($body['beneficiaryName'] ?? $body['beneficiaryname'] ?? ''));
$mobile = preg_replace('/\D+/', '', (string)($body['mobileNo'] ?? $body['mobileno'] ?? ''));
if ($upiId === '' || $name === '' || !preg_match('/^[A-Za-z0-9._-]{2,}@[A-Za-z0-9.-]{2,}$/', $upiId)) api_send(null, 7, 'Please enter a valid UPI ID', 200, 6);
$userId = (int)$user['id'];
$conn->begin_transaction();
try {
    $delete = $conn->prepare('DELETE FROM upi_withdrawal WHERE user_id=?');
    $delete->bind_param('i', $userId); $delete->execute(); $delete->close();
    $insert = $conn->prepare('INSERT INTO upi_withdrawal(user_id,upi_id,mobile,name,created_at) VALUES (?,?,?,?,NOW())');
    $insert->bind_param('isss', $userId, $upiId, $mobile, $name);
    if (!$insert->execute()) throw new RuntimeException('UPI save failed');
    $insert->close(); $conn->commit();
    api_send(null);
} catch (Throwable $e) {
    $conn->rollback(); error_log('[withdraw_upi] '.$e->getMessage());
    api_send(null, 8, 'Service temporarily unavailable', 503, 8);
}
