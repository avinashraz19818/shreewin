<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_daily_awards.php';
api_require_post();
$body = api_input();
api_require_signature($body);
$user = api_user();
$userId = (int)$user['id'];
$schedule = daily_valid_bet_amount($userId);
$claimed = daily_claimed_configs($userId);
$count = 0;
foreach (daily_award_tiers() as $tier) {
    if ($schedule >= (float)$tier['target'] && !isset($claimed[(int)$tier['configId']])) $count++;
}
api_send($count);
