<?php
require_once __DIR__ . '/_common.php';
api_require_post();
$body = api_input();
api_require_signature($body);
$user = api_user();
$balance = api_wallet_balance((int)$user['id']);
api_send([
    'amount' => round($balance, 2),
    'uRate' => 93,
    'uGold' => 0
]);
