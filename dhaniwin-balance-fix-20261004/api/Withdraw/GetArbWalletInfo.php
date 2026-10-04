<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

// Balance endpoints always answer from the database (no saved snapshot).

$user = api_primary_user();
$payload = [
        'data' => [
            'merchantCode' => 'AR0063',
            'memberId' => null,
            'walletActivationStatus' => 0,
            'balance' => api_wallet_balance_of($user),
            'walletAddress' => null,
            'withdrawalRewardRatio' => null,
            'minimumWithdrawalAmount' => null,
            'maximumWithdrawalAmount' => null,
            'timestamp' => null,
        ],
        'code' => 0,
        'msg' => 'Succeed',
        'msgCode' => 0,
        'serverTime' => 1780315501340,
    ];

api_refresh_times($payload);
api_emit($payload);
