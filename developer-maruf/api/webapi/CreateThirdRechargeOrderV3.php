<?php

/**
 * Creates the local redirect contract expected by the bundled Recharge page.
 *
 * The payment itself is handled by the existing /pay/wepay.php and
 * /pay/usdt.php pages. Those pages collect the UTR/reference number and the
 * existing /pay/adddeposit.php endpoint stores the pending `thevani` row.
 */

require_once __DIR__ . '/_common.php';

api_require_post();
$body = api_input();

function third_recharge_fail(
    int $code,
    string $message,
    int $messageCode,
    int $httpStatus = 200,
    array $context = []
): void {
    if (function_exists('app_log_event')) {
        app_log_event('warning', 'CreateThirdRechargeOrderV3 API failure', [
            'code' => $code,
            'msg_code' => $messageCode,
            'http_status' => $httpStatus,
            'reason' => $message,
            'context' => $context,
        ]);
    }
    api_send(null, $code, $message, $httpStatus, $messageCode);
}

foreach (['language', 'random', 'signature', 'timestamp'] as $requiredField) {
    if (!array_key_exists($requiredField, $body)) {
        third_recharge_fail(7, 'Param is Invalid', 6, 200, [
            'missing_field' => $requiredField,
            'body_keys' => array_keys($body),
        ]);
    }
}
if (!api_signature_valid($body)) {
    third_recharge_fail(5, 'Wrong signature', 3, 200, [
        'body_keys' => array_keys($body),
    ]);
}

$auth = api_user();
$userId = (int)$auth['id'];
$payIdValue = $body['payId'] ?? $body['payid'] ?? null;
$amountValue = $body['amount'] ?? null;

if (!is_numeric($payIdValue) || !is_numeric($amountValue)) {
    third_recharge_fail(7, 'Param is Invalid', 6, 200, [
        'pay_id_type' => gettype($payIdValue),
        'amount_type' => gettype($amountValue),
    ]);
}

$payId = (int)$payIdValue;
$amount = round((float)$amountValue, 2);

// This table mirrors the payment IDs returned by GetPayTypeName.php and the
// channel IDs already returned by GetRechargeTypes.php. No gateway/channel
// selection is changed here; the missing order-to-payment-page bridge is added.
$channels = [
    1 => ['pay_type_id' => 1010, 'page' => 'wepay.php', 'minimum' => 100.0, 'maximum' => 50000.0],
    2 => ['pay_type_id' => 1023, 'page' => 'wepay.php', 'minimum' => 100.0, 'maximum' => 50000.0],
    3 => ['pay_type_id' => 1030, 'page' => 'wepay.php', 'minimum' => 100.0, 'maximum' => 50000.0],
    11 => ['pay_type_id' => 2123, 'page' => 'usdt.php', 'minimum' => 10.0, 'maximum' => 50000.0],
    13 => ['pay_type_id' => 2191, 'page' => 'wepay.php', 'minimum' => 100.0, 'maximum' => 50000.0],
    21 => ['pay_type_id' => 21001, 'page' => 'wepay.php', 'minimum' => 100.0, 'maximum' => 50000.0],
];

if (!isset($channels[$payId])) {
    third_recharge_fail(7, 'Unsupported recharge channel', 6, 200, [
        'pay_id' => $payId,
    ]);
}

$channel = $channels[$payId];
if (!is_finite($amount) || $amount < $channel['minimum'] || $amount > $channel['maximum']) {
    third_recharge_fail(7, 'Recharge amount is outside the allowed range', 6, 200, [
        'pay_id' => $payId,
        'amount' => $amount,
        'minimum' => $channel['minimum'],
        'maximum' => $channel['maximum'],
    ]);
}

$profile = $conn->prepare(
    "SELECT COALESCE(createdate,''), COALESCE(mobile,'') " .
    'FROM shonu_subjects WHERE id=? AND status=1 LIMIT 1'
);
if (!$profile) {
    third_recharge_fail(8, 'Service temporarily unavailable', 8, 503, [
        'pay_id' => $payId,
        'database_error' => $conn->error,
    ]);
}
$profile->bind_param('i', $userId);
$profile->execute();
$createdAt = '';
$mobile = '';
$profile->bind_result($createdAt, $mobile);
$found = $profile->fetch();
$profile->close();
if (!$found) {
    third_recharge_fail(4, 'No operation permission', 2, 401, [
        'pay_id' => $payId,
    ]);
}

$mobileDigits = preg_replace('/\D+/', '', (string)$mobile);
$displayMobile = str_starts_with($mobileDigits, '91') ? $mobileDigits : '91' . $mobileDigits;
$paymentSign = strtoupper(hash('sha256', $userId . '|' . $displayMobile . '|' . $createdAt));

$forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
$scheme = ($forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'))
    ? 'https'
    : 'http';
$host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
if ($host === '') {
    third_recharge_fail(8, 'Service temporarily unavailable', 8, 503, [
        'pay_id' => $payId,
        'reason_detail' => 'missing_request_host',
    ]);
}
$siteOrigin = $scheme . '://' . $host;

$query = http_build_query([
    'amount' => number_format($amount, 2, '.', ''),
    'payid' => $payId,
    'tyid' => (int)$channel['pay_type_id'],
    'uid' => $userId,
    'sign' => $paymentSign,
    'urlInfo' => $siteOrigin . '/#/wallet/RechargeHistory',
], '', '&', PHP_QUERY_RFC3986);
$paymentUrl = $siteOrigin . '/pay/' . $channel['page'] . '?' . $query;

if (function_exists('app_log_event')) {
    app_log_event('info', 'Recharge payment redirect created', [
        'pay_id' => $payId,
        'pay_type_id' => (int)$channel['pay_type_id'],
        'amount' => $amount,
        'target' => '/pay/' . $channel['page'],
    ]);
}

api_send([
    'redirectUrl' => $paymentUrl,
    'submitUrl' => $paymentUrl,
    'scanCodePay' => false,
    'formUrl' => '',
    'formBody' => null,
    'orderResult' => 1,
]);
