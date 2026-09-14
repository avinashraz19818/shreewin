<?php

require_once __DIR__ . '/_common.php';
api_require_post();
$body = api_input();
api_require_signature($body);
$auth = api_user();
$userId = (int)$auth['id'];

$stmt = $conn->prepare(
    "SELECT COALESCE(codechorkamukala,''), COALESCE(user_photo,'1'), " .
    "COALESCE(createdate,''), COALESCE(shonullgnt,'') " .
    'FROM shonu_subjects WHERE id=? LIMIT 1'
);
if (!$stmt) {
    api_send(null, 8, 'Service temporarily unavailable', 503, 8);
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$nickname = '';
$photo = '1';
$createdAt = '';
$lastLogin = '';
$email = '';
$stmt->bind_result($nickname, $photo, $createdAt, $lastLogin);
$found = $stmt->fetch();
$stmt->close();
if (!$found) {
    api_send(null, 4, 'No operation permission', 401, 2);
}

$mobile = preg_replace('/\D+/', '', (string)$auth['mobile']);
$displayMobile = str_starts_with($mobile, '91') ? $mobile : '91' . $mobile;
$balance = api_wallet_balance($userId);
$unread = 0;
if (api_table_exists('notification')) {
    $count = $conn->prepare('SELECT COUNT(*) FROM notification WHERE user_id=? AND state=0');
    if ($count) {
        $count->bind_param('i', $userId);
        $count->execute();
        $count->bind_result($unreadValue);
        if ($count->fetch()) {
            $unread = (int)$unreadValue;
        }
        $count->close();
    }
}

$usdtRate = (float)app_setting('usdt_inr_rate', 103);
$data = [
    'userId' => $userId,
    'userPhoto' => (string)$photo,
    'userName' => $displayMobile,
    'nickName' => (string)$nickname,
    'amount' => $balance,
    'uRate' => $usdtRate,
    'sign' => strtoupper(hash('sha256', $userId . '|' . $displayMobile . '|' . $createdAt)),
    'amountofCode' => 0.0,
    'isWithdraw' => null,
    'message' => null,
    'withdrawCount' => 0,
    'addTime' => $createdAt,
    'userLoginDate' => $lastLogin,
    'startTime' => null,
    'endTime' => null,
    'fee' => 0.0,
    'unRead' => $unread,
    'trxRate' => 10.0,
    'uGold' => 0.0,
    'googleVerify' => 0,
    'isvalidator' => 0,
    'isRePwd' => '1',
    'integral' => 0,
    'isOpenPointMall' => '0',
    'isOpenAmountOfCode' => '1',
    'isOpenOfficialRechargeInputDialog' => '0',
    'isAllowUserAddUSDT' => '1',
    'isShowWalletTotalCT' => '1',
    'isShowRechargeBankList' => '0',
    'isPopupCommissionSwitch' => '0',
    'groupDataShowAuth' => [
        ['id' => 11, 'isShow' => true], ['id' => 12, 'isShow' => true],
        ['id' => 15, 'isShow' => true], ['id' => 16, 'isShow' => true],
        ['id' => 17, 'isShow' => true], ['id' => 18, 'isShow' => true],
        ['id' => 19, 'isShow' => true], ['id' => 20, 'isShow' => true],
    ],
    'verifyMethods' => ['mobile' => $displayMobile, 'email' => (string)$email, 'google' => '0'],
    'regType' => 1,
    'userGroupAuth' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
    'bindReward' => 0.0,
    'isGoogle' => '0',
    'isOpenChampion' => '0',
    'isAllowWithdraw' => 1,
    'userRechargeTimes' => 1,
    'allowNoRechargeGame' => '0',
    'isPartnerReward' => '1',
    'canDirectToGame' => false,
    'isOpenNewSafe' => '1',
    'useLanguage' => 'en',
];

api_send($data);
