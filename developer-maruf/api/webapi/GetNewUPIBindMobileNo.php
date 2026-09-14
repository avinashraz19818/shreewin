<?php
// Returns the logged-in user's mobile number for the Add UPI form.
// Frontend calls this endpoint without a custom payload and expects data to be a string.

require_once __DIR__ . '/_common.php';

api_require_post();

try {
    $user = api_user();
    $userId = (int)($user['id'] ?? 0);

    // api_user() already reads the canonical mobile from shonu_subjects.
    $mobile = preg_replace('/\D+/', '', (string)($user['mobile'] ?? ''));

    // Safe fallback to the saved UPI record only when the account mobile is blank.
    // upi_withdrawal is installed by app_install_schema() in _common.php.
    if ($mobile === '' && $userId > 0) {
        $stmt = $conn->prepare('SELECT mobile FROM upi_withdrawal WHERE user_id=? ORDER BY id DESC LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $savedMobile = '';
            $stmt->bind_result($savedMobile);
            if ($stmt->fetch()) {
                $mobile = preg_replace('/\D+/', '', (string)$savedMobile);
            }
            $stmt->close();
        }
    }

    // The Add UPI page's getCurrentNumberType() expects a country-code-prefixed value.
    // Existing local Indian 10-digit accounts are therefore returned as 91XXXXXXXXXX.
    if (strlen($mobile) === 11 && substr($mobile, 0, 1) === '0') {
        $mobile = substr($mobile, 1);
    }
    if (strlen($mobile) === 10) {
        $mobile = '91' . $mobile;
    }

    // Keep the original API contract: successful request, string in data.
    api_send($mobile, 0, 'Succeed', 200, 0);
} catch (Throwable $e) {
    error_log('[GetNewUPIBindMobileNo] ' . $e->getMessage());

    // Do not leak a PHP/SQL 500 page to the frontend. Returning an empty string keeps
    // the Add UPI form usable so the user can type the mobile number manually.
    api_send('', 0, 'Succeed', 200, 0);
}
