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
$list = [];
foreach (daily_award_tiers() as $tier) $list[] = daily_award_item($userId, $tier, $schedule, $claimed);
api_send($list);
