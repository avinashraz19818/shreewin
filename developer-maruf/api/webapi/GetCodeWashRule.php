<?php
require_once __DIR__ . '/_common.php';
$rate=0.85;
api_send([
    'washRate'=>$rate,
    'lotteryRate'=>$rate,
    'rule'=>"Lottery valid betting turnover rebate: {$rate}% of unclaimed valid wager volume. Each wager volume can be claimed only once."
]);
