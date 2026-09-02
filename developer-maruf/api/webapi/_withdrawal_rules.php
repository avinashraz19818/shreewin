<?php
function withdrawal_turnover(int $userId): array
{
    global $conn;
    $required = 0.0;
    foreach ([['thevani','motta','balakedara',"sthiti='1'"],['hodike_balakedara','price','userkani','1=1']] as [$table,$amountCol,$userCol,$extra]) {
        if (!api_table_exists($table)) continue;
        $stmt = $conn->prepare("SELECT COALESCE(SUM(`{$amountCol}`),0) FROM `{$table}` WHERE `{$userCol}`=? AND {$extra}");
        if (!$stmt) continue;
        $stmt->bind_param('i',$userId); $stmt->execute(); $value=0.0; $stmt->bind_result($value); if ($stmt->fetch()) $required+=(float)$value; $stmt->close();
    }
    $played = daily_valid_bet_amount_all_time($userId);
    return ['required'=>round($required,2),'played'=>round($played,2),'remaining'=>round(max(0,$required-$played),2)];
}

function daily_valid_bet_amount_all_time(int $userId): float
{
    global $conn;
    $total=0.0;
    if (api_table_exists('saas_lottery_bets')) {
        $stmt=$conn->prepare('SELECT COALESCE(SUM(stake),0) FROM saas_lottery_bets WHERE user_id=?');
        if ($stmt) { $stmt->bind_param('i',$userId); $stmt->execute(); $v=0.0; $stmt->bind_result($v); if($stmt->fetch())$total+=(float)$v; $stmt->close(); }
    }
    $tables=['bajikattuttate','bajikattuttate_drei','bajikattuttate_funf','bajikattuttate_zehn','bajikattuttate_trx','bajikattuttate_trx3','bajikattuttate_trx5','bajikattuttate_trx10','bajikattuttate_aidudi','bajikattuttate_aidudi_drei','bajikattuttate_aidudi_funf','bajikattuttate_aidudi_zehn','bajikattuttate_kemuru','bajikattuttate_kemuru_drei','bajikattuttate_kemuru_funf','bajikattuttate_kemuru_zehn'];
    foreach($tables as $table){
        if(!api_table_exists($table))continue;
        $stmt=$conn->prepare("SELECT COALESCE(SUM(ketebida),0) FROM `{$table}` WHERE byabaharkarta=?");
        if(!$stmt)continue; $stmt->bind_param('i',$userId); $stmt->execute(); $v=0.0; $stmt->bind_result($v); if($stmt->fetch())$total+=(float)$v; $stmt->close();
    }
    return $total;
}
