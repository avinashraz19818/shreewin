<?php

function daily_award_tiers(): array
{
    return [
        ['mainConfigId'=>9, 'configId'=>3742, 'target'=>(float)app_setting('daily_award_tier4_target',100000), 'award'=>(float)app_setting('daily_award_tier4_reward',500), 'lId'=>19],
        ['mainConfigId'=>8, 'configId'=>3741, 'target'=>(float)app_setting('daily_award_tier3_target',50000),  'award'=>(float)app_setting('daily_award_tier3_reward',200), 'lId'=>17],
        ['mainConfigId'=>6, 'configId'=>3740, 'target'=>(float)app_setting('daily_award_tier2_target',5000),   'award'=>(float)app_setting('daily_award_tier2_reward',20),  'lId'=>13],
        ['mainConfigId'=>4, 'configId'=>3739, 'target'=>(float)app_setting('daily_award_tier1_target',500),    'award'=>(float)app_setting('daily_award_tier1_reward',3),   'lId'=>9],
    ];
}

function daily_valid_bet_amount(int $userId): float
{
    global $conn;
    $total = 0.0;

    if (api_table_exists('saas_lottery_bets')) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(stake),0) FROM saas_lottery_bets WHERE user_id=? AND created_at>=CURDATE() AND created_at<DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $value = 0.0;
            $stmt->bind_result($value);
            if ($stmt->fetch()) $total += (float)$value;
            $stmt->close();
        }
    }

    $legacyTables = [
        'bajikattuttate','bajikattuttate_drei','bajikattuttate_funf','bajikattuttate_zehn',
        'bajikattuttate_trx','bajikattuttate_trx3','bajikattuttate_trx5','bajikattuttate_trx10',
        'bajikattuttate_aidudi','bajikattuttate_aidudi_drei','bajikattuttate_aidudi_funf','bajikattuttate_aidudi_zehn',
        'bajikattuttate_kemuru','bajikattuttate_kemuru_drei','bajikattuttate_kemuru_funf','bajikattuttate_kemuru_zehn'
    ];
    foreach ($legacyTables as $table) {
        if (!api_table_exists($table)) continue;
        $stmt = $conn->prepare("SELECT COALESCE(SUM(ketebida),0) FROM `{$table}` WHERE byabaharkarta=? AND tiarikala>=CURDATE() AND tiarikala<DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
        if (!$stmt) continue;
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $value = 0.0;
        $stmt->bind_result($value);
        if ($stmt->fetch()) $total += (float)$value;
        $stmt->close();
    }
    return round($total, 2);
}

function daily_claimed_configs(int $userId): array
{
    global $conn;
    $claimed = [];
    $stmt = $conn->prepare('SELECT config_id FROM app_daily_award_claims WHERE user_id=? AND claim_date=CURDATE()');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $claimed[(int)$row['config_id']] = true;
        $stmt->close();
    }
    return $claimed;
}

function daily_award_item(int $userId, array $tier, float $schedule, array $claimed): array
{
    $target = (float)$tier['target'];
    $configId = (int)$tier['configId'];
    $done = $schedule >= $target;
    $wasClaimed = isset($claimed[$configId]);
    return [
        'userId'=>$userId, 'mainConfigId'=>(int)$tier['mainConfigId'], 'configId'=>$configId,
        'schedule'=>min($schedule, $target), 'status'=>$wasClaimed ? 3 : ($done ? 2 : 1),
        'taskTitle'=>'Daily betting bonus ', 'taskDescribe'=>'Daily betting bonus ', 'taskId'=>'B5',
        'taskTarget'=>$target, 'taskAwardAmount'=>(float)$tier['award'], 'createDate'=>date('Y-m-d 00:00:03'),
        'targetTwo'=>0.0, 'targetItem'=>0, 'targetSubItem'=>null, 'rechargeCategories'=>null,
        'scheduleTwo'=>0.0, 'lId'=>(int)$tier['lId'], 'receiveType'=>1,
        'displayRewardMinAmount'=>0.0, 'displayRewardMaxAmount'=>0.0,
        'activityCenterDisplayMaxAmount'=>null, 'actualRewardMinAmount'=>0.0,
        'actualRewardMaxAmount'=>0.0, 'isReceiveButtonHidden'=>false,
    ];
}
